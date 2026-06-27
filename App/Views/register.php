<?php
if (!defined('ACCESS_ALLOWED')) {
    header('HTTP/1.1 403 Forbidden');
    exit('Direct access not allowed.');
}
$baseUrl = $baseUrl ?? '/HomeWorks/Design_Project';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Cuenta | MiMundoTrenzas</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>/src/output.css">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        h1, h2, h3, h4 { font-family: 'Outfit', sans-serif; }
    </style>
</head>
<body class="bg-[#FAF7F2] text-[#5C4333] antialiased min-h-screen selection:bg-[#B56B45]/20">

    <div class="min-h-screen flex flex-col md:flex-row">
        <!-- Left Column: Form -->
        <div class="w-full md:w-1/2 flex flex-col justify-between p-8 sm:p-12 lg:p-16">
            <div class="flex items-center justify-center mr-9 space-x-3 mb-8 md:mb-0">
                <img src="<?php echo $baseUrl; ?>/src/img/logo.jpeg" alt="Logo de MiMundoTrenzas" class="size-16 rounded-full border border-[#C5A059] shadow-md">
                <span class="font-extrabold tracking-tight text-lg text-[#5C4333]">MiMundoTrenzas</span>
            </div>

            <div class="max-w-sm w-full mx-auto my-auto py-6">
                <h2 class="text-3xl font-extrabold tracking-tight mb-2 text-[#3D2314]">Crea tu cuenta</h2>
                <p class="text-sm text-[#5C4333]/65 mb-6">Regístrate a continuación para agendar tus peinados</p>

                <form id="register-form" class="space-y-4">
                    <div>
                        <label for="username" class="block text-xs font-bold uppercase tracking-wider text-[#5C4333]/80 mb-1.5">Nombre de Usuario</label>
                        <input type="text" id="username" name="username" placeholder="TuNombre12"
                            class="block w-full px-4 py-2.5 bg-white border border-[#E5DDD5] rounded-xl shadow-inner focus:outline-none focus:ring-2 focus:ring-[#B56B45] focus:border-transparent transition-all placeholder:text-[#5C4333]/30 text-[#3D2314] font-medium text-xs">
                    </div>
                    <div>
                        <label for="email" class="block text-xs font-bold uppercase tracking-wider text-[#5C4333]/80 mb-1.5">Correo Electrónico</label>
                        <input type="email" id="email" name="email" placeholder="correo@ejemplo.com"
                            class="block w-full px-4 py-2.5 bg-white border border-[#E5DDD5] rounded-xl shadow-inner focus:outline-none focus:ring-2 focus:ring-[#B56B45] focus:border-transparent transition-all placeholder:text-[#5C4333]/30 text-[#3D2314] font-medium text-xs">
                    </div>
                    <div>
                        <label for="password" class="block text-xs font-bold uppercase tracking-wider text-[#5C4333]/80 mb-1.5">Contraseña</label>
                        <input type="password" id="password" name="password" minlength="8" placeholder="Mínimo 8 caracteres"
                            class="block w-full px-4 py-2.5 bg-white border border-[#E5DDD5] rounded-xl shadow-inner focus:outline-none focus:ring-2 focus:ring-[#B56B45] focus:border-transparent transition-all placeholder:text-[#5C4333]/30 text-[#3D2314] font-medium text-xs">
                    </div>
                    <div class="mb-5">
                        <label for="password_confirm" class="block text-xs font-bold uppercase tracking-wider text-[#5C4333]/80 mb-1.5">Confirmar Contraseña</label>
                        <input type="password" id="password_confirm" name="password_confirm" minlength="8" placeholder="Repite la contraseña"
                            class="block w-full px-4 py-2.5 bg-white border border-[#E5DDD5] rounded-xl shadow-inner focus:outline-none focus:ring-2 focus:ring-[#B56B45] focus:border-transparent transition-all placeholder:text-[#5C4333]/30 text-[#3D2314] font-medium text-xs">
                    </div>
                    <button type="submit" id="submit-btn"
                        class="w-full flex justify-center items-center py-3.5 px-4 rounded-xl text-sm font-bold text-white bg-[#5C4333] hover:bg-[#4A3628] active:scale-[0.98] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#B56B45] transition-all cursor-pointer shadow-lg mt-2">
                        <span id="btn-text">Crear Cuenta</span>
                        <span id="btn-spinner" class="hidden ml-2 size-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                    </button>
                </form>

                <div class="mt-6 text-center text-xs text-[#5C4333]/60">
                    ¿Ya tienes una cuenta?
                    <a href="<?php echo $baseUrl; ?>/login" class="font-bold text-[#B56B45] hover:text-[#8B4A2E] transition-colors ml-1">Inicia sesión</a>
                </div>
            </div>

            <div class="text-center md:text-left text-[11px] text-[#5C4333]/30">
                &copy; <?php echo date('Y'); ?> MiMundoTrenzas. Diseñado para brillar.
            </div>
        </div>

        <div class="hidden md:block w-1/2 relative bg-[#5C4333] overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-t from-[#3D2314] via-transparent to-[#3D2314]/40 z-10"></div>
            <img src="<?php echo $baseUrl; ?>/src/img/braid_box.png" alt="Box Braids" class="w-full h-full object-cover opacity-70 scale-105 hover:scale-100 transition-transform duration-[2000ms]">
            <div class="absolute bottom-16 left-16 z-20 max-w-md space-y-3">
                <span class="inline-flex px-3 py-1 rounded-full text-[10px] font-bold bg-white/10 backdrop-blur-md text-[#C5A059] border border-white/15 uppercase tracking-wider">Cuidado & Protección</span>
                <h3 class="text-3xl font-extrabold text-white leading-tight">Luce espectacular, siéntete única</h3>
                <p class="text-sm text-white/70">Únete a nuestra comunidad hoy mismo para acceder a promociones exclusivas e historial de apartados.</p>
            </div>
        </div>
    </div>

    <?php require_once __DIR__ . '/partials/js_modal_utils.php'; ?>

    <script src="<?php echo $baseUrl; ?>/assets/js/register.js"></script>
    <script>
        window.BASE_URL = '<?php echo $baseUrl; ?>';
    </script>
</body>
</html>
