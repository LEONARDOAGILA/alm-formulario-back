<?php

namespace App\Models\rrhh;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Telefono extends Model
{
    use HasFactory;

    protected $table = 'rrhh.telefono';

    protected $fillable = [
        "id_persona",
        "id_tipo_telefono",
        "numero",
        "estado",
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

    public function tipo_telefono()
    {
        return $this->hasMany(TipoTelefono::class, 'id', 'id_tipo_telefono');
    }

}