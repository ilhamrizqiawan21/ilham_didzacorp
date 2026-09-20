<?php

namespace Tests\Feature;

use App\Models\BlockedIp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlockedIpMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_ip_is_rejected_before_the_application_route_runs(): void
    {
        BlockedIp::create([
            'ip_address' => '127.0.0.1',
            'blocked_until' => now()->addMinutes(15),
            'reason' => 'Tes keamanan',
            'created_at' => now(),
        ]);

        $this->get(route('login'))
            ->assertStatus(403)
            ->assertSee('IP ini sedang diblokir');
    }

    public function test_expired_ip_is_not_rejected(): void
    {
        BlockedIp::create([
            'ip_address' => '127.0.0.1',
            'blocked_until' => now()->subMinute(),
            'reason' => 'Tes keamanan',
            'created_at' => now()->subMinute(),
        ]);

        $this->get(route('login'))->assertOk();
    }
}
