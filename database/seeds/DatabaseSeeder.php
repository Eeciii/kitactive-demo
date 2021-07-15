<?php

use App\Equipment;
use App\Warehouse;
use Illuminate\Database\Seeder;
use \App\User;
use \Illuminate\Support\Facades\Hash;
use \Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        User::create([
            'login' => 'test',
            'password' => Hash::make('test'),
            'isAdmin' => true,
            'email' => 'test@test.ru',
            'api_token' => 'a94a8fe5ccb19ba61c4c0873d391e987982fbbd3'
        ]);

        factory(User::class, 4)->create();
        factory(Warehouse::class, 10)->create();
        factory(Equipment::class, 50)->create();



    }
}
