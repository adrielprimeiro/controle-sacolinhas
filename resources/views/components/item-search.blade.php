{{-- resources/views/components/item-search.blade.php --}}
<div class="item-search-wrapper" data-item-search="true">
    <div class="position-relative">
        <input type="text" 
               class="form-control item-search-input" 
               data-search-input="true"
               placeholder="{{ $placeholder ?? 'Buscar item...' }}" 
               autocomplete="off"
               data-bs-toggle="dropdown">
        
        <input type="hidden" 
               name="{{ $name }}" 
               class="item-id-input" 
               value="{{ $value ?? '' }}">
        
        @if(isset($priceField))
        <input type="hidden" 
               name="{{ $priceField }}" 
               class="item-price-input" 
               value="{{ $priceValue ?? '' }}">
        @endif
        
        <div class="dropdown-menu w-100 item-search-dropdown" style="max-height: 300px; overflow-y: auto;">
            <div class="dropdown-item-text text-center py-2 search-placeholder">
                <i class="fas fa-box text-muted me-2"></i>
                Digite para buscar itens
            </div>
        </div>
    </div>
    
    <!-- Card do item selecionado -->
    <div class="selected-item-card mt-2" style="display: none;">
        <div class="card border-success">
            <div class="card-body py-2">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center">
                        <img src="" class="item-image me-2" width="40" height="40" style="object-fit: cover; border-radius: 4px;">
                        <div>
                            <div class="fw-bold item-name"></div>
                            <small class="text-muted item-details"></small>
                            <div class="text-success fw-bold item-price"></div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-danger clear-item-btn">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const itemSearchWrappers = document.querySelectorAll('[data-item-search="true"]');
    
    itemSearchWrappers.forEach(wrapper => {
        initItemSearch(wrapper);
    });
});

function initItemSearch(wrapper) {
    const input = wrapper.querySelector('.item-search-input');
    const hiddenInput = wrapper.querySelector('.item-id-input');
    const priceInput = wrapper.querySelector('.item-price-input');
    const dropdown = wrapper.querySelector('.item-search-dropdown');
    const selectedCard = wrapper.querySelector('.selected-item-card');
    const clearBtn = wrapper.querySelector('.clear-item-btn');
    
    let searchTimeout;
    let currentFocus = -1;
    let searchResults = [];
    let isDropdownOpen = false;

    // Event listeners
    input.addEventListener('input', handleInput);
    input.addEventListener('keydown', handleKeydown);
    input.addEventListener('focus', handleFocus);
    input.addEventListener('blur', handleBlur);
    clearBtn.addEventListener('click', clearSelection);
    
    // Event listener customizado para limpar seleção
    wrapper.addEventListener('itemCleared', clearSelection);

    function handleInput(e) {
        const query = e.target.value.trim();
        
        clearTimeout(searchTimeout);
        currentFocus = -1;
        
        if (query.length === 0) {
            hideDropdown();
            return;
        }
        
        // Busca sem limitação de caracteres
        searchTimeout = setTimeout(() => {
            searchItems(query);
        }, 300);
    }

    function handleKeydown(e) {
        if (!isDropdownOpen) return;
        
        const items = dropdown.querySelectorAll('.item-search-item');
        
        switch(e.key) {
            case 'ArrowDown':
                e.preventDefault();
                currentFocus++;
                if (currentFocus >= items.length) currentFocus = 0;
                updateFocus(items);
                break;
                
            case 'ArrowUp':
                e.preventDefault();
                currentFocus--;
                if (currentFocus < 0) currentFocus = items.length - 1;
                updateFocus(items);
                break;
                
            case 'Enter':
                e.preventDefault();
                if (currentFocus >= 0 && items[currentFocus]) {
                    selectItem(searchResults[currentFocus]);
                }
                break;
                
            case 'Escape':
                hideDropdown();
                input.blur();
                break;
        }
    }

    function handleFocus() {
        if (input.value.trim().length > 0 && searchResults.length > 0) {
            showDropdown();
        }
    }

    function handleBlur(e) {
        setTimeout(() => {
            if (!dropdown.contains(document.activeElement)) {
                hideDropdown();
            }
        }, 150);
    }

    function searchItems(query) {
        showLoading();
        
        fetch(`/api/items/search?q=${encodeURIComponent(query)}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            hideLoading();
            
            if (data.success && data.data) {
                searchResults = data.data;
                displayResults(data.data);
            } else {
                searchResults = [];
                showEmpty();
            }
        })
        .catch(error => {
            console.error('Erro na busca:', error);
            hideLoading();
            showEmpty();
        });
    }

    function displayResults(items) {
        let html = '';
        
        items.forEach((item, index) => {
            const details = [];
            if (item.sku) details.push(`SKU: ${item.sku}`);
            if (item.brand) details.push(`Marca: ${item.brand}`);
            if (item.color) details.push(`Cor: ${item.color}`);
            if (item.size) details.push(`Tam: ${item.size}`);
            
            html += `
                <button type="button" 
                        class="dropdown-item item-search-item d-flex align-items-center py-2" 
                        data-index="${index}">
                    <img src="${item.image_url || '/images/default-item.png'}" 
                         class="me-2" 
                         width="40" 
                         height="40" 
                         style="object-fit: cover; border-radius: 4px;">
                    <div class="flex-grow-1">
                        <div class="fw-bold">${item.name}</div>
                        <small class="text-muted">${details.join(' | ')}</small>
                        <div class="text-success fw-bold">${item.formatted_price}</div>
                    </div>
                    <small class="text-muted">ID: ${item.id}</small>
                </button>
            `;
        });
        
        dropdown.innerHTML = html;
        
        // Adicionar event listeners aos itens
        dropdown.querySelectorAll('.item-search-item').forEach((item, index) => {
            item.addEventListener('click', () => selectItem(items[index]));
            item.addEventListener('mouseenter', () => {
                currentFocus = index;
                updateFocus(dropdown.querySelectorAll('.item-search-item'));
            });
        });
        
        showDropdown();
    }

    function selectItem(item) {
        input.value = item.name;
        hiddenInput.value = item.id;
        
        // Atualizar preço se o campo existir
        if (priceInput) {
            priceInput.value = item.price;
            
            // Atualizar também o campo visível de preço
            const visiblePriceInput = document.getElementById('item-price');
            if (visiblePriceInput) {
                visiblePriceInput.value = item.price;
				visiblePriceInput.focus();
            }
        }
        
        // Atualizar card do item selecionado
        const details = [];
        if (item.sku) details.push(`SKU: ${item.sku}`);
        if (item.brand) details.push(`Marca: ${item.brand}`);
        if (item.color) details.push(`Cor: ${item.color}`);
        if (item.size) details.push(`Tam: ${item.size}`);
        
        selectedCard.querySelector('.item-image').src = item.image_url || '/images/default-item.png';
        selectedCard.querySelector('.item-name').textContent = item.name;
        selectedCard.querySelector('.item-details').textContent = details.join(' | ');
        selectedCard.querySelector('.item-price').textContent = item.formatted_price;
        selectedCard.style.display = 'block';
        
        hideDropdown();
        
        // Disparar evento customizado
        wrapper.dispatchEvent(new CustomEvent('itemSelected', {
            detail: { item: item }
        }));
    }

    function clearSelection() {
        input.value = '';
        hiddenInput.value = '';
        if (priceInput) {
            priceInput.value = '';
            const visiblePriceInput = document.getElementById('item-price');
            if (visiblePriceInput) {
                visiblePriceInput.value = '';
            }
        }
        selectedCard.style.display = 'none';
        hideDropdown();
        currentFocus = -1;
        searchResults = [];
        

    }

    function updateFocus(items) {
        items.forEach((item, index) => {
            if (index === currentFocus) {
                item.classList.add('active');
                item.scrollIntoView({ block: 'nearest' });
            } else {
                item.classList.remove('active');
            }
        });
    }

    function showDropdown() {
        dropdown.classList.add('show');
        isDropdownOpen = true;
    }

    function hideDropdown() {
        dropdown.classList.remove('show');
        isDropdownOpen = false;
        currentFocus = -1;
    }

    function showLoading() {
        dropdown.innerHTML = `
            <div class="dropdown-item-text text-center py-2">
                <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                Buscando itens...
            </div>
        `;
        showDropdown();
    }
	
	// ✅ ADICIONE ESTA FUNÇÃO
    function hideLoading() {
        // As funções displayResults ou showEmpty já sobrescrevem o conteúdo,
        // então esta função pode ser vazia ou você pode adicionar lógica para remover o spinner explicitamente.
        // Por enquanto, uma função vazia já resolve o ReferenceError.
    }

    function showEmpty() {
        dropdown.innerHTML = `
            <div class="dropdown-item-text text-center py-2 text-muted">
                <i class="fas fa-search me-2"></i>
                Nenhum item encontrado
            </div>
        `;
        showDropdown();
    }
}
</script>

<style>
.item-search-wrapper .dropdown-menu {
    border: 1px solid #dee2e6;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

.item-search-wrapper .item-search-item:hover,
.item-search-wrapper .item-search-item.active {
    background-color: #f8f9fa;
}

.item-search-wrapper .item-search-item.active {
    background-color: #e9ecef;
}

.item-search-wrapper .selected-item-card {
    animation: slideDown 0.3s ease-out;
}
</style>