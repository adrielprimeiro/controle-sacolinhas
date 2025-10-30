<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Gerenciar Sacolas de Lives</title>

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
        /* Estilo para linha de live selecionada */
        .live-row.table-primary {
            background-color: #cfe2ff !important; /* Cor de destaque do Bootstrap */
            border-left: 5px solid #0d6efd;
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
                            <a class="nav-link text-white" href="{{ route('admin.sacolinhas.index') }}"> <!-- Atualizado para a nova rota -->
                                <i class="fas fa-broadcast-tower"></i> Lives e Sacolas
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

                <!-- Alerts (mantido para mensagens genéricas) -->
                <div id="alert-container">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif
                </div>

                <!-- Card de Busca no Topo para Filtrar Lives -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-search"></i>
                            Buscar e Filtrar Lives
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="search-client" class="form-label">Filtrar por Cliente (nas sacolas da live selecionada)</label>
                                <input type="text" class="form-control" id="search-client" placeholder="Nome ou email do cliente...">
                            </div>
                            <div class="col-md-6">
                                <label for="search-live" class="form-label">Filtrar por Live (tipo, plataforma, ID)</label>
                                <input type="text" class="form-control" id="search-live" placeholder="Tipo de live, plataforma, ID...">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Seção Inferior - Tabela de Lives e Sacolas da Live Selecionada -->
                <div class="row">
                    <!-- Coluna Esquerda: Tabela de Lives -->
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">
                                    <i class="fas fa-broadcast-tower"></i>
                                    Todas as Lives
                                </h6>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover table-sm mb-0">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Tipo</th>
                                                <th>Plataformas</th>
                                                <th>Status</th>
                                                <th>Criada em</th>
                                            </tr>
                                        </thead>
                                        <tbody id="lives-table-body">
                                            <!-- Lives serão carregadas aqui via JavaScript -->
                                            <tr>
                                                <td colspan="5" class="text-center text-muted py-3">
                                                    <i class="fas fa-spinner fa-spin me-2"></i> Carregando lives...
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Coluna Direita: Sacolas da Live Selecionada -->
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">
                                    <i class="fas fa-shopping-bag"></i>
                                    Sacolas da Live Selecionada
                                </h6>
                            </div>
                            <div class="card-body">
                                <div id="selected-live-bags-display">
                                    <div class="text-center text-muted py-5">
                                        <i class="fas fa-hand-pointer fa-3x mb-3 opacity-50"></i>
                                        <h5>Selecione uma live para ver suas sacolas</h5>
                                        <p>Clique em uma live na tabela ao lado para exibir os detalhes das sacolas.</p>
                                    </div>
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

    <!-- JavaScript das Lives e Sacolinhas -->
    <script>
        let allLives = [];    // Todas as lives carregadas para filtragem
        let selectedLiveId = null; // ID da live selecionada na tabela para visualização de sacolas

        document.addEventListener('DOMContentLoaded', function() {
            carregarTodasAsLives(); // Carrega todas as lives na tabela ao iniciar

            // Event listeners para os campos de busca de lives
            document.getElementById('search-client').addEventListener('input', filterLivesTable);
            document.getElementById('search-live').addEventListener('input', filterLivesTable);
        });

        // Função para carregar todas as lives e popular a tabela
        async function carregarTodasAsLives() {
            const livesTableBody = document.getElementById('lives-table-body');
            livesTableBody.innerHTML = `
                <tr>
                    <td colspan="5" class="text-center text-muted py-3">
                        <i class="fas fa-spinner fa-spin me-2"></i> Carregando lives...
                    </td>
                </tr>
            `;
            try {
                // Endpoint API para buscar todas as lives
                const response = await fetch('/api/lives/all', {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                const data = await response.json();
                if (data.success) {
                    allLives = data.data; // Armazena todas as lives para filtragem
                    renderLivesTable(allLives);
                } else {
                    livesTableBody.innerHTML = `
                        <tr>
                            <td colspan="5" class="text-center text-danger py-3">
                                Erro ao carregar lives: ${data.message}
                            </td>
                        </tr>
                    `;
                }
            } catch (error) {
                console.error('Erro ao carregar todas as lives:', error);
                livesTableBody.innerHTML = `
                    <tr>
                        <td colspan="5" class="text-center text-danger py-3">
                            Erro de rede ao carregar lives.
                        </td>
                    </tr>
                `;
            }
        }

        // Função para renderizar a tabela de lives (pode ser filtrada)
        function renderLivesTable(livesToRender) {
            const livesTableBody = document.getElementById('lives-table-body');
            let html = '';
            if (livesToRender.length === 0) {
                html = `
                    <tr>
                        <td colspan="5" class="text-center text-muted py-3">
                            Nenhuma live encontrada com os critérios de busca.
                        </td>
                    </tr>
                `;
            } else {
                livesToRender.forEach(live => {
                    const statusClass = live.status === 'ativa' ? 'live-ativa' : 'live-encerrada';
                    const formattedPlatforms = live.plataformas ? live.plataformas.split(',').map(p => p.charAt(0).toUpperCase() + p.slice(1)).join(', ') : 'N/A';
                    const formattedDate = new Date(live.created_at).toLocaleDateString('pt-BR', {
                        day: '2-digit',
                        month: '2-digit',
                        year: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                    const isSelected = selectedLiveId == live.id ? 'table-primary' : ''; // Destaque se for a live selecionada
                    html += `
                        <tr class="live-row ${isSelected}" data-live-id="${live.id}" style="cursor: pointer;">
                            <td>${live.id}</td>
                            <td>${live.tipo_live.replace('-', ' ').toUpperCase()}</td>
                            <td>${formattedPlatforms}</td>
                            <td><span class="live-status ${statusClass}">${live.status.toUpperCase()}</span></td>
                            <td>${formattedDate}</td>
                        </tr>
                    `;
                });
            }
            livesTableBody.innerHTML = html;

            // Adiciona event listeners às novas linhas
            livesTableBody.querySelectorAll('.live-row').forEach(row => {
                row.addEventListener('click', function() {
                    const liveId = this.dataset.liveId;
                    selectLive(liveId);
                });
            });
        }

        // Função para filtrar a tabela de lives
        function filterLivesTable() {
            const searchClient = document.getElementById('search-client').value.toLowerCase();
            const searchLive = document.getElementById('search-live').value.toLowerCase();

            const filteredLives = allLives.filter(live => {
                const liveTypeMatch = live.tipo_live.toLowerCase().includes(searchLive);
                const platformsMatch = live.plataformas ? live.plataformas.toLowerCase().includes(searchLive) : false;
                const liveIdMatch = String(live.id).includes(searchLive);

                // O filtro de cliente será aplicado apenas na visualização das sacolas da live selecionada.
                // O filtro de live se aplica ao tipo, plataformas e ID.
                return (liveTypeMatch || platformsMatch || liveIdMatch);
            });
            renderLivesTable(filteredLives);

            // Se houver uma live selecionada e o filtro de cliente for alterado, recarrega as sacolas
            if (selectedLiveId && searchClient) {
                carregarSacolas(selectedLiveId);
            }
        }

        // Função para lidar com a seleção de uma live na tabela
        function selectLive(liveId) {
            selectedLiveId = liveId;

            // Remove destaque da linha previamente selecionada
            document.querySelectorAll('.live-row.table-primary').forEach(row => {
                row.classList.remove('table-primary');
            });

            // Adiciona destaque à linha recém-selecionada
            const selectedRow = document.querySelector(`.live-row[data-live-id="${liveId}"]`);
            if (selectedRow) {
                selectedRow.classList.add('table-primary');
            }

            // Carrega as sacolas para a live selecionada no painel de visualização
            carregarSacolas(liveId);
        }

        // Função para carregar sacolas
        function carregarSacolas(liveId) {
            const container = document.getElementById('selected-live-bags-display');
            container.innerHTML = `
                <div class="text-center text-muted py-5">
                    <i class="fas fa-spinner fa-spin fa-3x mb-3 opacity-50"></i>
                    <h5>Carregando sacolas para a Live ID: ${liveId}...</h5>
                </div>
            `;

            fetch(`/api/sacolinhas/live/${liveId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Filtra as sacolas por cliente se o campo de busca de cliente estiver preenchido
                        let bagsToDisplay = data.data;
                        const searchClient = document.getElementById('search-client').value.toLowerCase();
                        if (searchClient) {
                            bagsToDisplay = bagsToDisplay.filter(bag =>
                                bag.client.name.toLowerCase().includes(searchClient) ||
                                bag.client.email.toLowerCase().includes(searchClient) ||
                                (bag.client.phone && bag.client.phone.includes(searchClient))
                            );
                        }
                        exibirSacolas(bagsToDisplay, container, liveId);
                    } else {
                        console.error('Erro ao carregar sacolas:', data.message);
                        container.innerHTML = `
                            <div class="text-center text-danger py-5">
                                <i class="fas fa-exclamation-triangle fa-3x mb-3 opacity-50"></i>
                                <h5>Erro ao carregar sacolas</h5>
                                <p>${data.message}</p>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    container.innerHTML = `
                        <div class="text-center text-danger py-5">
                            <i class="fas fa-exclamation-triangle fa-3x mb-3 opacity-50"></i>
                            <h5>Erro de rede ao carregar sacolas</h5>
                            <p>Verifique sua conexão ou tente novamente.</p>
                        </div>
                    `;
                });
        }

        // Função para exibir sacolas (MODIFICADA)
        function exibirSacolas(bags, targetContainer, currentLiveId) {
            if (bags.length === 0) {
                targetContainer.innerHTML = `
                    <div class="text-center text-muted py-3">
                        <i class="fas fa-shopping-bag fa-2x mb-2 opacity-50"></i>
                        <h6>Nenhuma sacola encontrada para esta live ou com o filtro de cliente.</h6>
                        <p class="mb-0">Adicione itens ou ajuste o filtro de busca.</p>
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
                                    <span class="badge bg-primary me-2">Total de Itens: ${bag.total_items}</span> <!-- MODIFICADO: Texto mais explícito -->
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
                                            <th>Preço</th> <!-- MODIFICADO: De "Total" para "Preço" -->
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
                            <td>
                                <strong>${item.item_name}</strong>
                            </td>
                            <td>
                                <small class="text-muted">${details.join(' | ')}</small>
                            </td>
                            <td class="fw-bold text-success">${item.formatted_total_price}</td> <!-- Mantido, pois é o preço do item -->
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-danger" onclick="removerTodosItens(${item.item_id}, ${bag.client.id}, 1, ${currentLiveId})" title="Remover item"> <!-- MODIFICADO: Botão único para remover item -->
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
            targetContainer.innerHTML = html;
        }

        // Funções de remover item (MODIFICADAS para refletir a remoção de quantidade)
        function removerUmItem(itemId, userId, liveIdToRefresh) {
            // Esta função não será mais chamada diretamente, pois o botão de "Remover 1" foi removido.
            // A função removerTodosItens será usada para remover o item.
            console.warn("removerUmItem foi chamado, mas o botão foi removido. Usando removerItens com quantidade 1.");
            removerItens(itemId, userId, 1, liveIdToRefresh);
        }

        function removerTodosItens(itemId, userId, quantity, liveIdToRefresh) {
            if (!confirm(`Tem certeza que deseja remover este item da sacola?`)) { // Texto da confirmação ajustado
                return;
            }
            // Como cada item é único na sacola, remover "todos" significa remover o item.
            removerItens(itemId, userId, 1, liveIdToRefresh); // Sempre remove 1 (o item completo)
        }

        function removerItens(itemId, userId, quantity, liveIdToRefresh) {
            const data = {
                item_id: itemId,
                user_id: userId,
                live_id: liveIdToRefresh,
                quantity: quantity // A quantidade aqui será sempre 1 para remover o item
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
                        carregarSacolas(liveIdToRefresh); // Recarrega a lista de sacolas da live correta
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