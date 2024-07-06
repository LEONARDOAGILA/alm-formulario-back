<?php

namespace App\Models\proylecma;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class EmpresaProylecma extends Model
{
    use HasFactory;

    protected $connection = "bdproylecma";

    protected $table = 'public.empresa';

    protected $fillable = [
        'emp_id',
        'emp_ruc',
        'emp_nombre',
        'emp_nombre_comercial',
        'emp_calle_principal',
        'emp_numeracion',
        'emp_calle_secundaria',
        'emp_nombre_edificio',
        'emp_piso',
        'emp_oficina',
        'emp_indicaciones_adicionales',
        'emp_ubicacion_gps',
        'emp_telefono1',
        'emp_telefono2',
        'emp_telefono3',
        'emp_ciudad',
        'emp_email',
        'emp_replegal',
        'emp_rep_calle_principal',
        'emp_rep_numeracion',
        'emp_rep_calle_secundaria',
        'emp_rep_nombre_edificio',
        'emp_rep_piso',
        'emp_rep_oficina',
        'emp_rep_indicaciones_adicionales',
        'emp_reptelefono',
        'emp_repruc',
        'emp_contruc',
        'emp_cuenta',
        'locked',
        'emp_imagen1',
        'emp_imagen2',
        'emp_nomproyecto',
        'emp_codigodinardap',
        'emp_fecha',
        'emp_mensaje_bloqueo',
        'emp_mensaje_fecha_bloqueo',
        'emp_id_soporte',
        'emp_bloqueo',
        'emp_fecha_bloqueo',
        'emp_dias_aviso',
        'emp_mensaje',
    ];

    // public function setCreatedAtAttribute($value)
    // {
    //     date_default_timezone_set("America/Guayaquil");
    //     $this->attributes["created_at"] = Carbon::now();
    // }
    // public function setUpdatedAtAttribute($value)
    // {
    //     date_default_timezone_set("America/Guayaquil");
    //     $this->attributes["updated_at"] = Carbon::now();
    // }

}