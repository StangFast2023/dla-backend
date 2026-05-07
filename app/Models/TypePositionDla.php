<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TypePositionDla extends Model
{
    protected   $table      = 'type_positions_dla';
    protected   $primaryKey = 'id';
    public      $timestamps = false;
    public function position()
    {
        return $this->belongsTo(PositionDla::class, 'id', 'id_level');
    }
}
