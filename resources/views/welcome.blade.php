<!DOCTYPE html>
<html lang="tr">
    <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>{{ config('app.name', 'Laravel') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    </head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center">
    <div class="text-center">
        <h1 class="text-4xl font-bold text-gray-900 mb-8">{{ config('app.name', 'Laravel') }}</h1>
        <a 
            href="{{ route('admin.login') }}" 
            class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-8 rounded-lg transition duration-200 shadow-lg hover:shadow-xl"
        >
            Admin Paneline Giriş
        </a>
        </div>
    </body>
</html>
