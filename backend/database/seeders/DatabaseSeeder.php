<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $lucas = User::query()->create([
            'name' => 'Lucas',
            'email' => 'lucas@teste.com',
            'password' => 'password123',
        ]);

        Wallet::query()->create([
            'user_id' => $lucas->id,
            'balance' => '0.00',
        ]);

        $barbara = User::query()->create([
            'name' => 'Barbara',
            'email' => 'barbara@teste.com',
            'password' => 'password123',
        ]);

        Wallet::query()->create([
            'user_id' => $barbara->id,
            'balance' => '0.00',
        ]);
    }
}
