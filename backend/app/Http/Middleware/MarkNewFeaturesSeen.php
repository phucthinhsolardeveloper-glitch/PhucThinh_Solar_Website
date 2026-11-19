<?php

namespace App\Http\Middleware;

use App\Filament\Support\NewFeatures;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** After an Admin page is built, notes which "Mới" labels that page counts as seen for the account. */
class MarkNewFeaturesSeen
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        NewFeatures::markVisited($request, $request->user('admin'));

        return $response;
    }
}
