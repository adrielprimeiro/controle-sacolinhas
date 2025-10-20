<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Gerenciamento de Sacolinhas</title>

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
        .search-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-left: 4px solid #667eea;
        }
        .live-card {
            border-left: 4px solid #28a745;
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
        .table-hover tbody tr:hover {
            background-color: rgba(102, 126, 234, 0.1);
        }
    </style>
</head>
<body class="bg-light">
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 sidebar text-white p-0">
                <div class="p-3">
                    <h4><i class="fas fa-shopping-bag"></i> Admin</h4>
                    <hr>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link text-white" href="{{ route('items.index') }}">
                                <i class="fas fa-box"></i> Itens
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white active" href="{{ route('admin.sacolinhas.index') }}">
                                <i class="fas fa-shopping-bag"></i> Sacolinhas
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="{{ route('bags.index') }}">
                                <i class="fas fa-broadcast-tower"></i> Live
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
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>
                        <i class="fas fa-shopping-bag text-primary"></i>
                        Gerenciamento de Sacolinhas
                    </h2>
                    <div class="d-flex gap-2">
                        <a href="{{ route('bags.index') }}" class="btn btn-outline-primary">
                            <i class="fas fa-broadcast-tower"></i> Gerenciar Lives
                        </a>
                    </div>
                </div>

                <!-- Alerts -->
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle"></i>
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle"></i>
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <!-- Busca por Cliente -->
                <div class="card mb-4 search-card">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-user-search"></i>
                            Buscar por Cliente
                        </h6>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('admin.sacolinhas.search-client') }}" method="GET">
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-search"></i>
                                        </span>
                                        <input type="text" 
                                               name="client_search" 
                                               class="form-control" 
                                               placeholder="Buscar por cliente (nome, email ou ID)..."
                                               value="{{ request('client_search') }}">
                                        <button class="btn btn-primary" type="submit">
                                            <i class="fas fa-search"></i> Buscar Cliente
                                        </button>
                                    </div>
                                </div>
                                @if(request('client_search'))
                                    <div class="col-md-4">
                                        <a href="{{ route('admin.sacolinhas.index') }}" class="btn btn-outline-secondary">
                                            <i class="fas fa-times"></i> Limpar Busca
                                        </a>
                                    </div>
                                @endif
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Busca por Lives -->
                <div class="card mb-4 live-card">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-broadcast-tower"></i>
                            Filtrar Lives
                        </h6>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('admin.sacolinhas.index') }}" method="GET">
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-filter"></i>
                                        </span>
                                        <input type="text" 
                                               name="search" 
                                               class="form-control" 
                                               placeholder="Buscar lives por tipo ou data..."
                                               value="{{ request('search') }}">
                                        <button class="btn btn-outline-primary" type="submit">
                                            <i class="fas fa-search"></i> Filtrar
                                        </button>
                                    </div>
                                </div>
                                @if(request('search'))
                                    <div class="col-md-4">
                                        <a href="{{ route('admin.sacolinhas.index') }}" class="btn btn-outline-secondary">
                                            <i class="fas fa-times"></i> Limpar Filtro
                                        </a>
                                    </div>
                                @endif
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Lista de Lives -->
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-list"></i>
                            Lives e Sacolinhas
                            <span class="badge bg-primary ms-2">{{ $lives->total() }} lives encontradas</span>
                        </h6>
                    </div>
                    <div class="card-body p-0">
                        @if($lives->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>
                                                <i class="fas fa-broadcast-tower"></i>
                                                Tipo de Live
                                            </th>
                                            <th>
                                                <i class="fas fa-calendar"></i>
                                                Data
                                            </th>
                                            <th>
                                                <i class="fas fa-share-alt"></i>
                                                Plataformas
                                            </th>
                                            <th>
                                                <i class="fas fa-shopping-bag"></i>
                                                Sacolinhas
                                            </th>
                                            <th width="150">
                                                <i class="fas fa-cogs"></i>
                                                Ações
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($lives as $live)
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="live-status {{ $live->status === 'ativa' ? 'live-ativa' : 'live-encerrada' }}">
                                                        <i class="fas fa-circle"></i>
                                                        {{ ucfirst($live->status ?? 'encerrada') }}
                                                    </div>
                                                    <div class="ms-2">
                                                        <strong>{{ $live->tipo_live }}</strong>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div>
                                                    <strong>{{ $live->formatted_date }}</strong>
                                                </div>
                                                <small class="text-muted">
                                                    <i class="fas fa-clock"></i>
                                                    {{ $live->created_at->format('H:i') }}
                                                </small>
                                            </td>
                                            <td>
                                                @if($live->plataformas)
                                                    @foreach(explode(',', $live->plataformas) as $plataforma)
                                                        <span class="badge bg-info me-1">
                                                            <i class="fab fa-{{ trim($plataforma) }}"></i>
                                                            {{ ucfirst(trim($plataforma)) }}
                                                        </span>
                                                    @endforeach
                                                @else
                                                    <span class="text-muted">
                                                        <i class="fas fa-question-circle"></i>
                                                        Não informado
                                                    </span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <span class="badge bg-primary fs-6 me-2">
                                                        {{ $live->sacolinhas_count }}
                                                    </span>
                                                    <small class="text-muted">sacolinhas</small>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="{{ route('admin.sacolinhas.show', $live) }}" 
                                                       class="btn btn-primary" 
                                                       title="Ver Sacolinhas">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    @if($live->sacolinhas_count > 0)
                                                        <a href="{{ route('admin.sacolinhas.export', $live) }}" 
                                                           class="btn btn-success" 
                                                           title="Exportar">
                                                            <i class="fas fa-download"></i>
                                                        </a>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-5">
                                <i class="fas fa-search fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">Nenhuma live encontrada</h5>
                                @if(request('search') || request('client_search'))
                                    <p class="text-muted">Tente ajustar os filtros de busca.</p>
                                    <a href="{{ route('admin.sacolinhas.index') }}" class="btn btn-outline-primary">
                                        <i class="fas fa-refresh"></i> Ver Todas as Lives
                                    </a>
                                @else
                                    <p class="text-muted">Ainda não há lives cadastradas no sistema.</p>
                                    <a href="{{ route('bags.index') }}" class="btn btn-primary">
                                        <i class="fas fa-plus"></i> Criar Nova Live
                                    </a>
                                @endif
                            </div>
                        @endif
                    </div>

                    <!-- Paginação -->
                    @if($lives->hasPages())
                        <div class="card-footer">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="text-muted">
                                    Mostrando {{ $lives->firstItem() }} a {{ $lives->lastItem() }} 
                                    de {{ $lives->total() }} resultados
                                </div>
                                <div>
                                    {{ $lives->appends(request()->query())->links() }}
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Auto-hide alerts após 5 segundos
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    if (alert && alert.parentNode) {
                        const bsAlert = new bootstrap.Alert(alert);
                        bsAlert.close();
                    }
                }, 5000);
            });
        });

        // Adicionar loading aos botões de busca
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function() {
                const button = this.querySelector('button[type="submit"]');
                if (button) {
                    const originalText = button.innerHTML;
                    button.disabled = true;
                    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Buscando...';
                    
                    // Restaurar após 3 segundos (fallback)
                    setTimeout(() => {
                        button.disabled = false;
                        button.innerHTML = originalText;
                    }, 3000);
                }
            });
        });
    </script>
</body>
</html>