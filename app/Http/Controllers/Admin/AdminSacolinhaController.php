<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Live;
use App\Models\User;
use App\Models\Sacolinhas;
use Illuminate\Http\Request;

class AdminSacolinhaController extends Controller
{
    public function index(Request $request)
    {
        $query = Live::withCount('sacolinhas');

        // Filtro por busca geral
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('tipo_live', 'LIKE', "%{$search}%")
                  ->orWhere('data', 'LIKE', "%{$search}%")
                  ->orWhere('plataformas', 'LIKE', "%{$search}%");
            });
        }

        // Filtro por status
        if ($request->filled('status')) {
            if ($request->status === 'ativa') {
                $query->where('status', 'ativa');
            } elseif ($request->status === 'encerrada') {
                $query->where('status', '!=', 'ativa');
            }
        }

        // Filtro por tipo
        if ($request->filled('tipo')) {
            $query->where('tipo_live', $request->tipo);
        }

        // Ordenar por data mais recente primeiro
        $lives = $query->orderBy('data', 'desc')
                      ->orderBy('created_at', 'desc')
                      ->paginate(15);

        return view('admin.sacolinhas.index', compact('lives'));
    }

    public function searchClient(Request $request)
    {
        if (!$request->filled('client_search')) {
            return redirect()->route('admin.sacolinhas.index');
        }

        $search = $request->client_search;
        
        // Buscar lives que têm sacolinhas de clientes que correspondem à busca
        $query = Live::withCount('sacolinhas')
            ->whereHas('sacolinhas', function($q) use ($search) {
                $q->whereHas('user', function($userQuery) use ($search) {
                    $userQuery->where('name', 'LIKE', "%{$search}%")
                             ->orWhere('email', 'LIKE', "%{$search}%")
                             ->orWhere('id', $search);
                });
            });

        $lives = $query->orderBy('data', 'desc')
                      ->orderBy('created_at', 'desc')
                      ->paginate(15);

        $message = $lives->total() > 0 
            ? "Encontradas {$lives->total()} lives com sacolinhas do cliente pesquisado."
            : "Nenhuma live encontrada com sacolinhas do cliente pesquisado.";

        return view('admin.sacolinhas.index', compact('lives'))
               ->with($lives->total() > 0 ? 'success' : 'info', $message);
    }

    public function show(Live $live)
    {
        // Usar o método da model para obter sacolinhas agrupadas
        $sacolinhasPorUsuario = $live->getSacolinhasByClient();
        $totals = $live->getTotals();
        
        return view('admin.sacolinhas.show', compact('live', 'sacolinhasPorUsuario', 'totals'));
    }

    public function export(Live $live)
    {
        // Implementar exportação (CSV, Excel, etc.)
        return redirect()->route('admin.sacolinhas.show', $live)
                        ->with('info', 'Funcionalidade de exportação será implementada em breve.');
    }
}