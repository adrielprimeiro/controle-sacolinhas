<?php
// app/Http/Controllers/Admin/AdminSacolinhaController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Live;
use App\Models\Sacolinhas;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminSacolinhaController extends Controller
{
    public function index(Request $request)
    {
        $query = Live::withCount('sacolinhas');

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('tipo_live', 'like', "%{$request->search}%")
                  ->orWhere('data', 'like', "%{$request->search}%")
                  ->orWhere('plataformas', 'like', "%{$request->search}%");
            });
        }

        $lives = $query->orderBy('data', 'desc')->paginate(15);

        return view('admin.sacolinhas.index', compact('lives'));
    }

    public function show(Live $live, Request $request)
    {
        $query = $live->sacolinhas()->with(['user', 'item']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('client_search')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->client_search}%")
                  ->orWhere('email', 'like', "%{$request->client_search}%");
            });
        }

        $sacolinhas = $query->orderBy('add_at', 'desc')->paginate(20);

        // Estatísticas da live
        $stats = [
            'total_sacolinhas' => $live->sacolinhas()->count(),
            'valor_total' => $live->sacolinhas()->with('item')->get()->sum(function($s) {
                return $s->item ? $s->item->preco : 0;
            }),
            'status_counts' => $live->sacolinhas()
                ->select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray(),
            'clientes_unicos' => $live->sacolinhas()->distinct('user_id')->count('user_id')
        ];

        return view('admin.sacolinhas.show', compact('live', 'sacolinhas', 'stats'));
    }

    public function searchByClient(Request $request)
    {
        $request->validate([
            'client_search' => 'required|string|min:2'
        ]);

        $sacolinhas = Sacolinhas::with(['user', 'live', 'item'])
            ->whereHas('user', function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->client_search}%")
                  ->orWhere('email', 'like', "%{$request->client_search}%")
                  ->orWhere('id', $request->client_search);
            })
            ->orderBy('add_at', 'desc')
            ->paginate(20);

        return view('admin.sacolinhas.client-search', compact('sacolinhas'));
    }

    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:delete,export,update_status',
            'sacolinha_ids' => 'required|array',
            'sacolinha_ids.*' => 'exists:sacolinhas,id',
            'status' => 'required_if:action,update_status|in:pendente,processando,concluido,cancelado,entregue'
        ]);

        $sacolinhas = Sacolinhas::whereIn('id', $request->sacolinha_ids);

        switch ($request->action) {
            case 'delete':
                $count = $sacolinhas->count();
                $sacolinhas->delete();
                return back()->with('success', "Deletadas {$count} sacolinhas com sucesso.");

            case 'update_status':
                $count = $sacolinhas->update(['status' => $request->status]);
                return back()->with('success', "Status atualizado para {$count} sacolinhas.");

            case 'export':
                return $this->exportSacolinhas($request->sacolinha_ids);
        }
    }

    private function exportSacolinhas($ids)
    {
        $sacolinhas = Sacolinhas::with(['user', 'live', 'item'])
            ->whereIn('id', $ids)
            ->get();

        $filename = 'sacolinhas_' . now()->format('Y-m-d_H-i-s') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename="{$filename}"",
        ];

        $callback = function() use ($sacolinhas) {
            $file = fopen('php://output', 'w');
            
            // Header do CSV
            fputcsv($file, [
                'ID', 'Live', 'Data Live', 'Cliente', 'Email Cliente', 
                'Produto', 'Preço', 'Status', 'Bandeja', 'Observações', 
                'Data Adição'
            ]);

            foreach ($sacolinhas as $sacolinha) {
                fputcsv($file, [
                    $sacolinha->id,
                    $sacolinha->live->tipo_live ?? 'N/A',
                    $sacolinha->live->data ? $sacolinha->live->data->format('d/m/Y') : 'N/A',
                    $sacolinha->user->name ?? 'N/A',
                    $sacolinha->user->email ?? 'N/A',
                    $sacolinha->item->nome_do_produto ?? 'N/A',
                    $sacolinha->item ? 'R$ ' . number_format($sacolinha->item->preco, 2, ',', '.') : 'N/A',
                    $sacolinha->status,
                    $sacolinha->tray,
                    $sacolinha->obs,
                    $sacolinha->add_at ? $sacolinha->add_at->format('d/m/Y H:i') : 'N/A'
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function updateStatus(Sacolinha $sacolinha, Request $request)
    {
        $request->validate([
            'status' => 'required|in:pendente,processando,concluido,cancelado,entregue',
            'obs' => 'nullable|string|max:1000',
            'tray' => 'nullable|integer'
        ]);

        $sacolinha->update([
            'status' => $request->status,
            'obs' => $request->obs,
            'tray' => $request->tray
        ]);

        return back()->with('success', 'Status da sacolinha atualizado com sucesso!');
    }

    public function details(Sacolinhas $sacolinha)
    {
        $sacolinha->load(['user', 'live', 'item']);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $sacolinha->id,
                'client' => [
                    'name' => $sacolinha->user->name ?? 'N/A',
                    'email' => $sacolinha->user->email ?? 'N/A',
                ],
                'live' => [
                    'name' => $sacolinha->live->tipo_live ?? 'N/A',
                    'date' => $sacolinha->live->data ? $sacolinha->live->data->format('d/m/Y') : 'N/A',
                ],
                'item' => $sacolinha->item_data,
                'status' => $sacolinha->status,
                'tray' => $sacolinha->tray,
                'obs' => $sacolinha->obs,
                'add_at' => $sacolinha->add_at ? $sacolinha->add_at->format('d/m/Y H:i') : 'N/A',
            ]
        ]);
    }
}