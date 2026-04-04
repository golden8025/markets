<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Traits\ApiResponses;

class CheckRole
{
    use ApiResponses;
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        if(!$request->user() || $request->user()->role !== $role){
            // return $this->ok('Sizda bunga xuquq yoq!');
            return response()->json([
                'message' => 'Sizda bunga xuquq yoq!'
            ], 403);
        }
        return $next($request);
    }
}
