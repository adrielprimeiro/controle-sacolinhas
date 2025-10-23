{{-- resources/views/components/user-search.blade.php --}}
<div class="user-search-wrapper" data-user-search="true">
    <div class="position-relative">
        <input type="text" 
               class="form-control user-search-input" 
               data-search-input="true"
               placeholder="{{ $placeholder ?? 'Buscar usuário...' }}" 
               autocomplete="new-password"
               data-bs-toggle="dropdown">
        
        <input type="hidden" 
               name="{{ $name }}" 
               class="user-id-input" 
               value="{{ $value ?? '' }}">
        
        <div class="dropdown-menu w-100 user-search-dropdown" style="max-height: 300px; overflow-y: auto;">
            <div class="dropdown-item-text text-center py-2 search-loading" style="display: none;">
                <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                Buscando...
            </div>
            <div class="dropdown-item-text text-center py-2 search-empty" style="display: none;">
                <i class="fas fa-search text-muted me-2"></i>
                Nenhum usuário encontrado
            </div>
            <div class="dropdown-item-text text-center py-2 search-placeholder">
                <i class="fas fa-user text-muted me-2"></i>
                Digite para buscar usuários
            </div>
        </div>
    </div>
    
    <!-- Card do usuário selecionado -->
    <div class="selected-user-card mt-2" style="display: none;">
        <div class="card border-primary">
            <div class="card-body py-2">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center">
                        <img src="" class="rounded-circle me-2 user-avatar" width="32" height="32">
                        <div>
                            <div class="fw-bold user-name"></div>
                            <small class="text-muted user-email"></small>
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-danger clear-user-btn">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const userSearchWrappers = document.querySelectorAll('[data-user-search="true"]');
    
    userSearchWrappers.forEach(wrapper => {
        initUserSearch(wrapper);
    });
});

function initUserSearch(wrapper) {
    const input = wrapper.querySelector('.user-search-input');
    const hiddenInput = wrapper.querySelector('.user-id-input');
    const dropdown = wrapper.querySelector('.user-search-dropdown');
    const selectedCard = wrapper.querySelector('.selected-user-card');
    const clearBtn = wrapper.querySelector('.clear-user-btn');
    
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
    wrapper.addEventListener('userCleared', clearSelection);

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
            searchUsers(query);
        }, 300);
    }

    function handleKeydown(e) {
        if (!isDropdownOpen) return;
        
        const items = dropdown.querySelectorAll('.user-search-item');
        
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
                    selectUser(searchResults[currentFocus]);
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
        // Delay para permitir clique nos itens
        setTimeout(() => {
            if (!dropdown.contains(document.activeElement)) {
                hideDropdown();
            }
        }, 150);
    }

    function searchUsers(query) {
        showLoading();
        
        fetch(`/api/users/search?q=${encodeURIComponent(query)}`, {
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

    function displayResults(users) {
        let html = '';
        
        users.forEach((user, index) => {
            html += `
                <button type="button" 
                        class="dropdown-item user-search-item d-flex align-items-center py-2" 
                        data-index="${index}">
                    <img src="${user.avatar_url || '/images/default-avatar.png'}" 
                         class="rounded-circle me-2" 
                         width="32" 
                         height="32">
                    <div class="flex-grow-1">
                        <div class="fw-bold">${user.name}</div>
                        <small class="text-muted">${user.email}</small>
                        ${user.phone ? `<small class="text-muted d-block">${user.phone}</small>` : ''}
                    </div>
                    <small class="text-muted">ID: ${user.id}</small>
                </button>
            `;
        });
        
        dropdown.innerHTML = html;
        
        // Adicionar event listeners aos itens
        dropdown.querySelectorAll('.user-search-item').forEach((item, index) => {
            item.addEventListener('click', () => selectUser(users[index]));
            item.addEventListener('mouseenter', () => {
                currentFocus = index;
                updateFocus(dropdown.querySelectorAll('.user-search-item'));
            });
        });
        
        showDropdown();
    }

    function selectUser(user) {
        input.value = user.name;
        hiddenInput.value = user.id;
        
        // Atualizar card do usuário selecionado
        selectedCard.querySelector('.user-avatar').src = user.avatar_url || '/images/default-avatar.png';
        selectedCard.querySelector('.user-name').textContent = user.name;
        selectedCard.querySelector('.user-email').textContent = user.email;
        selectedCard.style.display = 'block';
        
        hideDropdown();
        
        // Disparar evento customizado
        wrapper.dispatchEvent(new CustomEvent('userSelected', {
            detail: { user: user }
        }));
    }

    function clearSelection() {
        input.value = '';
        hiddenInput.value = '';
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
                Buscando usuários...
            </div>
        `;
        showDropdown();
    }

    function hideLoading() {
        // Implementado nas funções de resultado
    }

    function showEmpty() {
        dropdown.innerHTML = `
            <div class="dropdown-item-text text-center py-2 text-muted">
                <i class="fas fa-search me-2"></i>
                Nenhum usuário encontrado
            </div>
        `;
        showDropdown();
    }
}
</script>

<style>
.user-search-wrapper .dropdown-menu {
    border: 1px solid #dee2e6;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

.user-search-wrapper .user-search-item:hover,
.user-search-wrapper .user-search-item.active {
    background-color: #f8f9fa;
}

.user-search-wrapper .user-search-item.active {
    background-color: #e9ecef;
}

.user-search-wrapper .selected-user-card {
    animation: slideDown 0.3s ease-out;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>