<?php

namespace Tests\Unit\BackgroundService\Data;

use Illuminate\Support\Facades\Log;

class ClassA
{
    public function method1()
    {
        Log::debug(__CLASS__ . '::' . __FUNCTION__);
        return true;
    }

    public function method2()
    {
        Log::debug(__CLASS__ . '::' . __FUNCTION__);
        return 2;
    }

    public function method3(int $param1, string $param2)
    {
        Log::debug(__CLASS__ . '::' . __FUNCTION__);
    }

    public function method4Sleep(int $sleep)
    {
        Log::debug(__CLASS__ . '::' . __FUNCTION__);
        sleep($sleep);
        Log::debug(__CLASS__ . '::' . __FUNCTION__ . ' Slept for ' . $sleep . ' seconds');
    }

    public function method5ThrowException()
    {
        throw new \Exception('This is a test exception');
        Log::debug(__CLASS__ . '::' . __FUNCTION__);
    }

    public function method6SleepThrowException(int $sleep)
    {
        Log::debug(__CLASS__ . '::' . __FUNCTION__);
        sleep($sleep);
        Log::debug(__CLASS__ . '::' . __FUNCTION__ . ' Slept for ' . $sleep . ' seconds');
        throw new \Exception('This is a test exception');
    }
}
