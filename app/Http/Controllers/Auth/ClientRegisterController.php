<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class ClientRegisterController extends Controller
{
    /**
     * Exibe o formulário de registro de cliente
     */
    public function showRegistrationForm()
    {
        return view('auth.client-register');
    }

    /**
     * Processa o registro do cliente
     */
    public function register(Request $request)
    {
        // Validação dos dados
        $validator = $this->validator($request->all());

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Criar o usuário
        $user = $this->create($request->all());

        // Fazer login automático
        Auth::login($user);

        // Redirecionar para dashboard
        return redirect()->route('dashboard')->with('success', 'Cadastro realizado com sucesso!');
    }

    /**
     * Validar os dados do formulário
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'phone' => ['required', 'string', 'max:20'],
            'address' => ['required', 'string', 'max:500'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'name.required' => 'O nome é obrigatório.',
            'email.required' => 'O email é obrigatório.',
            'email.unique' => 'Este email já está cadastrado.',
            'phone.required' => 'O telefone é obrigatório.',
            'address.required' => 'O endereço é obrigatório.',
            'password.min' => 'A senha deve ter pelo menos 8 caracteres.',
            'password.confirmed' => 'A confirmação da senha não confere.',
        ]);
    }

    /**
     * Criar um novo usuário
     */
    protected function create(array $data)
    {
        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'address' => $data['address'],
            'birth_date' => $data['birth_date'] ?? null,
            'role' => 'client',
            'password' => Hash::make($data['password']),
        ]);
    }
}