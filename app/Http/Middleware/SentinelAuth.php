<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Cartalyst\Sentinel\Laravel\Facades\Sentinel;
use Cartalyst\Sentinel\Hashing\BcryptHasher;


class SentinelAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @param array $permission
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle($request, Closure $next, $permission = [])
    {
        Sentinel::setHasher(new BcryptHasher());
        $user = Sentinel::check();

        if (!$user) {
            return redirect()->guest('login');
        }

        // This Is Admin User?
        $roles = Sentinel::getRoles()->pluck('slug')->all();

        if (is_array($roles)) {
            if (in_array('admin', $roles,)) {
                return $next($request);
            }
        }

        // Check Access When User Is Not Admin
        if ($user->hasAccess($permission)) {
            return $next($request);
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response(trans('backpack::base.unauthorized'), 401);
        }

        return abort(403, 'Unauthorized action.');
    }
}
