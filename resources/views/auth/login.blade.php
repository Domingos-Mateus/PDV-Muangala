<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Entrar - PDV</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js"></script>
    <style>
        [x-cloak] {
            display: none !important
        }
    </style>
</head>

<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <form method="POST" action="{{ route('login.post') }}" class="bg-white p-8 rounded shadow w-full max-w-sm space-y-4">
        @csrf
        <h1 class="text-xl font-bold">Entrar no PDV</h1>

        <input name="username" value="{{ old('username') }}" placeholder="Utilizador" class="w-full border rounded p-2"
            autofocus>

        <div class="relative" x-data="{ show: false }">
            <input name="password" :type="show ? 'text' : 'password'" placeholder="Senha"
                class="w-full border rounded p-2 pr-10">

            <button type="button" @click="show = !show"
                class="absolute inset-y-0 right-0 px-3 text-gray-500 hover:text-gray-700"
                :aria-label="show ? 'Esconder senha' : 'Mostrar senha'">

                <!-- senha ESCONDIDA: olho riscado -->
                <svg x-show="!show" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                </svg>

                <!-- senha VISÍVEL: olho aberto -->
                <svg x-show="show" x-cloak xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
            </button>
        </div>

        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="remember"> Manter sessão
        </label>
        <button class="w-full bg-blue-600 text-white rounded p-2">Entrar</button>
    </form>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.14.5/dist/sweetalert2.all.min.js"></script>
    @if ($errors->any())
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Erro ao entrar',
                text: @json($errors->first()),
                confirmButtonColor: '#2563eb'
            });
        </script>
    @endif
</body>

</html>
