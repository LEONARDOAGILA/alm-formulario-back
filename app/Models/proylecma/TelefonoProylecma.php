<?php

namespace App\Models\proylecma;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TelefonoProylecma extends Model
{
    protected $connection = "bdproylecma";
    protected $table = 'public.telefono';
    protected $primaryKey = 'tel_id';
    public $timestamps = false;
    protected $fillable = [
        "tte_id",
        "tel_numero",
        "locked",
        "tel_activo",
    ];
}
