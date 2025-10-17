<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function search(Request $request)
    {
        $query = $request->get('q');
        $role = $request->get('role'); // client, admin, etc.
        
        if (strlen($query) < 2) {
            return response()->json([
                'success' => false,
                'message' => 'Query muito curta'
            ]);
        }
        
        $usersQuery = User::where('name', 'like', "%{$query}%")
                         ->orWhere('email', 'like', "%{$query}%")
                         ->orWhere('id', 'like', "%{$query}%");
        
        // Filtrar por role se especificado
        if ($role) {
            $usersQuery->where('role', $role);
        }
        
        $users = $usersQuery->limit(10)->get();
        
        // Formatar dados para o component
        $formattedUsers = $users->map(function($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar_url' => $user->avatar ? asset('storage/' . $user->avatar) : $this->getDefaultAvatar($user->name),
                'role' => $user->role ?? 'client'
            ];
        });
        
        return response()->json([
            'success' => true,
            'data' => $formattedUsers
        ]);
    }
    
    /**
     * Gerar avatar padrão baseado no nome
     */
    private function getDefaultAvatar($name)
    {
        $initials = strtoupper(substr($name, 0, 1));
        return "https://ui-avatars.com/api/?name=" . urlencode($name) . "&background=667eea&color=fff&size=128";
    }
}