<?php

namespace App\Http\Middleware;

use Illuminate\Support\Facades\DB;
use App\Utils\TelegramUtils;
use Closure;

class CheckLastUpdate
{

    public function __construct()
    {
        $this->utils = new TelegramUtils();
    }
    public function handle($request, Closure $next)
    {
        if(!$this->utils->checkSalt($request->salt)) {
            return response()->json(['status' => 'error', 'message' => 'wrong salt!']);
        }
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
        return response()->json(['status' => 'error', 'message' => 'Dublicate']);
    }
}