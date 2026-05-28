<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro | Plataforma</title>
    <link href="/HomeWorks/Design_Project/src/output.css" rel="stylesheet">
</head>
<body class="bg-slate-900 text-slate-800 font-sans antialiased min-h-screen flex items-center justify-center selection:bg-blue-200">
     <img src="/HomeWorks/Design_Project/src/img/logo.jpeg" alt="Logo de la Plataforma" class="mx-auto size-90 rounded-full">

    <div class="max-w-md w-full px-10 py-8 bg-white shadow-lg rounded-xl border border-gray-100 mx-16 my-6">
        <div class="mb-8 text-center">
            <h2 class="text-3xl font-bold text-slate-900 tracking-tight">Crear Cuenta</h2>
            <!-- NOTA: Modificar mensaje de registro -->
            <p class="text-sm text-slate-500 mt-2">Bienvenido a MiMundoTrenza</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="mb-6 bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded-r-md text-sm shadow-sm" >
                <p><?php echo htmlspecialchars($error); ?></p>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="mb-6 bg-emerald-50 border-l-4 border-emerald-500 text-emerald-800 p-4 rounded-r-md text-sm shadow-sm" role="alert">
                <p class="font-medium"><?php echo htmlspecialchars($success); ?></p>
                <div class="mt-3">
                    <a href="/HomeWorks/Design_Project/login" class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded shadow-sm text-white bg-emerald-600 hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 transition-colors">
                        Ir al login
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <?php if (empty($success)): ?>
        <form method="POST" action="/HomeWorks/Design_Project/register" class="space-y-5">
            <div>
                <label for="username" class="block text-sm font-medium text-slate-700 mb-1">Nombre de Usuario</label>
                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                    class="block w-full px-4 py-2 bg-slate-50 border border-slate-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-slate-700 mb-1">Correo Electrónico</label>
                <input type="text" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                    class="block w-full px-4 py-2 bg-slate-50 border border-slate-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-slate-700 mb-1">Contraseña</label>
                <input type="password" id="password" name="password" minlength="8"
                    class="block w-full px-4 py-2 bg-slate-50 border border-slate-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                <p class="text-xs text-slate-500 mt-1.5 leading-snug">La contraseña debe contener al menos 8 caracteres.</p>
            </div>

            <div>
                <label for="password_confirm" class="block text-sm font-medium text-slate-700 mb-1">Confirmar Contraseña</label>
                <input type="password" id="password_confirm" name="password_confirm" minlength="8"
                    class="block w-full px-4 py-2 bg-slate-50 border border-slate-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
            </div>

            <button type="submit" 
                class="w-full flex justify-center py-2.5 px-4 border border-transparent rounded-lg shadow-sm text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-600 transition-colors mt-6">
                Crear Cuenta
            </button>
        </form>
        <?php endif; ?>

        <p class="mt-8 text-center text-sm text-slate-600">
            ¿Ya tienes una cuenta? 
            <a href="/HomeWorks/Design_Project/login" class="font-semibold text-blue-600 hover:text-blue-700 transition-colors">Inicia sesión</a>
        </p>
    </div>

</body>
</html>
