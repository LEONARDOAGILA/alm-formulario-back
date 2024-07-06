<?php

namespace App\Models\rrhh;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Referencia extends Model
{
    use HasFactory;

    protected $table = 'rrhh.referencia';

    protected $fillable = [
        "id_persona",
        "id_parentesco",
        "colaborador",
        "nombres",
        "apellidos",
        "telefono_1",
        "telefono_2",
        "estado",
        "email",
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

    public function parentesco()
    {
        return $this->belongsTo(Parentesco::class, 'id_parentesco');
    }

}