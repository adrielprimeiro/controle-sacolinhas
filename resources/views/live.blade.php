@extends('layouts.app')

@section('content')
<div class="container mt-5">
    <!-- Identificar a Live -->
    <div class="card">
        <div class="card-header bg-primary text-white">Identificar a Live</div>
        <div class="card-body">
            <form id="form-live">
                <div class="mb-3">
                    <label for="tipo_live" class="form-label">Tipo de Live</label>
                    <select id="tipo_live" class="form-control" name="tipo_live" required>
                        <option value="Venda">Venda</option>
                        <option value="Promoção">Promoção</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="plataformas" class="form-label">Plataformas</label>
                    <select id="plataformas" class="form-control" name="plataformas[]" multiple>
                        <option value="Instagram">Instagram</option>
                        <option value="Facebook">Facebook</option>
                        <option value="YouTube">YouTube</option>
                    </select>
                </div>
                <button type="button" id="btn-iniciar" class="btn btn-primary">Iniciar</button>
            </form>
        </div>
    </div>

    <!-- Inserir Itens nas Sacolinhas -->
    <div class="card mt-4">
        <div class="card-header bg-success text-white">Inserir Itens nas Sacolinhas</div>
        <div class="card-body">
            <form id="form-sacolinhas">
                <div class="mb-3">
                    <label for="user_id" class="form-label">Cliente</label>
                    <input type="text" id="user_id" class="form-control" placeholder="Buscar Cliente">
                </div>
                <div class="mb-3">
                    <label for="item_id" class="form-label">Item</label>
                    <input type="text" id="item_id" class="form-control" placeholder="Buscar Item">
                </div>
                <div class="mb-3">
                    <label for="obs" class="form-label">Observações</label>
                    <textarea id="obs" class="form-control"></textarea>
                </div>
                <button type="button" id="btn-incluir" class="btn btn-success">Incluir</button>
            </form>
        </div>
    </div>

    <!-- Mostrar Itens Selecionados -->
    <div class="card mt-4">
        <div class="card-header bg-info text-white">Itens Selecionados</div>
        <div class="card-body">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Nome do Produto</th>
                        <th>Marca</th>
                        <th>Cor</th>
                        <th>Tamanho</th>
                        <th>Estado</th>
                        <th>Preço</th>
                        <th>Cliente</th>
                    </tr>
                </thead>
                <tbody id="itens-selecionados"></tbody>
            </table>
            <div class="text-end">
                <strong>Total: R$ <span id="soma-precos">0,00</span></strong>
            </div>
        </div>
    </div>
</div>
@endsection