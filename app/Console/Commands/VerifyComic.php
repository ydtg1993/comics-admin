<?php

namespace App\Console\Commands;

use App\Models\Comic;
use App\Models\SourceComic;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class VerifyComic extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'verify.comic';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '自动审核漫画';

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
        $page = 0;
        $limit = 15;
        if(time() > strtotime(date('Y-m-d 22:00:00'))){
            SourceComic::where('status',2)
                ->where('created_at','<',date('Y-m-d',strtotime('3 days ago')))
                ->update(['status'=>0]);
        }

        $sources = SourceComic::where('status',0)->offset($page * $limit)->limit($limit)->orderBy('created_at','ASC')->get();
        foreach ($sources as $sourceComic){
            if(Comic::where('title',trim($sourceComic->title))->exists()){
                SourceComic::where('id',$sourceComic->id)->update(['status'=>3]);
                continue;
            }
            /*章节检查*/
            $id = $sourceComic->id;
            $chapters = DB::table(DB::raw("(SELECT source_chapter.*, source_image.state
            FROM source_chapter LEFT JOIN source_image ON source_image.chapter_id = source_chapter.id
            WHERE source_chapter.comic_id = {$id}
            ) as temp"))
                ->where('temp.state','!=',1)
                ->orWhereNull('temp.state')
                ->get()->toArray();
            if(!empty($chapters)){
                foreach ($chapters as $chapter){
                    $chapter = (array)$chapter;
                    if($chapter['source'] == 1){
                        $redis = Redis::connection('kk');
                        $redis->rpush("source:comic:retry:chapter",$chapter['id']);
                    }else if($chapter['source'] == 2){
                        $redis = Redis::connection('tx');
                        $redis->rpush("source:comic:retry:chapter",$chapter['id']);
                    }
                }
                SourceComic::where('id',$sourceComic['id'])->update(['status'=>2]);
                continue;
            }

            Comic::insert([
                "source_comic_id" => $sourceComic['id'],
                "cover" => $sourceComic['cover'],
                "title" => $sourceComic['title'],
                "author" => $sourceComic['author'],
                "label" => json_encode($sourceComic['label']),
                "category" => $sourceComic['category'],
                "region" => $sourceComic['region'],
                "chapter_count" => 0,
                "like" => $sourceComic['like'],
                "popularity" => $sourceComic['popularity'],
                "is_finish" => $sourceComic['is_finish'],
                "description" => trim($sourceComic['description'])
            ]);
            SourceComic::where('id',$sourceComic['id'])->update(['status'=>1]);
        }

    }

}
