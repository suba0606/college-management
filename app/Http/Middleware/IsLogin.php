<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Admin\Common;
use Illuminate\Support\Facades\DB;
use Auth;

class IsLogin {

    public function handle($request, Closure $next) {
     
        if (!Auth::check()) {
            
          return redirect(url('login'));
            
        } 
        return $next($request);
    }

}
