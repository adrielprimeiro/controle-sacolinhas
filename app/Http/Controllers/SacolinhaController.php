<?php

namespace App\Http\Controllers;

use App\Models\Sacolinhas;
use App\Models\Live;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SacolinhaController extends Controller
{
    public function index()
    {
        return view('admin.live.index');
    }

    public function store(Request $request)
    {
        try {
            // Validação
            $request->validate([
                'client_id' => 'required|integer|exists:users,id',
                'item_id' => 'required|integer|exists:items,id',
                'item_price' => 'required|numeric|min:0',
                'item_quantity' => 'required|integer|min:1'
            ]);

            // Buscar live mais recente de hoje (sem usar 'status')
            $liveAtiva = DB::table('lives')
                          ->whereDate('created_at', today())
                          ->orderBy('created_at', 'desc')
                          ->first();

            if (!$liveAtiva) {
                return response()->json([
                    'success' => false,
                    'message' => 'Não há live criada hoje!'
                ], 400);
            }

            // Buscar dados do cliente
            $client = User::find($request->client_id);
            if (!$client) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cliente não encontrado!'
                ], 404);
            }

            // Buscar dados do item
            $item = DB::table('items')->where('id', $request->item_id)->first();
            if (!$item) {
                return response()->json([
                    'success' => false,
                    'message' => 'Item não encontrado!'
                ], 404);
            }

            // CORRIGIDO: Criar array vazio e adicionar sacolinhas corretamente
            $sacolinhasArray = [];
            
            for ($i = 0; $i < $request->item_quantity; $i++) {
                $sacolinha = Sacolinhas::create([
                    'user_id' => $request->client_id,
                    'item_id' => $request->item_id,
                    'live_id' => $liveAtiva->id,  // ID da live encontrada
                    'add_at' => now(),
                    'status' => 'pendente',       // Status da sacolinha (não da live)
                    'obs' => $request->obs ?? null
                ]);
                
                //  Adicionar ao array corretamente
                $sacolinhasArray[] = $sacolinha;
            }

            return response()->json([
                'success' => true,
                'message' => count($sacolinhasArray) > 1 
                    ? count($sacolinhasArray) . ' itens adicionados à sacola com sucesso!' 
                    : 'Item adicionado à sacola com sucesso!',
                'data' => [
                    'sacolinhas' => $sacolinhasArray,
                    'client' => [
                        'id' => $client->id,
                        'name' => $client->name,
                        'email' => $client->email
                    ],
                    'item' => [
                        'id' => $item->id,
                        'name' => $item->nome_do_produto,
                        'price' => (float) $item->preco,
                        'formatted_price' => 'R$ ' . number_format((float) $item->preco, 2, ',', '.')
                    ],
                    'quantity' => count($sacolinhasArray)
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro interno: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getBagsByLive($liveId = null)
    {
        try {
            if (!$liveId) {
                // CORRIGIDO: Buscar live mais recente de hoje (sem usar 'status')
                $live = DB::table('live')
                          ->whereDate('created_at', today())
                          ->orderBy('created_at', 'desc')
                          ->first();
                
                if (!$live) {
                    return response()->json([
                        'success' => true,
                        'data' => [],
                        'message' => 'Nenhuma live criada hoje'
                    ]);
                }
                
                $liveId = $live->id;
            }

            // Buscar sacolinhas da live
            $sacolinhas = DB::table('sacolinhas as s')
                ->join('users as u', 's.user_id', '=', 'u.id')
                ->join('items as i', 's.item_id', '=', 'i.id')
                ->where('s.live_id', $liveId)
                ->select([
                    's.id as sacolinha_id',
                    's.add_at',
                    's.status',
                    's.tray',
                    's.obs',
                    'u.id as user_id',
                    'u.name as user_name',
                    'u.email as user_email',
                    'i.id as item_id',
                    'i.nome_do_produto as item_name',
                    'i.preco as item_price',
                    'i.codigo as item_sku',
                    'i.marca as item_brand',
                    'i.cor as item_color',
                    'i.tamanho as item_size'
                ])
                ->orderBy('s.add_at', 'desc')
                ->get();

            // Agrupar por cliente
            $bagsByClient = $sacolinhas->groupBy('user_id')->map(function ($clientSacolinhas) {
                $firstItem = $clientSacolinhas->first();
                
                $itemsGrouped = $clientSacolinhas->groupBy('item_id')->map(function ($itemSacolinhas) {
                    $firstItem = $itemSacolinhas->first();
                    $quantity = $itemSacolinhas->count();
                    $totalPrice = $quantity * (float) $firstItem->item_price;
                    
                    return [
                        'sacolinha_ids' => $itemSacolinhas->pluck('sacolinha_id')->toArray(),
                        'item_id' => $firstItem->item_id,
                        'item_name' => $firstItem->item_name,
                        'item_sku' => $firstItem->item_sku,
                        'item_brand' => $firstItem->item_brand,
                        'item_color' => $firstItem->item_color,
                        'item_size' => $firstItem->item_size,
                        'quantity' => $quantity,
                        'unit_price' => (float) $firstItem->item_price,
                        'total_price' => $totalPrice,
                        'formatted_unit_price' => 'R$ ' . number_format((float) $firstItem->item_price, 2, ',', '.'),
                        'formatted_total_price' => 'R$ ' . number_format($totalPrice, 2, ',', '.'),
                        'status' => $firstItem->status,
                        'last_added' => $itemSacolinhas->max('add_at')
                    ];
                })->values();

                $totalBagValue = $itemsGrouped->sum('total_price');
                $totalQuantity = $itemsGrouped->sum('quantity');

                return [
                    'client' => [
                        'id' => $firstItem->user_id,
                        'name' => $firstItem->user_name,
                        'email' => $firstItem->user_email,
                        'avatar_url' => "https://ui-avatars.com/api/?name=" . urlencode($firstItem->user_name) . "&background=007bff&color=fff&size=128"
                    ],
                    'items' => $itemsGrouped,
                    'total_items' => $itemsGrouped->count(),
                    'total_quantity' => $totalQuantity,
                    'total_value' => $totalBagValue,
                    'formatted_total' => 'R$ ' . number_format($totalBagValue, 2, ',', '.')
                ];
            })->values();

            return response()->json([
                'success' => true,
                'data' => $bagsByClient,
                'total_bags' => $bagsByClient->count(),
                'total_items' => $sacolinhas->count()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao carregar sacolas: ' . $e->getMessage()
            ], 500);
        }
    }

    public function removeItem(Request $request)
    {
        try {
            $itemId = $request->input('item_id');
            $userId = $request->input('user_id');
            $liveId = $request->input('live_id');
            $quantity = $request->input('quantity', 1);

            // CORRIGIDO: Buscar sacolinhas para remover com nome diferente
            $sacolinhasToRemove = Sacolinhas::where('item_id', $itemId)
                                  ->where('user_id', $userId)
                                  ->where('live_id', $liveId)
                                  ->limit($quantity)
                                  ->get();

            if ($sacolinhasToRemove->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Item não encontrado na sacola!'
                ], 404);
            }

            // CORRIGIDO: Remover as sacolinhas com variável diferente
            $removedCount = 0;
            foreach ($sacolinhasToRemove as $sacolinha) {
                $sacolinha->delete();
                $removedCount++;
            }

            return response()->json([
                'success' => true,
                'message' => $removedCount > 1 
                    ? $removedCount . ' itens removidos da sacola!' 
                    : 'Item removido da sacola com sucesso!'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao remover item: ' . $e->getMessage()
            ], 500);
        }
    }
}