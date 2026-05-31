document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('custom-modal');
    const modalBox = document.getElementById('modal-box');
    const modalTitle = document.getElementById('modal-title');
    const modalMessage = document.getElementById('modal-message');
    const modalIcon = document.getElementById('modal-icon');
    const modalIconBg = document.getElementById('modal-icon-bg');
    const confirmBtn = document.getElementById('modal-confirm-btn');

    let activeCallback = null;

    // Show Modal Utility
    window.showModal = function(title, message, isSuccess = false, callback = null) {
        modalTitle.textContent = title;
        modalMessage.textContent = message;
        activeCallback = callback;

        if (isSuccess) {
            // Success styling (Olive/Emerald Gold theme)
            modalIconBg.className = "p-3 rounded-full bg-emerald-50 border border-emerald-100 text-emerald-600";
            modalIcon.innerHTML = `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>`;
            confirmBtn.className = "w-full py-2.5 px-4 bg-emerald-600 text-white hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 rounded-xl font-bold tracking-wide transition-colors shadow-md";
        } else {
            // Error styling (Warm Copper/Red theme)
            modalIconBg.className = "p-3 rounded-full bg-orange-50 border border-orange-100 text-orange-600";
            modalIcon.innerHTML = `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>`;
            confirmBtn.className = "w-full py-2.5 px-4 bg-stone-900 text-amber-100 hover:bg-stone-800 focus:outline-none focus:ring-2 focus:ring-stone-500 rounded-xl font-bold tracking-wide transition-colors shadow-md";
        }

        // Open Animations
        modal.classList.remove('opacity-0', 'pointer-events-none');
        setTimeout(() => {
            modalBox.classList.remove('scale-95');
            modalBox.classList.add('scale-100');
        }, 10);
    };

    // Close Modal Utility
    window.closeModal = function() {
        modalBox.classList.remove('scale-100');
        modalBox.classList.add('scale-95');
        modal.classList.add('opacity-0', 'pointer-events-none');
        
        if (activeCallback && typeof activeCallback === 'function') {
            const cb = activeCallback;
            activeCallback = null;
            cb();
        }
    };

    confirmBtn.addEventListener('click', closeModal);
    modal.addEventListener('click', (e) => {
        if (e.target === modal) closeModal();
    });

    // Show Toast Utility (Top Right)
    window.showToast = function(message, isSuccess = true) {
        const container = document.getElementById('toast-container');
        const toast = document.createElement('div');
        
        // Base classes
        toast.className = "pointer-events-auto flex items-center p-4 rounded-xl shadow-xl border translate-x-12 opacity-0 transition-all duration-300 bg-amber-50/95 backdrop-blur-md ";
        
        if (isSuccess) {
            toast.className += "border-emerald-200 text-stone-900";
            toast.innerHTML = `
                <div class="p-1 rounded-full bg-emerald-100 text-emerald-600 mr-3">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="text-sm font-semibold">${message}</div>
            `;
        } else {
            toast.className += "border-orange-200 text-stone-900";
            toast.innerHTML = `
                <div class="p-1 rounded-full bg-orange-100 text-orange-600 mr-3">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>
                <div class="text-sm font-semibold">${message}</div>
            `;
        }

        container.appendChild(toast);
        
        // Animate In
        setTimeout(() => {
            toast.classList.remove('translate-x-12', 'opacity-0');
            toast.classList.add('translate-x-0', 'opacity-100');
        }, 10);

        // Auto dismiss after 4s
        setTimeout(() => {
            toast.classList.remove('translate-x-0', 'opacity-100');
            toast.classList.add('translate-x-12', 'opacity-0');
            setTimeout(() => {
                toast.remove();
            }, 300);
        }, 4000);
    };
});
