<?php

namespace App\Http\Controllers;

use App\Models\Sacolinhas;
use App\Models\Live;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
			]);

			// Buscar live ativa
			$liveAtiva = DB::table('lives')
						  ->where('ativo', 1)
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

			// MODIFICADO: Verificar se item já existe na sacola (evitar duplicatas)
			$sacolaExistente = Sacolinhas::where([
				'user_id' => $request->client_id,
				'item_id' => $request->item_id,
				'live_id' => $liveAtiva->id
			])->first();

			if ($sacolaExistente) {
				return response()->json([
					'success' => false,
					'message' => 'Este item já está na sacola deste cliente!'
				]);
			}

			// Verificar se as colunas existem
			$columns = \Schema::getColumnListing('sacolinhas');
			
			// MODIFICADO: Usar o preço enviado pelo formulário (não o preço original do item)
			$priceToStore = (float) $request->item_price;

			// Preparar dados para inserção
			$data = [
				'user_id' => $request->client_id,
				'item_id' => $request->item_id,
				'live_id' => $liveAtiva->id,
				'add_at' => now(),
				'status' => 'pendente',
				'obs' => $request->obs ?? null
			];

			// MODIFICADO: Sempre quantidade 1 para itens únicos
			if (in_array('quantity', $columns)) {
				$data['quantity'] = 1;
			}
			if (in_array('price', $columns)) {
				$data['price'] = $priceToStore;
			}

			// Criar nova entrada (sem lógica de atualização de quantidade)
			$sacolinha = Sacolinhas::create($data);

			return response()->json([
				'success' => true,
				'message' => 'Item adicionado à sacola com sucesso!',
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
						'price' => $priceToStore, // MODIFICADO: Retornar o preço usado na sacola
						'formatted_price' => 'R$ ' . number_format($priceToStore, 2, ',', '.')
					]
				]
			]);
		} catch (\Exception $e) {
			Log::error("Erro ao adicionar item à sacola: " . $e->getMessage());
			return response()->json([
				'success' => false,
				'message' => 'Erro interno: ' . $e->getMessage()
			], 500);
		}
	}

	public function getBagsByLive($liveId = null)
	{
		try {
			Log::info("getBagsByLive iniciado com liveId: " . $liveId);
			
			// Verificar se as colunas existem
			$columns = \Schema::getColumnListing('sacolinhas');
			Log::info("Colunas da tabela sacolinhas: " . implode(', ', $columns));
			
			if (!$liveId) {
				$live = DB::table('lives')
						  ->where('ativo', 1)
						  ->orderBy('created_at', 'desc')
						  ->first();
				
				if (!$live) {
					return response()->json([
						'success' => true,
						'data' => [],
						'message' => 'Nenhuma live ativa encontrada'
					]);
				}
				
				$liveId = $live->id;
			}

			// Verificar se existem registros
			$count = DB::table('sacolinhas')->where('live_id', $liveId)->count();
			Log::info("Registros encontrados: " . $count);

			if ($count === 0) {
				return response()->json([
					'success' => true,
					'data' => [],
					'live_id' => $liveId,
					'total_bags' => 0,
					'total_items' => 0,
					'total_value' => 0
				]);
			}

			// Query adaptada às colunas existentes
			$selectFields = [
				's.id as sacolinha_id',
				's.user_id',
				's.item_id',
				's.add_at',
				's.status',
				's.obs',
				'u.id as user_id',
				'u.name as user_name', 
				'u.email as user_email',
				'i.id as item_id',
				'i.nome_do_produto as item_name',
				'i.codigo as item_sku',        // CORRIGIDO: 'codigo' em vez de 'sku'
				'i.marca as item_brand',       // CORRETO
				'i.cor as item_color',         // CORRETO
				'i.tamanho as item_size'       // CORRETO
			];

			// MODIFICADO: Sempre usar o preço armazenado na sacola
			if (in_array('price', $columns)) {
				$selectFields[] = 's.price';
			} else {
				$selectFields[] = 'i.preco as price';
			}

			$sacolinhas = DB::table('sacolinhas as s')
				->join('users as u', 's.user_id', '=', 'u.id')
				->join('items as i', 's.item_id', '=', 'i.id')
				->where('s.live_id', $liveId)
				->select($selectFields)
				->orderBy('s.add_at', 'desc')
				->get();

			Log::info("Query executada. Registros retornados: " . $sacolinhas->count());

			// MODIFICADO: Processar resultados para itens únicos
			$bagsByClient = $sacolinhas->groupBy('user_id')->map(function ($clientSacolinhas) {
				$firstItem = $clientSacolinhas->first();
				
				$items = $clientSacolinhas->map(function ($sacola) {
					$itemPrice = $sacola->price ?? 0;
					
					return [
						'sacolinha_id' => $sacola->sacolinha_id,
						'item_id' => $sacola->item_id,
						'item_name' => $sacola->item_name,
						'item_sku' => $sacola->item_sku,
						'item_brand' => $sacola->item_brand,
						'item_color' => $sacola->item_color,
						'item_size' => $sacola->item_size,
						'price' => (float) $itemPrice,
						'formatted_total_price' => 'R$ ' . number_format($itemPrice, 2, ',', '.'), // MODIFICADO: Preço total = preço unitário para itens únicos
						'status' => $sacola->status,
						'added_at' => $sacola->add_at,
						'obs' => $sacola->obs
					];
				});

				$totalItems = $items->count(); // MODIFICADO: Contar itens únicos
				$totalValue = $items->sum('price'); // MODIFICADO: Somar preços individuais

				return [
					'client' => [
						'id' => $firstItem->user_id,
						'name' => $firstItem->user_name,
						'email' => $firstItem->user_email,
						'avatar_url' => 'https://ui-avatars.com/api/?name=' . urlencode($firstItem->user_name) . '&background=007bff&color=fff&size=128'
					],
					'items' => $items->values(),
					'total_items' => $totalItems, // MODIFICADO: Número de itens únicos
					'total_value' => $totalValue,
					'formatted_total' => 'R$ ' . number_format($totalValue, 2, ',', '.')
				];
			});

			return response()->json([
				'success' => true,
				'data' => $bagsByClient->values(),
				'live_id' => $liveId,
				'total_bags' => $bagsByClient->count(),
				'total_items' => $sacolinhas->count(), // MODIFICADO: Total de itens únicos
				'total_value' => $bagsByClient->sum('total_value')
			]);

		} catch (\Exception $e) {
			Log::error("Erro completo em getBagsByLive: " . $e->getMessage());
			Log::error("Linha: " . $e->getLine());
			Log::error("Arquivo: " . $e->getFile());
			
			return response()->json([
				'success' => false,
				'message' => 'Erro ao buscar sacolinhas: ' . $e->getMessage()
			], 500);
		}
	}

	public function removeItems(Request $request)
	{
		try {
			$request->validate([
				'item_id' => 'required|integer',
				'user_id' => 'required|integer',
				'live_id' => 'required|integer'
			]);

			$sacola = Sacolinhas::where([
				'item_id' => $request->item_id,
				'user_id' => $request->user_id,
				'live_id' => $request->live_id
			])->first();

			if (!$sacola) {
				return response()->json([
					'success' => false,
					'message' => 'Item não encontrado na sacola'
				], 404);
			}

			// MODIFICADO: Sempre remover completamente (itens únicos)
			$sacola->delete();

			return response()->json([
				'success' => true,
				'message' => 'Item removido da sacola com sucesso!'
			]);

		} catch (\Exception $e) {
			Log::error("Erro ao remover item: " . $e->getMessage());
			return response()->json([
				'success' => false,
				'message' => 'Erro ao remover item: ' . $e->getMessage()
			], 500);
		}
	}
}