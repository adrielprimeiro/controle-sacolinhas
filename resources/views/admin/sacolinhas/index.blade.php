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
            transition: all 0.3s ease;
        }
        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
        }
        .filters-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-left: 4px solid #667eea;
        }
        .live-card {
            border-left: 4px solid #28a745;
        }
        .sacolinhas-card {
            border-left: 4px solid #ffc107;
            display: none;
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
        .live-row {
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .live-row:hover {
            background-color: rgba(102, 126, 234, 0.05);
        }
        .live-row.selected {
            background-color: rgba(102, 126, 234, 0.1);
            border-left: 4px solid #667eea;
        }
        .filter-section {
            background: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            border: 1px solid #e9ecef;
        }
        .section-divider {
            border-left: 2px solid #dee2e6;
            height: 60px;
            margin: 0 20px;
        }
        .loading-overlay {
            display: none;
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.8);
            z-index: 1000;
            border-radius: 15px;
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
                <!-- Card de Filtros - VERSÃO SUPER COMPACTA -->
				<div class="card mb-4 filters-card">
					<div class="card-body py-3">
						<div class="row g-2 align-items-center">
							<div class="col-auto">
								<h6 class="mb-0 text-muted">
									<i class="fas fa-filter"></i> Filtros:
								</h6>
							</div>
							
							<!-- Busca por Cliente -->
							<div class="col-md-5">
								<form action="{{ route('admin.sacolinhas.search-client') }}" method="GET" class="d-flex">
									<div class="input-group input-group-sm">
										<span class="input-group-text">
											<i class="fas fa-user text-primary"></i>
										</span>
										<input type="text" 
											   name="client_search" 
											   class="form-control" 
											   placeholder="Cliente..."
											   value="{{ request('client_search') }}">
										<button class="btn btn-primary btn-sm" type="submit">
											<i class="fas fa-search"></i>
										</button>
									</div>
								</form>
							</div>

							<!-- Filtro por Lives -->
							<div class="col-md-5">
								<form action="{{ route('admin.sacolinhas.index') }}" method="GET" class="d-flex">
									<div class="input-group input-group-sm">
										<span class="input-group-text">
											<i class="fas fa-broadcast-tower text-success"></i>
										</span>
										<input type="text" 
											   name="search" 
											   class="form-control" 
											   placeholder="Lives..."
											   value="{{ request('search') }}">
										<button class="btn btn-outline-success btn-sm" type="submit">
											<i class="fas fa-filter"></i>
										</button>
									</div>
								</form>
							</div>

							<!-- Botão Limpar -->
							@if(request('search') || request('client_search'))
								<div class="col-auto">
									<a href="{{ route('admin.sacolinhas.index') }}" class="btn btn-outline-secondary btn-sm">
										<i class="fas fa-times"></i>
									</a>
								</div>
							@endif
						</div>
					</div>
				</div>
                <div class="row">
                    <!-- Card de Lives -->
                    <div class="col-md-6">
                        <div class="card live-card">
                            <div class="card-header">
                                <h6 class="mb-0">
                                    <i class="fas fa-broadcast-tower"></i>
                                    Lives Disponíveis
                                    <span class="badge bg-primary ms-2">{{ $lives->total() }}</span>
                                </h6>
                            </div>
                            <div class="card-body p-0" style="max-height: 600px; overflow-y: auto;">
                                @if($lives->count() > 0)
                                    <div class="table-responsive">
                                        <table class="table table-hover mb-0">
                                            <thead class="table-light sticky-top">
                                                <tr>
                                                    <th>Live</th>
                                                    <th>Data</th>
                                                    <th>Sacolinhas</th>
                                                    <th width="80">Ações</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($lives as $live)
                                                <tr class="live-row" data-live-id="{{ $live->id }}" onclick="selectLive({{ $live->id }})">
                                                    <td>
                                                        <div>
                                                            <div class="live-status {{ $live->status === 'ativa' ? 'live-ativa' : 'live-encerrada' }}">
                                                                <i class="fas fa-circle"></i>
                                                                {{ ucfirst($live->status ?? 'encerrada') }}
                                                            </div>
                                                            <div class="mt-1">
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
                                                        <span class="badge bg-primary fs-6">
                                                            {{ $live->sacolinhas_count }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group btn-group-sm">
                                                            @if($live->sacolinhas_count > 0)
                                                                <a href="{{ route('admin.sacolinhas.export', $live) }}" 
                                                                   class="btn btn-success btn-sm" 
                                                                   title="Exportar"
                                                                   onclick="event.stopPropagation()">
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
                                        <h6 class="text-muted">Nenhuma live encontrada</h6>
                                        @if(request('search') || request('client_search'))
                                            <p class="text-muted small">Tente ajustar os filtros de busca.</p>
                                        @else
                                            <p class="text-muted small">Ainda não há lives cadastradas.</p>
                                            <a href="{{ route('bags.index') }}" class="btn btn-primary btn-sm">
                                                <i class="fas fa-plus"></i> Criar Nova Live
                                            </a>
                                        @endif
                                    </div>
                                @endif
                            </div>

                            <!-- Paginação -->
                            @if($lives->hasPages())
                                <div class="card-footer">
                                    <div class="d-flex justify-content-center">
                                        {{ $lives->appends(request()->query())->links() }}
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Card de Sacolinhas -->
                    <div class="col-md-6">
                        <div class="card sacolinhas-card" id="sacolinhasCard">
                            <div class="loading-overlay d-flex align-items-center justify-content-center" id="loadingOverlay">
                                <div class="text-center">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Carregando...</span>
                                    </div>
                                    <div class="mt-2">Carregando sacolinhas...</div>
                                </div>
                            </div>
                            
                            <div class="card-header">
                                <h6 class="mb-0">
                                    <i class="fas fa-shopping-bag"></i>
                                    Sacolinhas da Live
                                    <span class="badge bg-warning ms-2" id="sacolinhasCount">0</span>
                                </h6>
                                <small class="text-muted" id="liveInfo">Selecione uma live para ver as sacolinhas</small>
                            </div>
                            <div class="card-body p-0" style="max-height: 600px; overflow-y: auto;" id="sacolinhasContent">
                                <div class="text-center py-5">
                                    <i class="fas fa-hand-pointer fa-3x text-muted mb-3"></i>
                                    <h6 class="text-muted">Selecione uma live</h6>
                                    <p class="text-muted small">Clique em uma live ao lado para ver suas sacolinhas</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

	<script>
		// Auto-hide alerts
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

		// Loading nos formulários
		document.querySelectorAll('form').forEach(form => {
			form.addEventListener('submit', function() {
				const button = this.querySelector('button[type="submit"]');
				if (button) {
					const originalText = button.innerHTML;
					button.disabled = true;
					button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Carregando...';
					
					setTimeout(() => {
						button.disabled = false;
						button.innerHTML = originalText;
					}, 3000);
				}
			});
		});

	function selectLive(liveId) {
		// Remove seleção anterior
		document.querySelectorAll('.live-row').forEach(row => {
			row.classList.remove('selected');
		});
		
		// Adiciona seleção atual
		document.querySelector(`[data-live-id="${liveId}"]`).classList.add('selected');
		
		// Mostra o card de sacolinhas
		const sacolinhasCard = document.getElementById('sacolinhasCard');
		sacolinhasCard.style.display = 'block';
		
		// Mostrar loading CORRETAMENTE
		const loadingElement = document.getElementById('loadingOverlay');
		if (loadingElement) {
			loadingElement.classList.remove('d-none');
			loadingElement.classList.add('d-flex');
			loadingElement.style.display = 'flex';
		}
		
		// Carregar dados
		setTimeout(() => {
			loadSacolinhas(liveId);
		}, 300);
	}

	// Função para carregar sacolinhas via AJAX - VERSÃO COM DEBUG
	function loadSacolinhas(liveId) {
		console.log('🔄 Carregando live:', liveId);
		
		fetch(`/admin/sacolinhas/live/${liveId}/sacolinhas`)
			.then(response => response.json())
			.then(data => {
				console.log('📦 Dados recebidos:', data);
				
				// SOLUÇÃO: Remover classes Bootstrap que conflitam
				const loadingElement = document.getElementById('loadingOverlay');
				if (loadingElement) {
					loadingElement.classList.remove('d-flex');
					loadingElement.classList.add('d-none');
					loadingElement.style.display = 'none';
					console.log('✅ Loading escondido - classes removidas');
				}
				
				if (data.success) {
					// Atualizar informações
					const countElement = document.getElementById('sacolinhasCount');
					const infoElement = document.getElementById('liveInfo');
					
					if (countElement) countElement.textContent = data.count || 0;
					if (infoElement) infoElement.textContent = `${data.live.tipo_live} - ${data.live.formatted_date}`;
					
					let content = '';
					
					if (data.sacolinhas && data.sacolinhas.length > 0) {
						content = `
							<div class="table-responsive">
								<table class="table table-hover mb-0">
									<thead class="table-light sticky-top">
										<tr>
											<th>Cliente</th>
											<th>Itens</th>
											<th>Total</th>
											<th>Status</th>
										</tr>
									</thead>
									<tbody>
						`;
						
						data.sacolinhas.forEach(sacolinha => {
							content += `
								<tr>
									<td>
										<div class="d-flex align-items-center">
											<img src="https://ui-avatars.com/api/?name=${encodeURIComponent(sacolinha.client_name || 'Cliente')}&background=667eea&color=fff&size=32" 
												 class="rounded-circle me-2" width="32" height="32">
											<div>
												<strong>${sacolinha.client_name || 'Nome não informado'}</strong>
												<br><small class="text-muted">${sacolinha.client_email || ''}</small>
											</div>
										</div>
									</td>
									<td>
										<span class="badge bg-info">${sacolinha.total_items || 0} itens</span>
									</td>
									<td>
										<strong>R$ ${sacolinha.total_value || '0,00'}</strong>
									</td>
									<td>
										<span class="badge bg-success">Ativa</span>
									</td>
								</tr>
							`;
						});
						
						content += `</tbody></table></div>`;
					} else {
						content = `
							<div class="text-center py-5">
								<i class="fas fa-shopping-bag fa-3x text-muted mb-3"></i>
								<h6 class="text-muted">Nenhuma sacolinha encontrada</h6>
								<p class="text-muted small">Esta live ainda não possui sacolinhas.</p>
							</div>
						`;
					}
					
					const contentElement = document.getElementById('sacolinhasContent');
					if (contentElement) {
						contentElement.innerHTML = content;
						console.log('✅ Conteúdo atualizado');
					}
				}
			})
			.catch(error => {
				console.error('❌ Erro:', error);
				
				// Esconder loading mesmo com erro
				const loadingElement = document.getElementById('loadingOverlay');
				if (loadingElement) {
					loadingElement.classList.remove('d-flex');
					loadingElement.classList.add('d-none');
					loadingElement.style.display = 'none';
				}
				
				const contentElement = document.getElementById('sacolinhasContent');
				if (contentElement) {
					contentElement.innerHTML = `
						<div class="text-center py-5">
							<i class="fas fa-exclamation-triangle fa-3x text-danger mb-3"></i>
							<h6 class="text-danger">Erro ao carregar</h6>
							<button class="btn btn-primary btn-sm" onclick="loadSacolinhas(${liveId})">
								<i class="fas fa-refresh"></i> Tentar Novamente
							</button>
						</div>
					`;
				}
			});
	}
		// Funções auxiliares para as ações
		function viewSacolinhaDetails(sacolinhaId) {
			// Implementar modal ou página de detalhes
			console.log('Ver detalhes da sacolinha:', sacolinhaId);
			// Você pode implementar um modal aqui ou redirecionar para uma página
		}

		function exportSacolinha(sacolinhaId) {
			// Implementar exportação individual
			console.log('Exportar sacolinha:', sacolinhaId);
			// Você pode implementar download de PDF/Excel aqui
		}
	</script>
</body>
</html>