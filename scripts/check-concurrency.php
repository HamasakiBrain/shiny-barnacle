<?php

// Dedicated MySQL integration probe. Never runs against the application database.
use App\DTOs\CheckoutData;
use App\Enums\PaymentMethod;
use App\Events\OrderPlaced;
use App\Exceptions\BusinessException;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\CheckoutService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

require dirname(__DIR__) . '/vendor/autoload.php';
$app = require dirname(__DIR__) . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

if (
    !app()->environment('testing')
    || config('database.default') !== 'mysql'
    || config('database.connections.mysql.database') !== 'market_testing'
    || !function_exists('pcntl_fork')
) {
    throw new RuntimeException('Requires APP_ENV=testing, MySQL market_testing, and pcntl (use the documented Docker command).');
}

Event::fake([OrderPlaced::class]);
Artisan::call('migrate:fresh', ['--force' => true]);
$address = [
    'name' => 'Concurrency test',
    'line1' => '1 Test St',
    'city' => 'Boston',
    'postal_code' => '02101',
    'country' => 'US',
];

function race(array $tasks): array
{
    $directory = sys_get_temp_dir() . '/market-race-' . bin2hex(random_bytes(8));
    mkdir($directory, 0700);
    $start = microtime(true) + 0.5;
    DB::disconnect();
    $children = [];

    foreach ($tasks as $i => $task) {
        $pid = pcntl_fork();

        if ($pid === -1) {
            throw new RuntimeException('Cannot fork.');
        }

        if ($pid === 0) {
            DB::purge();

            while (microtime(true) < $start) {
                usleep(1000);
            }

            try {
                $result = ['ok' => $task()];
            } catch (BusinessException $e) {
                $result = ['conflict' => $e->getMessage()];
            } catch (Throwable $e) {
                $result = ['error' => get_class($e) . ': ' . $e->getMessage()];
            }
            file_put_contents($directory . '/' . $i, json_encode($result, JSON_THROW_ON_ERROR));
            exit(0);
        }

        $children[] = $pid;
    }

    foreach ($children as $pid) {
        pcntl_waitpid($pid, $status);
    }

    DB::purge();
    $results = [];

    foreach (array_keys($tasks) as $i) {
        $results[] = json_decode(file_get_contents($directory . '/' . $i), true, 512, JSON_THROW_ON_ERROR);
        unlink($directory . '/' . $i);
    }
    rmdir($directory);

    return $results;
}
function check(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$p = Product::factory()->create(['stock' => 1]);
$users = User::factory()
    ->count(2)
    ->create();
$tasks = [];

foreach ($users as $user) {
    CartItem::factory()->create([
        'user_id' => $user->id,
        'product_id' => $p->id,
        'quantity' => 1,
    ]);
    $data = new CheckoutData((string) Str::uuid(), PaymentMethod::Cod, $address);
    $tasks[] = fn () => app(CheckoutService::class)->checkout($user, $data)->id;
}

$results = race($tasks);
check(
    count(array_filter($results, fn ($r) => isset($r['ok']))) === 1,
    'Expected exactly one successful stock reservation: ' . json_encode($results),
);
check(
    count(array_filter($results, fn ($r) => isset($r['conflict']))) === 1,
    'Expected one stock conflict.',
);
check($p->fresh()->stock === 0 && Order::count() === 1, 'Stock/order invariant failed.');
echo "PASS: concurrent customers cannot oversell the final unit.\n";

$p = Product::factory()->create(['stock' => 5]);
$user = User::factory()->create();
CartItem::factory()->create([
    'user_id' => $user->id,
    'product_id' => $p->id,
    'quantity' => 2,
]);
$data = new CheckoutData((string) Str::uuid(), PaymentMethod::Cod, $address);
$task = fn () => app(CheckoutService::class)->checkout($user, $data)->id;
$results = race([$task, $task]);
check(
    isset($results[0]['ok'],$results[1]['ok']) && $results[0]['ok'] === $results[1]['ok'],
    'Same-key concurrent requests must return one order: ' . json_encode($results),
);
check(
    $p->fresh()->stock === 3 && Order::where('user_id', $user->id)->count() === 1,
    'Idempotency stock invariant failed.',
);
echo "PASS: concurrent identical checkout retries create one order and deduct stock once.\n";
