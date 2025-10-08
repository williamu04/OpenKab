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
        // Tambahkan field OTP ke tabel users
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('otp_enabled')->default(false);
            $table->json('otp_channel')->nullable(); // untuk mendukung multiple channels  
            $table->string('otp_identifier')->nullable(); // email atau telegram chat_id
            $table->string('telegram_chat_id')->nullable();
        });

        // Buat tabel untuk menyimpan token OTP
        Schema::create('otp_tokens', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('token_hash');
            $table->enum('channel', ['email', 'telegram']);
            $table->string('identifier'); // email atau telegram chat_id
            $table->timestamp('expires_at');
            $table->integer('attempts')->default(0);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['user_id', 'expires_at']);
        });

        // Buat permissions untuk OTP
        $permissions = [
            'pengaturan-otp-read',
            'pengaturan-otp-write', 
            'pengaturan-otp-edit',
            'pengaturan-otp-update',
        ];

        foreach ($permissions as $permission) {
            \Spatie\Permission\Models\Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }
        
        // Update menu untuk administrator
        \Illuminate\Support\Facades\Artisan::call('admin:menu-update');

        $teams = \App\Models\Team::all();
        foreach ($teams as $team) {
            foreach ($permissions as $permission) {                
                foreach (($team->roles ?? []) as $role) {
                    $role->givePermissionTo($permission);
                }                
            }
            // Tambahkan menu OTP di Pengaturan Aplikasi pada field menu
            $menu = $team->menu ?? [];
            $menu = is_string($menu) ? json_decode($menu, true) : $menu;
            if (!is_array($menu)) $menu = [];

            // Cari atau tambahkan menu Pengaturan Aplikasi
            $pengaturanIndex = collect($menu)->search(function ($item) {
                return isset($item['text']) && $item['text'] === 'Pengaturan Aplikasi';
            });

            if ($pengaturanIndex === false) {
                // Tambahkan menu Pengaturan Aplikasi jika belum ada
                $menu[] = [
                    'text' => 'Pengaturan Aplikasi',
                    'icon' => 'fas fa-fw fa-cogs',
                    'url' => null,
                    'permission' => null,
                    'submenu' => [
                        [
                            'text' => 'Aktivasi OTP',
                            'icon' => 'far fa-fw fa-circle',
                            'url' => 'pengaturan/otp',
                            'permission' => 'pengaturan-otp',
                        ]
                    ],
                ];
            } else {
                // Tambahkan submenu Aktivasi OTP jika belum ada
                if (!isset($menu[$pengaturanIndex]['submenu'])) {
                    $menu[$pengaturanIndex]['submenu'] = [];
                }
                $otpMenuExists = collect($menu[$pengaturanIndex]['submenu'])->firstWhere('url', 'pengaturan/otp');
                if (!$otpMenuExists) {
                    $menu[$pengaturanIndex]['submenu'][] = [
                        'text' => 'Aktivasi OTP',
                        'icon' => 'far fa-fw fa-circle',
                        'url' => 'pengaturan/otp',
                        'permission' => 'pengaturan-otp',
                    ];
                }
            }
            $team->menu = $menu;
            $team->save();

            $menuOrder = $team->menu_order ?? [];
            if (!$menuOrder) continue;

            $menuOrder = collect($menuOrder)->map(function ($item) {
                if ($item['text'] === 'Pengaturan Aplikasi') {
                    if (!isset($item['submenu'])) {
                        $item['submenu'] = [];
                    }

                    $otpMenuExists = collect($item['submenu'])->firstWhere('url', 'pengaturan/otp');
                    if (!$otpMenuExists) {
                        $item['submenu'][] = [
                            'text' => 'Aktivasi OTP',
                            'icon' => 'far fa-fw fa-circle',
                            'url' => 'pengaturan/otp',
                            'permission' => 'pengaturan-otp',
                        ];
                    }
                }
                return $item;
            })->toArray();

            $team->menu_order = $menuOrder;
            $team->save();
        }                
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['otp_enabled', 'otp_channel', 'otp_identifier', 'telegram_chat_id']);
        });

        Schema::dropIfExists('otp_tokens');

        // Hapus permissions OTP
        $permissions = [
            'pengaturan-otp-read',
            'pengaturan-otp-write', 
            'pengaturan-otp-edit',
            'pengaturan-otp-update',
        ];

        foreach ($permissions as $permission) {
            $perm = \Spatie\Permission\Models\Permission::where('name', $permission)->where('guard_name', 'web')->first();
            if ($perm) {
                foreach ($perm->roles as $role) {
                    $role->revokePermissionTo($perm);
                }
            }
            if ($perm) {
                $perm->delete();
            }
        }
    }
};
