<!DOCTYPE html>
<html>
<head>
    <title>Importar CSV</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h4>📊 Importar CSV - Produtos</h4>
                    </div>

                    <div class="card-body">
                        @if (session('success'))
                            <div class="alert alert-success">{{ session('success') }}</div>
                        @endif

                        @if (session('error'))
                            <div class="alert alert-danger">{{ session('error') }}</div>
                        @endif

                        <form method="POST" action="{{ route('csv.import') }}" enctype="multipart/form-data">
                            @csrf
                            
                            <div class="mb-3">
                                <label for="file" class="form-label">📁 Arquivo CSV:</label>
                                <input type="file" class="form-control" name="file" accept=".csv,.txt" required>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">📤 Importar</button>
                                <a href="{{ route('csv.template') }}" class="btn btn-outline-secondary">📥 Baixar Modelo</a>
                            </div>
                        </form>

                        <hr>
                        
                        <div class="alert alert-info">
                            <h5>📋 Formato:</h5>
                            <ul>
                                <li><strong>Separador:</strong> ; (ponto e vírgula)</li>
                                <li><strong>Obrigatório:</strong> codigo, preco</li>
                                <li><strong>Opcional:</strong> nome_do_produto, marca, cor, etc.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>