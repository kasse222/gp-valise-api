<?php

use Illuminate\Support\Facades\Auth;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Unit', 'Feature');

function loginAs(\App\Models\User $user): void
{
    Auth::login($user);
}
