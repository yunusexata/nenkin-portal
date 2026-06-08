<?php

namespace Database\Seeders\User;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::create([
            'name' => "Admin",
            'email' => "admin@gmail.com",
            'password' => Hash::make("123exata"),
        ]);

        $user->assignRole(User::ROLE_SUPER_ADMIN);

        // HS
        $user = User::create([
            'name' => "Vita",
            'email' => "dira.exataindonesia2018@gmail.com",
            'pic' => 'AI',
            'password' => Hash::make("123exata"),
        ]);
        $user->assignRole(User::ROLE_HS);

        $user = User::create([
            'name' => "Cynthia",
            'email' => "amin.exataindonesia2021@gmail.com",
            'pic' => 'MT',
            'password' => Hash::make("123exata"),
        ]);

        $user->assignRole(User::ROLE_HS);
        $user = User::create([
            'name' => "Mutia",
            'email' => "internship.exatagroup05@gmail.com",
            'pic' => 'SN',
            'password' => Hash::make("123exata"),
        ]);
        $user->assignRole(User::ROLE_HS);
        $user = User::create([
            'name' => "client",
            'email' => "client@gmail.com",
            'pic' => 'AI',
            'password' => Hash::make("123exata"),
        ]);
        $user->assignRole(User::ROLE_CLIENT);
    }
}
