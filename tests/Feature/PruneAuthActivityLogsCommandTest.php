<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PruneAuthActivityLogsCommandTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_deletes_only_login_and_logout_logs(): void
    {
        $user = User::factory()->create();

        ActivityLog::create([
            'user_id' => $user->id,
            'role' => 'admin',
            'action' => 'login',
            'description' => 'User berhasil login ke sistem.',
        ]);

        ActivityLog::create([
            'user_id' => $user->id,
            'role' => 'admin',
            'action' => 'logout',
            'description' => 'User logout dari sistem.',
        ]);

        ActivityLog::create([
            'user_id' => $user->id,
            'role' => 'admin',
            'action' => 'create',
            'description' => 'Tambah absensi manual',
        ]);

        $this->artisan('activity-log:prune-login-logout', ['--force' => true])
            ->assertSuccessful();

        $this->assertDatabaseMissing('activity_logs', ['action' => 'login']);
        $this->assertDatabaseMissing('activity_logs', ['action' => 'logout']);
        $this->assertDatabaseHas('activity_logs', ['action' => 'create']);
    }

    #[Test]
    public function dry_run_does_not_delete_logs(): void
    {
        $user = User::factory()->create();

        ActivityLog::create([
            'user_id' => $user->id,
            'role' => 'admin',
            'action' => 'login',
            'description' => 'User berhasil login ke sistem.',
        ]);

        $this->artisan('activity-log:prune-login-logout', ['--dry-run' => true])
            ->assertSuccessful();

        $this->assertDatabaseHas('activity_logs', ['action' => 'login']);
    }
}
