<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk Sistem — SIMUTASI-GIS BKPSDM</title>
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        navy: {
                            50: '#f0f5fa',
                            100: '#e1ecf5',
                            600: '#1b4d7e',
                            800: '#14385c',
                            900: '#0f243c',
                        }
                    }
                }
            }
        }
    </script>
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body { font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; }
    </style>
</head>
<body class="min-h-full flex flex-col justify-center py-12 sm:px-6 lg:px-8 bg-slate-50 antialiased text-slate-800">

    <div class="sm:mx-auto sm:w-full sm:max-w-md">
        <!-- Logo Brand -->
        <div class="flex justify-center">
            <div class="w-14 h-14 rounded-2xl bg-blue-700 flex items-center justify-center text-white shadow-md shadow-blue-700/20">
                <i data-lucide="map-pin" class="w-8 h-8"></i>
            </div>
        </div>
        <h2 class="mt-4 text-center text-2xl font-extrabold tracking-tight text-slate-900">
            SIMUTASI-GIS
        </h2>
        <p class="mt-1 text-center text-xs text-slate-500 font-medium">
            Sistem Pemetaan Spasial & Rekomendasi Mutasi ASN Terintegrasi<br>
            <span class="text-blue-700 font-semibold">BKPSDM Kabupaten Indramayu</span>
        </p>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md px-4 sm:px-0">
        <div class="bg-white py-8 px-6 shadow-sm border border-slate-200 sm:rounded-2xl sm:px-10">

            <!-- Flash Alert Messages -->
            @if(session('error'))
                <div class="mb-5 p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs flex items-center gap-2.5">
                    <i data-lucide="alert-circle" class="w-4 h-4 shrink-0"></i>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            @if(session('info'))
                <div class="mb-5 p-3.5 rounded-xl bg-blue-50 border border-blue-200 text-blue-700 text-xs flex items-center gap-2.5">
                    <i data-lucide="info" class="w-4 h-4 shrink-0"></i>
                    <span>{{ session('info') }}</span>
                </div>
            @endif

            @if($errors->any())
                <div class="mb-5 p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs">
                    <div class="font-bold flex items-center gap-2 mb-1">
                        <i data-lucide="alert-triangle" class="w-4 h-4"></i>
                        <span>Autentikasi Gagal:</span>
                    </div>
                    <ul class="list-disc list-inside space-y-0.5">
                        @foreach($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('login.post') }}" method="POST" class="space-y-4">
                @csrf

                <!-- Input Login (NIP / Email) -->
                <div>
                    <label for="login" class="block text-xs font-semibold text-slate-700 mb-1">
                        NIP atau Alamat Email
                    </label>
                    <div class="relative rounded-xl shadow-2xs">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                            <i data-lucide="user" class="w-4 h-4"></i>
                        </div>
                        <input type="text" name="login" id="login" value="{{ old('login') }}" required autofocus
                            placeholder="Contoh: 19850101... atau nama@bkpsdm.go.id"
                            class="block w-full pl-9 pr-3 py-2.5 text-xs bg-slate-50/50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-600 focus:border-transparent transition">
                    </div>
                    <p class="mt-1 text-[11px] text-slate-400">
                        Admin: gunakan email dinas | Pegawai ASN: gunakan 18 digit NIP.
                    </p>
                </div>

                <!-- Input Password -->
                <div>
                    <label for="password" class="block text-xs font-semibold text-slate-700 mb-1">
                        Kata Sandi (Password)
                    </label>
                    <div class="relative rounded-xl shadow-2xs">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                            <i data-lucide="lock" class="w-4 h-4"></i>
                        </div>
                        <input type="password" name="password" id="password" required
                            placeholder="Masukkan kata sandi akun Anda"
                            class="block w-full pl-9 pr-10 py-2.5 text-xs bg-slate-50/50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-600 focus:border-transparent transition">
                        <button type="button" onclick="togglePassword()" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600">
                            <i data-lucide="eye" id="eyeIcon" class="w-4 h-4"></i>
                        </button>
                    </div>
                </div>

                <!-- Remember Me -->
                <div class="flex items-center justify-between text-xs pt-1">
                    <label class="flex items-center gap-2 cursor-pointer text-slate-600 select-none">
                        <input type="checkbox" name="remember" class="w-4 h-4 rounded text-blue-600 focus:ring-blue-500 border-slate-300">
                        <span>Ingat sesi masuk di perangkat ini</span>
                    </label>
                </div>

                <!-- Submit Button -->
                <div class="pt-2">
                    <button type="submit" class="w-full flex justify-center items-center gap-2 py-2.5 px-4 border border-transparent rounded-xl shadow-xs text-xs font-bold text-white bg-blue-700 hover:bg-blue-800 focus:outline-hidden focus:ring-2 focus:ring-offset-2 focus:ring-blue-600 transition">
                        <i data-lucide="log-in" class="w-4 h-4"></i>
                        <span>Masuk ke Dashboard</span>
                    </button>
                </div>
            </form>

            <!-- Quick Demo Credentials Selector (Sangat Membantu saat Ujian/Sidang Skripsi) -->
            <div class="mt-6 pt-5 border-t border-slate-100">
                <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400 text-center mb-3">
                    Akses Cepat Pengujian (Demo Akun Skripsi)
                </div>
                <div class="grid grid-cols-2 gap-2 text-xs">
                    <button type="button" onclick="fillCredentials('admin@bkpsdm.go.id', 'admin123')"
                        class="p-2.5 rounded-xl border border-blue-200 bg-blue-50/60 hover:bg-blue-100 text-blue-900 text-left transition flex flex-col">
                        <span class="font-bold flex items-center gap-1.5 text-[11px]">
                            <i data-lucide="shield-check" class="w-3.5 h-3.5 text-blue-600"></i>
                            Role Admin BKPSDM
                        </span>
                        <span class="text-[10px] text-blue-600/80 font-mono mt-0.5">admin@bkpsdm.go.id</span>
                    </button>

                    <button type="button" onclick="fillCredentials('198507122010011005', 'password123')"
                        class="p-2.5 rounded-xl border border-slate-200 bg-slate-50 hover:bg-slate-100 text-slate-800 text-left transition flex flex-col">
                        <span class="font-bold flex items-center gap-1.5 text-[11px]">
                            <i data-lucide="user-check" class="w-3.5 h-3.5 text-emerald-600"></i>
                            Role Pegawai ASN
                        </span>
                        <span class="text-[10px] text-slate-500 font-mono mt-0.5">NIP: 19850712...</span>
                    </button>
                </div>
            </div>

        </div>

        <div class="text-center mt-6 text-xs text-slate-400">
            &copy; {{ date('Y') }} Badan Kepegawaian dan Pengembangan Sumber Daya Manusia (BKPSDM)
        </div>
    </div>

    <script>
        lucide.createIcons();

        function togglePassword() {
            const pwd = document.getElementById('password');
            const icon = document.getElementById('eyeIcon');
            if (pwd.type === 'password') {
                pwd.type = 'text';
                icon.setAttribute('data-lucide', 'eye-off');
            } else {
                pwd.type = 'password';
                icon.setAttribute('data-lucide', 'eye');
            }
            lucide.createIcons();
        }

        function fillCredentials(login, pwd) {
            document.getElementById('login').value = login;
            document.getElementById('password').value = pwd;
        }
    </script>
</body>
</html>

