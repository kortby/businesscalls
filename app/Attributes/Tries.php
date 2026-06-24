<?php

namespace App\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Tries
{
    public function __construct(public int $count) {}
}
