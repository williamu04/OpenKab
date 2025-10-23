<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Tambahkan field 2fa ke tabel users
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('2fa_enabled')->default(false);
            $table->json('2fa_channel')->nullable(); // untuk mendukung multiple channels  
            $table->string('2fa_identifier')->nullable(); // email atau telegram chat_id            
        });
        
        // Update menu untuk administrator
        \Illuminate\Support\Facades\Artisan::call('admin:menu-update');             
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['2fa_enabled', '2fa_channel', '2fa_identifier']);
        });
    }
};
