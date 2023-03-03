<?php

namespace App\Http\Middleware;

use App\Services\Apista\ApistaApi;
use Closure;
use Illuminate\Support\Facades\Log;

class Apista
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        ApistaApi::tick();
        return $next($request);
    }

    /**
     * @param $request
     * @param $response
     * @
     */
    public function terminate($request, $response){
        ApistaApi::report();
        //检测是否是慢查询
        $start = request()->server('REQUEST_TIME'); // 脚本开始时间
        $cost_time = time() - $start;
        if ($cost_time>2){
            Log::warning("慢请求：{$cost_time}s,详细分析：".($GLOBALS['slow_log_detail']??'无'));
            $GLOBALS['slow_log_detail']= '';
        }
        return $response;
    }
}
