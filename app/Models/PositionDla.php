<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PositionDla extends Model
{
    protected   $table      = 'positions_dla';
    protected   $primaryKey = 'id';
    public      $timestamps = false;

    public function prefix()
    {
        return $this->belongsTo(PrefixsDla::class, 'id_prefix', 'id');
    }

    public function typePosition()
    {
        return $this->belongsTo(TypePositionDla::class, 'id_level', 'id');
    }
}
