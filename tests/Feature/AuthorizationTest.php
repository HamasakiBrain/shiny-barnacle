<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Order;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_policies_reject_other_customers(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $owner->id]);
        $review = Review::factory()->create(['user_id' => $owner->id]);
        $this->assertTrue(Gate::forUser($owner)->allows('view', $order));
        $this->assertFalse(Gate::forUser($other)->allows('view', $order));
        $this->assertFalse(Gate::forUser($other)->allows('delete', $review));
        $this->assertFalse(Gate::forUser($other)->allows('admin'));
        $other->role = Role::Admin;
        $this->assertTrue(Gate::forUser($other)->allows('admin'));
        $this->assertTrue(Gate::forUser($other)->allows('view', $order));
        $this->assertFalse(Gate::forUser($other)->allows('pay', $order));
    }

    public function test_role_is_not_mass_assignable(): void
    {
        $user = new User([
            'name' => 'Test',
            'role' => 'admin',
        ]);
        $this->assertNull($user->role);
    }
}
