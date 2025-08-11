<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notificacion extends Model
{
    use HasFactory;

    protected $table = 'notificaciones';

    protected $fillable = [
        'id_huesped',
        'titulo',
        'mensaje',
        'tipo',
        'leida',
        'multa_id'
    ];

    protected $casts = [
        'leida' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_huesped', 'id_huesped');
    }

    
    public function multa()
    {
        return $this->belongsTo(Multa::class, 'multa_id');
    }
}
