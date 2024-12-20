<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::factory()->create([
            'name'     => 'Hatem Mohamed',
            'email'    => 'hatem_mohamed_elsheref@yahoo.com',
            'password' => bcrypt('12345678'),
        ]);

        User::factory(10)->create();
    }
}
