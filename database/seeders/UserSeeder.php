<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\str;


class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name' => 'Alexander Graham',
            'email' => 'asw@gmail.com',
            'email_verified_at' => now(),
            'password' => Hash::make('123456'), // Ganti dengan password yang lebih aman
            'remember_token' => Str::random(10),
        ]);
    }
}
