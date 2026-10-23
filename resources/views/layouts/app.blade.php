<!DOCTYPE html>
<html lang="pt">

<head>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.14.5/dist/sweetalert2.all.min.js"></script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'PDV' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    @livewireStyles
</head>

<body class="bg-gray-100 text-gray-800">
    <nav class="bg-white shadow px-6 py-3 flex items-center justify-between">
        <div class="flex items-center gap-6">
            <a href="{{ route('dashboard') }}" class="font-bold">PDV</a>
            @can('sales.create')
                <a href="#">Vender</a>
            @endcan
            @can('sales.view')
                <a href="#">Vendas</a>
            @endcan
            @can('products.view')
                <a href="#">Produtos</a>
            @endcan
            @can('stock.view')
                <a href="#">Estoque</a>
            @endcan
            @can('purchases.view')
                <a href="#">Compras</a>
            @endcan
            @can('super-admin')
                <a href="{{ route('stores.index') }}">Lojas</a>
            @endcan
            @can('users.manage')
                <a href="{{ route('roles.index') }}">Perfis</a>
                <a href="{{ route('users.index') }}">Utilizadores</a>
            @endcan
        </div>
        <div class="flex items-center gap-4 text-sm">
            <span>{{ auth()->user()->name }}
                <small class="text-gray-500">({{ auth()->user()->store?->name ?? 'Todas as lojas' }})</small>
            </span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="text-red-600">Sair</button>
            </form>
        </div>
    </nav>

    <main class="p-6">
        @if (session('ok'))
            <div class="bg-green-100 text-green-800 p-3 rounded mb-4">{{ session('ok') }}</div>
        @endif
        {{ $slot ?? '' }}
        @yield('content')
    </main>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            document.addEventListener('submit', (e) => {
                const form = e.target;
                if (!form.dataset.confirm || form.dataset.confirmed) return;
                e.preventDefault();
                Swal.fire({
                    icon: 'warning',
                    title: form.dataset.confirm,
                    showCancelButton: true,
                    confirmButtonText: 'Sim, continuar',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#2563eb',
                }).then((r) => {
                    if (r.isConfirmed) {
                        form.dataset.confirmed = '1';
                        form.submit();
                    }
                });
            });
            @if (session('ok'))
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: @json(session('ok')),
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true,
                });
            @endif

            @if ($errors->any())
                Swal.fire({
                    icon: 'error',
                    title: 'Não foi possível guardar',
                    html: @json(collect($errors->all())->map(fn($m) => e($m))->implode('<br>')),
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#2563eb',
                });
            @endif
        });
    </script>

    @livewireScripts
</body>

</html>
