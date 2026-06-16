<?php
if (!defined('ACCESS_ALLOWED')) {
    header('HTTP/1.1 403 Forbidden');
    exit('Direct access not allowed.');
}
?>
<!-- Modal HTML Structure -->
<div id="custom-modal" class="fixed inset-0 bg-stone-950/60 backdrop-blur-sm flex items-center justify-center z-50 opacity-0 pointer-events-none transition-opacity duration-300">
    <div id="modal-box" class="bg-amber-50 border border-amber-200/50 rounded-2xl max-w-sm w-full p-6 shadow-2xl transform scale-95 transition-transform duration-300 mx-4">
        <!-- Icon Container -->
        <div class="flex justify-center mb-4">
            <div id="modal-icon-bg" class="p-3 rounded-full bg-amber-100/50">
                <!-- SVG Icon (changes dynamically) -->
                <svg id="modal-icon" class="w-8 h-8 text-amber-700" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
            </div>
        </div>
        <!-- Content -->
        <h3 id="modal-title" class="text-xl font-extrabold text-stone-900 text-center mb-2 tracking-tight">Alerta</h3>
        <p id="modal-message" class="text-stone-700 text-center text-sm leading-relaxed mb-6">Mensaje detallado aquí.</p>
        
        <!-- Button -->
        <button id="modal-confirm-btn" class="w-full py-2.5 px-4 bg-stone-900 text-amber-100 hover:bg-stone-800 focus:outline-none focus:ring-2 focus:ring-amber-500 rounded-xl font-bold tracking-wide transition-colors shadow-lg shadow-stone-900/10">
            Aceptar
        </button>
    </div>
</div>

<!-- Toast Container -->
<div id="toast-container" class="fixed top-4 right-4 z-50 flex flex-col gap-3 max-w-sm w-full pointer-events-none"></div>

<!-- Modal & Toast Scripts -->
<script src="<?php echo $baseUrl ?? '/HomeWorks/Design_Project'; ?>/assets/js/js_modal_utils.js"></script>
