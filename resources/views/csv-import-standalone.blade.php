<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Importar Produtos CSV</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h4 class="mb-0">📦 Importar Produtos via CSV</h4>
                    </div>
                    <div class="card-body">
                        @if(session('success'))
                            <div class="alert alert-success">
                                <strong>✅ Sucesso!</strong> {{ session('success') }}
                            </div>
                        @endif

                        @if(session('error'))
                            <div class="alert alert-danger">
                                <strong>❌ Erro!</strong> {{ session('error') }}
                            </div>
                        @endif

                        <div class="mb-4">
                            <h5>📋 Instruções:</h5>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item">📄 Baixe o modelo CSV clicando no botão abaixo</li>
                                <li class="list-group-item">✏️ Preencha os dados dos produtos</li>
                                <li class="list-group-item">⚠️ Campos obrigatórios: <strong>NomeProduto, Preco</strong></li>
                                <li class="list-group-item">💰 Use ponto ou vírgula para decimais (ex: 45.90 ou 45,90)</li>
                                <li class="list-group-item">🔄 Produtos existentes serão atualizados pelo código</li>
                            </ul>
                        </div>

                        <!-- Navegação entre importações -->
                        <div class="mb-3">
                            <a href="{{ route('csv.template') }}" class="btn btn-info">
                                📄 Baixar Modelo Produtos
                            </a>
                            <a href="{{ route('users.csv.form') }}" class="btn btn-secondary">
                                👥 Importar Usuários
                            </a>
                        </div>

                        <form action="{{ route('csv.import') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="mb-3">
                                <label for="file" class="form-label">📁 Arquivo CSV:</label>
                                <input type="file" class="form-control" id="file" name="file" accept=".csv,.txt" required>
                                <div class="form-text">Máximo 10MB. Formatos: CSV, TXT</div>
                            </div>

                            <button type="submit" class="btn btn-success btn-lg">
                                🚀 Importar Produtos
                            </button>
                        </form>

                        <!-- Informações adicionais -->
                        <div class="mt-4">
                            <h6>📊 Campos do CSV:</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <small class="text-muted">
                                        • Codigo<br>
                                        • NomeProduto ⚠️<br>
                                        • Cod_Categoria<br>
                                        • Marca<br>
                                        • Modelo
                                    </small>
                                </div>
                                <div class="col-md-6">
                                    <small class="text-muted">
                                        • Estado<br>
                                        • Cor<br>
                                        • Tamanho<br>
                                        • Preco ⚠️
                                    </small>
                                </div>
                            </div>
                            <small class="text-danger">⚠️ = Campos obrigatórios</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>