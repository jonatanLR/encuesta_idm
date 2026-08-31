<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Prueba de comunidades</title>

    @vite([
        'resources/css/app.css',
        'resources/js/app.js'
    ])

    @livewireStyles
</head>

<body class="bg-gray-100">

    <main class="mx-auto max-w-3xl px-6 py-12">

        <div class="rounded-xl bg-white p-8 shadow">

            <h1 class="text-2xl font-bold text-gray-900">
                Prueba de búsqueda de comunidades Test
            </h1>

            <p class="mt-2 text-gray-600">
                Distrito Central
            </p>

            <div class="mt-8">
                <livewire:community-search />
            </div>

        </div>

    </main>

    @livewireScripts

</body>

</html>