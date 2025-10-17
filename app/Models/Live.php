<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Live extends Model
{
    use HasFactory;

    protected $table = 'live'; // Nome da tabela

    protected $fillable = [
        'tipo_live',
        'data',
        'plataformas'
    ];

    protected $casts = [
        'data' => 'date'
    ];

    // Accessor para converter string de plataformas em array
    public function getPlataformasArrayAttribute()
    {
        return $this->plataformas ? explode(',', $this->plataformas) : [];
    }

    // Mutator para converter array em string
    public function setPlataformasAttribute($value)
    {
        if (is_array($value)) {
            $this->attributes['plataformas'] = implode(',', $value);
        } else {
            $this->attributes['plataformas'] = $value;
        }
    }

    // Accessor para formatar tipo de live
    public function getTipoLiveFormatadoAttribute()
    {
        return ucwords(str_replace('-', ' ', $this->tipo_live));
    }
}