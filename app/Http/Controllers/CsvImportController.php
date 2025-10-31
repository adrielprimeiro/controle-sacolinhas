<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Exception;
use DateTime;

class CsvImportController extends Controller
{
    // ==================== MÉTODOS PARA PRODUTOS ====================
    
    public function showForm()
    {
        return view('csv-import-standalone');
    }

    public function import(Request $request)
    {
        // Aumentar tempo limite
        set_time_limit(300);
        ini_set('memory_limit', '512M');
        
        try {
            // Validar arquivo
            $request->validate([
                'file' => 'required|file|mimes:csv,txt|max:10240'
            ]);

            $file = $request->file('file');
            $csvData = file_get_contents($file->getPathname());

            // Converter encoding se necessário
            if (!mb_check_encoding($csvData, 'UTF-8')) {
                $csvData = mb_convert_encoding($csvData, 'UTF-8', 'auto');
            }

            // Processar linhas
            $lines = explode("\n", $csvData);
            $lines = array_filter(array_map('trim', $lines));

            if (count($lines) < 2) {
                throw new Exception('Arquivo deve ter pelo menos 2 linhas (cabeçalho + dados)');
            }

            // Detectar separador
            $separator = (substr_count($lines[0], ';') > substr_count($lines[0], ',')) ? ';' : ',';
            
            // Processar cabeçalho
            $header = str_getcsv($lines[0], $separator);
            $header = array_map('trim', $header);
            
            // Remover cabeçalho
            array_shift($lines);
            
            $imported = 0;
            $updated = 0;
            $errors = [];
            $batch = [];
            $batchSize = 50;

            foreach ($lines as $index => $line) {
                if (empty($line)) continue;

                $data = str_getcsv($line, $separator);
                
                if (count($data) !== count($header)) {
                    $errors[] = "Linha " . ($index + 2) . ": número incorreto de colunas";
                    continue;
                }

                $row = array_combine($header, array_map('trim', $data));

                // Validar campos obrigatórios
                if (empty($row['NomeProduto']) || empty($row['Preco'])) {
                    $errors[] = "Linha " . ($index + 2) . ": Nome do produto ou preço vazio";
                    continue;
                }

                try {
                    // Mapear campos CORRETAMENTE
                    $codigo = $row['Codigo'] ?? '';
                    $nome_produto = $row['NomeProduto'];
                    $preco = (float) str_replace(',', '.', $row['Preco']);
                    $codigo_categoria = $row['Cod_Categoria'] ?? $row['Cod. Categoria'] ?? '';
                    $marca = $row['Marca'] ?? '';
                    $modelo = $row['Modelo'] ?? '';
                    $estado = $row['Estado'] ?? 'novo';
                    $cor = $row['Cor'] ?? '';
                    $tamanho = $row['Tamanho'] ?? '';

                    // Verificar se produto já existe (por código)
                    $exists = DB::table('items')
                        ->where('codigo', $codigo)
                        ->first();

                    $data_to_save = [
                        'codigo' => $codigo,
                        'nome_do_produto' => $nome_produto,
                        'descricao' => $estado, // Estado vai para descrição
                        'preco' => $preco,
                        'codigo_da_categoria' => $codigo_categoria,
                        'marca' => $marca,
                        'modelo' => $modelo,
                        'estado' => $estado,
                        'cor' => $cor,
                        'tamanho' => $tamanho,
                        'status' => 'disponivel',
                        'updated_at' => now()
                    ];

                    if ($exists) {
                        // Atualizar produto existente
                        DB::table('items')
                            ->where('codigo', $codigo)
                            ->update($data_to_save);
                        $updated++;
                    } else {
                        // Inserir novo produto
                        $data_to_save['created_at'] = now();
                        DB::table('items')->insert($data_to_save);
                        $imported++;
                    }

                } catch (Exception $e) {
                    $errors[] = "Linha " . ($index + 2) . ": " . $e->getMessage();
                }
            }

            $message = "Importação concluída! {$imported} novos produtos, {$updated} atualizados.";
            if (!empty($errors)) {
                $message .= " " . count($errors) . " erro(s): " . implode(', ', array_slice($errors, 0, 3));
            }

            return redirect()->route('csv.form')->with('success', $message);

        } catch (Exception $e) {
            return redirect()->route('csv.form')->with('error', $e->getMessage());
        }
    }

    public function downloadTemplate()
    {
        $filename = 'modelo_produtos.csv';
        
        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() {
            $file = fopen('php://output', 'w');
            
            // BOM UTF-8
            fwrite($file, chr(0xEF) . chr(0xBB) . chr(0xBF));
            
            // Cabeçalho - apenas campos que serão importados
            fputcsv($file, [
                'Codigo', 'NomeProduto', 'Cod_Categoria', 'Marca', 'Modelo', 
                'Estado', 'Cor', 'Tamanho', 'Preco'
            ], ';');
            
            // Exemplos
            fputcsv($file, ['A001', 'Camiseta Polo', 'CAT01', 'Lacoste', 'Polo Classic', 'Novo', 'Branca', 'M', '45.90'], ';');
            fputcsv($file, ['A002', 'Tênis Esportivo', 'CAT02', 'Nike', 'Air Max', 'Seminovo', 'Preto', '42', '180.00'], ';');
            fputcsv($file, ['A003', 'Vestido Floral', 'CAT03', 'Farm', 'Verão 2024', 'Usado', 'Rosa', 'P', '65.50'], ';');
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    // ==================== MÉTODOS PARA USUÁRIOS ====================

    public function showUserForm()
    {
        return view('user-csv-import');
    }

public function importUsers(Request $request)
{
    // Aumentar tempo limite
    set_time_limit(300);
    ini_set('memory_limit', '512M');
    
    try {
        // Validar arquivo
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:10240'
        ]);

        $file = $request->file('file');
        $csvData = file_get_contents($file->getPathname());

        // Converter encoding se necessário
        if (!mb_check_encoding($csvData, 'UTF-8')) {
            $csvData = mb_convert_encoding($csvData, 'UTF-8', 'auto');
        }

        // Processar linhas
        $lines = explode("\n", $csvData);
        $lines = array_filter(array_map('trim', $lines));

        if (count($lines) < 2) {
            throw new Exception('Arquivo deve ter pelo menos 2 linhas (cabeçalho + dados)');
        }

        // Detectar separador
        $separator = (substr_count($lines[0], ';') > substr_count($lines[0], ',')) ? ';' : ',';
        
        // Processar cabeçalho
        $header = str_getcsv($lines[0], $separator);
        $header = array_map('trim', $header);
        
        // Remover cabeçalho
        array_shift($lines);
        
        $imported = 0;
        $updated = 0;
        $errors = [];

        DB::beginTransaction();

        foreach ($lines as $index => $line) {
            if (empty($line)) continue;

            $data = str_getcsv($line, $separator);
            
            if (count($data) !== count($header)) {
                $errors[] = "Linha " . ($index + 2) . ": número incorreto de colunas";
                continue;
            }

            $row = array_combine($header, array_map('trim', $data));

            // Validar campos obrigatórios
            if (empty($row['Nome cliente']) || empty($row['E-mail'])) {
                $errors[] = "Linha " . ($index + 2) . ": Nome ou Email vazio";
                continue;
            }

            // Validar email
            if (!filter_var($row['E-mail'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Linha " . ($index + 2) . ": Email inválido";
                continue;
            }

            try {
                // Mapear campos para sua estrutura real
                $userData = [
					'codigo_cliente' => $row['Código cliente'],
                    'name' => $row['Nome cliente'],
                    'nome_cliente' => $row['Apelido'],
                    'email' => strtolower(trim($row['E-mail'])),
                    'phone' => $row['TelefonePrincipal'] ?? '',
                    'telefone_principal' => $row['TelefonePrincipal'] ?? '',
                    'telefone_2' => $row['Telefone2'] ?? '',
                    'address' => $row['Endereco'] ?? '',
                    'endereco' => $row['Endereco'] ?? '',
                    'numero_endereco' => $row['NumeroEndereco'] ?? '',
                    'complemento' => $row['Complemento'] ?? '',
                    'bairro' => $row['Bairro'] ?? '',
                    'cidade' => $row['Cidade'] ?? '',
                    'estado' => $row['Estado'] ?? '',
                    'cep' => $this->cleanCep($row['CEP'] ?? ''),
                    'cpf' => $this->cleanCpf($row['CPF'] ?? ''),
                    'rg' => $row['RG'] ?? '',
                    'data_nascimento' => $this->parseDate($row['DataNascimento'] ?? ''),
                    'birth_date' => $this->parseDate($row['DataNascimento'] ?? ''),
                    'sexo' => $row['Sexo'] ?? '',
                    'tipo_cliente' => $row['TipoCliente'] ?? 'pessoa_fisica',
                    'role' => $row['Role'] ?? 'client',
                    'is_admin' => ($row['Role'] ?? 'user') === 'admin' ? 1 : 0,
                    'data_cadastro' => $this->parseDate($row['DataCadastro'] ?? now()),
                    'ultima_compra' => $this->parseDate($row['UltimaCompra'] ?? null),
                    'ultima_visita' => $this->parseDate($row['UltimaVisita'] ?? null),
                    'total_pedidos' => (int) ($row['TotalPedidos'] ?? 0),
                    'observacao_cliente' => $row['ObservacaoCliente'] ?? '',
                    'bloqueado' => (int) ($row['Bloqueado'] ?? 0),
                    'pais' => $row['Pais'] ?? 'Brasil',
                    'updated_at' => now()
                ];

                // Verificar se usuário já existe
                $exists = DB::table('users')
                    ->where('email', $userData['email'])
                    ->first();

                if ($exists) {
                    // Atualizar usuário existente (sem alterar senha)
                    DB::table('users')
                        ->where('email', $userData['email'])
                        ->update($userData);
                    $updated++;
                } else {
                    // Inserir novo usuário
                    $userData['password'] = Hash::make($row['Senha'] ?? 'senha123');
                    $userData['email_verified_at'] = now();
                    $userData['created_at'] = now();
                   
                    DB::table('users')->insert($userData);
                    $imported++;
                }

            } catch (Exception $e) {
                $errors[] = "Linha " . ($index + 2) . ": " . $e->getMessage();
            }
        }

        DB::commit();

        $message = "Importação de usuários concluída! {$imported} novos usuários, {$updated} atualizados.";
        if (!empty($errors)) {
            $message .= " " . count($errors) . " erro(s): " . implode(', ', array_slice($errors, 0, 3));
        }

        return redirect()->route('users.csv.form')->with('success', $message);

    } catch (Exception $e) {
        DB::rollBack();
        return redirect()->route('users.csv.form')->with('error', $e->getMessage());
    }
}


	// Métodos auxiliares - adicione no final da classe
	private function cleanCpf($cpf)
	{
		return preg_replace('/[^0-9]/', '', $cpf);
	}

	private function cleanCep($cep)
	{
		return preg_replace('/[^0-9]/', '', $cep);
	}

	private function parseDate($date)
	{
		if (empty($date)) return null;
		
		try {
			// Tentar diferentes formatos
			$formats = ['d/m/Y', 'Y-m-d', 'd-m-Y', 'm/d/Y'];
			
			foreach ($formats as $format) {
				$parsed = DateTime::createFromFormat($format, $date);
				if ($parsed !== false) {
					return $parsed->format('Y-m-d');
				}
			}
			
			return null;
		} catch (Exception $e) {
			return null;
		}
	}

	private function generateCodigoCliente()
	{
		// Gerar código único para cliente
		do {
			$codigo = 'CLI' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
			$exists = DB::table('users')->where('codigo_cliente', $codigo)->exists();
		} while ($exists);
		
		return $codigo;
	}

	public function downloadUserTemplate()
	{
		$filename = 'modelo_usuarios.csv';
		
		$headers = [
			'Content-Type' => 'text/csv; charset=utf-8',
			'Content-Disposition' => 'attachment; filename="' . $filename . '"',
		];

		$callback = function() {
			$file = fopen('php://output', 'w');
			
			// BOM UTF-8 para evitar problemas de encoding
			fwrite($file, chr(0xEF) . chr(0xBB) . chr(0xBF));
			
			// Cabeçalho atualizado com todos os campos
			fputcsv($file, [
				'Código cliente', 'Nome', 'Email', 'Senha', 'TelefonePrincipal', 'Telefone2', 'Endereco', 
				'NumeroEndereco', 'Complemento', 'Bairro', 'Cidade', 'Estado', 'CEP', 
				'CPF', 'RG', 'DataNascimento', 'Sexo', 'TipoCliente', 'Role', 
				'DataCadastro', 'UltimaCompra', 'UltimaVisita', 'TotalPedidos', 
				'ObservacaoCliente', 'Bloqueado', 'Pais'
			], ';');
			
			// Exemplos de dados
			fputcsv($file, [
				'João Silva', 'joao@email.com', 'senha123', '(11) 99999-9999', '(11) 88888-8888', 
				'Rua das Flores, 123', '123', 'Apto 45', 'Centro', 'São Paulo', 'SP', '01234-567', 
				'123.456.789-00', '12.345.678-9', '15/03/1990', 'M', 'pessoa_fisica', 'user', 
				'01/01/2023', '10/10/2023', '15/10/2023', '5', 'Cliente VIP', '0', 'Brasil'
			], ';');
			
			fputcsv($file, [
				'Maria Santos', 'maria@email.com', 'senha456', '(11) 77777-7777', '', 
				'Av. Principal, 456', '456', '', 'Jardins', 'Rio de Janeiro', 'RJ', '20000-000', 
				'987.654.321-00', '98.765.432-1', '22/07/1985', 'F', 'pessoa_fisica', 'admin', 
				'05/02/2023', '12/10/2023', '15/10/2023', '10', 'Cliente com alto volume de compras', '1', 'Brasil'
			], ';');
			
			fputcsv($file, [
				'Pedro Costa', 'pedro@email.com', 'senha789', '(11) 66666-6666', '(11) 55555-5555', 
				'Rua Comercial, 789', '789', 'Loja 2', 'Industrial', 'Belo Horizonte', 'MG', '30000-000', 
				'456.789.123-00', '45.678.912-3', '10/12/1988', 'M', 'pessoa_juridica', 'user', 
				'15/03/2023', '08/10/2023', '12/10/2023', '3', 'Cliente com histórico de atrasos', '0', 'Brasil'
			], ';');
			
			fclose($file);
		};

		return response()->stream($callback, 200, $headers);
	}
}