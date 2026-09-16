<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\GoogleTokenVerifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Mockery\MockInterface;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ApiHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_inactive_user_cannot_use_existing_api_authentication(): void
    {
        $user = User::factory()->create(['status' => 'inactive']);
        Sanctum::actingAs($user, ['api:access']);

        $this->getJson('/api/v1/me')
            ->assertForbidden()
            ->assertJsonPath('message', 'Akun tidak aktif atau telah kedaluwarsa.');
    }

    public function test_api_token_has_finite_expiry_and_scoped_abilities(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password',
            'device_name' => 'test-device',
        ])->assertOk()->assertJsonStructure(['token', 'expires_at']);

        $token = $user->tokens()->latest('id')->firstOrFail();
        $this->assertNotNull($token->expires_at);
        $this->assertContains('api:access', $token->abilities);
        $this->assertTrue($token->expires_at->isFuture());
    }

    public function test_google_login_does_not_link_by_email(): void
    {
        $user = User::factory()->create([
            'email' => 'verified@example.test',
            'google_id' => null,
        ]);

        $this->mock(GoogleTokenVerifier::class, function (MockInterface $mock): void {
            $mock->shouldReceive('verify')->once()->andReturn([
                'sub' => 'google-subject',
                'email' => 'verified@example.test',
                'email_verified' => true,
            ]);
        });

        $this->postJson('/api/v1/auth/google', ['id_token' => 'valid-token'])
            ->assertUnprocessable();

        $this->assertNull($user->fresh()->google_id);
    }

    public function test_finance_and_dynamic_modules_enforce_existing_policies(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        Sanctum::actingAs($user, ['api:access']);

        $this->getJson('/api/v1/finance/dashboard')->assertForbidden();
        $this->getJson('/api/v1/modules/products')->assertForbidden();
    }

    public function test_reports_reject_ranges_over_one_year(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        Permission::findOrCreate('ViewAny:DataPembayaran', 'web');
        $user->givePermissionTo('ViewAny:DataPembayaran');
        Sanctum::actingAs($user, ['api:access']);

        $this->getJson('/api/v1/finance/reports/summary?from=2024-01-01&to=2025-12-31')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('from');
    }
}
