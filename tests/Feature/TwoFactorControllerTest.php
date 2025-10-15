<?php

namespace Tests\Feature;

use App\Models\OtpToken;
use App\Models\User;
use App\Services\OtpService;
use App\Services\TwoFactorService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Mockery;
use Tests\TestCase;

class TwoFactorControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected $twoFactorService;
    protected $otpService;

    public function setUp(): void
    {
        parent::setUp();
        
        // Start session for testing
        $this->startSession();
        
        // Skip auth middleware for these tests
        $this->withoutMiddleware([\App\Http\Middleware\TwoFactorMiddleware::class]);
        
        $this->user = User::factory()->create([
            '2fa_enabled' => false,
            '2fa_channel' => null,
            '2fa_identifier' => null,
        ]);
        
        // Mock the services
        $this->twoFactorService = Mockery::mock(TwoFactorService::class);
        $this->otpService = Mockery::mock(OtpService::class);
        
        // Bind mocks to the container
        $this->app->instance(TwoFactorService::class, $this->twoFactorService);
        $this->app->instance(OtpService::class, $this->otpService);
    }

    /** @test */
    public function it_shows_2fa_settings_page()
    {
        $this->twoFactorService
            ->shouldReceive('getUserTwoFactorStatus')
            ->once()
            ->with($this->user)
            ->andReturn([
                'enabled' => false,
                'channel' => null,
                'identifier' => null,
            ]);

        $response = $this->actingAs($this->user)
            ->get(route('2fa.index'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.pengaturan.2fa.index');
        $response->assertViewHas('user', $this->user);
        $response->assertViewHas('twoFactorStatus');
    }

    /** @test */
    public function it_can_initiate_2fa_enable_process()
    {
        $this->otpService
            ->shouldReceive('generateAndSend')
            ->once()
            ->with($this->user->id, 'email', 'test@example.com')
            ->andReturn([
                'success' => true,
                'message' => 'Kode OTP berhasil dikirim',
                'channel' => 'email'
            ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('2fa.enable'), [
                'channel' => 'email',
                'identifier' => 'test@example.com'
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Kode verifikasi telah dikirim untuk aktivasi 2FA',
            'redirect' => route('2fa.verify-form')
        ]);

        // Verify session has temp config
        $this->assertArrayHasKey('temp_2fa_config', session()->all());
        $this->assertEquals('email', session('temp_2fa_config.channel'));
        $this->assertEquals('test@example.com', session('temp_2fa_config.identifier'));
    }

    /** @test */
    public function it_handles_otp_service_failure_during_2fa_enable()
    {
        $this->otpService
            ->shouldReceive('generateAndSend')
            ->once()
            ->with($this->user->id, 'email', 'test@example.com')
            ->andReturn([
                'success' => false,
                'message' => 'Failed to send OTP'
            ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('2fa.enable'), [
                'channel' => 'email',
                'identifier' => 'test@example.com'
            ]);

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
            'message' => 'Failed to send OTP'
        ]);
    }

    /** @test */
    public function it_enforces_rate_limiting_on_2fa_enable()
    {
        // Hit rate limit
        for ($i = 0; $i < 3; $i++) {
            RateLimiter::hit('2fa-setup:' . $this->user->id);
        }

        $response = $this->actingAs($this->user)
            ->postJson(route('2fa.enable'), [
                'channel' => 'email',
                'identifier' => 'test@example.com'
            ]);

        $response->assertStatus(429);
        $response->assertJson([
            'success' => false
        ]);
    }

    /** @test */
    public function it_shows_2fa_verification_form()
    {
        session(['temp_2fa_config' => [
            'channel' => 'email',
            'identifier' => 'test@example.com'
        ]]);

        // No need to mock getUserTwoFactorStatus since we're not testing it here

        $response = $this->actingAs($this->user)
            ->get(route('2fa.verify-form'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.pengaturan.2fa.verify');
        $response->assertViewHas('user', $this->user);
        $response->assertViewHas('tempConfig');
    }

    /** @test */
    public function it_redirects_if_no_temp_config_when_showing_verification_form()
    {
        $response = $this->actingAs($this->user)
            ->get(route('2fa.verify-form'));

        $response->assertRedirect(route('2fa.index'));
        $response->assertSessionHas('error', 'Sesi aktivasi tidak ditemukan. Silakan mulai dari awal.');
    }

    /** @test */
    public function it_can_verify_and_enable_2fa()
    {
        session(['temp_2fa_config' => [
            'channel' => 'email',
            'identifier' => 'test@example.com'
        ]]);

        $this->otpService
            ->shouldReceive('verify')
            ->once()
            ->with($this->user->id, '123456')
            ->andReturn([
                'success' => true,
                'message' => 'OTP verified successfully'
            ]);

        $this->twoFactorService
            ->shouldReceive('enableTwoFactor')
            ->once()
            ->with($this->user, 'email', 'test@example.com')
            ->andReturn(true);

        $response = $this->actingAs($this->user)
            ->postJson(route('2fa.verify'), [
                'code' => '123456'
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => '2FA berhasil diaktifkan! Anda sekarang akan diminta kode verifikasi setelah login.',
            'redirect' => route('2fa.index')
        ]);

        // Verify session has 2fa_verified
        $this->assertTrue(session('2fa_verified'));
        
        // Verify temp config is removed
        $this->assertArrayNotHasKey('temp_2fa_config', session()->all());
    }

    /** @test */
    public function it_rejects_verification_without_temp_config()
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('2fa.verify'), [
                'code' => '123456'
            ]);

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
            'message' => 'Sesi aktivasi tidak ditemukan. Silakan mulai dari awal.'
        ]);
    }

    /** @test */
    public function it_handles_otp_verification_failure()
    {
        session(['temp_2fa_config' => [
            'channel' => 'email',
            'identifier' => 'test@example.com'
        ]]);

        $this->otpService
            ->shouldReceive('verify')
            ->once()
            ->with($this->user->id, '123456')
            ->andReturn([
                'success' => false,
                'message' => 'Invalid OTP'
            ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('2fa.verify'), [
                'code' => '123456'
            ]);

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
            'message' => 'Invalid OTP'
        ]);
    }

    /** @test */
    public function it_enforces_rate_limiting_on_2fa_verification()
    {
        session(['temp_2fa_config' => [
            'channel' => 'email',
            'identifier' => 'test@example.com'
        ]]);

        // Hit rate limit
        for ($i = 0; $i < 5; $i++) {
            RateLimiter::hit('2fa-verify:' . $this->user->id);
        }

        $response = $this->actingAs($this->user)
            ->postJson(route('2fa.verify'), [
                'code' => '123456'
            ]);

        $response->assertStatus(429);
        $response->assertJson([
            'success' => false
        ]);
    }

    /** @test */
    public function it_can_disable_2fa()
    {
        $this->twoFactorService
            ->shouldReceive('disableTwoFactor')
            ->once()
            ->with($this->user)
            ->andReturn(true);

        $response = $this->actingAs($this->user)
            ->postJson(route('2fa.disable'));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => '2FA berhasil dinonaktifkan'
        ]);
    }

    /** @test */
    public function it_can_resend_2fa_verification_code()
    {
        session(['temp_2fa_config' => [
            'channel' => 'email',
            'identifier' => 'test@example.com'
        ]]);

        $this->otpService
            ->shouldReceive('generateAndSend')
            ->once()
            ->with($this->user->id, 'email', 'test@example.com')
            ->andReturn([
                'success' => true,
                'message' => 'Kode OTP berhasil dikirim',
                'channel' => 'email'
            ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('2fa.resend'));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Kode OTP berhasil dikirim',
            'channel' => 'email'
        ]);
    }

    /** @test */
    public function it_rejects_resend_without_temp_config()
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('2fa.resend'));

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
            'message' => 'Sesi aktivasi tidak ditemukan.'
        ]);
    }

    /** @test */
    public function it_enforces_rate_limiting_on_resend()
    {
        session(['temp_2fa_config' => [
            'channel' => 'email',
            'identifier' => 'test@example.com'
        ]]);

        // Hit rate limit
        for ($i = 0; $i < 2; $i++) {
            RateLimiter::hit('2fa-resend:' . $this->user->id);
        }

        $response = $this->actingAs($this->user)
            ->postJson(route('2fa.resend'));

        $response->assertStatus(429);
        $response->assertJson([
            'success' => false
        ]);
    }

    /** @test */
    public function it_shows_2fa_challenge_page()
    {
        // Enable 2FA for user
        $this->user->update([
            '2fa_enabled' => true,
            '2fa_channel' => json_encode(['email']),
            '2fa_identifier' => 'test@example.com'
        ]);

        $this->twoFactorService
            ->shouldReceive('getTwoFactorChannels')
            ->once()
            ->with($this->user)
            ->andReturn(['email']);

        $this->twoFactorService
            ->shouldReceive('getTwoFactorIdentifier')
            ->once()
            ->with($this->user)
            ->andReturn('test@example.com');

        $this->otpService
            ->shouldReceive('generateAndSend')
            ->once()
            ->with($this->user->id, 'email', 'test@example.com')
            ->andReturn([
                'success' => true,
                'message' => 'Kode OTP berhasil dikirim'
            ]);

        $response = $this->actingAs($this->user)
            ->get(route('2fa.challenge'));

        $response->assertStatus(200);
        $response->assertViewIs('auth.2fa-challenge');
    }

    /** @test */
    public function it_redirects_to_dashboard_if_2fa_not_enabled()
    {
        // Ensure 2FA is disabled
        $this->user->update(['2fa_enabled' => false]);

        $response = $this->actingAs($this->user)
            ->get(route('2fa.challenge'));

        $response->assertRedirect(route('dasbor'));
    }

    /** @test */
    public function it_can_verify_2fa_challenge()
    {
        // Enable 2FA for user
        $this->user->update([
            '2fa_enabled' => true,
            '2fa_channel' => json_encode(['email']),
            '2fa_identifier' => 'test@example.com'
        ]);

        $this->otpService
            ->shouldReceive('verify')
            ->once()
            ->with($this->user->id, '123456')
            ->andReturn([
                'success' => true,
                'message' => 'OTP verified successfully'
            ]);

        session(['url.intended' => route('dasbor')]);

        $response = $this->actingAs($this->user)
            ->postJson(route('2fa.challenge.verify'), [
                'code' => '123456'
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Verifikasi berhasil',
            'redirect' => route('dasbor')
        ]);

        // Verify session has 2fa_verified
        $this->assertTrue(session('2fa_verified'));
    }

    /** @test */
    public function it_handles_2fa_challenge_verification_failure()
    {
        // Enable 2FA for user
        $this->user->update([
            '2fa_enabled' => true,
            '2fa_channel' => json_encode(['email']),
            '2fa_identifier' => 'test@example.com'
        ]);

        $this->otpService
            ->shouldReceive('verify')
            ->once()
            ->with($this->user->id, '123456')
            ->andReturn([
                'success' => false,
                'message' => 'Invalid OTP'
            ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('2fa.challenge.verify'), [
                'code' => '123456'
            ]);

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
            'message' => 'Invalid OTP'
        ]);
    }

    /** @test */
    public function it_enforces_rate_limiting_on_2fa_challenge()
    {
        // Enable 2FA for user
        $this->user->update([
            '2fa_enabled' => true,
            '2fa_channel' => json_encode(['email']),
            '2fa_identifier' => 'test@example.com'
        ]);

        // Hit rate limit
        for ($i = 0; $i < 5; $i++) {
            RateLimiter::hit('2fa-challenge:' . $this->user->id);
        }

        $response = $this->actingAs($this->user)
            ->postJson(route('2fa.challenge.verify'), [
                'code' => '123456'
            ]);

        $response->assertStatus(429);
        $response->assertJson([
            'success' => false
        ]);
    }

    protected function tearDown(): void
    {
        // Clear rate limiters after each test
        RateLimiter::clear('2fa-setup:' . $this->user->id);
        RateLimiter::clear('2fa-verify:' . $this->user->id);
        RateLimiter::clear('2fa-resend:' . $this->user->id);
        RateLimiter::clear('2fa-challenge:' . $this->user->id);
        
        // Mockery::close();
        parent::tearDown();
    }
}