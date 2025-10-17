<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Gerenciar Sacolinhas</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

    <style>
        .sidebar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
        }
        .bag-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
        }
        /* Estilo para desativar a interação */
        .card-disabled {
            pointer-events: none;
            opacity: 0.6;
            background-color: #f7f7f7;
        }
        .live-status {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8em;
            font-weight: bold;
        }
        .live-ativa {
            background-color: #d4edda;
            color: #155724;
        }
        .live-encerrada {
            background-color: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 sidebar text-white p-0">
                <div class="p-3">
                    <h4>Admin</h4>
                    <hr>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link text-white" href="{{ route('items.index') }}">
                                <i class="fas fa-box"></i> Itens
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white active" href="{{ route('bags.index') }}">
                                <i class="fas fa-shopping-bag"></i> Sacolinhas
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="{{ route('dashboard') }}">
                                <i class="fas fa-home"></i> Dashboard
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 p-4">
                <!-- Header -->
                <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
                    <!-- Título -->
                    <h2>Gerenciar Live</h2>

                    <!-- Campos de Seleção -->
                    <div class="d-flex align-items-center flex-wrap gap-3">
                        <!-- Combo Box -->
                        <div>
                            <label for="live-type" class="form-label">Tipo de Live</label>
                            <select id="live-type" name="live_type" class="form-select">
                                <option value="loja-aberta">Live Loja Aberta</option>
                                <option value="leilao">Live Leilão</option>
                                <option value="precinho">Live do Precinho</option>
                            </select>
                        </div>

                        <!-- Checkboxes -->
                        <div>
                            <label class="form-label">Plataformas</label>
                            <div class="form-check">
                                <input class="form-check-input platform-checkbox" type="checkbox" id="instagram" name="platforms[]" value="instagram">
                                <label class="form-check-label" for="instagram">Instagram</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input platform-checkbox" type="checkbox" id="tiktok" name="platforms[]" value="tiktok">
                                <label class="form-check-label" for="tiktok">Tiktok</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input platform-checkbox" type="checkbox" id="youtube" name="platforms[]" value="youtube">
                                <label class="form-check-label" for="youtube">YouTube</label>
                            </div>
                        </div>
                    </div>

                    <!-- Botão -->
                    <button type="button" id="toggle-live" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Nova Live
                    </button>
                </div>

                <!-- Alerts -->
                <div id="alert-container">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif
                </div>

                <!-- Lives Ativas -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-broadcast-tower text-danger"></i>
                            Lives de Hoje
                        </h5>
                    </div>
                    <div class="card-body">
                        <div id="lives-container">
                            <div class="text-center">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Carregando...</span>
                                </div>
                                <p class="mt-2 text-muted">Carregando lives...</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filtros -->
                <div class="card mb-4 card-disabled" id="filter-card">
                    <div class="card-body">
                        <form method="GET" action="{{ route('bags.index') }}">
                            <div class="row align-items-end">
                                <!-- Campo Cliente -->
                                <div class="col-md-5">
                                    <label for="search-client" class="form-label">Cliente</label>
                                    <input type="text"
                                           class="form-control"
                                           name="search_client"
                                           id="search-client"
                                           placeholder="Buscar por cliente..."
                                           value="{{ request('search_client') }}">
                                </div>

                                <!-- Campo Item -->
                                <div class="col-md-5">
                                    <label for="search-item" class="form-label">Item</label>
                                    <input type="text"
                                           class="form-control"
                                           name="search_item"
                                           id="search-item"
                                           placeholder="Buscar por item..."
                                           value="{{ request('search_item') }}">
                                </div>

                                <!-- Botão -->
                                <div class="col-md-2 text-end">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-plus"></i> Inserir Item
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Lista de Sacolinhas -->
                <div class="card">
                    <div class="card-body">
                        <!-- Aqui você pode adicionar conteúdo da lista -->
                        <p class="text-muted">Conteúdo das sacolinhas será exibido aqui...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- JavaScript Atualizado -->
    <script>
        let liveAtiva = null;

        // Carregar lives ao inicializar a página
        document.addEventListener('DOMContentLoaded', function() {
            carregarLives();
        });

        // Função para carregar lives
        function carregarLives() {
            fetch('/lives', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                const container = document.getElementById('lives-container');
                container.innerHTML = '';

                if (data.success && data.lives && data.lives.length > 0) {
                    liveAtiva = data.lives[0]; // Assumindo que só há uma live por dia
                    const liveElement = criarElementoLive(liveAtiva);
                    container.appendChild(liveElement);
                } else {
                    liveAtiva = null;
                    container.innerHTML = `
                        <div class="text-center text-muted">
                            <i class="fas fa-broadcast-tower fa-3x mb-3 opacity-50"></i>
                            <p>Nenhuma live ativa no momento.</p>
                            <small>Clique em "Nova Live" para começar uma transmissão.</small>
                        </div>
                    `;
                }

                atualizarEstadoBotao();
            })
            .catch(error => {
                console.error('Erro ao carregar lives:', error);
                mostrarAlert('Erro ao carregar lives', 'danger');
                document.getElementById('lives-container').innerHTML = `
                    <div class="text-center text-danger">
                        <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                        <p>Erro ao carregar lives</p>
                    </div>
                `;
            });
        }

        // Função para criar elemento de live
        function criarElementoLive(live) {
            const div = document.createElement('div');
            div.className = 'border rounded p-3 mb-2 bg-light border-success';
            div.innerHTML = `
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-1">
                            <i class="fas fa-broadcast-tower text-danger"></i>
                            <strong>${live.tipo_live_formatado}</strong>
                        </h6>
                        <small class="text-muted">
                            <i class="fas fa-calendar"></i> ${live.data} às ${live.created_at}
                        </small>
                        <br>
                        <small class="text-info">
                            <i class="fas fa-share-alt"></i> 
                            Plataformas: ${live.plataformas.map(p => p.charAt(0).toUpperCase() + p.slice(1)).join(', ')}
                        </small>
                    </div>
                    <div>
                        <span class="badge bg-success fs-6">
                            <i class="fas fa-circle"></i> ATIVA
                        </span>
                        <button class="btn btn-sm btn-danger ms-2" onclick="encerrarLive(${live.id})" title="Encerrar Live">
                            <i class="fas fa-times"></i> Encerrar
                        </button>
                    </div>
                </div>
            `;
            return div;
        }

        // Função para mostrar alertas
        function mostrarAlert(message, type = 'success') {
            const alertContainer = document.getElementById('alert-container');
            const alert = document.createElement('div');
            alert.className = `alert alert-${type} alert-dismissible fade show`;
            alert.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'danger' ? 'exclamation-circle' : 'exclamation-triangle'}"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            alertContainer.appendChild(alert);

            // Remover alert após 5 segundos
            setTimeout(() => {
                if (alert.parentNode) {
                    alert.remove();
                }
            }, 5000);
        }

        // Função para atualizar estado do botão
        function atualizarEstadoBotao() {
            const button = document.getElementById('toggle-live');
            const card = document.getElementById('filter-card');

            if (liveAtiva) {
                button.classList.remove('btn-primary');
                button.classList.add('btn-danger');
                button.innerHTML = '<i class="fas fa-times"></i> Encerrar Live';
                card.classList.remove('card-disabled');
            } else {
                button.classList.remove('btn-danger');
                button.classList.add('btn-primary');
                button.innerHTML = '<i class="fas fa-plus"></i> Nova Live';
                card.classList.add('card-disabled');
            }
        }

        // Event listener para o botão toggle
        document.getElementById('toggle-live').addEventListener('click', function() {
            if (liveAtiva) {
                encerrarLive(liveAtiva.id);
            } else {
                criarNovaLive();
            }
        });

        // Função para criar nova live
        function criarNovaLive() {
            const tipoLive = document.getElementById('live-type').value;
            const plataformas = Array.from(document.querySelectorAll('.platform-checkbox:checked'))
                                   .map(checkbox => checkbox.value);

            if (plataformas.length === 0) {
                mostrarAlert('Selecione pelo menos uma plataforma!', 'warning');
                return;
            }

            const dados = {
                tipo_live: tipoLive,
                plataformas: plataformas
            };

            // Desabilitar botão durante a requisição
            const button = document.getElementById('toggle-live');
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Criando...';

            fetch('/lives', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(dados)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    mostrarAlert(data.message, 'success');
                    liveAtiva = data.live;
                    carregarLives();
                } else {
                    mostrarAlert(data.message || 'Erro ao criar live', 'danger');
                    if (data.errors) {
                        Object.values(data.errors).forEach(errorArray => {
                            errorArray.forEach(error => {
                                mostrarAlert(error, 'warning');
                            });
                        });
                    }
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                mostrarAlert('Erro ao criar live', 'danger');
            })
            .finally(() => {
                // Reabilitar botão
                button.disabled = false;
                atualizarEstadoBotao();
            });
        }

        // Função para encerrar live
        function encerrarLive(liveId) {
            if (!confirm('Tem certeza que deseja encerrar esta live?')) {
                return;
            }

            fetch(`/lives/${liveId}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    mostrarAlert(data.message, 'success');
                    liveAtiva = null;
                    carregarLives();
                } else {
                    mostrarAlert(data.message || 'Erro ao encerrar live', 'danger');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                mostrarAlert('Erro ao encerrar live', 'danger');
            });
        }
    </script>
</body>
</html>