<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }} - {{ auth()->user()->role_display ?? 'Cliente' }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-semibold mb-4">Bem-vindo, {{ auth()->user()->name }}!</h3>
                    
                    @if(auth()->user()->isAdmin())
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                            <h4 class="text-lg font-semibold text-blue-800 mb-2">🛠️ Área Administrativa</h4>
                            <p class="text-blue-700 mb-3">Você tem acesso às funcionalidades administrativas.</p>
                            <a href="{{ route('items.index') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                📦 Gerenciar Itens
                            </a>
                        </div>
                    @else
                        <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-4">
                            <h4 class="text-lg font-semibold text-green-800 mb-2">🛒 Área do Cliente</h4>
                            <p class="text-green-700 mb-3">Bem-vindo ao sistema de delivery!</p>
                            <button class="bg-gray-400 text-white font-bold py-2 px-4 rounded cursor-not-allowed" disabled>
                                🍽️ Ver Cardápio (Em breve)
                            </button>
                        </div>
                    @endif

                    <!-- Informações do Usuário -->
                    <div class="mt-6 bg-gray-50 rounded-lg p-4">
                        <h4 class="font-semibold text-gray-800 mb-2">Suas Informações:</h4>
                        <p><strong>Nome:</strong> {{ auth()->user()->name }}</p>
                        <p><strong>Email:</strong> {{ auth()->user()->email }}</p>
                        <p><strong>Tipo de Conta:</strong> {{ auth()->user()->role_display }}</p>
                        <p><strong>Membro desde:</strong> {{ auth()->user()->created_at->format('d/m/Y') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>