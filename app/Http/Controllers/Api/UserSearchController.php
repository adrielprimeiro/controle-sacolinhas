<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserSearchController extends Controller
{
    public function search(Request $request)
    {
        try {
            $searchTerm = $request->get('q', '');
            $limit = $request->get('limit', 10);
            $roleFilter = $request->get('role', 'client');

            if (empty($searchTerm)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Termo de busca é obrigatório'
                ], 400);
            }

            $query = User::query();

            // Aplicar busca
            $query->where(function($q) use ($searchTerm) {
                $q->where('name', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('email', 'LIKE', "%{$searchTerm}%");
                  
                // Buscar por telefone se existir
                if (!empty($searchTerm)) {
                    $q->orWhere('phone', 'LIKE', "%{$searchTerm}%");
                }
                  
                // Busca por ID se for numérico
                if (is_numeric($searchTerm)) {
                    $q->orWhere('id', $searchTerm);
                }
            });

            // Filtrar por role se especificado
            if ($roleFilter !== 'all') {
                $query->where('role', $roleFilter);
            }

            $users = $query->orderBy('name')
                          ->limit($limit)
                          ->get();

            $formattedUsers = $users->map(function($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone ?? null,
                    'formatted_phone' => $this->formatPhone($user->phone),
                    'address' => $user->address ?? null,
                    'short_address' => $this->getShortAddress($user->address),
                    'birth_date' => $user->birth_date ? $user->birth_date->format('d/m/Y') : null,
                    'age' => $this->getAge($user->birth_date),
                    'role' => $user->role,
                    'is_admin' => $user->is_admin ?? false,
                    'display_name' => $user->name . ' (ID: ' . $user->id . ')',
                    'avatar_url' => $this->getAvatarUrl($user->name),
                    'created_at' => $user->created_at->format('d/m/Y H:i')
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $formattedUsers,
                'count' => $formattedUsers->count(),
                'query' => $searchTerm,
                'role_filter' => $roleFilter
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro interno do servidor',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getUser($id)
    {
        try {
            $user = User::findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone ?? null,
                    'formatted_phone' => $this->formatPhone($user->phone),
                    'address' => $user->address ?? null,
                    'short_address' => $this->getShortAddress($user->address),
                    'birth_date' => $user->birth_date ? $user->birth_date->format('d/m/Y') : null,
                    'age' => $this->getAge($user->birth_date),
                    'role' => $user->role,
                    'is_admin' => $user->is_admin ?? false,
                    'display_name' => $user->name . ' (ID: ' . $user->id . ')',
                    'avatar_url' => $this->getAvatarUrl($user->name),
                    'created_at' => $user->created_at->format('d/m/Y H:i')
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Usuário não encontrado'
            ], 404);
        }
    }

    private function formatPhone($phone)
    {
        if (!$phone) return null;
        
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        if (strlen($phone) == 11) {
            return '(' . substr($phone, 0, 2) . ') ' . 
                   substr($phone, 2, 5) . '-' . 
                   substr($phone, 7);
        } elseif (strlen($phone) == 10) {
            return '(' . substr($phone, 0, 2) . ') ' . 
                   substr($phone, 2, 4) . '-' . 
                   substr($phone, 6);
        }
        
        return $phone;
    }

    private function getShortAddress($address)
    {
        if (!$address) return null;
        
        return strlen($address) > 50 
            ? substr($address, 0, 47) . '...'
            : $address;
    }

    private function getAge($birthDate)
    {
        if (!$birthDate) return null;
        
        try {
            return $birthDate->age;
        } catch (\Exception $e) {
            return null;
        }
    }

    private function getAvatarUrl($name)
    {
        return "https://ui-avatars.com/api/?name=" . urlencode($name) . 
               "&background=667eea&color=fff&size=128";
    }
}