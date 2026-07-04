<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use App\Models\User;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $param = [
            'name' => 'user1',
            'email' => 'user1@example.com',
            'email_verified_at' => Carbon::now(),
            'password' => Hash::make('password'),
        ];
        User::create($param);

        $param = [
            'name' => 'user2',
            'email' => 'user2@example.com',
            'email_verified_at' => Carbon::now(),
            'password' => Hash::make('password'),
        ];
        User::create($param);

        $param = [
            'name' => 'user3',
            'email' => 'user3@example.com',
            'email_verified_at' => Carbon::now(),
            'password' => Hash::make('password'),
            'admin_status' => true,
        ];
        User::create($param);

    }
}
