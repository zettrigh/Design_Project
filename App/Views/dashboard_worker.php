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
    <title>Panel de Trabajador | MiMundoTrenzas</title>
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
                        <p class="text-[10px] uppercase font-bold tracking-widest text-[#C5A059]">Panel de Trabajador</p>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="hidden md:inline text-[#FAF6F0]/80 text-sm font-medium">Trabajador: <strong class="text-[#FAF6F0]"><?php echo htmlspecialchars($username); ?></strong></span>
                    <a href="<?php echo $baseUrl; ?>/logout" class="inline-flex items-center gap-1.5 px-5 py-2.5 bg-red-600 hover:bg-red-500 text-white font-bold rounded-xl text-xs sm:text-sm shadow-lg shadow-red-900/30 hover:shadow-red-800/40 transition-all duration-200 cursor-pointer">Cerrar Sesión</a>
                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto py-10 px-4 sm:px-6 lg:px-8 space-y-12">

        <!-- CRUD Peinados -->
        <section class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="bg-white rounded-3xl border border-[#EFE5D9] p-6 shadow-md h-fit space-y-6">
                <div>
                    <h3 id="form-title" class="text-xl font-extrabold tracking-tight">Agregar Nuevo Peinado</h3>
                    <p id="form-desc" class="text-xs text-[#5C4333]/55 mt-1">Completa los campos para publicar un peinado</p>
                </div>
                <form id="hairstyle-form" class="space-y-4">
                    <input type="hidden" id="hairstyle-id" name="id" value="">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-[#5C4333]/60 mb-1">Nombre</label>
                        <input type="text" id="name" name="name" required placeholder="Ej: Trenzas Box" class="block w-full px-4 py-2.5 bg-[#FAF6F0]/60 border border-[#EFE5D9] rounded-xl focus:outline-none focus:ring-2 focus:ring-[#B56B45] text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-[#5C4333]/60 mb-1">Descripción</label>
                        <textarea id="description" name="description" required rows="3" placeholder="Describe materiales, duración, etc." class="block w-full px-4 py-2.5 bg-[#FAF6F0]/60 border border-[#EFE5D9] rounded-xl focus:outline-none focus:ring-2 focus:ring-[#B56B45] text-sm"></textarea>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-[#5C4333]/60 mb-1">Precio (USD)</label>
                            <input type="number" id="price" name="price" step="0.01" min="1" required placeholder="0.00" class="block w-full px-4 py-2.5 bg-[#FAF6F0]/60 border border-[#EFE5D9] rounded-xl focus:outline-none focus:ring-2 focus:ring-[#B56B45] text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-[#5C4333]/60 mb-1">Estado</label>
                            <select id="status" name="status" required class="block w-full px-4 py-2.5 bg-[#FAF6F0]/60 border border-[#EFE5D9] rounded-xl focus:outline-none focus:ring-2 focus:ring-[#B56B45] text-sm">
                                <option value="active">Activo</option>
                                <option value="inactive">Inactivo</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-[#5C4333]/60 mb-1">URL Imagen</label>
                        <input type="text" id="image_url" name="image_url" placeholder="URL de la imagen" class="block w-full px-4 py-2.5 bg-[#FAF6F0]/60 border border-[#EFE5D9] rounded-xl focus:outline-none focus:ring-2 focus:ring-[#B56B45] text-sm">
                    </div>
                    <div class="flex flex-col gap-2 pt-2">
                        <button type="submit" id="submit-btn" class="w-full py-3 px-4 bg-[#5C4333] hover:bg-[#3D2B1E] text-[#FAF6F0] font-bold rounded-xl text-xs uppercase tracking-wider transition-all shadow-md cursor-pointer"><span id="btn-text">Publicar Peinado</span></button>
                        <button type="button" id="cancel-btn" class="hidden w-full py-2 px-4 bg-stone-200 hover:bg-stone-300 text-stone-700 font-bold rounded-xl text-xs uppercase tracking-wider transition-all cursor-pointer">Cancelar Edición</button>
                    </div>
                </form>
            </div>

            <div class="bg-white rounded-3xl border border-[#EFE5D9] p-6 shadow-md lg:col-span-2 space-y-6">
                <div>
                    <h3 class="text-xl font-extrabold tracking-tight">Catálogo Publicado</h3>
                    <p class="text-xs text-[#5C4333]/55 mt-1">Edita o elimina los peinados registrados</p>
                </div>
                <?php if (empty($hairstyles)): ?>
                    <p class="text-sm text-[#5C4333]/50 text-center py-6">No hay peinados registrados.</p>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-[#EFE5D9]/40">
                            <thead><tr class="bg-[#FAF6F0]/40">
                                <th class="px-4 py-3 text-left text-xs font-bold text-[#5C4333]/50 uppercase tracking-widest">Peinado</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-[#5C4333]/50 uppercase tracking-widest">Precio</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-[#5C4333]/50 uppercase tracking-widest">Estado</th>
                                <th class="px-4 py-3 text-right text-xs font-bold text-[#5C4333]/50 uppercase tracking-widest">Acciones</th>
                            </tr></thead>
                            <tbody class="divide-y divide-[#EFE5D9]/30">
                                <?php foreach ($hairstyles as $style): ?>
                                <tr class="hover:bg-[#FAF6F0]/20 transition-colors">
                                    <td class="px-4 py-3"><div class="flex items-center space-x-3"><img src="<?php echo htmlspecialchars($style['image_url']); ?>" alt="" class="size-10 rounded-lg object-cover border border-[#EFE5D9]"><div><div class="text-sm font-extrabold text-[#5C4333]"><?php echo htmlspecialchars($style['name']); ?></div><div class="text-xs text-[#5C4333]/40 line-clamp-1 max-w-[200px]"><?php echo htmlspecialchars($style['description']); ?></div></div></div></td>
                                    <td class="px-4 py-3 text-sm font-extrabold text-[#B56B45]">$<?php echo number_format($style['price'], 2); ?></td>
                                    <td class="px-4 py-3"><?php if ($style['status'] === 'active'): ?><span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200/50">Activo</span><?php else: ?><span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold bg-stone-100 text-stone-600 border border-stone-200">Inactivo</span><?php endif; ?></td>
                                    <td class="px-4 py-3 text-right whitespace-nowrap text-xs font-semibold space-x-1">
                                        <button onclick="iniciarEdicion(<?php echo htmlspecialchars(json_encode($style), ENT_QUOTES, 'UTF-8'); ?>)" class="px-2.5 py-1 text-[#B56B45] hover:bg-[#B56B45]/10 rounded-lg border border-[#B56B45]/20 hover:border-[#B56B45] transition-all cursor-pointer">Editar</button>
                                        <button onclick="eliminarPeinado(<?php echo $style['id']; ?>)" class="px-2.5 py-1 text-red-600 hover:bg-red-50 rounded-lg border border-red-200/60 hover:border-red-500 transition-all cursor-pointer">Eliminar</button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- Reservas -->
        <section class="bg-white rounded-3xl border border-[#EFE5D9] p-6 sm:p-8 shadow-md space-y-6">
            <div><h3 class="text-2xl font-extrabold tracking-tight">Gestión de Apartados</h3><p class="text-sm text-[#5C4333]/65">Confirma o cancela las solicitudes de los clientes</p></div>
            <?php if (empty($reservations)): ?>
                <p class="text-sm text-[#5C4333]/50 text-center py-8">No hay reservas pendientes.</p>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-[#EFE5D9]/40">
                        <thead class="bg-[#FAF6F0]/60"><tr>
                            <th class="px-6 py-4 text-left text-xs font-bold text-[#5C4333]/50 uppercase tracking-widest">Cliente</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-[#5C4333]/50 uppercase tracking-widest">Peinado</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-[#5C4333]/50 uppercase tracking-widest">Precio</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-[#5C4333]/50 uppercase tracking-widest">Fecha</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-[#5C4333]/50 uppercase tracking-widest">Estado</th>
                            <th class="px-6 py-4 text-right text-xs font-bold text-[#5C4333]/50 uppercase tracking-widest">Acciones</th>
                        </tr></thead>
                        <tbody class="divide-y divide-[#EFE5D9]/40">
                            <?php foreach ($reservations as $res): ?>
                            <tr class="hover:bg-[#FAF6F0]/20 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap"><div class="text-sm font-extrabold text-[#5C4333]"><?php echo htmlspecialchars($res['username']); ?></div><div class="text-xs text-[#5C4333]/50"><?php echo htmlspecialchars($res['email']); ?></div></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-[#5C4333]/85"><?php echo htmlspecialchars($res['hairstyle_name']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-extrabold text-[#B56B45]">$<?php echo number_format($res['price'], 2); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-[#5C4333]/75"><?php echo date('d M, Y', strtotime($res['reserved_at'])); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php if ($res['status'] === 'pending'): ?><span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200/50">Pendiente</span><?php elseif ($res['status'] === 'confirmed'): ?><span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200/50">Confirmado</span><?php else: ?><span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-stone-100 text-stone-600 border border-stone-200">Cancelado</span><?php endif; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-xs font-semibold space-x-1.5">
                                    <?php if ($res['status'] === 'pending'): ?>
                                        <button onclick="cambiarEstadoReserva(<?php echo $res['id']; ?>, 'confirmed')" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg transition-all cursor-pointer shadow-sm">Confirmar</button>
                                        <button onclick="cambiarEstadoReserva(<?php echo $res['id']; ?>, 'cancelled')" class="px-3 py-1.5 bg-stone-200 hover:bg-stone-300 text-stone-700 rounded-lg transition-all cursor-pointer">Cancelar</button>
                                    <?php else: ?>
                                        <button onclick="cambiarEstadoReserva(<?php echo $res['id']; ?>, 'pending')" class="px-2.5 py-1.5 text-amber-700 hover:bg-amber-50 border border-amber-200 rounded-lg transition-all cursor-pointer">Reabrir</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <?php require_once __DIR__ . '/partials/js_modal_utils.php'; ?>
    <script>window.BASE_URL = '<?php echo $baseUrl; ?>';</script>
    <script src="<?php echo $baseUrl; ?>/assets/js/dashboard_worker.js"></script>
</body>
</html>
