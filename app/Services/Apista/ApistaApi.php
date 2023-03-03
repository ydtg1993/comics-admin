<?php


namespace App\Services\Apista;


use Illuminate\Support\Facades\Route;

class ApistaApi {
    public static function tick($interface=''){
        if(!config('apista.open')){
            return true;
        }
        $module = config('apista.module');
        $interface = $interface ?: Route::current()->uri();
        $interface = str_replace('/','_',$interface);
        StatisticClient::tick($module, $interface);
        return true;
    }

    public static function report($msg='',$success=1,$interface=""){
        $config = config('apista');
        if(!$config['open']){
            return true;
        }
        $module = $config['module'];
        $interface = $interface ?: Route::current()->uri();
        $interface = str_replace('/','_',$interface);
        $report_address = "udp://{$config['host']}:{$config['port']}";
        StatisticClient::report($module, $interface, $success, $code=200, $msg, $report_address);
        return true;
    }
}
