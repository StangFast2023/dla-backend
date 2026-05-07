<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CallingDla extends Model
{
    protected   $table      = 'calling_dla';
    protected   $primaryKey = 'id';
    public      $timestamps = false;


    public function province()
    {
        return $this->belongsTo(ProvincesDla::class, 'id_main_province', 'id_main_province');
    }

    public function position()
    {
        return $this->belongsTo(PositionDla::class, 'id_position', 'id_position');
    }
}
