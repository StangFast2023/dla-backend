<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrefixsDla extends Model
{
    protected   $table      = 'prefixes_dla';
    protected   $primaryKey = 'id';
    public      $timestamps = false;
    public function position()
    {
        return $this->belongsTo(PrefixsDla::class, 'id_prefix', 'id');
    }
}
