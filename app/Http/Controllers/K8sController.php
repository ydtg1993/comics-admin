<?php
/**
 * Created by PhpStorm.
 * User: night
 * Date: 2022/9/26
 * Time: 16:31
 */

namespace App\Controller\Api;

use App\Http\Controllers\Controller;
use App\Models\Bank;
use Illuminate\Support\Facades\Redis;

class K8sController extends Controller
{
    public function healthy(){
        return response()->json(['status'=>'ok'],200);
    }

    public function ready()
    {
        if (!Redis::set('ready:test',1,10)) {
            return response()->json(['status'=>'redisError'],500);
        }
        $redis = Redis::get('ready:test');
        if (intval($redis) != 1) {
            return response()->json(['status'=>'redisError'],500);
        }

        try{
            Bank::where('is_del',2)->count();
        }catch (\Exception $e) {
            return response()->json(['status'=>'mysqlError'],500);
        }

        return response()->json(['status'=>'ok'],200);
    }
}
