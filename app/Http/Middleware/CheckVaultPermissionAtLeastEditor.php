<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CheckVaultPermissionAtLeastEditor
{
    /**
     * Handle an incoming request.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $requestedVaultId = $request->route()->parameter('vault');

        $exists = DB::table('user_vault')->where([
            ['vault_id', '=', $requestedVaultId],
            ['user_id', '=', Auth::id()],
            ['permission', '<=', 200],
        ])->exists();

        abort_if(! $exists, 401);

        return $next($request);
    }
}