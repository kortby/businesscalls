<?php

namespace App\Concerns;

use App\Attributes\Casts;
use ReflectionClass;

trait HasAttributeCasts
{
    /**
     * The cached attribute casts per class.
     *
     * @var array<string, array<string, string>>
     */
    protected static array $attributeCastsCache = [];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        $class = static::class;

        if (isset(static::$attributeCastsCache[$class])) {
            return static::$attributeCastsCache[$class];
        }

        $reflection = new ReflectionClass($class);
        $attributes = $reflection->getAttributes(Casts::class);

        if (count($attributes) > 0) {
            return static::$attributeCastsCache[$class] = $attributes[0]->newInstance()->casts;
        }

        return static::$attributeCastsCache[$class] = parent::casts();
    }
}
