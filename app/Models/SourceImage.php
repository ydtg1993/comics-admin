<?php
/**
 * Created by PhpStorm.
 * User: night
 * Date: 2021/5/31
 * Time: 16:53
 */

namespace App\Models;


class SourceImage extends BaseModel
{
    protected $table = 'source_image';

    public function getImagesAttribute($v)
    {
        return array_filter((array)json_decode($v, true));
    }

    public function getSourceDataAttribute($v)
    {
        return array_filter((array)json_decode($v, true));
    }
}
