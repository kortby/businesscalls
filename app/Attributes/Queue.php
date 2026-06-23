<?php

namespace App\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Queue
{
    public function __construct(public string $name) {}
}
