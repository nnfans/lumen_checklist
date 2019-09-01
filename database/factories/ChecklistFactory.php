<?php
use Illuminate\Support\Str;
use Faker\Generator as Faker;

$factory->define(App\Checklist::class, function (Faker $faker) {
    return [
        'object_domain' => $faker->sentence(3, true),
        'object_id' => $faker->randomDigitNotNull(),
        'description' => $faker->sentence(6),
        'urgency' => $faker->randomDigitNotNull(),
        'due' => $faker->dateTime(),
        'task_id' => $faker->randomNumber(3),
        'created_by' => 1,
        'updated_by' => 1
    ];
});