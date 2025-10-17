@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header">
                    <h4>📊 Importar CSV - Produtos</h4>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success">
                            ✅ {{ session('success') }}
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger">
                            ❌ {{ session('error') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('csv.upload') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label for="file" class="form-label">Arquivo CSV:</label>
                            <input type="file" class="form-control" name="file" accept=".csv,.txt" required>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">📤 Importar</button>
                            <a href="{{ route('csv.template') }}" class="btn btn-outline-secondary">�� Baixar Modelo</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection