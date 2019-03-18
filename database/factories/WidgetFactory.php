<?php

use App\Models\Widget;
use Carbon\Carbon;
use Faker\Generator as Faker;

$factory->define(Widget::class, function (Faker $faker) {

    return [
        'key' => Str::slug($faker->sentence),
        'value' => $faker->sentence,
        'meta' => json_encode([
            "fname" => $faker->firstName,
            "lname" => $faker->lastName,
            "email" => $faker->email,
            "number_value" => $faker->numberBetween(1, 1000),
            "created_at" => Carbon::now()->toDateTimeString()
        ]),
    ];
});
