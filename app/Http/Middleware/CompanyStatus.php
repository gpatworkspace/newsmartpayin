<?php

namespace App\Http\Middleware;

use Closure;

class CompanyStatus
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        if($request->user() && $request->user()->company && !$request->user()->company->status && $request->user()->role->slug !="admin"){
            abort(503);
        }

        if($request->user() && $request->user()->role->slug !="admin" && ($request->user()->kyc == "pending" || $request->user()->kyc == "submitted")){
            return redirect(route('home'));
        }

        if($request->user() && $request->user()->role->slug !="admin" && $request->user()->kyc == "rejected"){
            return redirect(route('profile'));
        }

        if($request->user() && $request->user()->role->slug !="admin" && $request->user()->status == "blocked"){
            return redirect(route('logout'));
        }

        return $next($request);
    }
}
