<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * This is an API-only app — never redirect to a login page.
     * Returning null prevents the framework from calling route('login'),
     * which would throw a RouteNotFoundException since no web login route exists.
     */
    protected function redirectTo(Request $request): ?string
    {
        return null;
    }
}
