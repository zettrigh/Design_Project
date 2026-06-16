<?php
if (!defined('ACCESS_ALLOWED')) {
    header('HTTP/1.1 403 Forbidden');
    exit('Direct access not allowed.');
}
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($username)) { header('Location: ' . ($baseUrl ?? '/HomeWorks/Design_Project') . '/login'); exit; }
$baseUrl = $baseUrl ?? '/HomeWorks/Design_Project';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Cliente | MiMundoTrenzas</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <style>body{font-family:'Plus Jakarta Sans',sans-serif}h1,h2,h3,h4{font-family:'Outfit',sans-serif}</style>
</head>
<body class="bg-[#FAF6F0] text-[#5C4333] antialiased min-h-screen selection:bg-[#B56B45]/20">

    <nav class="bg-[#5C4333] shadow-md border-b border-[#5C4333]/10 sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-20">
                <div class="flex items-center space-x-3">
                    <img src="<?php echo $baseUrl; ?>/src/img/logo.jpeg" alt="Logo" class="size-12 rounded-full border-2 border-[#FAF6F0] shadow-md">
                    <div>
                        <h1 class="text-xl sm:text-2xl font-extrabold text-[#FAF6F0] tracking-tight">MiMundoTrenzas</h1>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="<?php echo $baseUrl; ?>/logout" class="inline-flex items-center gap-1.5 px-5 py-2.5 bg-red-600 hover:bg-red-500 text-white font-bold rounded-xl text-xs sm:text-sm shadow-lg shadow-red-900/30 hover:shadow-red-800/40 transition-all duration-200 cursor-pointer">Cerrar Sesión</a>
                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto py-10 px-4 sm:px-6 lg:px-8 space-y-12">

        <!-- Welcome Card -->
        <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-[#5C4333] to-[#3D2B1E] p-8 sm:p-10 text-[#FAF6F0] shadow-xl">
            <div class="absolute -right-16 -top-16 size-48 rounded-full bg-[#B56B45]/10 blur-xl"></div>
            <div class="relative z-10 space-y-3">
                <h2 class="text-3xl sm:text-4xl font-extrabold tracking-tight">¡Hola, <?php echo htmlspecialchars($username); ?>!</h2>
                <p class="text-sm sm:text-base text-[#FAF6F0]/75 max-w-xl">Explora los peinados de trenzas premium que tenemos listos para ti. Aparta tu favorito en línea y paga de forma segura.</p>
            </div>
        </div>

        <!-- Catalog Section -->
        <section class="space-y-6">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center border-b border-[#EFE5D9] pb-4">
                <div>
                    <h3 class="text-2xl font-extrabold tracking-tight">Catálogo de Trenzas</h3>
                    <p class="text-sm text-[#5C4333]/65">Selecciona el diseño de tus sueños para apartarlo</p>
                </div>
                <div class="mt-3 sm:mt-0">
                    <span class="inline-flex items-center px-3 py-1.5 bg-white border border-[#EFE5D9] rounded-lg text-xs font-bold text-[#5C4333]">
                        💵 Precios en USD
                    </span>
                </div>
            </div>

            <?php if (empty($hairstyles)): ?>
                <div class="bg-white rounded-3xl p-12 text-center border border-[#EFE5D9] shadow-sm max-w-md mx-auto">
                    <h4 class="text-lg font-bold">Sin peinados activos</h4>
                    <p class="text-sm text-[#5C4333]/50 mt-1">Actualmente no hay peinados listados para venta.</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    <?php foreach ($hairstyles as $style): ?>
                        <div class="bg-white rounded-3xl border border-[#EFE5D9]/70 overflow-hidden shadow-md flex flex-col group hover:shadow-xl hover:border-[#B56B45]/30 transition-all duration-300 transform hover:-translate-y-1" data-hairstyle-id="<?php echo $style['id']; ?>">
                            <div class="relative overflow-hidden aspect-[4/3] bg-stone-100">
                                <img src="<?php echo htmlspecialchars($style['image_url']); ?>" alt="<?php echo htmlspecialchars($style['name']); ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                                <span class="absolute top-3 right-3 inline-flex px-3 py-1 rounded-full text-xs font-bold bg-[#FAF6F0]/90 backdrop-blur-sm text-[#B56B45] shadow-sm border border-[#EFE5D9]">Activo</span>
                            </div>
                            <div class="p-6 flex-1 flex flex-col justify-between space-y-4">
                                <div class="space-y-2">
                                    <h4 class="text-lg font-extrabold text-[#5C4333] line-clamp-1"><?php echo htmlspecialchars($style['name']); ?></h4>
                                    <p class="text-xs text-[#5C4333]/70 line-clamp-3 leading-relaxed"><?php echo htmlspecialchars($style['description']); ?></p>
                                </div>
                                <div class="space-y-3 pt-2">
                                    <div class="flex justify-between items-center">
                                        <span class="text-xs uppercase font-bold tracking-widest text-[#5C4333]/40">Precio</span>
                                        <span class="text-xl font-extrabold text-[#B56B45]">$<?php echo number_format($style['price'], 2); ?> USD</span>
                                    </div>
                                    <button onclick="apartarPeinado(<?php echo $style['id']; ?>)" class="w-full py-2.5 px-4 bg-[#5C4333] hover:bg-[#B56B45] active:scale-[0.98] text-[#FAF6F0] font-bold rounded-xl text-xs tracking-wider uppercase transition-all shadow-md cursor-pointer">Apartar Peinado</button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <!-- Reservations Section -->
        <section class="space-y-6">
            <div class="border-b border-[#EFE5D9] pb-4">
                <h3 class="text-2xl font-extrabold tracking-tight">Mis Apartados Realizados</h3>
                <p class="text-sm text-[#5C4333]/65">Seguimiento en tiempo real del estado de tus reservas</p>
            </div>
            <?php if (empty($reservations)): ?>
                <div class="bg-white/60 rounded-3xl p-10 text-center border border-dashed border-[#EFE5D9] max-w-lg mx-auto">
                    <p class="text-[#5C4333]/60 text-sm">Aún no has apartado ningún peinado. ¡Anímate y elige el tuyo arriba!</p>
                </div>
            <?php else: ?>
                <div class="bg-white rounded-3xl border border-[#EFE5D9]/70 overflow-hidden shadow-md">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-[#EFE5D9]/50">
                            <thead class="bg-[#FAF6F0]/60"><tr>
                                <th class="px-6 py-4 text-left text-xs font-bold text-[#5C4333]/50 uppercase tracking-widest">Peinado</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-[#5C4333]/50 uppercase tracking-widest hidden md:table-cell">Precio</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-[#5C4333]/50 uppercase tracking-widest">Fecha</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-[#5C4333]/50 uppercase tracking-widest">Estado</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-[#5C4333]/50 uppercase tracking-widest">Pago</th>
                            </tr></thead>
                            <tbody class="divide-y divide-[#EFE5D9]/40">
                                <?php foreach ($reservations as $res): ?>
                                <tr class="hover:bg-[#FAF6F0]/30 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center space-x-3">
                                            <img src="<?php echo htmlspecialchars($res['image_url']); ?>" alt="" class="size-10 rounded-lg object-cover border border-[#EFE5D9]">
                                            <div>
                                                <div class="text-sm font-extrabold text-[#5C4333]"><?php echo htmlspecialchars($res['hairstyle_name']); ?></div>
                                                <div class="text-xs text-[#5C4333]/40 md:hidden">$<?php echo number_format($res['price'], 2); ?> USD</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-extrabold text-[#B56B45] hidden md:table-cell">$<?php echo number_format($res['price'], 2); ?> USD</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-[#5C4333]/70 font-medium"><?php echo date('d M, Y', strtotime($res['reserved_at'])); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php if ($res['status'] === 'pending'): ?>
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200/50 animate-pulse">Pendiente</span>
                                        <?php elseif ($res['status'] === 'confirmed'): ?>
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200/50">Confirmado</span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-stone-100 text-stone-600 border border-stone-200">Cancelado</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php if ($res['status'] === 'pending'): ?>
                                            <button onclick="iniciarPago(<?php echo $res['id']; ?>, '<?php echo number_format($res['price'], 2, '.', ''); ?>', '<?php echo htmlspecialchars($res['hairstyle_name'], ENT_QUOTES); ?>')" class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-bold transition-all cursor-pointer shadow-sm">Pagar Ahora</button>
                                        <?php else: ?>
                                            <span class="text-xs text-[#5C4333]/40">—</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <!-- Modal de Pago -->
    <div id="payment-modal" class="fixed inset-0 bg-stone-950/60 backdrop-blur-sm flex items-center justify-center z-50 opacity-0 pointer-events-none transition-opacity duration-300">
        <div id="payment-box" class="bg-white border border-[#EFE5D9] rounded-2xl max-w-md w-full p-6 shadow-2xl transform scale-95 transition-transform duration-300 mx-4">
            <h3 class="text-xl font-extrabold text-[#5C4333] mb-2">Procesar Pago</h3>
            <div id="payment-info" class="space-y-3 mb-6">
                <p class="text-sm text-[#5C4333]/70">Peinado: <strong id="pay-hairstyle-name"></strong></p>                    <div class="bg-[#FAF6F0] rounded-xl p-4 space-y-2">
                        <div class="flex justify-between text-sm"><span class="text-[#5C4333]/60">Peinado:</span><span class="font-bold" id="pay-hairstyle-detail"></span></div>
                        <div class="border-t border-[#EFE5D9] pt-2 flex justify-between"><span class="text-sm font-bold text-[#5C4333]">Total a pagar:</span><span class="text-lg font-extrabold text-[#B56B45]" id="pay-amount-usd">Calculando...</span></div>
                    </div>
            </div>
            <div class="flex gap-2">
                <button onclick="confirmarPago()" id="confirm-pay-btn" class="flex-1 py-2.5 px-4 bg-[#5C4333] hover:bg-[#3D2B1E] text-[#FAF6F0] font-bold rounded-xl text-xs uppercase tracking-wider transition-all cursor-pointer">Confirmar Pago</button>
                <button onclick="closePaymentModal()" class="px-4 py-2.5 bg-stone-200 hover:bg-stone-300 text-stone-700 font-bold rounded-xl text-xs uppercase tracking-wider transition-all cursor-pointer">Cancelar</button>
            </div>
        </div>
    </div>

    <?php require_once __DIR__ . '/partials/js_modal_utils.php'; ?>
    <script>window.BASE_URL = '<?php echo $baseUrl; ?>';</script>
    <script src="<?php echo $baseUrl; ?>/assets/js/dashboard_user.js"></script>
</body>
</html>
