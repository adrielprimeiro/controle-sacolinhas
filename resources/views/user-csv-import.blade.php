<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Importar Usuários CSV</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">👥 Importar Usuários via CSV</h4>
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
								<li class="list-group-item">✏️ Preencha os dados dos usuários</li>
								<li class="list-group-item">⚠️ Campos obrigatórios: <strong>Nome, Email</strong></li>
								<li class="list-group-item">👤 Roles válidos: <code>admin</code>, <code>user</code></li>
								<li class="list-group-item">🏢 Tipos de cliente: <code>pessoa_fisica</code>, <code>pessoa_juridica</code></li>
								<li class="list-group-item">👫 Sexo: <code>M</code>, <code>F</code></li>
								<li class="list-group-item">📅 Data de nascimento: formato <code>dd/mm/aaaa</code></li>
								<li class="list-group-item">🔑 Se a senha não for informada, será usada "senha123"</li>
							</ul>
						</div>

                        <!-- Navegação entre importações -->
                        <div class="mb-3">
                            <a href="{{ route('users.csv.template') }}" class="btn btn-info">
                                📄 Baixar Modelo Usuários
                            </a>
                            <a href="{{ route('csv.form') }}" class="btn btn-secondary">
                                📦 Importar Produtos
                            </a>
                        </div>

                        <form action="{{ route('users.csv.import') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="mb-3">
                                <label for="file" class="form-label">📁 Arquivo CSV:</label>
                                <input type="file" class="form-control" id="file" name="file" accept=".csv,.txt" required>
                                <div class="form-text">Máximo 10MB. Formatos: CSV, TXT</div>
                            </div>

                            <button type="submit" class="btn btn-success btn-lg">
                                🚀 Importar Usuários
                            </button>
                        </form>

                        <!-- Informações adicionais -->
						<div class="mt-4">
							<h6>📊 Campos do CSV:</h6>
							<div class="row">
								<div class="col-md-6">
									<small class="text-muted">
										• Nome ⚠️<br>
										• Email ⚠️<br>
										• Senha<br>
										• Telefone<br>
										• Endereco<br>
										• Cidade<br>
										• Estado
									</small>
								</div>
								<div class="col-md-6">
									<small class="text-muted">
										• CEP<br>
										• CPF<br>
										• RG<br>
										• DataNascimento<br>
										• Sexo (M/F)<br>
										• TipoCliente<br>
										• Role
									</small>
								</div>
							</div>
							<small class="text-danger">⚠️ = Campos obrigatórios</small>
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