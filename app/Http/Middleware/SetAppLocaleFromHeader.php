<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class SetAppLocaleFromHeader
{
    public function handle(Request $request, Closure $next)
    {
        $locale = $request->getPreferredLanguage(['fr', 'en', 'ar']);
        App::setLocale($locale ?? config('app.locale'));

        return $next($request);
    }
}
