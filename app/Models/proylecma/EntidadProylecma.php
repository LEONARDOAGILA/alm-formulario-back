<?php

namespace App\Models\proylecma;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EntidadProylecma extends Model
{
    protected $connection = "bdproylecma";
    protected $table = 'public.entidad';
    protected $primaryKey = 'ent_id';
    public $timestamps = false;
    protected $fillable = [
        "ent_identificacion",
        "ent_nombres",
        "ent_apellidos",
        "ent_contacto",
        "tit_id",
        "ent_fechanacimiento",
        "ent_direccion_principal",
        "locked",
        "ent_foto",
        "ent_tipo_identificacion",
        "alm_id",
        "ent_email",
        "ent_telefono_principal",
        "tii_id",

    ];
}
