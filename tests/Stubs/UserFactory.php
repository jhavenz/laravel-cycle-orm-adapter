<?php

declare(strict_types=1);

namespace WayOfDev\Cycle\Tests\Stubs;


use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Laminas\Hydrator\ReflectionHydrator;

final class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'password' => 'password',
            'rememberToken' => Str::random(),
        ];
    }

    public function newModel(array $attributes = [])
    {
        $user = new User();
        $hydrator = new ReflectionHydrator();
        $hydrator->hydrate($attributes, $user);

        return $user;
    }
}
