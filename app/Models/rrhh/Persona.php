<?php

namespace App\Models\rrhh;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Persona extends Model
{
    use HasFactory;

    protected $table = 'rrhh.persona';

    protected $fillable = [
        "id_pais_nacimiento",
        "id_canton_nacimiento",
        "identificacion",
        "nombres",
        "apellidos",
        "fecha_nacimiento",
        "email",
        "discapacidad",
        "calle_principal",
        "calle_secundaria",
        "numero_casa",
        "referencia_direccion",
        "curriculum",
        "estado",
        "id_canton_reside",
        "porcentaje_discapacidad",
        "tipo_identificacion",
        "ley_proteccion_datos",
    ];

    public function setCreatedAtAttribute($value)
    {
        date_default_timezone_set("America/Guayaquil");
        $this->attributes["created_at"] = Carbon::now();
    }

    public function setUpdatedAtAttribute($value)
    {
        date_default_timezone_set("America/Guayaquil");
        $this->attributes["updated_at"] = Carbon::now();
    }

    public function telefonos()
    {
        return $this->hasMany(Telefono::class, 'id_persona', 'id');
    }

    public function referencias()
    {
        return $this->hasMany(Referencia::class, 'id_persona', 'id');
    }

    public function experiencias_laborales()
    {
        return $this->hasMany(ExperienciaLaboral::class, 'id_persona', 'id');
    }

}