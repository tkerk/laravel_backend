<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sesion extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'token_id',
        'token',
    ];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id', 'id_huesped');
    }
}
