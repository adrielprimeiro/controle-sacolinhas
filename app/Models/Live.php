<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class Live extends Model
{
    use HasFactory;

    protected $table = 'lives';
    
    protected $fillable = [
        'data',
        'tipo_live',
        'plataformas'
    ];

    protected $casts = [
        'data' => 'date',
    ];

    // Accessor para tipo_live_formatado
    public function getTipoLiveFormatadoAttribute()
    {
        $tipos = [
            'loja-aberta' => 'Loja Aberta',
            'leilao' => 'Leilão',
            'precinho' => 'Precinho'
        ];

        return $tipos[$this->tipo_live] ?? $this->tipo_live;
    }

    // Accessor para plataformas_array
    public function getPlataformasArrayAttribute()
    {
        if (empty($this->plataformas)) {
            return [];
        }

        // Se for string separada por vírgula
        if (is_string($this->plataformas)) {
            return explode(',', $this->plataformas);
        }

        return [];
    }

    // Mutator para plataformas
    public function setPlataformasAttribute($value)
    {
        if (is_array($value)) {
            $this->attributes['plataformas'] = implode(',', $value);
        } else {
            $this->attributes['plataformas'] = $value;
        }
    }

    // Scopes
    public function scopeToday($query)
    {
        return $query->whereDate('data', Carbon::today());
    }
	
	// Relacionamento com sacolinhas
    public function sacolinhas()
    {
        return $this->hasMany(Sacolinhas::class, 'live_id');
    }

    // Accessor para nome (usando tipo_live)
    public function getNameAttribute()
    {
        return $this->tipo_live;
    }

    // Accessor para data formatada
    public function getFormattedDateAttribute()
    {
        return $this->data->format('d/m/Y');
    }
}
