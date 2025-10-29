<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LiveController extends Controller
{
    /**
     * Buscar live ativa
     */
    public function index()
    {
        try {
            $live = DB::table('lives')
                      ->where('ativo', 1)
                      ->orderBy('created_at', 'desc')
                      ->first();
            
            if (!$live) {
                return response()->json([
                    'success' => true,
                    'message' => 'Nenhuma live ativa no momento',
                    'live' => null,
                    'live_id' => null,
                    'has_active_live' => false
                ]);
            }

            return response()->json([
                'success' => true,
                'live_id' => $live->id,
                'live' => $live,
                'has_active_live' => true
            ]);

        } catch (\Exception $e) {
            Log::error("Erro ao buscar live ativa: " . $e->getMessage());
            
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
    public function store(Request $request)
    {
        try {
            $request->validate([
                'tipo_live' => 'required|string|in:loja-aberta,precinho,outlet',
                'plataformas' => 'required|array|min:1',
                'plataformas.*' => 'string|in:instagram,tiktok,youtube,facebook'
            ]);

            // Verificar se já existe uma live ativa
            $liveAtiva = DB::table('lives')->where('ativo', 1)->first();
            
            if ($liveAtiva) {
                return response()->json([
                    'success' => false,
                    'message' => 'Já existe uma live ativa. Encerre-a antes de criar uma nova.'
                ], 400);
            }

            // Criar nova live
            $liveId = DB::table('lives')->insertGetId([
                'data' => now()->format('Y-m-d'),
                'tipo_live' => $request->tipo_live,
                'plataformas' => implode(',', $request->plataformas),
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            $live = DB::table('lives')->where('id', $liveId)->first();

            return response()->json([
                'success' => true,
                'message' => 'Live criada com sucesso!',
                'live' => $live
            ]);

        } catch (\Exception $e) {
            Log::error("Erro ao criar live: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar live: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Encerrar live
     */
    public function destroy($id)
    {
        try {
            $live = DB::table('lives')->where('id', $id)->first();
            
            if (!$live) {
                return response()->json([
                    'success' => false,
                    'message' => 'Live não encontrada'
                ], 404);
            }

            if (!$live->ativo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Esta live já foi encerrada'
                ], 400);
            }

            // Encerrar live (marcar como inativa)
            DB::table('lives')
                ->where('id', $id)
                ->update([
                    'ativo' => false,
                    'updated_at' => now()
                ]);

            return response()->json([
                'success' => true,
                'message' => 'Live encerrada com sucesso!'
            ]);

        } catch (\Exception $e) {
            Log::error("Erro ao encerrar live: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro ao encerrar live: ' . $e->getMessage()
            ], 500);
        }
    }
}