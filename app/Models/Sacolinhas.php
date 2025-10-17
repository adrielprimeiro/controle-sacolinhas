<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sacolinhas extends Model
{
    use HasFactory;

    protected $table = 'sacolinhas';

    protected $fillable = [
        'user_id',
        'item_id',
        'live_id',
        'add_at',
        'tray',
        'status',
        'obs'
    ];

    protected $casts = [
        'add_at' => 'datetime'
    ];

    // Relacionamentos
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function live()
    {
        return $this->belongsTo(Live::class);
    }

    // Accessor para dados do item da tabela items
    public function getItemDataAttribute()
    {
        $item = \DB::table('items')->where('id', $this->item_id)->first();
        
        if (!$item) {
            return null;
        }

        return [
            'id' => $item->id,
            'name' => $item->nome_do_produto,
            'price' => (float) $item->preco,
            'formatted_price' => 'R\$ ' . number_format((float) $item->preco, 2, ',', '.'),
            'sku' => $item->codigo,
            'description' => $item->descricao,
            'brand' => $item->marca,
            'color' => $item->cor,
            'size' => $item->tamanho
        ];
    }

    // Scope para status específico
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    // Scope para live específica
    public function scopeByLive($query, $liveId)
    {
        return $query->where('live_id', $liveId);
    }
}