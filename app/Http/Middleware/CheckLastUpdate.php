<?php

namespace App\Http\Middleware;

use Illuminate\Support\Facades\DB;
use Closure;

class CheckLastUpdate
{
    public function handle($request, Closure $next)
    {
        $params = $request->all();
        $cur = DB::table('general')->where('label', 'last-update-id')->first();
        $current = (int)@$cur->value;
        $new = (int)$params['update_id'];
        print_r($current);
        print_r('--');
        print_r($new);
        if(empty($cur) || $current < $new) {
            return $next($request);
        }
        return response()->json(['status' => 'skip']);
    }
}