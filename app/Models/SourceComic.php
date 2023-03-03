<?php
/**
 * Created by PhpStorm.
 * User: night
 * Date: 2021/5/31
 * Time: 16:53
 */

namespace App\Models;


class SourceComic extends BaseModel
{
    protected $table = 'source_comic';

    public function getLabelAttribute($v)
    {
        return array_filter((array)json_decode($v, true));
    }

    public function getSourceInfoAttribute($v)
    {
        return array_filter((array)json_decode($v, true));
    }
}
