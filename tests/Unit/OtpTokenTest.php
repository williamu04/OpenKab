<?php

namespace Tests\Unit;

use App\Models\OtpToken;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class OtpTokenTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_belongs_to_a_user()
    {
        $user = User::factory()->create();
        $otpToken = OtpToken::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $otpToken->user);
        $this->assertEquals($user->id, $otpToken->user->id);
    }

    /** @test */
    public function it_casts_expires_at_to_datetime()
    {
        $otpToken = OtpToken::factory()->create([
            'expires_at' => '2024-01-01 12:00:00'
        ]);

        $this->assertInstanceOf(\Carbon\Carbon::class, $otpToken->expires_at);
    }

    /** @test */
    public function it_casts_attempts_to_integer()
    {
        $otpToken = OtpToken::factory()->create(['attempts' => '5']);

        $this->assertIsInt($otpToken->attempts);
        $this->assertEquals(5, $otpToken->attempts);
    }

    /** @test */
    public function it_can_be_created_with_all_fillable_attributes()
    {
        $user = User::factory()->create();
        $expiresAt = now()->addMinutes(5);

        $otpToken = OtpToken::create([
            'user_id' => $user->id,
            'token_hash' => 'hashed_token',
            'channel' => 'email',
            'identifier' => 'test@example.com',
            'expires_at' => $expiresAt,
            'attempts' => 2,
        ]);

        $this->assertDatabaseHas('otp_tokens', [
            'user_id' => $user->id,
            'token_hash' => 'hashed_token',
            'channel' => 'email',
            'identifier' => 'test@example.com',
            'attempts' => 2,
        ]);

        $this->assertEquals($expiresAt->format('Y-m-d H:i:s'), $otpToken->expires_at->format('Y-m-d H:i:s'));
    }

    /** @test */
    public function it_can_increment_attempts()
    {
        $otpToken = OtpToken::factory()->create(['attempts' => 1]);

        $otpToken->increment('attempts');

        $this->assertEquals(2, $otpToken->fresh()->attempts);
    }

    /** @test */
    public function it_can_check_if_expired()
    {
        // Expired token
        $expiredToken = OtpToken::factory()->expired()->create();
        $this->assertTrue($expiredToken->expires_at->isPast());

        // Valid token
        $validToken = OtpToken::factory()->create([
            'expires_at' => now()->addMinutes(5)
        ]);
        $this->assertFalse($validToken->expires_at->isPast());
    }

    /** @test */
    public function it_can_check_max_attempts()
    {
        $otpToken = OtpToken::factory()->maxAttempts()->create();
        $this->assertEquals(3, $otpToken->attempts);
    }

    /** @test */
    public function it_supports_different_channels()
    {
        $emailToken = OtpToken::factory()->email('test@example.com')->create();
        $this->assertEquals('email', $emailToken->channel);
        $this->assertEquals('test@example.com', $emailToken->identifier);

        $telegramToken = OtpToken::factory()->telegram('123456789')->create();
        $this->assertEquals('telegram', $telegramToken->channel);
        $this->assertEquals('123456789', $telegramToken->identifier);
    }

    /** @test */
    public function it_can_be_queried_by_user_and_expiry()
    {
        $user = User::factory()->create();
        
        // Create expired token
        OtpToken::factory()->expired()->create(['user_id' => $user->id]);
        
        // Create valid token
        $validToken = OtpToken::factory()->create([
            'user_id' => $user->id,
            'expires_at' => now()->addMinutes(5)
        ]);

        // Query for valid tokens only
        $foundToken = OtpToken::where('user_id', $user->id)
            ->where('expires_at', '>', now())
            ->first();

        $this->assertNotNull($foundToken);
        $this->assertEquals($validToken->id, $foundToken->id);
    }

    /** @test */
    public function it_can_be_deleted_after_verification()
    {
        $otpToken = OtpToken::factory()->create();
        $tokenId = $otpToken->id;

        $otpToken->delete();

        $this->assertDatabaseMissing('otp_tokens', ['id' => $tokenId]);
    }

    /** @test */
    public function it_stores_hashed_token_securely()
    {
        $plainOtp = '123456';
        $otpToken = OtpToken::factory()->withOtp($plainOtp)->create();

        // Token should be hashed, not stored in plain text
        $this->assertNotEquals($plainOtp, $otpToken->token_hash);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check($plainOtp, $otpToken->token_hash));
    }

    /** @test */
    public function factory_creates_valid_tokens_by_default()
    {
        $otpToken = OtpToken::factory()->create();

        $this->assertNotNull($otpToken->user_id);
        $this->assertNotNull($otpToken->token_hash);
        $this->assertContains($otpToken->channel, ['email', 'telegram']);
        $this->assertNotNull($otpToken->identifier);
        $this->assertTrue($otpToken->expires_at->isFuture());
        $this->assertEquals(0, $otpToken->attempts);
    }
}