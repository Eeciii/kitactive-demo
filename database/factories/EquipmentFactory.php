<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Equipment;
use Faker\Generator as Faker;

$factory->define(Equipment::class, function (Faker $faker) {
    return [
        'title' => $faker->word(),
        'price' => $faker->randomNumber(5, true),
        'serial_number' => $faker->bothify('?-### ###'),
        'inventory_number' => $faker->bothify('?-### ###'),
        'user_id' => $faker->numberBetween(1, 5),
        'warehouse_id' => $faker->boolean() ? $faker->numberBetween(1, 10) : null,
        'created_at' => $faker->dateTimeBetween('-20 days', now()),
        'updated_at' => $faker->dateTimeBetween('-20 days', now()),
    ];
});
