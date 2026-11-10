<?php

namespace Tests\Feature;

use App\Events\OrderPlaced;
use App\Jobs\SendOrderNotification;
use App\Models\Order;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use DatabaseMigrations;

    public function test_event_queues_only_after_commit_and_not_on_rollback(): void
    {
        Queue::fake();
        DB::beginTransaction();
        OrderPlaced::dispatch(1);
        Queue::assertNothingPushed();
        DB::rollBack();
        Queue::assertNothingPushed();
        DB::beginTransaction();
        OrderPlaced::dispatch(2);
        Queue::assertNothingPushed();
        DB::commit();
        Queue::assertPushed(SendOrderNotification::class, fn ($job) => $job->orderId === 2);
    }

    public function test_job_is_idempotent_and_notifications_are_private(): void
    {
        $u = User::factory()->create();
        $o = Order::factory()->create(['user_id' => $u->id]);
        $job = new SendOrderNotification($o->id);
        $job->handle(app(NotificationService::class));
        $job->handle(app(NotificationService::class));
        $this->assertDatabaseCount('notifications', 1);
        $this->actingAs($u)
            ->getJson('/api/v1/notifications')
            ->assertOk()
            ->assertJsonCount(1, 'data');
        $this->actingAs(User::factory()->create())
            ->getJson('/api/v1/notifications')
            ->assertOk()
            ->assertJsonCount(
                0,
                'data',
            );
    }
}
