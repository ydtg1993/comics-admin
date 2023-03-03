<?php
/**
 * Created by PhpStorm.
 * User: night
 * Date: 2021/5/31
 * Time: 16:53
 */

namespace App\Models;


class SourceChapter extends BaseModel
{
    protected $table = 'source_chapter';

    public function image()
    {
        return $this->hasOne(SourceImage::class,'chapter_id','id');
    }
}
