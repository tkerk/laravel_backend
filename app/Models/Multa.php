<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Multa extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'Mult';

    protected $fillable = [
        'cantidad',        
        'razon',            
        'emitido_a_las',       
        'estatus',          
        'id_huesped',      
        'notificado_en',      
        'visualizado',
    ];
}