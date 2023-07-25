<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\User;

use Illuminate\Support\Facades\DB;

use Auth;

class LoginCheck {

    public function handle($request, Closure $next) {

     
        if (Auth::check()) {

            $sessionId = session()->getId();

            if($sessionId != Auth::user()->last_session){

                Auth::logout();
                
                $request->session()->invalidate();
                $request->session()->regenerateToken();
        
                return redirect(url('login'));
             }
  
        } 
        return $next($request);
    }

}
