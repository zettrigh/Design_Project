<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Plataforma</title>
    <link href="/HomeWorks/Design_Project/src/output.css" rel="stylesheet">
</head>
<body class="bg-slate-900 text-slate-800 font-sans antialiased min-h-screen">

    <nav class="bg-slate-800 shadow">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <img src="/HomeWorks/Design_Project/src/img/logo.jpeg" alt="Logo de la Plataforma" class="mx-2 size-10 rounded-full">
                    <h1 class="text-xl font-bold text-white tracking-tight">MiMundotrenzas</h1>
                </div>
                <div class=" flex items-center space-x-6">
                    <a href="/HomeWorks/Design_Project/logout" class="text-sm px-4 py-2 border border-red-600 rounded-lg shadow-sm font-medium text-white hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors hover:text-black">
                        Cerrar Sesión
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto py-10 px-4 sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm border border-slate-200 rounded-xl">
            <div class="px-6 py-8">
                <h3 class="text-2xl leading-6 font-bold text-center text-slate-900 mb-2">¡Bienvenido!</h3>
                <p class="text-slate-500 text-center text-sm mb-8">Estás conectado como <?php echo htmlspecialchars($username); ?>.</p>

                <!-- div para acomodarlo en columnas de 2 -->
                <div class="flex flex-row justify-between"> 
                    <div class="mb-4 mx-10 bg-blue-50/70 p-6 border border-blue-100 items rounded-lg">
                        <div class="mx-4 px-8">
                            <h4 class="text-center font-bold text-blue-900 mb-1">Foto1</h4>
                            <p class="text-sm text-blue-800 mt-2">
                                descripcion.
                            </p>
                        </div>
                    </div>
                    <div class="mb-4 mx-10 bg-blue-50/70 p-6 border border-blue-100 items rounded-lg">
                        <div class="mx-4 px-8">
                            <h4 class="text-center font-bold text-blue-900 mb-1">Foto2</h4>
                            <p class="text-sm text-blue-800 mt-2">
                                descripcion.
                            </p>
                        </div>
                    </div>
                    <div class="mb-4 mx-10 bg-blue-50/70 p-6 border border-blue-100 items rounded-lg">
                        <div class="mx-4 px-8">
                            <h4 class="text-center font-bold text-blue-900 mb-1">Foto3</h4>
                            <p class="text-sm text-blue-800 mt-2">
                                descripcion.
                            </p>
                        </div>
                    </div>
                    <div class="mb-4 mx-10 bg-blue-50/70 p-6 border border-blue-100 items rounded-lg">
                        <div class="mx-4 px-8">
                            <h4 class="text-center font-bold text-blue-900 mb-1">Foto4</h4>
                            <p class="text-sm text-blue-800 mt-2">
                                descripcion.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

</body>
</html>
