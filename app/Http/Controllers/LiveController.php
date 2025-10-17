<?php

namespace App\Http\Controllers;

use App\Models\Live;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class LiveController extends Controller
{
    public function index()
    {
        // Buscar lives do dia atual
        $lives = Live::whereDate('data', Carbon::today())
                    ->orderBy('created_at', 'desc')
                    ->get();

        // Se for requisição AJAX, retornar JSON
        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'lives' => $lives->map(function($live) {
                    return [
                        'id' => $live->id,
                        'tipo_live' => $live->tipo_live,
                        'tipo_live_formatado' => $live->tipo_live_formatado,
                        'data' => $live->data->format('d/m/Y'),
                        'plataformas' => $live->plataformas_array,
                        'plataformas_string' => $live->plataformas,
                        'created_at' => $live->created_at->format('H:i:s')
                    ];
                })
            ]);
        }

        return view('lives.index', compact('lives'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tipo_live' => 'required|in:loja-aberta,leilao,precinho',
            'plataformas' => 'required|array|min:1',
            'plataformas.*' => 'in:instagram,tiktok,youtube'
        ], [
            'tipo_live.required' => 'O tipo de live é obrigatório.',
            'tipo_live.in' => 'Tipo de live inválido.',
            'plataformas.required' => 'Selecione pelo menos uma plataforma.',
            'plataformas.min' => 'Selecione pelo menos uma plataforma.',
            'plataformas.*.in' => 'Plataforma inválida selecionada.'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
                'message' => 'Dados inválidos.'
            ], 422);
        }

        try {
            // Verificar se já existe uma live ativa hoje
            $liveExistente = Live::whereDate('data', Carbon::today())->first();
            
            if ($liveExistente) {
                return response()->json([
                    'success' => false,
                    'message' => 'Já existe uma live ativa hoje. Encerre a live atual antes de criar uma nova.'
                ], 400);
            }

            $live = Live::create([
                'tipo_live' => $request->tipo_live,
                'data' => Carbon::today(),
                'plataformas' => $request->plataformas // O mutator vai converter para string
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Live criada com sucesso!',
                'live' => [
                    'id' => $live->id,
                    'tipo_live' => $live->tipo_live,
                    'tipo_live_formatado' => $live->tipo_live_formatado,
                    'data' => $live->data->format('d/m/Y'),
                    'plataformas' => $live->plataformas_array,
                    'plataformas_string' => $live->plataformas,
                    'created_at' => $live->created_at->format('H:i:s')
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar live: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $live = Live::findOrFail($id);
            $live->delete();

            return response()->json([
                'success' => true,
                'message' => 'Live encerrada com sucesso!'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao encerrar live: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getLiveAtiva()
    {
        $live = Live::whereDate('data', Carbon::today())->first();
        
        if ($live) {
            return response()->json([
                'success' => true,
                'live' => [
                    'id' => $live->id,
                    'tipo_live' => $live->tipo_live,
                    'tipo_live_formatado' => $live->tipo_live_formatado,
                    'data' => $live->data->format('d/m/Y'),
                    'plataformas' => $live->plataformas_array,
                    'plataformas_string' => $live->plataformas,
                    'created_at' => $live->created_at->format('H:i:s')
                ]
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Nenhuma live ativa encontrada.'
        ]);
    }
}