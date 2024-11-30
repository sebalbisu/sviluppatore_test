<?php

namespace App\Services\BackgroundJob;

class ClassMethodNotFoundException extends \Exception
{
    public function __construct(string $className, string $method)
    {
        parent::__construct("$className::$method not found");
    }
}
