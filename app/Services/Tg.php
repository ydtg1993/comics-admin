<?php
/**
 * Created by PhpStorm.
 * User: Night
 * Date: 2022/11/10
 * Time: 16:12
 */

namespace App\Services\Logic;


use Illuminate\Support\Facades\Log;

class Tg
{

    public function Init()
    {

    }

    /**
     * 发送tg消息
     */
    function sendTgMsgNew($sText = '') {
        try{
            $sUrl = config('apollo_config.tg_host');
            $sChatId = config('apollo_config.tg_chat_id');
            if(empty($sUrl)){
                return ' tg host is empty ';
            }
            foreach ($sChatId as $row){

                $dParams = [
                    'chat_id' => $row,
                    'text'    => $sText
                ];

                $dConfig = [];
//                $dOptions = [
//                    'headers' => [
//                        'Accept-Encoding' => 'gzip'  // 使用gzip压缩让数据传输更快
//                    ]
//                ];

                $dOptions[] = 'Accept-Encoding: gzip, deflate'; //使用gzip压缩让数据传输更快

                $response = $this->httpGet($sUrl, $dParams, $dOptions);

                $sMessage
                    = '[' . __METHOD__ . ']'
                    . ', url=' . $sUrl
                    . ', params=' . json_encode($dParams,256)
                    . ', config=' . json_encode($dConfig,256)
                    . ', options=' . json_encode($dOptions,256)
                    . ', response=' . json_encode($response,256)
                ;
                Log::info($sMessage);
                //$this->saveLog($sMessage);
            }

        } catch (\Exception $oException) {
            $sMsg = $oException->getMessage() ?? 'it fails to do Ad test API';
            $sMessage
                = $sMsg .'[' . __METHOD__ . ']'
                . ', url=' . $sUrl
                . ', params=' . json_encode($dParams??[],256)
                . ', config=' . json_encode($dConfig??[],256)
                . ', options=' . json_encode($dOptions??[],256)
                . ', response=' . json_encode($response??[],256)
            ;

            Log::info($sMessage);
            //$this->saveLog($sMessage);
        }

        return $sMessage;
    }

    function httpGet($url,$data=[],$header = [])
    {

        $url = $url . '?' . http_build_query ($data);

        //初始化
        $ch = curl_init();

        //设置选项，包括URL
        curl_setopt($ch,CURLOPT_URL,(string)$url);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // https请求 不验证证书和hosts
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

        $output = curl_exec($ch);
        curl_close($ch);
        var_dump($url);
        var_dump($data);
        var_dump($output);
        return $output;
    }

}
