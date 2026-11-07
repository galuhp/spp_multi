<?php

namespace App\Http\Middleware;

use App\Support\LisensiPaket;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureProTier
{
    /**
     * Ensure the current installation has the Pro tier (or a specific Pro feature) enabled.
     *
     * Usage in routes: `->middleware('pro')` or `->middleware('pro:nama_fitur')`
     */
    public function handle(Request $request, Closure $next, ?string $feature = null): Response
    {
        LisensiPaket::abortUnlessPro($feature);

        return $next($request);
    }
}
