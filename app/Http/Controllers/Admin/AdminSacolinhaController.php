<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Sacolinhas;
use App\Models\Live;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AdminSacolinhaController extends Controller
{
    /**
     * Página principal de sacolinhas admin
     */
    public function index(Request $request)
    {
        try {
            $search = $request->get('search');
            
            $query = Live::withCount('sacolinhas');
            
            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('tipo_live', 'LIKE', "%{$search}%")
                      ->orWhere('data', 'LIKE', "%{$search}%")
                      ->orWhereDate('created_at', 'LIKE', "%{$search}%");
                });
            }
            
            $lives = $query->orderBy('created_at', 'desc')->paginate(15);
            
            // Adicionar data formatada
            $lives->getCollection()->transform(function ($live) {
                $live->formatted_date = $live->data 
                    ? Carbon::parse($live->data)->format('d/m/Y')
                    : Carbon::parse($live->created_at)->format('d/m/Y');
                return $live;
            });

            return view('admin.sacolinhas.index', compact('lives'));

        } catch (\Exception $e) {
            Log::error("Erro ao carregar sacolinhas admin: " . $e->getMessage());
            
            return view('admin.sacolinhas.index', ['lives' => collect()])
                ->with('error', 'Erro ao carregar dados');
        }
    }

    /**
     * Buscar sacolinhas por cliente
     */
    public function searchByClient(Request $request)
    {
        try {
            $search = $request->get('client_search');
            
            if (empty($search)) {
                return redirect()->route('admin.sacolinhas.index')
                    ->with('error', 'Digite um termo para buscar');
            }

            // Buscar clientes que correspondem ao termo
            $clients = User::where(function($query) use ($search) {
                $query->where('name', 'LIKE', "%{$search}%")
                      ->orWhere('email', 'LIKE', "%{$search}%")
                      ->orWhere('id', $search);
            })->pluck('id');

            if ($clients->isEmpty()) {
                return redirect()->route('admin.sacolinhas.index')
                    ->with('error', 'Nenhum cliente encontrado com esse termo');
            }

            // Buscar lives que têm sacolinhas desses clientes
            $lives = Live::whereHas('sacolinhas', function($query) use ($clients) {
                $query->whereIn('user_id', $clients);
            })
            ->withCount(['sacolinhas' => function($query) use ($clients) {
                $query->whereIn('user_id', $clients);
            }])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

            // Adicionar informação de data formatada
            $lives->getCollection()->transform(function ($live) {
                $live->formatted_date = $live->data 
                    ? Carbon::parse($live->data)->format('d/m/Y')
                    : Carbon::parse($live->created_at)->format('d/m/Y');
                return $live;
            });

            return view('admin.sacolinhas.index', compact('lives'))
                ->with('success', "Encontradas {$lives->total()} lives com sacolinhas do termo '{$search}'");

        } catch (\Exception $e) {
            Log::error("Erro na busca por cliente: " . $e->getMessage());
            
            return redirect()->route('admin.sacolinhas.index')
                ->with('error', 'Erro ao realizar busca: ' . $e->getMessage());
        }
    }

    /**
     * Buscar sacolinhas de uma live específica para AJAX
     */
    public function getSacolinhasByLive($liveId)
    {
        try {
            // Buscar informações da live
            $live = DB::table('lives')->where('id', $liveId)->first();
            
            if (!$live) {
                return response()->json([
                    'success' => false,
                    'message' => 'Live não encontrada'
                ], 404);
            }

            // Buscar sacolinhas da live
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
                    's.obs',
                    'u.id as user_id',
                    'u.name as user_name',
                    'u.email as user_email',
                    'i.id as item_id',
                    'i.nome_do_produto as item_name',
                    'i.preco as item_price_original',
                    'i.codigo as item_sku'
                ])
                ->orderBy('s.add_at', 'desc')
                ->get();

            // Agrupar por cliente e calcular totais
            $sacolinhasByClient = $sacolinhas->groupBy('user_id')->map(function ($clientSacolinhas) {
                $firstItem = $clientSacolinhas->first();
                
                $totalValue = $clientSacolinhas->sum(function($item) {
                    return $item->quantity * $item->price;
                });
                
                $totalItems = $clientSacolinhas->sum('quantity');
                
                return [
                    'id' => $firstItem->sacolinha_id,
                    'client_id' => $firstItem->user_id,
                    'client_name' => $firstItem->user_name,
                    'client_email' => $firstItem->user_email,
                    'items_count' => $clientSacolinhas->count(),
                    'total_items' => $totalItems,
                    'total_value' => number_format($totalValue, 2, ',', '.'),
                    'status' => 'ativa',
                    'created_at' => $firstItem->add_at,
                    'items' => $clientSacolinhas->map(function($item) {
                        return [
                            'name' => $item->item_name,
                            'sku' => $item->item_sku,
                            'quantity' => $item->quantity,
                            'price' => number_format($item->price, 2, ',', '.'),
                            'total' => number_format($item->quantity * $item->price, 2, ',', '.')
                        ];
                    })
                ];
            })->values();

            // Calcular totais gerais
            $totalValue = $sacolinhas->sum(function($item) {
                return $item->quantity * $item->price;
            });

            return response()->json([
                'success' => true,
                'live' => [
                    'id' => $live->id,
                    'tipo_live' => $live->tipo_live,
                    'formatted_date' => $live->data ? Carbon::parse($live->data)->format('d/m/Y') : Carbon::parse($live->created_at)->format('d/m/Y'),
                    'status' => $live->ativo ? 'ativa' : 'encerrada'
                ],
                'sacolinhas' => $sacolinhasByClient,
                'count' => $sacolinhasByClient->count(),
                'total_items' => $sacolinhas->sum('quantity'),
                'total_value' => number_format($totalValue, 2, ',', '.')
            ]);

        } catch (\Exception $e) {
            Log::error("Erro ao buscar sacolinhas da live {$liveId}: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erro ao carregar sacolinhas: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar detalhes de uma live
     */
    public function show(Live $live)
    {
        // Implementar se necessário
        return view('admin.sacolinhas.show', compact('live'));
    }

    /**
     * Exportar sacolinhas de uma live
     */
    public function export(Live $live)
    {
        // Implementar exportação se necessário
        return response()->json(['message' => 'Exportação em desenvolvimento']);
    }
}