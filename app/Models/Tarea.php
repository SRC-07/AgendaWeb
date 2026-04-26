<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tarea extends Model
{
    use HasFactory;

    protected $fillable = [
        'titulo',
        'descripcion',
        'estado',
        'prioridad',
        'fecha_vencimiento',
        'user_id',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class);
    }
}