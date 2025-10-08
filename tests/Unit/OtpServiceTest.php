<?php

namespace Tests\Unit;

use App\Models\OtpToken;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class OtpServiceTest extends TestCase
{
    use DatabaseTransactions;

    protected OtpService $otpService;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->otpService = new OtpService();
        $this->user = User::factory()->create();
    }

    /** @test */
    public function it_can_generate_and_send_otp_via_email()
    {
        Mail::fake();
        
        $result = $this->otpService->generateAndSend(
            $this->user->id,
            'email',
            'test@example.com'
        );

        $this->assertTrue($result['success']);
        $this->assertEquals('Kode OTP berhasil dikirim', $result['message']);
        $this->assertEquals('email', $result['channel']);

        // Verify OTP token was created
        $this->assertDatabaseHas('otp_tokens', [
            'user_id' => $this->user->id,
            'channel' => 'email',
            'identifier' => 'test@example.com',
            'attempts' => 0
        ]);

        // Verify email was sent
        Mail::assertSent(\App\Mail\OtpMail::class);
    }

    /** @test */
    public function it_can_generate_and_send_otp_via_telegram()
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true], 200)
        ]);
        
        // Set environment variable for Telegram bot token
        putenv('TELEGRAM_BOT_TOKEN=fake_token');
        $_ENV['TELEGRAM_BOT_TOKEN'] = 'fake_token';
        
        $result = $this->otpService->generateAndSend(
            $this->user->id,
            'telegram',
            '123456789'
        );

        $this->assertTrue($result['success']);
        $this->assertEquals('Kode OTP berhasil dikirim', $result['message']);
        $this->assertEquals('telegram', $result['channel']);

        // Verify OTP token was created
        $this->assertDatabaseHas('otp_tokens', [
            'user_id' => $this->user->id,
            'channel' => 'telegram',
            'identifier' => '123456789',
            'attempts' => 0
        ]);

        // Verify Telegram API was called
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'api.telegram.org') &&
                   $request['chat_id'] === '123456789';
        });
    }

    /** @test */
    public function it_deletes_existing_tokens_before_creating_new_one()
    {
        // Create existing token
        $existingToken = OtpToken::factory()->create([
            'user_id' => $this->user->id
        ]);

        Mail::fake();
        
        $this->otpService->generateAndSend(
            $this->user->id,
            'email',
            'test@example.com'
        );

        // Verify old token was deleted
        $this->assertDatabaseMissing('otp_tokens', [
            'id' => $existingToken->id
        ]);

        // Verify only one token exists for user
        $this->assertEquals(1, OtpToken::where('user_id', $this->user->id)->count());
    }

    /** @test */
    public function it_handles_telegram_send_failure()
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => false], 400)
        ]);
        
        putenv('TELEGRAM_BOT_TOKEN=fake_token');
        $_ENV['TELEGRAM_BOT_TOKEN'] = 'fake_token';
        
        $result = $this->otpService->generateAndSend(
            $this->user->id,
            'telegram',
            '123456789'
        );

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Gagal mengirim OTP', $result['message']);
    }    

    /** @test */
    public function it_can_verify_correct_otp()
    {
        $otp = '123456';
        $hashedOtp = Hash::make($otp);
        
        OtpToken::factory()->create([
            'user_id' => $this->user->id,
            'token_hash' => $hashedOtp,
            'expires_at' => now()->addMinutes(5),
            'attempts' => 0
        ]);

        $result = $this->otpService->verify($this->user->id, $otp);

        $this->assertTrue($result['success']);
        $this->assertEquals('Kode OTP berhasil diverifikasi', $result['message']);

        // Verify token was deleted after successful verification
        $this->assertEquals(0, OtpToken::where('user_id', $this->user->id)->count());
    }

    /** @test */
    public function it_rejects_incorrect_otp()
    {
        $correctOtp = '123456';
        $incorrectOtp = '654321';
        $hashedOtp = Hash::make($correctOtp);
        
        $token = OtpToken::factory()->create([
            'user_id' => $this->user->id,
            'token_hash' => $hashedOtp,
            'expires_at' => now()->addMinutes(5),
            'attempts' => 0
        ]);

        $result = $this->otpService->verify($this->user->id, $incorrectOtp);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Kode OTP salah', $result['message']);
        $this->assertStringContainsString('Percobaan tersisa: 1', $result['message']);

        // Verify attempts were incremented
        $token->refresh();
        $this->assertEquals(1, $token->attempts);
    }

    /** @test */
    public function it_rejects_expired_otp()
    {
        $otp = '123456';
        $hashedOtp = Hash::make($otp);
        
        OtpToken::factory()->create([
            'user_id' => $this->user->id,
            'token_hash' => $hashedOtp,
            'expires_at' => now()->subMinutes(1), // Expired
            'attempts' => 0
        ]);

        $result = $this->otpService->verify($this->user->id, $otp);

        $this->assertFalse($result['success']);
        $this->assertEquals('Token OTP tidak ditemukan atau sudah kedaluwarsa', $result['message']);
    }

    /** @test */
    public function it_rejects_otp_when_max_attempts_reached()
    {
        $otp = '123456';
        $hashedOtp = Hash::make($otp);
        
        OtpToken::factory()->create([
            'user_id' => $this->user->id,
            'token_hash' => $hashedOtp,
            'expires_at' => now()->addMinutes(5),
            'attempts' => 3 // Max attempts reached
        ]);

        $result = $this->otpService->verify($this->user->id, $otp);

        $this->assertFalse($result['success']);
        $this->assertEquals('Terlalu banyak percobaan salah. Silakan minta kode baru.', $result['message']);
    }

    /** @test */
    public function it_returns_error_when_no_token_exists()
    {
        $result = $this->otpService->verify($this->user->id, '123456');

        $this->assertFalse($result['success']);
        $this->assertEquals('Token OTP tidak ditemukan atau sudah kedaluwarsa', $result['message']);
    }

    /** @test */
    public function it_can_cleanup_expired_tokens()
    {
        // Create expired tokens
        OtpToken::factory()->count(3)->create([
            'expires_at' => now()->subMinutes(10)
        ]);

        // Create valid tokens
        OtpToken::factory()->count(2)->create([
            'expires_at' => now()->addMinutes(5)
        ]);

        $deletedCount = $this->otpService->cleanupExpired();

        $this->assertEquals(3, $deletedCount);
        $this->assertEquals(2, OtpToken::count());
    }

    /** @test */
    public function it_generates_otp_with_correct_properties()
    {
        Mail::fake();
        
        $result = $this->otpService->generateAndSend(
            $this->user->id,
            'email',
            'test@example.com'
        );

        $this->assertTrue($result['success']);
        $this->assertEquals('Kode OTP berhasil dikirim', $result['message']);
        $this->assertEquals('email', $result['channel']);

        // Verify token properties
        $token = OtpToken::where('user_id', $this->user->id)->first();
        $this->assertNotNull($token);
        $this->assertEquals('email', $token->channel);
        $this->assertEquals('test@example.com', $token->identifier);
        $this->assertEquals(0, $token->attempts);
        $this->assertTrue($token->expires_at->isFuture());
    }

    /** @test */
    public function it_handles_verification_attempts_correctly()
    {
        $correctOtp = '123456';
        $incorrectOtp = '654321';
        $hashedOtp = Hash::make($correctOtp);
        
        $token = OtpToken::factory()->create([
            'user_id' => $this->user->id,
            'token_hash' => $hashedOtp,
            'expires_at' => now()->addMinutes(5),
            'attempts' => 0
        ]);

        // First wrong attempt
        $result = $this->otpService->verify($this->user->id, $incorrectOtp);
        $this->assertFalse($result['success']);
        
        $token->refresh();
        $this->assertEquals(1, $token->attempts);

        // Correct attempt should still work
        $result = $this->otpService->verify($this->user->id, $correctOtp);
        $this->assertTrue($result['success']);
    }
}