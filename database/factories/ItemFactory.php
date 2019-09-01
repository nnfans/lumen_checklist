<?php
use Illuminate\Support\Str;
use Faker\Generator as Faker;

$factory->define(App\Item::class, function (Faker $faker) {
    return [
        'description' => $faker->sentence(6),
        'due' => $faker->dateTime(),
        'urgency' => $faker->randomDigitNotNull(),
        'assignee_id' => 1,
        'task_id' => $faker->randomNumber(3),
        'created_by' => 1,
        'updated_by' => 1
    ];
});