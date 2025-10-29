<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Search users for API (similar ao ItemController)
     */
    public function search(Request $request)
    {
        try {
            $query = $request->get('q');
            $role = $request->get('role', 'client');
            

            // Busca de usuários (ajuste os campos conforme sua tabela)
            $users = User::where(function($q) use ($query) {
                $q->where('name', 'LIKE', "%{$query}%")
                  ->orWhere('email', 'LIKE', "%{$query}%");
                  // Adicione outros campos se existirem:
                  // ->orWhere('phone', 'LIKE', "%{$query}%")
                  // ->orWhere('cpf', 'LIKE', "%{$query}%");
            })
            // Filtro por role (se tiver o campo)
            ->when($role === 'client', function($q) {
                // Descomente se tiver campo 'role':
                // $q->where('role', 'client');
                
                // Ou use outra lógica, exemplo:
                // $q->whereHas('roles', function($query) {
                //     $query->where('name', 'client');
                // });
            })
            ->select('id', 'name', 'email') // Campos que existem
            ->limit(10)
            ->get();

            // Formatar dados similar ao ItemController
            $formattedUsers = $users->map(function($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar_url' => $this->getDefaultAvatar($user->name),
                    // Adicione outros campos se necessário:
                    // 'phone' => $user->phone ?? '',
                    // 'role' => $user->role ?? 'client'
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $formattedUsers
            ]);

        } catch (\Exception $e) {
            \Log::error('Erro na busca de usuários', [
                'error' => $e->getMessage(),
                'query' => $request->get('q')
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * Gerar avatar padrão baseado no nome
     */
    private function getDefaultAvatar($name)
    {
        return "https://ui-avatars.com/api/?name=" . urlencode($name) . "&background=667eea&color=fff&size=128";
    }
}