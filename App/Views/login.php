<?php
if (!defined('ACCESS_ALLOWED')) {
    header('HTTP/1.1 403 Forbidden');
    exit('Direct access not allowed.');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión | MiMundoTrenzas</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        h1, h2, h3, h4 {
            font-family: 'Outfit', sans-serif;
        }
    </style>
</head>
<body class="bg-[#1E140F] text-[#FAF6F0] antialiased min-h-screen selection:bg-[#B56B45]/30">

    <div class="min-h-screen flex flex-col md:flex-row">
        <!-- Left Column: Form -->
        <div class="w-full md:w-1/2 flex flex-col justify-between p-8 sm:p-12 lg:p-16">
            <!-- Top Bar: Logo + Brand -->
            <div class="flex items-center justify-center space-x-3 mb-10 mr-9 md:mb-0">
                <img src="/HomeWorks/Design_Project/src/img/logo.jpeg" alt="Logo de MiMundoTrenzas" class="size-16 rounded-full border border-[#C5A059] shadow-md">
                <span class="font-extrabold tracking-tight text-lg text-white">MiMundoTrenzas</span>
            </div>

            <!-- Form Content -->
            <div class="max-w-sm w-full mx-auto my-auto py-8">
                <h2 class="text-3xl font-extrabold tracking-tight mb-2 text-white">Inicia sesión en tu cuenta</h2>
                <p class="text-sm text-[#FAF6F0]/65 mb-8">Ingresa tu correo a continuación para acceder al panel</p>

                <!-- Formulario AJAX -->
                <form id="login-form" class="space-y-5">
                    <div>
                        <label for="email" class="block text-xs font-bold uppercase tracking-wider text-[#FAF6F0]/80 mb-2">Correo Electrónico</label>
                        <input type="email" id="email" name="email" placeholder="correo@ejemplo.com"
                            class="block w-full px-4 py-3 bg-[#2D1E16] border border-[#3D2314] rounded-xl shadow-inner focus:outline-none focus:ring-2 focus:ring-[#B56B45] focus:border-transparent transition-all placeholder:text-[#FAF6F0]/30 text-[#FAF6F0] font-medium text-sm">
                    </div>

                    <div>
                        <div class="flex justify-between items-center mb-2">
                            <label for="password" class="block text-xs font-bold uppercase tracking-wider text-[#FAF6F0]/80">Contraseña</label>
                            <a href="#" class="text-[11px] font-bold text-[#C5A059] hover:text-[#B56B45] transition-colors">¿Olvidaste tu contraseña?</a>
                        </div>
                        <input type="password" id="password" name="password" placeholder="••••••••"
                            class="block w-full px-4 py-3 bg-[#2D1E16] border border-[#3D2314] rounded-xl shadow-inner focus:outline-none focus:ring-2 focus:ring-[#B56B45] focus:border-transparent transition-all placeholder:text-[#FAF6F0]/30 text-[#FAF6F0] font-medium text-sm">
                    </div>

                    <button type="submit" id="submit-btn" 
                        class="w-full flex justify-center items-center py-3.5 px-4 rounded-xl text-sm font-bold text-[#1E140F] bg-[#FAF6F0] hover:bg-[#B56B45] hover:text-white active:scale-[0.98] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#B56B45] transition-all cursor-pointer shadow-lg shadow-[#FAF6F0]/5">
                        <span id="btn-text">Ingresar</span>
                        <span id="btn-spinner" class="hidden ml-2 size-4 border-2 border-[#1E140F] border-t-transparent rounded-full animate-spin"></span>
                    </button>
                </form>

                

                <div class="mt-8 text-center text-xs text-[#FAF6F0]/60">
                    ¿No tienes una cuenta aún? 
                    <a href="/HomeWorks/Design_Project/register" class="font-bold text-[#C5A059] hover:text-[#B56B45] transition-colors ml-1">Regístrate aquí</a>
                </div>
            </div>

            <!-- Footer Copy -->
            <div class="text-center md:text-left text-[11px] text-[#FAF6F0]/30">
                &copy; <?php echo date('Y'); ?> MiMundoTrenzas. Diseñado para brillar.
            </div>
        </div>

        <!-- Right Column: Visual Image Cover -->
        <div class="hidden md:block w-1/2 relative bg-[#1E140F] overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-t from-[#1E140F] via-transparent to-[#1E140F]/40 z-10"></div>
            <img src="/HomeWorks/Design_Project/src/img/braid_goddess.png" alt="Goddess Braids" class="w-full h-full object-cover opacity-75 scale-105 hover:scale-100 transition-transform duration-[2000ms]">
            <!-- Slogan Over the Image -->
            <div class="absolute bottom-16 left-16 z-20 max-w-md space-y-3">
                <span class="inline-flex px-3 py-1 rounded-full text-[10px] font-bold bg-[#FAF6F0]/10 backdrop-blur-md text-[#C5A059] border border-[#FAF6F0]/15 uppercase tracking-wider">Estilo & Exclusividad</span>
                <h3 class="text-3xl font-extrabold text-white leading-tight">Trenzas hechas arte, diseñadas para ti</h3>
                <p class="text-sm text-[#FAF6F0]/70">Explora e inspírate con los peinados de trenzas premium más cotizados del mercado.</p>
            </div>
        </div>
    </div>

    <!-- Modales e Inyección de Utilidades JS de Alerta -->
    <?php require_once __DIR__ . '/partials/js_modal_utils.php'; ?>

    <script src="/HomeWorks/Design_Project/assets/js/login.js"></script>
</body>
</html>
