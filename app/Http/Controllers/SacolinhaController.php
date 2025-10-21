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

            // Buscar live ativa (usando campo 'ativo')
            $liveAtiva = DB::table('lives')
                          ->where('ativo', true)
                          ->orderBy('created_at', 'desc')
                          ->first();

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
            $item = DB::table('items')->where('id', $request->item_id)->first();
            if (!$item) {
                return response()->json([
                    'success' => false,
                    'message' => 'Item não encontrado!'
                ], 404);
            }

            // Verificar se item já existe na sacola do cliente para esta live
            $sacolaExistente = Sacolinhas::where([
                'user_id' => $request->client_id,
                'item_id' => $request->item_id,
                'live_id' => $liveAtiva->id
            ])->first();

            if ($sacolaExistente) {
                // Atualizar quantidade existente
                $sacolaExistente->update([
                    'quantity' => $sacolaExistente->quantity + $request->item_quantity,
                    'price' => $request->item_price,
                    'add_at' => now()
                ]);
                
                $message = 'Quantidade atualizada na sacola! Total: ' . $sacolaExistente->quantity . ' itens';
                $sacolinha = $sacolaExistente;
            } else {
                // Criar nova entrada (UM registro com quantidade)
                $sacolinha = Sacolinhas::create([
                    'user_id' => $request->client_id,
                    'item_id' => $request->item_id,
                    'live_id' => $liveAtiva->id,
                    'quantity' => $request->item_quantity,
                    'price' => $request->item_price,
                    'add_at' => now(),
                    'status' => 'pendente',
                    'obs' => $request->obs ?? null
                ]);
                
                $message = $request->item_quantity > 1 
                    ? $request->item_quantity . ' itens adicionados à sacola com sucesso!'
                    : 'Item adicionado à sacola com sucesso!';
            }

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
                    'quantity' => $sacolinha->quantity,
                    'total_price' => $sacolinha->quantity * $sacolinha->price,
                    'formatted_total' => 'R$ ' . number_format($sacolinha->quantity * $sacolinha->price, 2, ',', '.')
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
                // Buscar live ativa usando campo 'ativo'
                $live = DB::table('lives')
                          ->where('ativo', true)
                          ->orderBy('created_at', 'desc')
                          ->first();
                
                if (!$live) {
                    return response()->json([
                        'success' => true,
                        'data' => [],
                        'message' => 'Nenhuma live ativa no momento'
                    ]);
                }
                
                $liveId = $live->id;
            }

            // Buscar sacolinhas da live usando campos quantity e price
            $sacolinhas = DB::table('sacolinhas as s')
                ->join('users as u', 's.user_id', '=', 'u.id')
                ->join('items as i', 's.item_id', '=', 'i.id')
                ->where('s.live_id', $liveId)
                ->select([
                    's.id as sacolinha_id',
                    's.quantity',
                    's.price',
                    's.add_at',
                    's.status',
                    's.tray',
                    's.obs',
                    'u.id as user_id',
                    'u.name as user_name',
                    'u.email as user_email',
                    'i.id as item_id',
                    'i.nome_do_produto as item_name',
                    'i.preco as item_price_original',
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
                
                // Mapear itens (cada registro já tem sua quantidade)
                $items = $clientSacolinhas->map(function ($sacola) {
                    $totalPrice = $sacola->quantity * $sacola->price;
                    
                    return [
                        'sacolinha_id' => $sacola->sacolinha_id,
                        'item_id' => $sacola->item_id,
                        'item_name' => $sacola->item_name,
                        'item_sku' => $sacola->item_sku ?? '',
                        'item_brand' => $sacola->item_brand ?? '',
                        'item_color' => $sacola->item_color ?? '',
                        'item_size' => $sacola->item_size ?? '',
                        'quantity' => $sacola->quantity,
                        'unit_price' => (float) $sacola->price,
                        'total_price' => $totalPrice,
                        'formatted_unit_price' => 'R$ ' . number_format($sacola->price, 2, ',', '.'),
                        'formatted_total_price' => 'R$ ' . number_format($totalPrice, 2, ',', '.'),
                        'status' => $sacola->status,
                        'added_at' => $sacola->add_at,
                        'tray' => $sacola->tray,
                        'obs' => $sacola->obs
                    ];
                });

                $totalBagValue = $items->sum('total_price');
                $totalQuantity = $items->sum('quantity');

                return [
                    'client' => [
                        'id' => $firstItem->user_id,
                        'name' => $firstItem->user_name,
                        'email' => $firstItem->user_email,
                        'avatar_url' => "https://ui-avatars.com/api/?name=" . urlencode($firstItem->user_name) . "&background=007bff&color=fff&size=128"
                    ],
                    'items' => $items,
                    'total_items' => $items->count(),
                    'total_quantity' => $totalQuantity,
                    'total_value' => $totalBagValue,
                    'formatted_total' => 'R$ ' . number_format($totalBagValue, 2, ',', '.')
                ];
            })->values();

            return response()->json([
                'success' => true,
                'data' => $bagsByClient,
                'total_bags' => $bagsByClient->count(),
                'total_items' => $sacolinhas->sum('quantity'),
                'live_id' => $liveId
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
            $request->validate([
                'item_id' => 'required|integer',
                'user_id' => 'required|integer',
                'live_id' => 'required|integer',
                'quantity' => 'required|integer|min:1'
            ]);

            $itemId = $request->input('item_id');
            $userId = $request->input('user_id');
            $liveId = $request->input('live_id');
            $quantityToRemove = $request->input('quantity');

            // Buscar a sacola específica
            $sacola = Sacolinhas::where('item_id', $itemId)
                               ->where('user_id', $userId)
                               ->where('live_id', $liveId)
                               ->first();

            if (!$sacola) {
                return response()->json([
                    'success' => false,
                    'message' => 'Item não encontrado na sacola!'
                ], 404);
            }

            $quantidadeAnterior = $sacola->quantity;

            if ($sacola->quantity <= $quantityToRemove) {
                // Remover completamente
                $sacola->delete();
                $message = 'Item removido da sacola completamente! (' . $quantidadeAnterior . ' itens removidos)';
            } else {
                // Diminuir quantidade
                $novaQuantidade = $sacola->quantity - $quantityToRemove;
                $sacola->update([
                    'quantity' => $novaQuantidade
                ]);
                $message = $quantityToRemove . ' item(s) removido(s). Restam ' . $novaQuantidade . ' na sacola.';
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'removed_quantity' => $quantityToRemove,
                    'remaining_quantity' => $sacola->exists ? $sacola->quantity : 0
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao remover item: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getLiveStats($liveId = null)
    {
        try {
            if (!$liveId) {
                $live = DB::table('lives')->where('ativo', true)->first();
                if (!$live) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Nenhuma live ativa'
                    ]);
                }
                $liveId = $live->id;
            }

            $stats = DB::table('sacolinhas')
                ->where('live_id', $liveId)
                ->selectRaw('
                    COUNT(*) as total_entries,
                    SUM(quantity) as total_items,
                    SUM(quantity * price) as total_value,
                    COUNT(DISTINCT user_id) as total_clients,
                    COUNT(DISTINCT item_id) as unique_items
                ')
                ->first();

            return response()->json([
                'success' => true,
                'data' => [
                    'live_id' => $liveId,
                    'total_entries' => $stats->total_entries ?? 0,
                    'total_items' => $stats->total_items ?? 0,
                    'total_value' => $stats->total_value ?? 0,
                    'formatted_total_value' => 'R$ ' . number_format($stats->total_value ?? 0, 2, ',', '.'),
                    'total_clients' => $stats->total_clients ?? 0,
                    'unique_items' => $stats->unique_items ?? 0
                ]
            ]);

        } catch (\Exception $e) {
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
				$liveAtiva = DB::table('lives')
							  ->where('ativo', 1) // ✅ Usar 1 para MySQL
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

			// ✅ CORREÇÃO: Desativar a live (ativo = 0)
			$updated = DB::table('lives')
						->where('id', $liveId)
						->update([
							'ativo' => 0, // ✅ Usar 0 em vez de false para MySQL
							'updated_at' => now()
						]);

			if (!$updated) {
				return response()->json([
					'success' => false,
					'message' => 'Live não encontrada ou já estava fechada!'
				], 404);
			}

			// ✅ ADICIONAR: Log para debug
			\Log::info("Live {$liveId} foi encerrada com sucesso");

			return response()->json([
				'success' => true,
				'message' => 'Live encerrada com sucesso!',
				'data' => [
					'live_id' => $liveId,
					'ativo' => 0 // ✅ Retornar o status atual
				]
			]);

		} catch (\Exception $e) {
			\Log::error("Erro ao fechar live: " . $e->getMessage());
			
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
			// ✅ CORREÇÃO: Buscar live ativa usando ativo = 1 (MySQL trata boolean como tinyint)
			$live = DB::table('lives')
					  ->where('ativo', 1) // ✅ Usar 1 em vez de true para MySQL
					  ->orderBy('created_at', 'desc')
					  ->first();
			
			if (!$live) {
				return response()->json([
					'success' => true,
					'data' => [],
					'message' => 'Nenhuma live ativa no momento',
					'live' => null,
					'live_id' => null,
					'has_active_live' => false // ✅ ADICIONAR flag clara
				]);
			}

			return response()->json([
				'success' => true,
				'data' => [$live],
				'live_id' => $live->id,
				'live' => $live,
				'has_active_live' => true // ✅ ADICIONAR flag clara
			]);

		} catch (\Exception $e) {
			\Log::error("Erro ao buscar live ativa: " . $e->getMessage());
			
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
			$liveAtiva = DB::table('lives')
						  ->where('ativo', 1)
						  ->first();

			if ($liveAtiva) {
				return response()->json([
					'success' => false,
					'message' => 'Já existe uma live ativa. Encerre-a antes de criar uma nova.'
				]);
			}

			// Criar nova live
			$liveId = DB::table('lives')->insertGetId([
				'tipo_live' => $request->tipo_live,
				'plataformas' => implode(',', $request->plataformas),
				'data' => now()->toDateString(),
				'ativo' => 1, // ✅ Live ativa
				'nome' => auth()->id(),
				'created_at' => now(),
				'updated_at' => now()
			]);

			// Buscar a live criada
			$live = DB::table('lives')->where('id', $liveId)->first();

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
			\Log::error('Erro ao criar live: ' . $e->getMessage());
			
			return response()->json([
				'success' => false,
				'message' => 'Erro interno do servidor'
			], 500);
		}
	}
}