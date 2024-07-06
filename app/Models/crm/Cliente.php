<?php

namespace App\Models\crm;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    use HasFactory;

    protected $table = 'crm.cliente';

    protected $fillable = [
        "ent_id",
        "identificacion",
        "nombres",
        "apellidos",
        "pai_nombre",
        "ctn_nombre",
        "prq_nombre",
        "prv_nombre",
        "nivel_educacion",
        "cactividad_economica",
        "numero_dependientes",
        "nombre_empresa",
        "tipo_empresa",
        "direccion",
        "numero_casa",
        "calle_secundaria",
        "referencias_direccion",
        "trabajo_direccion",
        "fecha_ingreso",
        "ingresos_totales",
        "gastos_totales",
        "activos_totales",
        "pasivos_totales",
        "created_at",
        "updated_at",
        "cedula_conyuge",
        "nombres_conyuge",
        "apellidos_conyuge",
        "email_conyuge",
        "sexo_conyuge",
        "fecha_nacimiento_conyuge",
        "telefono_conyuge_1",
        "telefono_conyuge_2",
        "telefono_conyuge_3",
        "observacion_conyuge",
        "fechanacimiento",
        "tipo_identificacion",
        "email",
        "calle_principal",
        "nombre_comercial",
    ];
}