<?php

namespace App\Console\Commands;

use App\Services\OtpService;
use Illuminate\Console\Command;

class OtpCleanupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'otp:cleanup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cleanup expired OTP tokens';

    protected $otpService;

    public function __construct(OtpService $otpService)
    {
        parent::__construct();
        $this->otpService = $otpService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting OTP cleanup...');
        
        $deleted = $this->otpService->cleanupExpired();
        
        $this->info("Cleanup completed. Deleted {$deleted} expired tokens.");
        
        return 0;
    }
}
