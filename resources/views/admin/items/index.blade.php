<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gerenciar Itens - Sacolinhas</title>

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
        .item-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 sidebar text-white p-0">
                <div class="p-3">
                    <h4> Admin</h4>
                    <hr>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link text-white" href="{{ route('items.index') }}">
                                <i class="fas fa-box"></i> Itens
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="{{ route('bags.index') }}"> <!-- Novo item no menu -->
                                <i class="fas fa-broadcast-tower"></i> Live
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="{{ route('admin.sacolinhas.index') }}"> <!-- Atualizado para a nova rota -->
                                <i class="fas fa-shopping-bag"></i> Sacolas
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
                    <h2>Gerenciar Itens</h2>
                    <a href="{{ route('items.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Novo Item
                    </a>
                </div>

                <!-- Alerts -->
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <!-- Filtros -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" action="{{ route('items.index') }}">
                            <div class="row">
                                <div class="col-md-4">
                                    <input type="text"
                                           class="form-control"
                                           name="search"
                                           placeholder="Buscar por nome do produto..." {{-- Atualizado --}}
                                           value="{{ request('search') }}">
                                </div>
                                {{-- Removido filtro de categoria e status, se desejar mantê-los, adicione novamente --}}
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-outline-primary w-100">
                                        <i class="fas fa-search"></i> Filtrar
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Lista de Itens -->
                <div class="card">
                    <div class="card-body">
                        @if($items->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Imagem</th>
                                            <th>Nome do Produto</th> {{-- Atualizado --}}
                                            <th>Marca</th> {{-- Nova coluna --}}
                                            <th>Cor</th> {{-- Nova coluna --}}
                                            <th>Tamanho</th> {{-- Nova coluna --}}
                                            <th>Estado</th> {{-- Nova coluna --}}
                                            <th>Preço</th>
                                            <th>Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($items as $item)
                                            <tr>
                                                <td>
                                                    @if($item->image)
                                                        <img src="{{ asset('storage/' . $item->image) }}"
                                                             alt="{{ $item->nome_do_produto }}" {{-- Atualizado --}}
                                                             class="item-image">
                                                    @else
                                                        <div class="item-image bg-secondary d-flex align-items-center justify-content-center">
                                                            <i class="fas fa-image text-white"></i>
                                                        </div>
                                                    @endif
                                                </td>
                                                <td>
                                                    <strong>{{ $item->nome_do_produto }}</strong><br> {{-- Atualizado --}}
                                                    <small class="text-muted">{{ Str::limit($item->descricao, 50) }}</small> {{-- Atualizado --}}
                                                </td>
                                                <td>{{ $item->marca ?? 'N/A' }}</td> {{-- Nova coluna --}}
                                                <td>{{ $item->cor ?? 'N/A' }}</td> {{-- Nova coluna --}}
                                                <td>{{ $item->tamanho ?? 'N/A' }}</td> {{-- Nova coluna --}}
                                                <td>{{ ucfirst($item->estado ?? 'N/A') }}</td> {{-- Nova coluna, com ucfirst para formatar --}}
                                                <td>
                                                    <strong class="text-success">R$ {{ number_format($item->preco, 2, ',', '.') }}</strong> {{-- Atualizado para $item->preco --}}
                                                </td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <a href="{{ route('items.show', $item) }}"
                                                           class="btn btn-sm btn-outline-info">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <a href="{{ route('items.edit', $item) }}"
                                                           class="btn btn-sm btn-outline-warning">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <form action="{{ route('items.destroy', $item) }}"
                                                              method="POST"
                                                              style="display: inline;"
                                                              onsubmit="return confirm('Tem certeza que deseja deletar este item?')">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <!-- Paginação -->
                            <div class="d-flex justify-content-center mt-4">
                                {{ $items->links() }}
                            </div>
                        @else
                            <div class="text-center py-5">
                                <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">Nenhum item encontrado</h5>
                                <p class="text-muted">Comece criando seu primeiro item!</p>
                                <a href="{{ route('items.create') }}" class="btn btn-primary">
                                    <i class="fas fa-plus"></i> Criar Primeiro Item
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>