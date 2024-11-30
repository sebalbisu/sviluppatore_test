<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\Registration\EmailValidated;
use App\Notifications\Registration\EmailValidationPending;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class BackgroundServiceFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_1()
    {
        $this->assertTrue(true);
    }
}
