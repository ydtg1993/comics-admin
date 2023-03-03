<?php

namespace App\Console\Commands;

use App\Models\Chapter;
use App\Models\Comic;
use App\Models\SourceChapter;
use App\Models\SourceImage;
use Illuminate\Console\Command;

class VerifyChapter extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'verify.chapter';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '自动审核章节';

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
        $limit = 500;
        while (true) {
            $comics = Comic::join('source_comic', 'source_comic.id', '=', 'comic.source_comic_id')
                ->where('source_comic.last_chapter_update_at', '>', date('Y-m-d', strtotime("1 month ago")))
                ->whereRaw('comic.chapter_count != source_comic.chapter_count')
                ->offset($page * $limit)
                ->limit($limit)
                ->select('source_comic.id as source_comic_id', 'comic.id as comic_id')
                ->get()
                ->toArray();
            if (empty($comics)) break;
            foreach ($comics as $comic) {
                $chapters = SourceChapter::where('comic_id', $comic['source_comic_id'])->where('status', 0)->get()->toArray();
                foreach ($chapters as $chapter) {
                    $image = SourceImage::where('chapter_id', $chapter['id'])->where('state', 1)->select('id', 'images')->first();
                    if ($image) {
                        Chapter::insert([
                            'comic_id' => $comic['comic_id'],
                            'source_comic_id' => $comic['source_comic_id'],
                            'source_chapter_id' => $chapter['id'],
                            'source_image_id' => $image->id,
                            'title' => $chapter['title'],
                            'sort' => $chapter['sort'],
                            'is_free' => $chapter['is_free'],
                            'images' => json_encode($image->images)
                        ]);
                        SourceChapter::where('id',$chapter['id'])->update(['status'=>1]);
                    }
                }
                Comic::where('id',$comic['comic_id'])
                    ->update(['chapter_count'=>Chapter::where('comic_id',$comic['comic_id'])->count()]);
            }
            $page++;
        }

    }

}
