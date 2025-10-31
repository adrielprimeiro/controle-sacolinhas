<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Faker\Factory as Faker;

class UserSeeder extends Seeder
{
    public function run()
    {
        $faker = Faker::create('pt_BR');

        // Criar usuários de exemplo
        $users = [
            [
                'name' => 'João Silva',
                'username' => 'joao.silva',
                'email' => 'joao@example.com',
                'phone' => '(11) 99999-1111'
            ],
            [
                'name' => 'Maria Santos',
                'username' => 'maria.santos',
                'email' => 'maria@example.com',
                'phone' => '(11) 99999-2222'
            ],
            [
                'name' => 'Pedro Oliveira',
                'username' => 'pedro.oliveira',
                'email' => 'pedro@example.com',
                'phone' => '(11) 99999-3333'
            ]
        ];

        foreach ($users as $userData) {
            User::create($userData);
        }

        // Criar usuários aleatórios
        for ($i = 0; $i < 50; $i++) {
            $name = $faker->name;
            $username = strtolower(str_replace(' ', '.', $name)) . $faker->numberBetween(1, 999);
            
            User::create([
                'name' => $name,
                'username' => $username,
                'email' => $faker->unique()->email,
                'phone' => $faker->phoneNumber,
                'status' => $faker->randomElement(['ativo', 'ativo', 'ativo', 'inativo']) // 75% ativos
            ]);
        }
    }
}