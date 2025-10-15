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

        // Buat permissions untuk 2fa
        $permissions = [
            'pengaturan-2fa-read',
            'pengaturan-2fa-write', 
            'pengaturan-2fa-edit',
            'pengaturan-2fa-update',
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
            // Tambahkan menu 2fa di Pengaturan Aplikasi pada field menu
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
                            'text' => 'Aktivasi 2fa',
                            'icon' => 'far fa-fw fa-circle',
                            'url' => 'pengaturan/2fa',
                            'permission' => 'pengaturan-2fa',
                        ]
                    ],
                ];
            } else {
                // Tambahkan submenu Aktivasi 2fa jika belum ada
                if (!isset($menu[$pengaturanIndex]['submenu'])) {
                    $menu[$pengaturanIndex]['submenu'] = [];
                }
                $faMenuExists = collect($menu[$pengaturanIndex]['submenu'])->firstWhere('url', 'pengaturan/2fa');
                if (!$faMenuExists) {
                    $menu[$pengaturanIndex]['submenu'][] = [
                        'text' => 'Aktivasi 2fa',
                        'icon' => 'far fa-fw fa-circle',
                        'url' => 'pengaturan/2fa',
                        'permission' => 'pengaturan-2fa',
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

                    $faMenuExists = collect($item['submenu'])->firstWhere('url', 'pengaturan/2fa');
                    if (!$faMenuExists) {
                        $item['submenu'][] = [
                            'text' => 'Aktivasi 2fa',
                            'icon' => 'far fa-fw fa-circle',
                            'url' => 'pengaturan/2fa',
                            'permission' => 'pengaturan-2fa',
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
            $table->dropColumn(['2fa_enabled', '2fa_channel', '2fa_identifier']);
        });        

        // Hapus permissions 2fa
        $permissions = [
            'pengaturan-2fa-read',
            'pengaturan-2fa-write', 
            'pengaturan-2fa-edit',
            'pengaturan-2fa-update',
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
