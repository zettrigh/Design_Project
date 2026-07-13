<?php
if (!defined('ACCESS_ALLOWED')) {
    header('HTTP/1.1 403 Forbidden');
    exit('Direct access not allowed.');
}

/** @var string $username */
/** @var array $hairstyles */
/** @var array $reservations */
/** @var string $baseUrl */

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
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>/src/output.css">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif }
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            z-index: -1;
            background: url('<?php echo $baseUrl; ?>/src/img/braid_goddess.png') center / cover no-repeat;
            filter: blur(12px);
            -webkit-filter: blur(12px);
        }
        h1, h2, h3, h4 { font-family: 'Outfit', sans-serif }
        #sidebar { transition: width 0.3s ease, transform 0.3s ease; }
        #main-content { transition: margin-left 0.3s ease; }
        #sidebar.collapsed .sidebar-label { display: none; }
        #sidebar-overlay { opacity: 0; pointer-events: none; transition: opacity 0.3s ease; }
        #sidebar-overlay.show { opacity: 1; pointer-events: auto; }
        @media (max-width: 767px) {
            #section-hairstyles table,
            #section-hairstyles thead,
            #section-hairstyles tbody,
            #section-hairstyles th,
            #section-hairstyles td,
            #section-hairstyles tr,
            #section-reservations table,
            #section-reservations thead,
            #section-reservations tbody,
            #section-reservations th,
            #section-reservations td,
            #section-reservations tr { display: block; }
            #section-hairstyles thead,
            #section-reservations thead { display: none; }
            #section-hairstyles tr,
            #section-reservations tr {
                background: white;
                border: 1px solid #EFE5D9;
                border-radius: 0.75rem;
                padding: 0.75rem;
                margin-bottom: 0.75rem;
            }
            #section-hairstyles td,
            #section-reservations td {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 0.5rem 0;
                border: none;
                white-space: normal;
            }
            #section-hairstyles td::before,
            #section-reservations td::before {
                content: attr(data-label);
                font-weight: 700;
                font-size: 0.75rem;
                text-transform: uppercase;
                letter-spacing: 0.05em;
                color: rgba(92, 67, 51, 0.5);
            }
            #section-hairstyles .overflow-x-auto,
            #section-reservations .overflow-x-auto { overflow: visible !important; }
        }
        @media (max-width: 767px) {
            #sidebar { width: 16rem !important; transform: translateX(-100%); }
            #sidebar.mobile-open { transform: translateX(0); }
            #main-content { margin-left: 0 !important; }
        }
    </style>
</head>

<body class="bg-[#FAF6F0] text-[#5C4333] antialiased min-h-screen selection:bg-[#B56B45]/20">

    <div class="min-h-screen">

        <button class="fixed top-4 right-4 z-[60] p-2 rounded-xl bg-[#5C4333] text-[#FAF6F0] shadow-lg hover:bg-[#3D2B1E] transition-all cursor-pointer md:hidden" id="mobile-menu-btn">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>

        <div id="sidebar-overlay" class="fixed inset-0 bg-black/40 z-40" onclick="closeMobileSidebar()"></div>

        <!-- Sidebar -->
        <aside id="sidebar" class="fixed inset-y-0 left-0 z-50 w-64 bg-[#5C4333] flex flex-col shadow-xl border-r border-[#5C4333]/10">

            <div class="p-5 border-b border-[#FAF6F0]/10">
                <div class="flex items-center space-x-3">
                    <img src="<?php echo $baseUrl; ?>/src/img/logo.jpeg" alt="Logo" class="size-10 rounded-full border-2 border-[#FAF6F0] shadow-md">
                    <div class="sidebar-label whitespace-nowrap">
                        <h1 class="text-base font-extrabold text-[#FAF6F0] tracking-tight">MiMundoTrenzas</h1>
                        <p class="text-[9px] uppercase font-bold tracking-widest text-[#C5A059]">Trabajador</p>
                    </div>
                </div>
            </div>

            <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1">
                <button onclick="showSection('hairstyles')" class="nav-btn w-full flex items-center space-x-3 px-4 py-2.5 rounded-xl text-sm font-bold text-[#EFE5D9] hover:bg-[#FAF6F0]/10 hover:text-white transition-all text-left" data-section="hairstyles">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                    <span class="sidebar-label">Peinados</span>
                </button>
                <button onclick="showSection('schedule')" class="nav-btn w-full flex items-center space-x-3 px-4 py-2.5 rounded-xl text-sm font-bold text-[#EFE5D9] hover:bg-[#FAF6F0]/10 hover:text-white transition-all text-left" data-section="schedule">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span class="sidebar-label">Mi Disponibilidad</span>
                </button>
                <button onclick="showSection('reservations')" class="nav-btn w-full flex items-center space-x-3 px-4 py-2.5 rounded-xl text-sm font-bold text-[#EFE5D9] hover:bg-[#FAF6F0]/10 hover:text-white transition-all text-left" data-section="reservations">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                    <span class="sidebar-label">Reservas</span>
                </button>
            </nav>

            <div class="px-3 py-2 border-t border-[#FAF6F0]/10">
                <button class="w-full flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-bold text-[#EFE5D9] hover:bg-[#FAF6F0]/10 hover:text-white transition-all cursor-pointer hidden md:flex" id="sidebar-toggle-btn">
                    <svg class="w-5 h-5 shrink-0" id="sidebar-toggle-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/></svg>
                    <span class="sidebar-label">Colapsar menú</span>
                </button>
            </div>

            <div class="p-4 border-t border-[#FAF6F0]/10 space-y-2">
                <a href="<?php echo $baseUrl; ?>/logout" class="flex items-center space-x-3 px-4 py-2.5 rounded-xl text-sm font-bold text-red-300 hover:bg-red-500/20 hover:text-white transition-all text-left">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                    <span class="sidebar-label">Cerrar Sesión</span>
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main id="main-content" class="ml-64 p-6 lg:p-8 space-y-8">

            <!-- Section: Peinados -->
            <section id="section-hairstyles" class="section-panel space-y-6">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <div class="bg-white rounded-3xl border border-[#EFE5D9] p-6 shadow-md h-fit space-y-6">
                        <div>
                            <h3 id="form-title" class="text-xl font-extrabold tracking-tight">Agregar Nuevo Peinado</h3>
                            <p id="form-desc" class="text-xs text-[#5C4333]/55 mt-1">Completa los campos para publicar un peinado</p>
                        </div>
                        <form id="hairstyle-form" class="space-y-4" enctype="multipart/form-data">
                            <input type="hidden" name="_csrf_token" value="<?php echo csrf_token(); ?>">
                            <input type="hidden" id="hairstyle-id" name="id" value="">
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-[#5C4333]/60 mb-1">Nombre</label>
                                <input type="text" id="name" name="name" required placeholder="Ej: Trenzas Box" class="block w-full px-4 py-2.5 bg-[#FAF6F0]/60 border border-[#EFE5D9] rounded-xl focus:outline-none focus:ring-2 focus:ring-[#B56B45] text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-[#5C4333]/60 mb-1">Descripción</label>
                                <textarea id="description" name="description" required rows="3" placeholder="Describe materiales, duración, etc." class="block w-full px-4 py-2.5 bg-[#FAF6F0]/60 border border-[#EFE5D9] rounded-xl focus:outline-none focus:ring-2 focus:ring-[#B56B45] text-sm"></textarea>
                            </div>
                            <div class="grid grid-cols-3 gap-4 items-end">
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-[#5C4333]/60 mb-1">Precio</label>
                                    <input type="number" id="price" name="price" step="0.01" min="1" required placeholder="USD" class="block w-full h-10 px-2 py-2.5 bg-[#FAF6F0]/60 border border-[#EFE5D9] rounded-xl focus:outline-none focus:ring-2 focus:ring-[#B56B45] text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-[#5C4333]/60 mb-1">Duración</label>
                                    <input type="number" id="duration_minutes" name="duration_minutes" min="15" max="480" placeholder="MIN" required class="block w-full h-10 px-2 py-2.5 bg-[#FAF6F0]/60 border border-[#EFE5D9] rounded-xl focus:outline-none focus:ring-2 focus:ring-[#B56B45] text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-[#5C4333]/60 mb-2">Estado</label>
                                    <select id="status" name="status" required class="block w-full h-10 px-2 py-3 bg-[#FAF6F0]/60 border border-[#EFE5D9] rounded-xl focus:outline-none focus:ring-2 focus:ring-[#B56B45] text-sm">
                                        <option value="active">Activo</option>
                                        <option value="inactive">Inactivo</option>
                                    </select>
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-[#5C4333]/60 mb-1">Imagen</label>
                                <input type="file" id="image_file" name="image_file" accept="image/*" class="block w-full px-4 py-2.5 bg-[#FAF6F0]/60 border border-[#EFE5D9] rounded-xl focus:outline-none focus:ring-2 focus:ring-[#B56B45] text-sm file:mr-4 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-[#B56B45]/10 file:text-[#B56B45] file:cursor-pointer hover:file:bg-[#B56B45]/20">
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
                </div>
            </section>

            <!-- Section: Mi Disponibilidad -->
            <section id="section-schedule" class="section-panel hidden space-y-6">
                <div class="bg-white rounded-3xl border border-[#EFE5D9] p-6 sm:p-8 shadow-md space-y-6">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center border-b border-[#EFE5D9] pb-4">
                        <div>
                            <h3 class="text-2xl font-extrabold tracking-tight">Mi Disponibilidad</h3>
                            <p class="text-sm text-[#5C4333]/65">Configura los días y horas en que estarás disponible para atender</p>
                        </div>
                        <button onclick="loadWorkerSchedule()" class="mt-3 sm:mt-0 px-4 py-2 bg-[#B56B45] hover:bg-[#8C5435] text-white font-bold rounded-xl text-xs uppercase tracking-wider transition-all cursor-pointer">↻ Recargar</button>
                    </div>
                    <div id="worker-schedule-container">
                        <div id="worker-schedule-form" class="space-y-4">
                            <p class="text-sm text-[#5C4333]/50 text-center py-4">Cargando tu disponibilidad...</p>
                        </div>
                        <div class="flex justify-end pt-4 border-t border-[#EFE5D9]">
                            <button onclick="saveWorkerSchedule()" class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs uppercase tracking-wider transition-all cursor-pointer shadow-sm">Guardar Disponibilidad</button>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Section: Reservas -->
            <section id="section-reservations" class="section-panel hidden space-y-6">
                <div class="bg-white rounded-3xl border border-[#EFE5D9] p-6 sm:p-8 shadow-md">
                    <div>
                        <h3 class="text-2xl font-extrabold tracking-tight">Gestión de Apartados</h3>
                        <p class="text-sm text-[#5C4333]/65">Confirma o cancela las solicitudes de los clientes</p>
                    </div>
                    <?php if (empty($reservations)): ?>
                        <p class="text-sm text-[#5C4333]/50 text-center py-8 mt-6">No hay reservas pendientes.</p>
                    <?php else: ?>
                        <div class="mt-6">
                            <table class="w-full divide-y divide-[#EFE5D9]/40">
                                <thead class="bg-[#FAF6F0]/60">
                                    <tr>
                                        <th class="px-4 py-4 text-left text-xs font-bold text-[#5C4333]/50 uppercase tracking-widest">Cliente</th>
                                        <th class="px-4 py-4 text-left text-xs font-bold text-[#5C4333]/50 uppercase tracking-widest">Peinado</th>
                                        <th class="px-4 py-4 text-left text-xs font-bold text-[#5C4333]/50 uppercase tracking-widest">Precio</th>
                                        <th class="px-4 py-4 text-left text-xs font-bold text-[#5C4333]/50 uppercase tracking-widest">Cita</th>
                                        <th class="px-4 py-4 text-left text-xs font-bold text-[#5C4333]/50 uppercase tracking-widest">Estado</th>
                                        <th class="px-4 py-4 text-right text-xs font-bold text-[#5C4333]/50 uppercase tracking-widest">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-[#EFE5D9]/40">
                                    <?php foreach ($reservations as $res): ?>
                                        <tr class="hover:bg-[#FAF6F0]/20 transition-colors">
                                             <td data-label="Cliente" class="px-4 py-4 text-sm font-extrabold text-[#5C4333]">
                                                <?php echo htmlspecialchars($res['username']); ?>
                                            </td>
                                            <td data-label="Peinado" class="px-4 py-4 text-sm font-bold text-[#5C4333]/85"><?php echo htmlspecialchars($res['hairstyle_name']); ?></td>
                                            <td data-label="Precio" class="px-4 py-4 text-sm font-extrabold text-[#B56B45]">$<?php echo number_format($res['price'], 2); ?></td>
                                            <td data-label="Cita" class="px-4 py-4 text-sm text-[#5C4333]/75">
                                                <?php if (!empty($res['appointment_date'])): ?>
                                                    <?php echo date('d M', strtotime($res['appointment_date'])); ?> &middot; <?php echo date('g:i A', strtotime($res['appointment_time'])); ?>
                                                <?php else: ?>
                                                    <span class="text-xs text-[#5C4333]/40">Sin cita</span>
                                                <?php endif; ?>
                                            </td>
                                            <td data-label="Estado" class="px-4 py-4"><?php if ($res['status'] === 'pending'): ?><span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200/50">Pendiente</span><?php elseif ($res['status'] === 'confirmed'): ?><span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200/50">Confirmado</span><?php else: ?><span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-stone-100 text-stone-600 border border-stone-200">Cancelado</span><?php endif; ?></td>
                                             <td data-label="Acciones" class="px-4 py-4 text-right text-xs font-semibold">
                                                <?php if ($res['status'] === 'pending'): ?>
                                                    <span class="inline-flex gap-1">
                                                    <button onclick="cambiarEstadoReserva(<?php echo $res['id']; ?>, 'confirmed')" class="px-2 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg transition-all cursor-pointer shadow-sm">Confirmar</button>
                                                    <button onclick="cambiarEstadoReserva(<?php echo $res['id']; ?>, 'cancelled')" class="px-2 py-1.5 bg-stone-200 hover:bg-stone-300 text-stone-700 rounded-lg transition-all cursor-pointer">Cancelar</button>
                                                    </span>
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
                </div>
            </section>

        </main>
    </div>

    <?php require_once __DIR__ . '/partials/js_modal_utils.php'; ?>
    <script>window.BASE_URL = '<?php echo $baseUrl; ?>'; window.CSRF_TOKEN = '<?php echo csrf_token(); ?>';</script>
    <script src="<?php echo $baseUrl; ?>/assets/js/dashboard_worker.js"></script>

    <script>
        function showSection(id) {
            document.querySelectorAll('.section-panel').forEach(el => el.classList.add('hidden'));
            const target = document.getElementById('section-' + id);
            if (target) target.classList.remove('hidden');
            document.querySelectorAll('.nav-btn').forEach(el => {
                el.classList.remove('bg-[#FAF6F0]/15', 'text-white');
                el.classList.add('text-[#EFE5D9]');
            });
            const active = document.querySelector('.nav-btn[data-section="' + id + '"]');
            if (active) {
                active.classList.remove('text-[#EFE5D9]');
                active.classList.add('bg-[#FAF6F0]/15', 'text-white');
            }
            if (window.innerWidth < 768) {
                closeMobileSidebar();
            }
        }

        var sidebar = document.getElementById('sidebar');
        var main = document.getElementById('main-content');
        var iconPath = document.querySelector('#sidebar-toggle-icon path');
        var toggleLabel = document.querySelector('#sidebar-toggle-btn .sidebar-label');

        function openSidebar() {
            sidebar.classList.add('mobile-open');
            document.getElementById('sidebar-overlay').classList.add('show');
            var btn = document.getElementById('mobile-menu-btn');
            if (btn) btn.innerHTML = '<svg class=\"w-6 h-6\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M6 18L18 6M6 6l12 12\"/></svg>';
        }
        function closeMobileSidebar() {
            sidebar.classList.remove('mobile-open');
            document.getElementById('sidebar-overlay').classList.remove('show');
            var btn = document.getElementById('mobile-menu-btn');
            if (btn) btn.innerHTML = '<svg class=\"w-6 h-6\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M4 6h16M4 12h16M4 18h16\"/></svg>';
        }
        function toggleDesktopSidebar() {
            sidebar.classList.toggle('collapsed');
            sidebar.classList.toggle('w-64');
            sidebar.classList.toggle('w-20');
            if (sidebar.classList.contains('collapsed')) {
                main.classList.remove('ml-64');
                main.classList.add('ml-20');
                iconPath.setAttribute('d', 'M15 19l-7-7 7-7');
                if (toggleLabel) toggleLabel.textContent = 'Expandir menú';
            } else {
                main.classList.remove('ml-20');
                main.classList.add('ml-64');
                iconPath.setAttribute('d', 'M11 19l-7-7 7-7m8 14l-7-7 7-7');
                if (toggleLabel) toggleLabel.textContent = 'Colapsar menú';
            }
        }

        var mobileBtn = document.getElementById('mobile-menu-btn');
        if (mobileBtn) {
            mobileBtn.addEventListener('click', function(e) {
                e.preventDefault();
                if (sidebar.classList.contains('mobile-open')) {
                    closeMobileSidebar();
                } else {
                    openSidebar();
                }
            });
        }
        var desktopBtn = document.getElementById('sidebar-toggle-btn');
        if (desktopBtn) {
            desktopBtn.addEventListener('click', function(e) {
                e.preventDefault();
                toggleDesktopSidebar();
            });
        }

        showSection('hairstyles');
    </script>
</body>
</html>
