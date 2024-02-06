<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        User::create([
            'first_name' => 'Nodar',
            'last_name' => 'Tchikadze',
            'email' => 'nodaritchikadze@flatrocktech.com',
            'role' => 'admin',
            'password' => bcrypt('flatrocktech'),
        ]);
    }
}
