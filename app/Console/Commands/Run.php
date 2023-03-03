<?php

namespace App\Console\Commands;


use App\Models\SourceComic;
use App\Models\SourceImage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

class Run extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'run';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '';

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
        $redis = Redis::connection('kk');
        while (true){
            $comic = $redis->lpop("source:comic:task");
            if(!$comic)break;
            if(SourceComic::where('id',$comic)->where('chapter_count','>',0)->exists()){
                continue;
            }
            $redis->rpush("source:comic:task",$comic);
        }
    }
}
