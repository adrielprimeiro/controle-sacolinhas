<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CsvController extends Controller
{
    public function showUploadForm()
    {
        return view('admin.csv.upload');
    }

    public function import(Request $request)
    {
        // Validação simples
        if (!$request->hasFile('file')) {
            return back()->with('error', 'Selecione um arquivo!');
        }

        $file = $request->file('file');
        
        if (!in_array($file->getClientOriginalExtension(), ['csv', 'txt'])) {
            return back()->with('error', 'Arquivo deve ser CSV!');
        }

        try {
            // Ler arquivo
            $csvData = file_get_contents($file->getRealPath());
            
            // Converter encoding
            if (!mb_check_encoding($csvData, 'UTF-8')) {
                $csvData = mb_convert_encoding($csvData, 'UTF-8', 'auto');
            }
            
            // Dividir em linhas
            $lines = explode("\n", $csvData);
            $lines = array_filter(array_map('trim', $lines));
            
            if (count($lines) < 2) {
                return back()->with('error', 'Arquivo deve ter pelo menos 2 linhas!');
            }

            // Detectar separador
            $firstLine = $lines[0];
            $separator = (substr_count($firstLine, ';') > substr_count($firstLine, ',')) ? ';' : ',';
            
            // Processar cabeçalho
            $header = str_getcsv($lines[0], $separator);
            $header = array_map('trim', $header);
            
            // Remover cabeçalho
            array_shift($lines);
            
            $imported = 0;
            $updated = 0;
            $errors = [];

            foreach ($lines as $index => $line) {
                if (empty($line)) continue;
                
                $data = str_getcsv($line, $separator);
                
                if (count($data) !== count($header)) {
                    $errors[] = "Linha " . ($index + 2) . ": colunas incorretas";
                    continue;
                }
                
                $row = array_combine($header, array_map('trim', $data));
                
                // Validação básica
                if (empty($row['codigo']) || empty($row['preco'])) {
                    $errors[] = "Linha " . ($index + 2) . ": código ou preço vazio";
                    continue;
                }

                try {
                    $itemData = [
                        'nome_do_produto' => $row['nome_do_produto'] ?? $row['nome'] ?? 'Produto',
                        'preco' => (float) str_replace(',', '.', $row['preco']),
                        'marca' => $row['marca'] ?? '',
                        'cor' => $row['cor'] ?? '',
                        'tamanho' => $row['tamanho'] ?? '',
                        'categoria' => $row['categoria'] ?? '',
                        'descricao' => $row['descricao'] ?? '',
                        'estoque' => (int) ($row['estoque'] ?? 0),
                        'ativo' => !empty($row['ativo']) ? (bool) $row['ativo'] : true,
                        'updated_at' => date('Y-m-d H:i:s'),
                    ];

                    // Verificar se existe
                    $exists = DB::table('items')->where('codigo', $row['codigo'])->exists();
                    
                    if ($exists) {
                        DB::table('items')->where('codigo', $row['codigo'])->update($itemData);
                        $updated++;
                    } else {
                        $itemData['codigo'] = $row['codigo'];
                        $itemData['created_at'] = date('Y-m-d H:i:s');
                        DB::table('items')->insert($itemData);
                        $imported++;
                    }
                    
                } catch (\Exception $e) {
                    $errors[] = "Linha " . ($index + 2) . ": " . $e->getMessage();
                }
            }

            $message = "✅ Importação concluída! {$imported} criados, {$updated} atualizados.";
            if (!empty($errors)) {
                $message .= " ⚠️ " . count($errors) . " erro(s).";
            }

            return back()->with('success', $message);

        } catch (\Exception $e) {
            return back()->with('error', 'Erro: ' . $e->getMessage());
        }
    }

    public function downloadTemplate()
    {
        $filename = 'modelo_produtos.csv';
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        echo chr(0xEF).chr(0xBB).chr(0xBF); // BOM UTF-8
        
        $output = fopen('php://output', 'w');
        
        // Cabeçalho
        fputcsv($output, [
            'codigo',
            'nome_do_produto',
            'preco',
            'marca',
            'cor',
            'tamanho',
            'categoria',
            'descricao',
            'estoque',
            'ativo'
        ], ';');
        
        // Exemplos
        fputcsv($output, ['PROD001', 'Camiseta Básica', '29.90', 'Nike', 'Azul', 'M', 'Roupas', 'Camiseta algodão', '10', '1'], ';');
        fputcsv($output, ['PROD002', 'Calça Jeans', '89.90', 'Levis', 'Azul', 'G', 'Roupas', 'Calça jeans', '5', '1'], ';');
        
        fclose($output);
        exit;
    }
}