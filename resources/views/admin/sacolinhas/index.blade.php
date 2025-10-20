{{-- resources/views/admin/sacolinhas/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Gerenciamento de Sacolinhas')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-shopping-bag mr-2"></i>
                        Gerenciamento de Sacolinhas
                    </h3>
                </div>

                <div class="card-body">
                    <!-- Busca por Cliente -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <form action="{{ route('admin.sacolinhas.search-client') }}" method="GET">
                                <div class="input-group">
                                    <input type="text" 
                                           name="client_search" 
                                           class="form-control" 
                                           placeholder="Buscar por cliente (nome, email ou ID)..."
                                           value="{{ request('client_search') }}">
                                    <div class="input-group-append">
                                        <button class="btn btn-primary" type="submit">
                                            <i class="fas fa-search"></i> Buscar Cliente
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Busca por Lives -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <form action="{{ route('admin.sacolinhas.index') }}" method="GET">
                                <div class="input-group">
                                    <input type="text" 
                                           name="search" 
                                           class="form-control" 
                                           placeholder="Buscar lives por tipo ou data..."
                                           value="{{ request('search') }}">
                                    <div class="input-group-append">
                                        <button class="btn btn-outline-secondary" type="submit">
                                            <i class="fas fa-search"></i>
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Lista de Lives -->
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Tipo de Live</th>
                                    <th>Data</th>
                                    <th>Plataformas</th>
                                    <th>Total Sacolinhas</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($lives as $live)
                                <tr>
                                    <td>
                                        <strong>{{ $live->tipo_live }}</strong>
                                    </td>
                                    <td>{{ $live->formatted_date }}</td>
                                    <td>
                                        <span class="badge badge-info">
                                            {{ $live->plataformas ?: 'Não informado' }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-primary">
                                            {{ $live->sacolinhas_count }} sacolinhas
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.sacolinhas.show', $live) }}" 
                                           class="btn btn-sm btn-primary">
                                            <i class="fas fa-eye"></i> Ver Sacolinhas
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center">
                                        <em>Nenhuma live encontrada</em>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Paginação -->
                    <div class="d-flex justify-content-center">
                        {{ $lives->appends(request()->query())->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection