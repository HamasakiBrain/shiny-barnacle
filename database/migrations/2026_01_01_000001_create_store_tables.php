<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('users', fn (Blueprint $t) => $t->string('role')->default('customer'));
        Schema::create('categories', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('slug')->unique();
            $t->boolean('is_active')
                ->default(true)
                ->index();
            $t->timestamps();
        });
        Schema::create('products', function (Blueprint $t) {
            $t->id();
            $t->foreignId('category_id')
                ->constrained()
                ->restrictOnDelete();
            $t->string('name');
            $t->string('slug')->unique();
            $t->string('sku')->unique();
            $t->text('description');
            $t->unsignedInteger('price');
            $t->unsignedInteger('stock')->default(0);
            $t->boolean('is_active')->default(true);
            $t->index(['is_active', 'category_id']);
            $t->timestamps();
        });
        Schema::create('cart_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();
            $t->foreignId('product_id')
                ->constrained()
                ->cascadeOnDelete();
            $t->unsignedInteger('quantity');
            $t->unique(['user_id', 'product_id']);
            $t->timestamps();
        });
        Schema::create('orders', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')
                ->constrained()
                ->restrictOnDelete();
            $t->uuid('idempotency_key');
            $t->string('request_hash', 64);
            $t->string('status')->default('pending');
            $t->unsignedBigInteger('total');
            $t->string('currency', 3)->default('USD');
            $t->json('shipping_address');
            $t->unique(['user_id', 'idempotency_key']);
            $t->index(['user_id', 'status']);
            $t->timestamps();
        });
        Schema::create('order_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('order_id')
                ->constrained()
                ->cascadeOnDelete();
            $t->foreignId('product_id')
                ->constrained()
                ->restrictOnDelete();
            $t->string('name');
            $t->string('sku');
            $t->unsignedInteger('unit_price');
            $t->unsignedInteger('quantity');
            $t->timestamps();
        });
        Schema::create('payments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('order_id')
                ->unique()
                ->constrained()
                ->cascadeOnDelete();
            $t->string('method');
            $t->string('status')->default('pending');
            $t->unsignedBigInteger('amount');
            $t->timestamp('paid_at')->nullable();
            $t->timestamps();
        });

        foreach (['reviews', 'favorites'] as $table) {
            Schema::create($table, function (Blueprint $t) use ($table) {
                $t->id();
                $t->foreignId('user_id')
                    ->constrained()
                    ->cascadeOnDelete();
                $t->foreignId('product_id')
                    ->constrained()
                    ->cascadeOnDelete();

                if ($table === 'reviews') {
                    $t->unsignedTinyInteger('rating');
                    $t->text('body');
                }

                $t->unique(['user_id', 'product_id']);
                $t->timestamps();
            });
        }

        Schema::create('notifications', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->string('type');
            $t->morphs('notifiable');
            $t->text('data');
            $t->timestamp('read_at')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach ([
            'notifications',
            'favorites',
            'reviews',
            'payments',
            'order_items',
            'orders',
            'cart_items',
            'products',
            'categories',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::table('users', fn (Blueprint $t) => $t->dropColumn('role'));
    }
};
