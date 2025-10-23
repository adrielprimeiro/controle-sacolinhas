<?php

namespace App\Http\Controllers;

use App\Models\Sacolinhas;
use App\Models\Live; // Certifique-se de que o modelo Live existe e está importado
use App\Models\User;
use App\Models\Item; // Importar o modelo Item para buscar o preço original
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log; // Para usar Log::error

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
                // 'item_quantity' não é mais necessário, pois sempre será 1
            ]);

            // Buscar live ativa
            $liveAtiva = Live::where('ativo', 1)->orderBy('created_at', 'desc')->first();

            if (!$liveAtiva) {
                return response()->json([
                    'success' => false,
                    'message' => 'Não há live ativa no momento!'
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
            $item = Item::find($request->item_id); // Usar o modelo Item
            if (!$item) {
                return response()->json([
                    'success' => false,
                    'message' => 'Item não encontrado!'
                ], 404);
            }

            $finalPrice = $request->item_price;

            // Aplicar desconto de 50% se for Live do Precinho
            if ($liveAtiva->tipo_live === 'precinho') {
                // O preço já deve vir com desconto do frontend, mas podemos recalcular para garantir
                $finalPrice = $item->preco * 0.5;
            }

            // Sempre criar uma nova entrada para cada item adicionado
            $sacolinha = Sacolinhas::create([
                'user_id' => $request->client_id,
                'item_id' => $request->item_id,
                'live_id' => $liveAtiva->id,
                'quantity' => 1, // Quantidade fixa em 1
                'price' => $finalPrice, // Preço final (com ou sem desconto)
                'add_at' => now(),
                'status' => 'pendente',
                'obs' => $request->obs ?? null
            ]);

            $message = 'Item adicionado à sacola com sucesso!';

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'sacolinha' => $sacolinha,
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
                    'quantity' => 1, // Sempre 1
                    'total_price' => $finalPrice,
                    'formatted_total' => 'R$ ' . number_format($finalPrice, 2, ',', '.')
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao adicionar item à sacola: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'success' => false,
                'message' => 'Erro interno: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getBagsByLive($liveId = null)
    {
        try {
            $live = null;
            if (!$liveId) {
                // Buscar live ativa
                $live = Live::where('ativo', 1)
                          ->orderBy('created_at', 'desc')
                          ->first();

                if (!$live) {
                    return response()->json([
                        'success' => true,
                        'data' => [],
                        'message' => 'Nenhuma live ativa no momento',
                        'live_info' => null
                    ]);
                }
                $liveId = $live->id;
            } else {
                // Buscar informações da live específica
                $live = Live::find($liveId);
            }

            // Buscar sacolinhas com informações completas
            $sacolinhas = Sacolinhas::with(['user', 'item']) // Usar relacionamentos do Eloquent
                ->where('live_id', $liveId)
                ->orderBy('add_at', 'desc')
                ->get();

            // Agrupar por cliente com estatísticas
            $bagsByClient = $sacolinhas->groupBy('user_id')->map(function ($clientSacolinhas) {
                $firstItem = $clientSacolinhas->first();
                $user = $firstItem->user; // Acessar o usuário via relacionamento

                $items = $clientSacolinhas->map(function ($sacola) {
                    // Cada sacola aqui é um registro Sacolinhas, que representa um item único
                    return [
                        'sacolinha_id' => $sacola->id, // ID do registro Sacolinhas
                        'item_id' => $sacola->item_id,
                        'item_name' => $sacola->item->nome_do_produto, // Acessar nome do item via relacionamento
                        'item_sku' => $sacola->item->codigo ?? '',
                        'item_brand' => $sacola->item->marca ?? '',
                        'item_color' => $sacola->item->cor ?? '',
                        'item_size' => $sacola->item->tamanho ?? '',
                        'quantity' => 1, // Sempre 1 para cada registro
                        'unit_price' => (float) $sacola->price,
                        'total_price' => (float) $sacola->price, // Total é o próprio preço
                        'formatted_unit_price' => 'R$ ' . number_format($sacola->price, 2, ',', '.'),
                        'formatted_total_price' => 'R$ ' . number_format($sacola->price, 2, ',', '.'),
                        'status' => $sacola->status,
                        'added_at' => $sacola->add_at,
                        'tray' => $sacola->tray,
                        'obs' => $sacola->obs
                    ];
                });

                $totalBagValue = $items->sum('total_price');
                $totalQuantity = $items->count(); // Contagem de registros = quantidade de itens

                return [
                    'client' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'phone' => $user->phone ?? '',
                        'avatar_url' => "https://ui-avatars.com/api/?name=" . urlencode($user->name) . "&background=667eea&color=fff&size=128"
                    ],
                    'items' => $items,
                    'total_items' => $items->count(), // Total de registros (itens)
                    'total_quantity' => $totalQuantity, // Total de registros (itens)
                    'total_value' => $totalBagValue,
                    'formatted_total' => 'R$ ' . number_format($totalBagValue, 2, ',', '.'),
                    'last_update' => $items->max('added_at')
                ];
            })->values();

            return response()->json([
                'success' => true,
                'data' => $bagsByClient,
                'total_bags' => $bagsByClient->count(), // Quantidade de sacolas (clientes com itens)
                'total_items' => $sacolinhas->count(), // Quantidade total de itens (registros)
                'total_value' => $sacolinhas->sum('price'), // Valor total de todos os itens
                'live_id' => $liveId,
                'live_info' => $live ? [
                    'id' => $live->id,
                    'tipo_live' => $live->tipo_live,
                    'data' => $live->data,
                    'ativo' => $live->ativo,
                    'plataformas' => $live->plataformas ?? ''
                ] : null
            ]);

        } catch (\Exception $e) {
            Log::error("Erro ao carregar sacolas da live {$liveId}: " . $e->getMessage(), ['exception' => $e]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao carregar sacolas: ' . $e->getMessage()
            ], 500);
        }
    }

    // NOVO MÉTODO: Remover um item específico da sacola pelo ID do registro Sacolinhas
    public function destroySacolinhaItem(Sacolinhas $sacolinha)
    {
        try {
            $sacolinha->delete();

            return response()->json([
                'success' => true,
                'message' => 'Item removido da sacola com sucesso!',
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao remover item da sacola: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'success' => false,
                'message' => 'Erro ao remover item: ' . $e->getMessage()
            ], 500);
        }
    }

    // REMOVA OU REAPROVEITE ESTE MÉTODO SE NÃO FOR MAIS USADO
    // public function removeItem(Request $request) { ... }

    public function getLiveStats($liveId = null)
    {
        try {
            if (!$liveId) {
                $live = Live::where('ativo', 1)->first();
                if (!$live) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Nenhuma live ativa'
                    ]);
                }
                $liveId = $live->id;
            }

            $stats = Sacolinhas::where('live_id', $liveId)
                ->selectRaw('
                    COUNT(*) as total_items_count,       -- Total de registros (itens únicos)
                    SUM(price) as total_value_sum,       -- Soma dos preços dos itens
                    COUNT(DISTINCT user_id) as total_clients_count
                ')
                ->first();

            return response()->json([
                'success' => true,
                'data' => [
                    'live_id' => $liveId,
                    'total_bags' => $stats->total_clients_count ?? 0, // Total de clientes com sacolas
                    'total_items' => $stats->total_items_count ?? 0, // Total de itens únicos adicionados
                    'total_value' => $stats->total_value_sum ?? 0,
                    'formatted_total_value' => 'R$ ' . number_format($stats->total_value_sum ?? 0, 2, ',', '.'),
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao obter estatísticas: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'success' => false,
                'message' => 'Erro ao obter estatísticas: ' . $e->getMessage()
            ], 500);
        }
    }

    public function clearClientBag(Request $request)
    {
        try {
            $request->validate([
                'user_id' => 'required|integer|exists:users,id',
                'live_id' => 'required|integer|exists:lives,id'
            ]);

            $deletedCount = Sacolinhas::where('user_id', $request->user_id)
                                     ->where('live_id', $request->live_id)
                                     ->delete();

            return response()->json([
                'success' => true,
                'message' => 'Sacola limpa com sucesso! ' . $deletedCount . ' itens removidos.',
                'deleted_count' => $deletedCount
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao limpar sacola: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'success' => false,
                'message' => 'Erro ao limpar sacola: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateItemStatus(Request $request)
    {
        try {
            $request->validate([
                'sacolinha_id' => 'required|integer|exists:sacolinhas,id',
                'status' => 'required|string|in:pendente,processando,concluido,cancelado,entregue'
            ]);

            $sacola = Sacolinhas::findOrFail($request->sacolinha_id);
            $sacola->update([
                'status' => $request->status
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Status atualizado com sucesso!',
                'data' => [
                    'sacolinha_id' => $sacola->id,
                    'new_status' => $sacola->status
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao atualizar status: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Fechar/desativar a live ativa
     */
    public function closeLive(Request $request)
    {
        try {
            $request->validate([
                'live_id' => 'nullable|integer|exists:lives,id'
            ]);

            $liveId = $request->live_id;

            if (!$liveId) {
                // Buscar live ativa automaticamente
                $liveAtiva = Live::where('ativo', 1)
                              ->orderBy('created_at', 'desc')
                              ->first();

                if (!$liveAtiva) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Não há live ativa para fechar!'
                    ], 400);
                }

                $liveId = $liveAtiva->id;
            }

            $updated = Live::where('id', $liveId)
                        ->update([
                            'ativo' => 0,
                            'updated_at' => now()
                        ]);

            if (!$updated) {
                return response()->json([
                    'success' => false,
                    'message' => 'Live não encontrada ou já estava fechada!'
                ], 404);
            }

            Log::info("Live {$liveId} foi encerrada com sucesso");

            return response()->json([
                'success' => true,
                'message' => 'Live encerrada com sucesso!',
                'data' => [
                    'live_id' => $liveId,
                    'ativo' => 0
                ]
            ]);

        } catch (\Exception $e) {
            Log::error("Erro ao fechar live: " . $e->getMessage(), ['exception' => $e]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao fechar live: ' . $e->getMessage()
            ], 500);
        }
    }

     /**
     * Buscar live ativa
     */
    public function getActiveLive()
    {
        try {
            $live = Live::where('ativo', 1)
                      ->orderBy('created_at', 'desc')
                      ->first();

            if (!$live) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'message' => 'Nenhuma live ativa no momento',
                    'live' => null,
                    'live_id' => null,
                    'has_active_live' => false
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => [$live],
                'live_id' => $live->id,
                'live' => $live,
                'has_active_live' => true
            ]);

        } catch (\Exception $e) {
            Log::error("Erro ao buscar live ativa: " . $e->getMessage(), ['exception' => $e]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar live ativa: ' . $e->getMessage(),
                'has_active_live' => false
            ], 500);
        }
    }
    /**
     * Criar nova live
     */
    public function createLive(Request $request)
    {
        try {
            // Validar dados
            $request->validate([
                'tipo_live' => 'required|string|in:loja-aberta,leilao,precinho',
                'plataformas' => 'required|array|min:1',
                'plataformas.*' => 'string|in:instagram,tiktok,youtube'
            ]);

            // Verificar se já existe uma live ativa
            $liveAtiva = Live::where('ativo', 1)
                          ->first();

            if ($liveAtiva) {
                return response()->json([
                    'success' => false,
                    'message' => 'Já existe uma live ativa. Encerre-a antes de criar uma nova.'
                ]);
            }

            // Criar nova live
            $live = Live::create([ // Usar o modelo Live
                'tipo_live' => $request->tipo_live,
                'plataformas' => implode(',', $request->plataformas),
                'data' => now()->toDateString(),
                'ativo' => 1,
                'nome' => auth()->id(), // Assumindo que 'nome' é o user_id
                'created_at' => now(),
                'updated_at' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Live criada com sucesso!',
                'live' => [
                    'id' => $live->id,
                    'tipo_live' => $live->tipo_live,
                    'plataformas' => explode(',', $live->plataformas),
                    'data' => $live->data,
                    'ativo' => $live->ativo,
                    'created_at' => $live->created_at
                ]
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Dados inválidos: ' . implode(', ', $e->validator->errors()->all())
            ], 422);
        } catch (\Exception $e) {
            Log::error('Erro ao criar live: ' . $e->getMessage(), ['exception' => $e]);

            return response()->json([
                'success' => false,
                'message' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * Buscar sacolinhas de uma live específica para AJAX (ADMIN)
     * Este método é para o painel administrativo, então pode ter uma lógica diferente
     * de agrupamento/exibição se necessário.
     * Ajustado para refletir a nova estrutura de "um registro por item".
     */
    public function getSacolinhasByLive(Live $live)
    {
        try {
            $sacolinhas = Sacolinhas::with(['user', 'item'])
                ->where('live_id', $live->id)
                ->orderBy('add_at', 'desc')
                ->get();

            // Agrupar por cliente e calcular totais
            $sacolinhasByClient = $sacolinhas->groupBy('user_id')->map(function ($clientSacolinhas) {
                $firstItem = $clientSacolinhas->first();
                $user = $firstItem->user;

                $totalValue = $clientSacolinhas->sum('price');
                $totalItems = $clientSacolinhas->count(); // Cada registro é um item

                return [
                    'id' => $user->id, // ID do cliente
                    'client_id' => $user->id,
                    'client_name' => $user->name,
                    'client_email' => $user->email,
                    'items_count' => $totalItems, // Total de itens únicos para este cliente
                    'total_items' => $totalItems,
                    'total_value' => number_format($totalValue, 2, ',', '.'),
                    'status' => 'ativa', // Ou o status real da sacola do cliente
                    'created_at' => $firstItem->add_at,
                    'items' => $clientSacolinhas->map(function($sacolaItem) { // Renomeado para sacolaItem para clareza
                        return [
                            'sacolinha_id' => $sacolaItem->id, // ID do registro Sacolinhas
                            'name' => $sacolaItem->item->nome_do_produto,
                            'sku' => $sacolaItem->item->codigo,
                            'quantity' => 1, // Sempre 1
                            'price' => number_format($sacolaItem->price, 2, ',', '.'),
                            'total' => number_format($sacolaItem->price, 2, ',', '.') // Total é o próprio preço
                        ];
                    })
                ];
            })->values();

            return response()->json([
                'success' => true,
                'live' => [
                    'id' => $live->id,
                    'tipo_live' => $live->tipo_live,
                    'formatted_date' => $live->data ? \Carbon\Carbon::parse($live->data)->format('d/m/Y') : \Carbon\Carbon::parse($live->created_at)->format('d/m/Y'),
                    'status' => $live->ativo ? 'ativa' : 'encerrada'
                ],
                'sacolinhas' => $sacolinhasByClient,
                'count' => $sacolinhasByClient->count(), // Número de clientes com sacolas
                'total_items' => $sacolinhas->count(), // Total de itens únicos em todas as sacolas
                'total_value' => number_format($sacolinhas->sum('price'), 2, ',', '.')
            ]);

        } catch (\Exception $e) {
            Log::error("Erro ao buscar sacolinhas da live {$live->id}: " . $e->getMessage(), ['exception' => $e]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao carregar sacolinhas: ' . $e->getMessage()
            ], 500);
        }
    }
}