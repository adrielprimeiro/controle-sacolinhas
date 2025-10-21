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
        'quantity',
        'price',
        'add_at',
        'tray',
        'status',
        'obs'
    ];

    protected $casts = [
        'add_at' => 'datetime',
        'quantity' => 'integer',
        'price' => 'decimal:2'
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

    // Accessor para nome do item
    public function getItemNameAttribute()
    {
        return $this->item_data['name'] ?? 'Item não encontrado';
    }

    // Accessor para SKU do item
    public function getItemSkuAttribute()
    {
        return $this->item_data['sku'] ?? '';
    }

    // Accessor para marca do item
    public function getItemBrandAttribute()
    {
        return $this->item_data['brand'] ?? '';
    }

    // Accessor para cor do item
    public function getItemColorAttribute()
    {
        return $this->item_data['color'] ?? '';
    }

    // Accessor para tamanho do item
    public function getItemSizeAttribute()
    {
        return $this->item_data['size'] ?? '';
    }

    // Accessor para preço unitário formatado
    public function getFormattedUnitPriceAttribute()
    {
        return 'R\$ ' . number_format($this->price, 2, ',', '.');
    }

    // Accessor para preço total formatado
    public function getFormattedTotalPriceAttribute()
    {
        $total = $this->price * $this->quantity;
        return 'R\$ ' . number_format($total, 2, ',', '.');
    }

    // Accessor para valor total
    public function getTotalPriceAttribute()
    {
        return $this->price * $this->quantity;
    }
    
    // Accessor para badge do status
    public function getStatusBadgeAttribute()
    {
        $badges = [
            'pendente' => 'badge-warning',
            'processando' => 'badge-info',
            'concluido' => 'badge-success',
            'cancelado' => 'badge-danger',
            'entregue' => 'badge-success'
        ];

        return $badges[$this->status] ?? 'badge-secondary';
    }

    // Accessor para status formatado
    public function getStatusFormatadoAttribute()
    {
        $status = [
            'pendente' => 'Pendente',
            'processando' => 'Processando',
            'concluido' => 'Concluído',
            'cancelado' => 'Cancelado',
            'entregue' => 'Entregue'
        ];

        return $status[$this->status] ?? ucfirst($this->status);
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
    
    // Scope para busca por cliente
    public function scopeByClient($query, $clientSearch)
    {
        return $query->whereHas('user', function ($q) use ($clientSearch) {
            $q->where('name', 'like', "%{$clientSearch}%")
              ->orWhere('email', 'like', "%{$clientSearch}%")
              ->orWhere('id', $clientSearch);
        });
    }

    // Scope para busca por item
    public function scopeByItem($query, $itemSearch)
    {
        return $query->whereHas('item', function ($q) use ($itemSearch) {
            $q->where('nome_do_produto', 'like', "%{$itemSearch}%")
              ->orWhere('codigo', 'like', "%{$itemSearch}%")
              ->orWhere('marca', 'like', "%{$itemSearch}%");
        });
    }

    // Scope para data específica
    public function scopeByDate($query, $date)
    {
        return $query->whereDate('add_at', $date);
    }

    // Scope para período
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('add_at', [$startDate, $endDate]);
    }

    // Método estático para calcular totais por live
    public static function getTotalsByLive($liveId)
    {
        return self::where('live_id', $liveId)
            ->selectRaw('
                COUNT(*) as total_items,
                SUM(quantity) as total_quantity,
                SUM(price * quantity) as total_value,
                COUNT(DISTINCT user_id) as total_clients
            ')
            ->first();
    }

    // Método estático para agrupar por cliente em uma live
    public static function getByClientInLive($liveId)
    {
        return self::with(['user', 'item'])
            ->where('live_id', $liveId)
            ->get()
            ->groupBy('user_id')
            ->map(function ($sacolinhas) {
                $user = $sacolinhas->first()->user;
                $totalValue = $sacolinhas->sum('total_price');
                $totalQuantity = $sacolinhas->sum('quantity');
                
                return [
                    'user' => $user,
                    'items' => $sacolinhas,
                    'total_value' => $totalValue,
                    'total_quantity' => $totalQuantity,
                    'formatted_total' => 'R\$ ' . number_format($totalValue, 2, ',', '.')
                ];
            });
    }
}