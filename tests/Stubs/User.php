<?php

declare(strict_types=1);

namespace WayOfDev\Cycle\Tests\Stubs;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * @method static UserFactory factory($count = null, $state = [])
 */
#[Entity(
    role: 'users',
    repository: UserRepository::class,
    table: 'users',
    database: 'default',
)]
final class User
{
    use HasFactory;

    #[Column(type: 'primary')]
    private int $id;

    #[Column(type: 'string')]
    private string $password;

    #[Column(type: 'string')]
    private string $rememberToken = '';

    #[Column(type: 'string')]
    private string $name;

    public static function migrate(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('password');
            $table->string('remember_token');
        });
    }

    public static function resolveFactoryName(): string
    {
        return UserFactory::class;
    }

    protected static function newFactory(): UserFactory
    {
        return new UserFactory();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(mixed $id): void
    {
        $this->id = $id;
    }

    public function setPassword(mixed $password): void
    {
        $this->password = $password;
    }

    public function setRememberToken(string $rememberToken): void
    {
        $this->rememberToken = $rememberToken;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }
}
