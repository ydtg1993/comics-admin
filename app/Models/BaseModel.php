<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class BaseModel extends Model
{
    public function getCreatedAtAttribute($v)
    {
        return date('Y-m-d H:i:s', strtotime($v));
    }

    public function getUpdatedAtAttribute($v)
    {
        return date('Y-m-d H:i:s', strtotime($v));
    }
}
