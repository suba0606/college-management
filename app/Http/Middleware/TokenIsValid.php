<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\User;

class TokenIsValid
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if ($request->token == '') {
            return response()->json(['message' => "invalid token"], 400);
        }

        $tokenvalid =  User::where('api_token', $request->input('token'))->count();

        if ($tokenvalid != 1) {
            return response()->json(['message' => "invalid token"], 400);
        }

        return $next($request);
    }
}
