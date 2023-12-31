<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\User;
use App\Order;
use Faker\Generator as Faker;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| This directory should contain each of the model factory definitions for
| your application. Factories provide a convenient way to generate new
| model instances for testing / seeding your application's database.
|
*/

$factory->define(User::class, function (Faker $faker) {
    return [
        'name' => $faker->name,
        'email' => $faker->unique()->safeEmail,
        'email_verified_at' => now(),
        'password' => Hash::make('123456'), // password
        'remember_token' => Str::random(10),
    ];
});

$factory->define(Order::class, function (Faker $faker) {
    $products = ['Mobile', 'Laptop', 'Watch', 'Mac Book'];
    static $invoice = 20;

    return [
        'product_name' => $products[rand(0, 3)],
        'currency' => 'BDT',
        'amount' => rand(1500, 2000),
        'invoice' => $invoice++,
        'status' => 'Pending',
    ];
});
