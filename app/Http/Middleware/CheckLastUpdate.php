<?php

namespace App\Http\Middleware;

use Closure;

class CheckLastUpdate
{
    public function handle($request, Closure $next)
    {
        $params = $request->all();
        $cur = DB::table('general')->where('label', 'last-update-id')->first();
        if(empty($cur) || (int)$cur->value < (int)$params['update_id']) {
            return $next($request);
        }
    }
}