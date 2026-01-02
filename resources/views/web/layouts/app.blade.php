<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Ürünler')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow-sm sticky top-0 z-50">
        <div class="container mx-auto px-4">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center space-x-8">
                    <a href="{{ route('products.index') }}" class="text-2xl font-bold text-orange-500">
                        <i class="fas fa-shopping-bag"></i> Mağaza
                    </a>
                    <nav class="hidden md:flex space-x-6">
                        <a href="{{ route('products.index') }}" class="text-gray-700 hover:text-orange-500 transition">Ana Sayfa</a>
                    </nav>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="{{ route('admin.login') }}" class="px-4 py-2 text-gray-700 hover:text-orange-500 transition">
                        <i class="fas fa-user-circle"></i> Giriş Yap
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main>
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white mt-16">
        <div class="container mx-auto px-4 py-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <h3 class="text-lg font-semibold mb-4">Hakkımızda</h3>
                    <p class="text-gray-400 text-sm">En iyi ürünleri sizlerle buluşturuyoruz.</p>
                </div>
                <div>
                    <h3 class="text-lg font-semibold mb-4">Hızlı Linkler</h3>
                    <ul class="space-y-2 text-sm text-gray-400">
                        <li><a href="{{ route('products.index') }}" class="hover:text-white">Ana Sayfa</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-lg font-semibold mb-4">İletişim</h3>
                    <p class="text-gray-400 text-sm">Destek için bizimle iletişime geçin.</p>
                </div>
                <div>
                    <h3 class="text-lg font-semibold mb-4">Takip Edin</h3>
                    <div class="flex space-x-4">
                        <a href="#" class="text-gray-400 hover:text-white"><i class="fab fa-facebook"></i></a>
                        <a href="#" class="text-gray-400 hover:text-white"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-gray-400 hover:text-white"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
            </div>
            <div class="border-t border-gray-700 mt-8 pt-8 text-center text-sm text-gray-400">
                <p>&copy; {{ date('Y') }} Tüm hakları saklıdır.</p>
            </div>
        </div>
    </footer>
</body>
</html>

