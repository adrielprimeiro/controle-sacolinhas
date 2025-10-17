<div class="item-search-wrapper" data-item-search="true">
    <div class="position-relative">
        <!-- Input de Busca -->
        <div class="input-group">
            <span class="input-group-text">
                <i class="fas fa-box text-muted"></i>
            </span>
			<input 
				type="text" 
				class="form-control item-search-input" 
				placeholder="{{ $placeholder ?? 'Buscar por nome, SKU ou descrição...' }}"
				autocomplete="new-password"
				autocapitalize="none"
				autocorrect="off"
				spellcheck="false"
				data-form-type="other"
				data-search-input="true"
			>
            <button class="btn btn-outline-secondary item-clear-btn d-none" type="button" data-clear-btn="true">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Dropdown de Sugestões -->
        <div class="item-suggestions-dropdown" data-suggestions="true" style="display: none;">
            <!-- Resultados aparecerão aqui -->
        </div>

        <!-- Campos Hidden -->
        <input type="hidden" class="item-selected-id" name="{{ $name ?? 'item_id' }}" value="{{ $value ?? '' }}" data-hidden-input="true">
        <input type="hidden" class="item-selected-price" name="{{ $priceField ?? 'item_price' }}" value="{{ $priceValue ?? '' }}" data-price-input="true">
    </div>

    <!-- Item Selecionado -->
    <div class="item-selected-display mt-2 d-none" data-selected-display="true">
        <div class="card border-success">
            <div class="card-body p-2">
                <div class="d-flex align-items-center">
                    <img class="item-selected-image rounded me-2" src="" alt="" width="32" height="32">
                    <div class="flex-grow-1">
                        <div class="item-selected-name fw-bold"></div>
                        <small class="item-selected-price text-success fw-bold"></small>
                        <br><small class="item-selected-sku text-muted"></small>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-danger item-remove-btn" data-remove-btn="true">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.item-search-wrapper {
    position: relative;
}

.item-suggestions-dropdown {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    z-index: 9999;
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    max-height: 400px;
    overflow-y: auto;
    margin-top: 2px;
}

.item-suggestion-item {
    padding: 0.75rem 1rem;
    cursor: pointer;
    border-bottom: 1px solid #f8f9fa;
    transition: background-color 0.15s ease;
}

.item-suggestion-item:hover {
    background-color: #f8f9fa;
}

.item-suggestion-item:last-child {
    border-bottom: none;
}

.item-search-loading,
.item-search-error,
.item-search-no-results {
    padding: 1rem;
    text-align: center;
    color: #6c757d;
}

.item-search-error {
    color: #dc3545;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('📦 Inicializando busca de itens...');
    
    const wrapper = document.querySelector('[data-item-search="true"]');
    if (!wrapper) {
        console.error('❌ Item wrapper não encontrado');
        return;
    }
    
    console.log('✅ Item wrapper encontrado');
    
    const elements = {
        input: wrapper.querySelector('[data-search-input="true"]'),
        dropdown: wrapper.querySelector('[data-suggestions="true"]'),
        hiddenInput: wrapper.querySelector('[data-hidden-input="true"]'),
        priceInput: wrapper.querySelector('[data-price-input="true"]'),
        clearBtn: wrapper.querySelector('[data-clear-btn="true"]'),
        selectedDisplay: wrapper.querySelector('[data-selected-display="true"]'),
        removeBtn: wrapper.querySelector('[data-remove-btn="true"]')
    };

    // Verificar elementos
    const missing = Object.keys(elements).filter(key => !elements[key]);
    if (missing.length > 0) {
        console.error('❌ Elementos de item não encontrados:', missing);
        return;
    }
    
    console.log('✅ Todos os elementos de item encontrados');

    let debounceTimer;

    // Event listener para input
    elements.input.addEventListener('input', function(e) {
        const query = e.target.value.trim();
        console.log('📝 Digitando item:', query);
        
        if (query.length > 0) {
            elements.clearBtn.classList.remove('d-none');
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => searchItems(query), 300);
        } else {
            elements.clearBtn.classList.add('d-none');
            hideDropdown();
        }
    });

    // Event listener para limpar
    elements.clearBtn.addEventListener('click', function() {
        elements.input.value = '';
        elements.clearBtn.classList.add('d-none');
        hideDropdown();
        clearSelection();
    });

    // Event listener para remover
    elements.removeBtn.addEventListener('click', function() {
        clearSelection();
    });

    // Buscar itens
    async function searchItems(query) {
        console.log('🔎 Buscando itens:', query);
        showLoading();

        try {
            const response = await fetch(`/api/items/search?q=${encodeURIComponent(query)}`);
            const data = await response.json();
            
            console.log('📦 Dados de itens:', data);

            if (data.success && data.data) {
                displaySuggestions(data.data);
            } else {
                showNoResults();
            }
        } catch (error) {
            console.error('💥 Erro na busca de itens:', error);
            showError();
        }
    }

    // Mostrar loading
    function showLoading() {
        elements.dropdown.innerHTML = `
            <div class="item-search-loading">
                <div class="spinner-border spinner-border-sm text-success"></div>
                <div class="mt-2">Buscando itens...</div>
            </div>
        `;
        showDropdown();
    }

    // Mostrar sugestões
    function displaySuggestions(items) {
        console.log(`📋 ${items.length} itens encontrados`);
        
        if (items.length === 0) {
            showNoResults();
            return;
        }

        elements.dropdown.innerHTML = '';

        items.forEach(item => {
            const itemElement = document.createElement('div');
            itemElement.className = 'item-suggestion-item';
            itemElement.innerHTML = `
                <div class="d-flex align-items-center">
                    <img src="${item.image_url}" class="rounded me-2" width="32" height="32">
                    <div class="flex-grow-1">
                        <div class="fw-bold">${item.name}</div>
                        <small class="text-success fw-bold">${item.formatted_price}</small>
                        ${item.sku ? `<br><small class="text-muted">SKU: ${item.sku}</small>` : ''}
                    </div>
                    <div class="text-end">
                        <small class="text-muted">Estoque: ${item.stock}</small>
                    </div>
                </div>
            `;

            itemElement.addEventListener('click', () => selectItem(item));
            elements.dropdown.appendChild(itemElement);
        });

        showDropdown();
    }

    // Sem resultados
    function showNoResults() {
        elements.dropdown.innerHTML = `
            <div class="item-search-no-results">
                <i class="fas fa-box-open fa-2x mb-2"></i>
                <div>Nenhum item encontrado</div>
            </div>
        `;
        showDropdown();
    }

    // Erro
    function showError() {
        elements.dropdown.innerHTML = `
            <div class="item-search-error">
                <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                <div>Erro ao buscar itens</div>
            </div>
        `;
        showDropdown();
    }

    // Selecionar item
    function selectItem(item) {
        console.log('📦 Item selecionado:', item.name);
        
        elements.hiddenInput.value = item.id;
        elements.priceInput.value = item.price;
        elements.input.value = item.name;
        
        elements.selectedDisplay.querySelector('.item-selected-image').src = item.image_url;
        elements.selectedDisplay.querySelector('.item-selected-name').textContent = item.name;
        elements.selectedDisplay.querySelector('.item-selected-price').textContent = item.formatted_price;
        elements.selectedDisplay.querySelector('.item-selected-sku').textContent = item.sku ? `SKU: ${item.sku}` : 'Sem SKU';
        
        elements.selectedDisplay.classList.remove('d-none');
        elements.clearBtn.classList.add('d-none');
        hideDropdown();

        // Preencher campo de preço automaticamente
        const priceField = document.getElementById('item-price');
        if (priceField) {
            priceField.value = item.price;
        }

        // Evento
        wrapper.dispatchEvent(new CustomEvent('itemSelected', {
            detail: { item: item }
        }));
    }

    // Limpar seleção
    function clearSelection() {
        elements.hiddenInput.value = '';
        elements.priceInput.value = '';
        elements.selectedDisplay.classList.add('d-none');
        
        // Limpar campo de preço
        const priceField = document.getElementById('item-price');
        if (priceField) {
            priceField.value = '';
        }
        
        wrapper.dispatchEvent(new CustomEvent('itemCleared'));
    }

    // Mostrar/esconder dropdown
    function showDropdown() {
        elements.dropdown.style.display = 'block';
    }

    function hideDropdown() {
        elements.dropdown.style.display = 'none';
    }

    // Clique fora
    document.addEventListener('click', function(e) {
        if (!wrapper.contains(e.target)) {
            hideDropdown();
        }
    });

    console.log('🎉 Componente de itens pronto!');
});
</script>