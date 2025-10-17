<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Exception;

class UserCsvImportController extends Controller
{
    public function showForm()
    {
        return view('user-csv-import');
    }

    public function import(Request $request)
    {
        // Configurações de performance
        set_time_limit(300);
        ini_set('memory_limit', '512M');
        
        try {
            // Validação do arquivo
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

                // Validar dados do usuário
                $validationResult = $this->validateUserData($row, $index + 2);
                if (!$validationResult['valid']) {
                    $errors = array_merge($errors, $validationResult['errors']);
                    continue;
                }

                try {
                    // Mapear campos
                    $nome = $row['Nome'];
                    $email = strtolower(trim($row['Email']));
                    $senha = $row['Senha'] ?? 'senha123'; // Senha padrão se não informada
                    $tipo = $row['Tipo'] ?? 'user';
                    $telefone = $row['Telefone'] ?? '';
                    $cpf = $this->cleanCpf($row['CPF'] ?? '');
                    $endereco = $row['Endereco'] ?? '';
                    $cidade = $row['Cidade'] ?? '';
                    $estado = $row['Estado'] ?? '';
                    $cep = $this->cleanCep($row['CEP'] ?? '');
                    $status = $row['Status'] ?? 'ativo';

                    // Verificar se usuário já existe
                    $exists = DB::table('users')
                        ->where('email', $email)
                        ->first();

                    $userData = [
                        'name' => $nome,
                        'email' => $email,
                        'tipo' => $tipo,
                        'telefone' => $telefone,
                        'cpf' => $cpf,
                        'endereco' => $endereco,
                        'cidade' => $cidade,
                        'estado' => $estado,
                        'cep' => $cep,
                        'status' => $status,
                        'updated_at' => now()
                    ];

                    if ($exists) {
                        // Atualizar usuário existente (sem alterar senha)
                        DB::table('users')
                            ->where('email', $email)
                            ->update($userData);
                        $updated++;
                    } else {
                        // Inserir novo usuário
                        $userData['password'] = Hash::make($senha);
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

            $message = "Importação concluída! {$imported} novos usuários, {$updated} atualizados.";
            if (!empty($errors)) {
                $message .= " " . count($errors) . " erro(s): " . implode(', ', array_slice($errors, 0, 3));
                if (count($errors) > 3) {
                    $message .= " e mais " . (count($errors) - 3) . " erro(s).";
                }
            }

            return redirect()->route('users.csv.form')->with('success', $message);

        } catch (Exception $e) {
            DB::rollBack();
            return redirect()->route('users.csv.form')->with('error', $e->getMessage());
        }
    }

    private function validateUserData($row, $lineNumber)
    {
        $errors = [];
        
        // Validar campos obrigatórios
        if (empty($row['Nome'])) {
            $errors[] = "Linha {$lineNumber}: Nome é obrigatório";
        }
        
        if (empty($row['Email'])) {
            $errors[] = "Linha {$lineNumber}: Email é obrigatório";
        } elseif (!filter_var($row['Email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Linha {$lineNumber}: Email inválido";
        }
        
        // Validar tipo de usuário
        $tiposValidos = ['admin', 'user', 'moderator'];
        if (!empty($row['Tipo']) && !in_array($row['Tipo'], $tiposValidos)) {
            $errors[] = "Linha {$lineNumber}: Tipo deve ser: " . implode(', ', $tiposValidos);
        }
        
        // Validar CPF se informado
        if (!empty($row['CPF']) && !$this->isValidCpf($row['CPF'])) {
            $errors[] = "Linha {$lineNumber}: CPF inválido";
        }
        
        // Validar status
        $statusValidos = ['ativo', 'inativo', 'suspenso'];
        if (!empty($row['Status']) && !in_array($row['Status'], $statusValidos)) {
            $errors[] = "Linha {$lineNumber}: Status deve ser: " . implode(', ', $statusValidos);
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    private function cleanCpf($cpf)
    {
        return preg_replace('/[^0-9]/', '', $cpf);
    }

    private function cleanCep($cep)
    {
        return preg_replace('/[^0-9]/', '', $cep);
    }

    private function isValidCpf($cpf)
    {
        $cpf = $this->cleanCpf($cpf);
        
        if (strlen($cpf) != 11) return false;
        if (preg_match('/(\d)\1{10}/', $cpf)) return false;
        
        // Validação dos dígitos verificadores
        for ($t = 9; $t < 11; $t++) {
            for ($d = 0, $c = 0; $c < $t; $c++) {
                $d += $cpf[$c] * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if ($cpf[$c] != $d) return false;
        }
        
        return true;
    }

    public function downloadTemplate()
    {
        $filename = 'modelo_usuarios.csv';
        
        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() {
            $file = fopen('php://output', 'w');
            
            // BOM UTF-8
            fwrite($file, chr(0xEF) . chr(0xBB) . chr(0xBF));
            
            // Cabeçalho
            fputcsv($file, [
                'Nome', 'Email', 'Senha', 'Tipo', 'Telefone', 'CPF', 
                'Endereco', 'Cidade', 'Estado', 'CEP', 'Status'
            ], ';');
            
            // Exemplos
            fputcsv($file, [
                'João Silva', 'joao@email.com', 'senha123', 'user', 
                '(11) 99999-9999', '123.456.789-00', 'Rua das Flores, 123', 
                'São Paulo', 'SP', '01234-567', 'ativo'
            ], ';');
            
            fputcsv($file, [
                'Maria Santos', 'maria@email.com', 'senha456', 'admin', 
                '(11) 88888-8888', '987.654.321-00', 'Av. Principal, 456', 
                'Rio de Janeiro', 'RJ', '20000-000', 'ativo'
            ], ';');
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}