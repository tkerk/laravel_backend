<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Multa extends Model
{
    
    protected $table = 'multas';

    protected $fillable = [
        'cantidad',        
        'razon',            
        'emitido_a_las',       
        'estatus',          
        'id_huesped',      
        'notificado_en',      
        'visualizado',
        'estado', 
    ];

    
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_huesped', 'id_huesped');
    }
}