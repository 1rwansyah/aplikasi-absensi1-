<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => 'admin',    'label' => 'Administrator'],
            ['name' => 'hr',       'label' => 'HR / Personalia'],
            ['name' => 'employee', 'label' => 'Karyawan'],
        ];

        foreach ($roles as $data) {
            Role::updateOrCreate(['name' => $data['name']], ['label' => $data['label']]);
        }

        // Migrate existing users.role → pivot
        User::whereNotNull('role')->get()->each(function (User $user) {
            $role = Role::where('name', $user->role)->first();
            if ($role && ! $user->roles()->where('role_id', $role->id)->exists()) {
                $user->roles()->attach($role->id);
            }
        });
    }
}
