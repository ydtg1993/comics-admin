<?php

namespace App\Console\Commands;


use ApolloSdk\Config\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class RefreshConfiguration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:RefreshConfiguration';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '刷新配置';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        echo '开始执行配置刷新！'.PHP_EOL;
        $configTemp = self::ConfigSet();
        if ($configTemp !== false)
        {
            $configTemp['ENV_TIME']=time();
            self::modifyEnv($configTemp);
            echo '配置刷新执行完成！'.PHP_EOL;
            return;
        }
        echo '配置刷新执行失败！'.PHP_EOL;
    }

    /**
     * 配置处理
     * @return bool|mixed
     * @throws \Exception
     */
    private static function ConfigSet()
    {
        $project_name = config('apollo_config.project_name','');
        $configServer = config('apollo_config.server_url','');
        $config_server_url = config('apollo_config.server_url','');
        $secret = config('apollo_config.secret','');
        $client = new Client([
            'config_server_url' => $config_server_url,//Apollo配置服务的地址，必须传入这个参数
            'secret' => $secret,//密钥，如果配置了密钥可以通过
        ]);

        $appId = config('apollo_config.appid','GoldCoinMall');
        $namespaceName = config('apollo_config.namespaces','test');
        $useCacheApi = false;//设置为false可以通过不带缓存的Http接口从Apollo读取配置
        $config = $client->getConfig($appId, $namespaceName, $useCacheApi);
        $httpInfo  = $client->getHttpInfo();
        if($config === false || (intval($httpInfo['response_code']??0) != 200)) {//获取配置失败
            Log::info('配置获取失败：'.json_encode($client->getErrorInfo()).' HttpInfo:'.json_encode($client->getHttpInfo()));
            //发送Tg信息
            $str = '';
            $str .= '报警项目 ：' . $project_name . PHP_EOL;
            $str .= '报警内容 ：pull config of namespace[' . $namespaceName . '] error:'  . PHP_EOL;
            $str .= '机器 ：' . $configServer  . PHP_EOL;
            $str .= '错误详情 ：' . json_encode($client->getErrorInfo()) . PHP_EOL  ;
            $str .= '错误详情HTTP ：' . json_encode($client->getHttpInfo()) . PHP_EOL  ;
            $tg = new Tg();
            $tg->sendTgMsgNew($str);
            //print_r($client->getErrorInfo());//当产生curl错误时此处有值
            //print_r($client->getHttpInfo());//可能不是产生curl错误，而是阿波罗接口返回的http状态码不是200或304
            return false;
        }

        return $config;
    }

    /**
     * 修改env 文件
     * @param array $data
     */
    public  static function modifyEnv(array $data)
    {
        $envPath = base_path() . DIRECTORY_SEPARATOR . '.env';
        $contentArray = collect(file($envPath, FILE_IGNORE_NEW_LINES));

        $tempItem = [];
        $contentArray = $contentArray->transform(function ($item) use ($data,&$tempItem){
            foreach ($data as $key => $value){
                if(str_contains($item, $key)){
                    $tempItem[$key] = $value;
                    return $key . '=' . $value;
                }
            }

            return $item;
        });
        $contentNewArray =   $contentArray->toArray();
        foreach ($data as  $key => $value)
        {
            if (isset($tempItem[$key]))
            {
                continue;
            }
            $contentNewArray[] = $key . '=' . $value;
        }
        $content = implode("\n", $contentNewArray);
        Storage::disk('env')->put('.env_a',$content);
    }
}
