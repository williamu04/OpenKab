<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Config;
use Tests\BaseTestCase;

class OtpConfigurationTest extends BaseTestCase
{
    use DatabaseTransactions;

    /** @test */
    public function otp_routes_are_registered()
    {
        $routes = [
            'otp.index' => 'GET',
            'otp.setup' => 'POST', 
            'otp.verify-activation' => 'POST',
            'otp.disable' => 'POST',
            'otp.resend' => 'POST',
        ];

        foreach ($routes as $routeName => $method) {
            $this->assertTrue(
                \Illuminate\Support\Facades\Route::has($routeName),
                "Route {$routeName} is not registered"
            );
            
            $route = route($routeName);
            $this->assertNotNull($route, "Route {$routeName} does not have a valid URL");
        }
    }

    /** @test */
    public function telegram_configuration_is_testable()
    {
        // Test without token
        Config::set('telegram.bot_token', null);
        $this->assertNull(config('telegram.bot_token'));
        
        // Test with token
        Config::set('telegram.bot_token', 'test_token');
        $this->assertEquals('test_token', config('telegram.bot_token'));
    }

    /** @test */
    public function mail_configuration_supports_otp_mails()
    {
        $this->assertTrue(
            class_exists(\App\Mail\OtpMail::class),
            'OtpMail class does not exist'
        );
    }

    /** @test */
    public function database_tables_exist()
    {
        // Test otp_tokens table exists
        $this->assertTrue(
            \Illuminate\Support\Facades\Schema::hasTable('otp_tokens'),
            'otp_tokens table does not exist'
        );

        // Test required columns exist
        $requiredColumns = [
            'id', 'user_id', 'token_hash', 'channel', 
            'identifier', 'expires_at', 'attempts', 
            'created_at', 'updated_at'
        ];

        foreach ($requiredColumns as $column) {
            $this->assertTrue(
                \Illuminate\Support\Facades\Schema::hasColumn('otp_tokens', $column),
                "Column {$column} does not exist in otp_tokens table"
            );
        }

        // Test users table has OTP columns
        $otpColumns = ['otp_enabled', 'otp_channel', 'otp_identifier'];
        
        foreach ($otpColumns as $column) {
            $this->assertTrue(
                \Illuminate\Support\Facades\Schema::hasColumn('users', $column),
                "Column {$column} does not exist in users table"
            );
        }
    }

    /** @test */
    public function models_have_proper_relationships()
    {
        $user = \App\Models\User::factory()->create();
        $otpToken = \App\Models\OtpToken::factory()->create(['user_id' => $user->id]);

        // Test OtpToken belongs to User
        $this->assertInstanceOf(\App\Models\User::class, $otpToken->user);
        $this->assertEquals($user->id, $otpToken->user->id);

        // Test User has OTP methods
        $this->assertTrue(method_exists($user, 'hasOtpEnabled'));
        $this->assertTrue(method_exists($user, 'getOtpChannels'));
        $this->assertTrue(method_exists($user, 'otpTokens'));
    }

    /** @test */
    public function service_container_bindings_work()
    {
        $otpService = app(\App\Services\OtpService::class);
        $this->assertInstanceOf(\App\Services\OtpService::class, $otpService);
    }

    /** @test */
    public function request_classes_are_properly_configured()
    {
        $setupRequest = new \App\Http\Requests\OtpSetupRequest();
        $verifyRequest = new \App\Http\Requests\OtpVerifyRequest();

        $this->assertIsArray($setupRequest->rules());
        $this->assertIsArray($verifyRequest->rules());
        $this->assertIsArray($verifyRequest->messages());
    }

    /** @test */  
    public function controller_dependencies_can_be_resolved()
    {
        $controller = app(\App\Http\Controllers\OtpController::class);
        $this->assertInstanceOf(\App\Http\Controllers\OtpController::class, $controller);
    }
}