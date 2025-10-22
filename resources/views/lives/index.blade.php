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

                <!-- SEÇÃO COM BUSCA DE USUÁRIO -->
                <!-- Adicionar Item à Sacola -->
                <div class="card mb-4 card-disabled" id="filter-card">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-shopping-bag"></i>
                            Adicionar Item à Sacola do Cliente
                        </h6>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('sacolinhas.store') }}" id="add-item-form">
                            @csrf
                            <div class="row">
                                <!-- Busca de Cliente -->
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">
                                        <i class="fas fa-user"></i>
                                        Selecionar Cliente
                                    </label>
                                    
                                    <!-- COMPONENTE DE BUSCA DE USUÁRIO -->
                                    @include('components.user-search', [
                                        'name' => 'client_id',
                                        'placeholder' => 'Buscar cliente por nome, email ou telefone...',
                                        'value' => old('client_id')
                                    ])
                                </div>

                                <!-- Campo Item - NOVO COMPONENTE -->
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">
                                        <i class="fas fa-box"></i>
                                        Selecionar Item
                                    </label>
                                    
                                    @include('components.item-search', [
                                        'name' => 'item_id',
                                        'priceField' => 'item_price',
                                        'placeholder' => 'Buscar item por nome, SKU ou descrição...',
                                        'value' => old('item_id'),
                                        'priceValue' => old('item_price')
                                    ])
                                </div>
                            </div>

                            <div class="row">
                                <!-- Preço -->
                                <div class="col-md-4 mb-3">
                                    <label for="item-price" class="form-label">
                                        <i class="fas fa-dollar-sign"></i>
                                        Preço
                                    </label>
                                    <input type="number"
                                           class="form-control"
                                           name="item_price"
                                           id="item-price"
                                           placeholder="0.00"
                                           step="0.01"
                                           min="0"
                                           value="{{ old('item_price') }}"
                                           required>
                                </div>

                                <!-- Quantidade -->
                                <div class="col-md-4 mb-3">
                                    <label for="item-quantity" class="form-label">
                                        <i class="fas fa-hashtag"></i>
                                        Quantidade
                                    </label>
                                    <input type="number"
                                           class="form-control"
                                           name="item_quantity"
                                           id="item-quantity"
                                           placeholder="1"
                                           min="1"
                                           value="{{ old('item_quantity', 1) }}"
                                           required>
                                </div>

                                <!-- Botão -->
                                <div class="col-md-4 mb-3 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-plus"></i> Adicionar à Sacola
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Lista de Sacolinhas -->
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-shopping-bag"></i>
                            Sacolinhas da Live Atual
                        </h6>
                    </div>
                    <div class="card-body">
                        <div id="bags-list">
                            <div class="text-center text-muted py-5">
                                <i class="fas fa-shopping-bag fa-3x mb-3 opacity-50"></i>
                                <h5>Nenhuma sacola criada ainda</h5>
                                <p>Inicie uma live e adicione itens às sacolas dos clientes.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- JavaScript das Lives e Sacolinhas -->
	<script>
	let liveAtiva = null;

	// Carregar lives ao inicializar a página
	document.addEventListener('DOMContentLoaded', function() {
		carregarLives();
		
		// Event listeners para componentes de busca
		const userSearchComponent = document.querySelector('[data-user-search="true"]');
		if (userSearchComponent) {
			userSearchComponent.addEventListener('userSelected', function(e) {
				const user = e.detail.user;
				console.log('Cliente selecionado:', user);
				mostrarAlert(`Cliente selecionado: ${user.name}`, 'info');
				
				const itemInput = document.querySelector('[data-item-search="true"] [data-search-input="true"]');
				if (itemInput) {
					itemInput.focus();
				}
			});

			userSearchComponent.addEventListener('userCleared', function(e) {
				console.log('Seleção de cliente limpa');
			});
		}

		const itemSearchComponent = document.querySelector('[data-item-search="true"]');
		if (itemSearchComponent) {
			itemSearchComponent.addEventListener('itemSelected', function(e) {
				const item = e.detail.item;
				console.log('Item selecionado:', item);
				mostrarAlert(`Item selecionado: ${item.name} - ${item.formatted_price}`, 'info');
				document.getElementById('item-quantity').focus();
			});

			itemSearchComponent.addEventListener('itemCleared', function(e) {
				console.log('Seleção de item limpa');
			});
		}

		// Event listener para o formulário
		document.getElementById('add-item-form').addEventListener('submit', function(e) {
			e.preventDefault();
			
			const clientId = document.querySelector('input[name="client_id"]').value;
			const itemId = document.querySelector('input[name="item_id"]').value;
			
			if (!clientId) {
				mostrarAlert('Por favor, selecione um cliente primeiro!', 'warning');
				return false;
			}

			if (!itemId) {
				mostrarAlert('Por favor, selecione um item primeiro!', 'warning');
				return false;
			}

			if (!liveAtiva) {
				mostrarAlert('Inicie uma live antes de adicionar itens!', 'warning');
				return false;
			}

			const formData = new FormData(this);
			const button = this.querySelector('button[type="submit"]');
			const originalText = button.innerHTML;
			
			button.disabled = true;
			button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adicionando...';

			fetch('/sacolinhas', {
				method: 'POST',
				headers: {
					'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
				},
				body: formData
			})
			.then(response => response.json())
			.then(data => {
				if (data.success) {
					mostrarAlert(data.message, 'success');
					this.reset();
					
					const userWrapper = document.querySelector('[data-user-search="true"]');
					if (userWrapper) {
						userWrapper.dispatchEvent(new CustomEvent('userCleared'));
					}
					
					const itemWrapper = document.querySelector('[data-item-search="true"]');
					if (itemWrapper) {
						itemWrapper.dispatchEvent(new CustomEvent('itemCleared'));
					}
					
					carregarSacolas();
				} else {
					mostrarAlert(data.message, 'danger');
				}
			})
			.catch(error => {
				console.error('Erro:', error);
				mostrarAlert('Erro ao adicionar item à sacola', 'danger');
			})
			.finally(() => {
				button.disabled = false;
				button.innerHTML = originalText;
			});
		});

		// Event listener para o botão toggle
		document.getElementById('toggle-live').addEventListener('click', function() {
			if (liveAtiva) {
				encerrarLive(liveAtiva.id);
			} else {
				criarNovaLive();
			}
		});
	});

	// ✅ FUNÇÃO ÚNICA PARA CARREGAR LIVES
	function carregarLives() {
		console.log('🔄 Carregando lives...');
		
		fetch('/api/sacolinhas/live', {
			method: 'GET',
			headers: {
				'X-Requested-With': 'XMLHttpRequest',
				'Accept': 'application/json',
				'Cache-Control': 'no-cache'
			}
		})
		.then(response => {
			console.log('📡 Status da resposta:', response.status);
			if (!response.ok) {
				throw new Error(`HTTP error! status: ${response.status}`);
			}
			return response.json();
		})
		.then(data => {
			console.log('📦 Dados recebidos:', data);
			
			const container = document.getElementById('lives-container');
			container.innerHTML = '';

			if (data.success && data.live && data.live_id) {
				liveAtiva = data.live;
				console.log('✅ Live ativa encontrada:', liveAtiva);
				
				container.innerHTML = `
					<div class="alert alert-success border-0 shadow-sm">
					<div class="d-flex align-items-center justify-content-between">
						<div class="d-flex align-items-center">
							<div class="live-indicator me-3">
								<span class="badge bg-danger position-relative">
									<i class="fas fa-circle text-white me-1"></i>
									AO VIVO
									<span class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle">
										<span class="visually-hidden">Live ativa</span>
									</span>
								</span>
							</div>
							<div>
								<h6 class="mb-1 text-success">
									<i class="fas fa-broadcast-tower me-2"></i>
									${liveAtiva.tipo_live_formatado || liveAtiva.tipo_live}
								</h6>
								<small class="text-muted">
									<i class="fas fa-calendar me-1"></i>
									Iniciada em: ${new Date(liveAtiva.created_at).toLocaleString('pt-BR')}
								</small>
								<div class="mt-1">
									<small class="text-muted">
										<i class="fas fa-share-alt me-1"></i>
										Plataformas: ${Array.isArray(liveAtiva.plataformas) ? liveAtiva.plataformas.join(', ') : liveAtiva.plataformas}
									</small>
								</div>
							</div>
						</div>
						<div class="text-end">
							<button class="btn btn-outline-danger btn-sm" onclick="encerrarLive(${liveAtiva.id})" title="Encerrar Live">
								<i class="fas fa-stop-circle me-1"></i>
								Encerrar
							</button>
						</div>
					</div>
				</div>
						`;
				
				setTimeout(carregarSacolas, 500);
			} else {
				liveAtiva = null;
				console.log('❌ Nenhuma live ativa encontrada');
				
				container.innerHTML = `
					<div class="text-center text-muted">
						<i class="fas fa-broadcast-tower fa-3x mb-3 opacity-50"></i>
						<p>Nenhuma live ativa no momento.</p>
						<small>Clique em "Nova Live" para começar uma transmissão.</small>
					</div>
				`;
				
				carregarSacolas();
			}

			atualizarEstadoBotao();
		})
		.catch(error => {
			console.error('❌ Erro ao carregar lives:', error);
			mostrarAlert('Erro ao carregar lives: ' + error.message, 'danger');
			
			liveAtiva = null;
			atualizarEstadoBotao();
		});
	}

	// Função para carregar sacolas
	function carregarSacolas() {
		if (!liveAtiva) {
			document.getElementById('bags-list').innerHTML = `
				<div class="text-center text-muted py-5">
					<i class="fas fa-shopping-bag fa-3x mb-3 opacity-50"></i>
					<h5>Nenhuma sacola criada ainda</h5>
					<p>Inicie uma live e adicione itens às sacolas dos clientes.</p>
				</div>
			`;
			return;
		}

		fetch(`/api/sacolinhas/live/${liveAtiva.id}`)
			.then(response => response.json())
			.then(data => {
				if (data.success) {
					exibirSacolas(data.data);
				} else {
					console.error('Erro ao carregar sacolas:', data.message);
				}
			})
			.catch(error => {
				console.error('Erro:', error);
			});
	}

	// Função para exibir sacolas
	function exibirSacolas(bags) {
		const container = document.getElementById('bags-list');
		
		if (bags.length === 0) {
			container.innerHTML = `
				<div class="text-center text-muted py-3">
					<i class="fas fa-shopping-bag fa-2x mb-2 opacity-50"></i>
					<h6>Nenhuma sacola ainda</h6>
					<p class="mb-0">Adicione itens às sacolas dos clientes.</p>
				</div>
			`;
			return;
		}

		let html = '';
		bags.forEach(bag => {
			html += `
				<div class="card mb-3">
					<div class="card-header">
						<div class="d-flex align-items-center">
							<img src="${bag.client.avatar_url}" class="rounded-circle me-2" width="32" height="32">
							<div class="flex-grow-1">
								<h6 class="mb-0">${bag.client.name}</h6>
								<small class="text-muted">${bag.client.email} (ID: ${bag.client.id})</small>
							</div>
							<div class="text-end">
								<span class="badge bg-primary">${bag.total_quantity} item(s)</span>
								<div class="fw-bold text-success">${bag.formatted_total}</div>
							</div>
						</div>
					</div>
					<div class="card-body p-0">
						<div class="table-responsive">
							<table class="table table-sm mb-0">
								<thead class="table-light">
									<tr>
										<th>Item</th>
										<th>Detalhes</th>
										<th>Qtd</th>
										<th>Preço Unit.</th>
										<th>Total</th>
										<th width="100">Ações</th>
									</tr>
								</thead>
								<tbody>
			`;
			
			bag.items.forEach(item => {
				const details = [];
				if (item.item_sku) details.push(`SKU: ${item.item_sku}`);
				if (item.item_brand) details.push(`Marca: ${item.item_brand}`);
				if (item.item_color) details.push(`Cor: ${item.item_color}`);
				if (item.item_size) details.push(`Tam: ${item.item_size}`);
				
				html += `
					<tr>
						<td><strong>${item.item_name}</strong></td>
						<td><small class="text-muted">${details.join(' | ')}</small></td>
						<td><span class="badge bg-secondary">${item.quantity}</span></td>
						<td>${item.formatted_unit_price}</td>
						<td class="fw-bold text-success">${item.formatted_total_price}</td>
						<td>
							<div class="btn-group btn-group-sm">
								<button class="btn btn-outline-warning" onclick="removerUmItem(${item.item_id}, ${bag.client.id})" title="Remover 1">
									<i class="fas fa-minus"></i>
								</button>
								<button class="btn btn-outline-danger" onclick="removerTodosItens(${item.item_id}, ${bag.client.id}, ${item.quantity})" title="Remover todos">
									<i class="fas fa-trash"></i>
								</button>
							</div>
						</td>
					</tr>
				`;
			});
			
			html += `
								</tbody>
							</table>
						</div>
					</div>
				</div>
			`;
		});

		container.innerHTML = html;
	}

	// Função para atualizar estado do botão
	function atualizarEstadoBotao() {
		const button = document.getElementById('toggle-live');
		const card = document.getElementById('filter-card');

		console.log('🔄 Atualizando estado do botão. Live ativa:', liveAtiva);

		if (liveAtiva) {
			button.classList.remove('btn-primary');
			button.classList.add('btn-danger');
			button.innerHTML = '<i class="fas fa-times"></i> Encerrar Live';
			card.classList.remove('card-disabled');
			console.log('✅ Botão configurado para ENCERRAR');
		} else {
			button.classList.remove('btn-danger');
			button.classList.add('btn-primary');
			button.innerHTML = '<i class="fas fa-plus"></i> Nova Live';
			card.classList.add('card-disabled');
			console.log('✅ Botão configurado para NOVA LIVE');
		}
	}

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

		const button = document.getElementById('toggle-live');
		button.disabled = true;
		button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Criando...';

		fetch('/api/sacolinhas/live', {
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
			}
		})
		.catch(error => {
			console.error('Erro:', error);
			mostrarAlert('Erro ao criar live', 'danger');
		})
		.finally(() => {
			button.disabled = false;
			atualizarEstadoBotao();
		});
	}

	// Função para encerrar live
	function encerrarLive(liveId) {
		if (!confirm('Tem certeza que deseja encerrar esta live?')) {
			return;
		}

		const button = document.querySelector(`button[onclick="encerrarLive(${liveId})"]`);
		if (button) {
			button.disabled = true;
			button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Encerrando...';
		}

		fetch('/sacolinhas/close-live', {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
				'X-Requested-With': 'XMLHttpRequest'
			},
			body: JSON.stringify({
				live_id: liveId
			})
		})
		.then(response => response.json())
		.then(data => {
			console.log('📦 Resposta do encerramento:', data);
			
			if (data.success) {
				mostrarAlert(data.message, 'success');
				liveAtiva = null;
				atualizarInterfaceAposEncerrar();
			} else {
				mostrarAlert(data.message || 'Erro ao encerrar live', 'danger');
			}
		})
		.catch(error => {
			console.error('❌ Erro:', error);
			mostrarAlert('Erro ao encerrar live', 'danger');
		})
		.finally(() => {
			if (button) {
				button.disabled = false;
			}
		});
	}

	// Função para atualizar interface após encerrar live
	function atualizarInterfaceAposEncerrar() {
		const livesContainer = document.getElementById('lives-container');
		livesContainer.innerHTML = `
			<div class="text-center text-muted">
				<i class="fas fa-broadcast-tower fa-3x mb-3 opacity-50"></i>
				<p>Nenhuma live ativa no momento.</p>
				<small>Clique em "Nova Live" para começar uma transmissão.</small>
			</div>
		`;
		
		atualizarEstadoBotao();
		
		const bagsContainer = document.getElementById('bags-list');
		bagsContainer.innerHTML = `
			<div class="text-center text-muted py-5">
				<i class="fas fa-shopping-bag fa-3x mb-3 opacity-50"></i>
				<h5>Nenhuma sacola criada ainda</h5>
				<p>Inicie uma live e adicione itens às sacolas dos clientes.</p>
			</div>
		`;
		
		const filterCard = document.getElementById('filter-card');
		filterCard.classList.add('card-disabled');
		
		const form = document.getElementById('add-item-form');
		if (form) {
			form.reset();
			
			const userWrapper = document.querySelector('[data-user-search="true"]');
			if (userWrapper) {
				userWrapper.dispatchEvent(new CustomEvent('userCleared'));
			}
			
			const itemWrapper = document.querySelector('[data-item-search="true"]');
			if (itemWrapper) {
				itemWrapper.dispatchEvent(new CustomEvent('itemCleared'));
			}
		}
	}

	// Função para remover um item
	function removerUmItem(itemId, userId) {
		if (!confirm('Remover 1 unidade deste item da sacola?')) {
			return;
		}
		removerItens(itemId, userId, 1);
	}

	// Função para remover todos os itens
	function removerTodosItens(itemId, userId, quantity) {
		if (!confirm(`Remover todas as ${quantity} unidades deste item da sacola?`)) {
			return;
		}
		removerItens(itemId, userId, quantity);
	}

	// Função genérica para remover itens
	function removerItens(itemId, userId, quantity) {
		const data = {
			item_id: itemId,
			user_id: userId,
			live_id: liveAtiva.id,
			quantity: quantity
		};

		fetch('/api/sacolinhas/remove', {
			method: 'DELETE',
			headers: {
				'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
				'Content-Type': 'application/json'
			},
			body: JSON.stringify(data)
		})
		.then(response => response.json())
		.then(data => {
			if (data.success) {
				mostrarAlert(data.message, 'success');
				carregarSacolas();
			} else {
				mostrarAlert(data.message, 'danger');
			}
		})
		.catch(error => {
			console.error('Erro:', error);
			mostrarAlert('Erro ao remover item', 'danger');
		});
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

		setTimeout(() => {
			if (alert.parentNode) {
				alert.remove();
			}
		}, 5000);
	}
	</script>
</body>
</html>