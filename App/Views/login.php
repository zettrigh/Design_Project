<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión</title>
    <link href="/HomeWorks/Design_Project/src/output.css" rel="stylesheet">
</head>
<body class="bg-slate-900 text-slate-800 font-sans antialiased min-h-screen flex items-center justify-center selection:bg-blue-200">
    <img src="/HomeWorks/Design_Project/src/img/logo.jpeg" alt="Logo de la Plataforma" class="mx-auto size-90 rounded-full">

    <div class="max-w-md w-full px-10 py-8 bg-white shadow-lg rounded-xl border border-gray-100 mx-16 my-8">
        
        <div class="mb-8 text-center">
            <h2 class="text-3xl font-bold text-slate-900 tracking-tight">Bienvenido</h2>
            <!-- NOTA: Modificar mensaje de login -->
            <p class="text-sm text-slate-500 mt-2">Inicia sesión para acceder a tu panel de bienvenida</p>
        </div>

        <!-- Mostrar mensaje de error -->
        <?php if (!empty($error)): ?>
            <div class="mb-6 bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded-r-md text-sm shadow-sm" role="alert">
                <p><?php echo htmlspecialchars($error); ?></p>
            </div>
        <?php endif; ?>

        <!-- La acción apunta de vuelta a la misma ruta -->
        <form method="POST" action="/HomeWorks/Design_Project/login" class="space-y-6">
            <div>
                <label for="email" class="block text-sm font-medium text-slate-700 mb-1">Correo Electrónico</label>
                <input type="email" id="email" name="email"  
                    class="block w-full px-4 py-2.5 bg-slate-50 border border-slate-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-slate-700 mb-1">Contraseña</label>
                <input type="password" id="password" name="password" 
                    class="block w-full px-4 py-2.5 bg-slate-50 border border-slate-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
            </div>

            <button type="submit" 
                class="w-full flex justify-center py-2.5 px-4 border border-transparent rounded-lg shadow-sm text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-600 transition-colors">
                Ingresar
            </button>
        </form>

        <p class="mt-8 text-center text-sm text-slate-600">
            ¿No tienes cuenta? 
            <a href="/HomeWorks/Design_Project/register" class="font-semibold text-blue-600 hover:text-blue-700 transition-colors">Regístrate aquí</a>
        </p>
    </div>

</body>
</html>
