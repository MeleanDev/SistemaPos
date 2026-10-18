<!-- PWA Client Registration & Interactivity Component -->
<div id="pwa-install-banner" class="position-fixed bottom-0 start-50 translate-middle-x mb-4 shadow-lg rounded-4 p-3 bg-white border border-light-subtle d-none" style="z-index: 1090; max-width: 92%; width: 420px; transition: all 0.3s ease;">
    <div class="d-flex align-items-center justify-content-between gap-3">
        <div class="d-flex align-items-center gap-3">
            <img src="{{ asset('icons/icon-96x96.png') }}" alt="App Icon" class="rounded-3 shadow-xs" width="46" height="46">
            <div>
                <h6 class="mb-0 fw-bold text-dark fs-6">Instalar App POS</h6>
                <p class="mb-0 text-muted small">Accede rápido sin barra de navegador</p>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-2 py-1" id="pwa-btn-dismiss" title="Descartar">
                <i class="fas fa-times"></i>
            </button>
            <button type="button" class="btn btn-sm btn-primary rounded-pill px-3 py-1 fw-semibold shadow-xs" id="pwa-btn-install">
                <i class="fas fa-download me-1"></i> Instalar
            </button>
        </div>
    </div>
</div>

<!-- Toast Conectividad Red -->
<div class="position-fixed top-0 start-50 translate-middle-x mt-3" style="z-index: 1099;">
    <div id="pwa-network-toast" class="toast align-items-center border-0 rounded-pill shadow-lg text-white d-none" role="alert" aria-live="assertive" aria-atomic="true" style="transition: all 0.3s ease;">
        <div class="d-flex align-items-center px-3 py-2">
            <div class="toast-body py-0 px-2 fw-medium small d-flex align-items-center gap-2" id="pwa-network-toast-body">
                <i class="fas fa-wifi"></i>
                <span id="pwa-network-toast-msg">Conexión establecida</span>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    'use strict';

    let deferredPrompt = null;
    const installBanner = document.getElementById('pwa-install-banner');
    const btnInstall = document.getElementById('pwa-btn-install');
    const btnDismiss = document.getElementById('pwa-btn-dismiss');

    // 1. Service Worker Registration
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function() {
            navigator.serviceWorker.register('/sw.js', { scope: '/' })
                .then(function(registration) {
                    // Detect updates
                    registration.addEventListener('updatefound', function() {
                        const installingWorker = registration.installing;
                        if (!installingWorker) return;

                        installingWorker.addEventListener('statechange', function() {
                            if (installingWorker.state === 'installed' && navigator.serviceWorker.controller) {
                                if (typeof Swal !== 'undefined') {
                                    Swal.fire({
                                        title: 'Nueva Versión Disponible',
                                        text: 'Se ha descargado una actualización del Sistema POS. ¿Deseas recargar para aplicarla?',
                                        icon: 'info',
                                        showCancelButton: true,
                                        confirmButtonColor: '#4f46e5',
                                        cancelButtonColor: '#64748b',
                                        confirmButtonText: '<i class="fas fa-sync-alt me-1"></i> Actualizar ahora',
                                        cancelButtonText: 'Más tarde',
                                        customClass: {
                                            popup: 'rounded-4 border-0 shadow-lg',
                                            confirmButton: 'rounded-pill px-4',
                                            cancelButton: 'rounded-pill px-3'
                                        }
                                    }).then(function(result) {
                                        if (result.isConfirmed) {
                                            installingWorker.postMessage({ type: 'SKIP_WAITING' });
                                            window.location.reload();
                                        }
                                    });
                                }
                            }
                        });
                    });
                })
                .catch(function(err) {
                    console.warn('[PWA] Fallo al registrar Service Worker:', err);
                });
        });
    }

    // 2. Manejo de beforeinstallprompt
    window.addEventListener('beforeinstallprompt', function(e) {
        e.preventDefault();
        deferredPrompt = e;

        // Si el usuario descartó el banner en esta sesión, no molestarlo
        if (sessionStorage.getItem('pwa_prompt_dismissed') === 'true') {
            return;
        }

        if (installBanner) {
            installBanner.classList.remove('d-none');
        }

        // Habilitar cualquier botón con selector data-pwa-install
        document.querySelectorAll('[data-pwa-install]').forEach(function(el) {
            el.classList.remove('d-none');
        });
    });

    if (btnInstall) {
        btnInstall.addEventListener('click', async function() {
            if (!deferredPrompt) return;

            deferredPrompt.prompt();
            const { outcome } = await deferredPrompt.userChoice;

            if (outcome === 'accepted') {
                if (installBanner) installBanner.classList.add('d-none');
            }
            deferredPrompt = null;
        });
    }

    if (btnDismiss) {
        btnDismiss.addEventListener('click', function() {
            if (installBanner) installBanner.classList.add('d-none');
            sessionStorage.setItem('pwa_prompt_dismissed', 'true');
        });
    }

    // Delegación para botones auxiliares con [data-pwa-install]
    document.addEventListener('click', async function(e) {
        const trigger = e.target.closest('[data-pwa-install]');
        if (trigger && deferredPrompt) {
            deferredPrompt.prompt();
            await deferredPrompt.userChoice;
            deferredPrompt = null;
        }
    });

    // 3. App instalada exitosamente
    window.addEventListener('appinstalled', function() {
        if (installBanner) installBanner.classList.add('d-none');
        deferredPrompt = null;
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: '¡App POS instalada con éxito!',
                showConfirmButton: false,
                timer: 3000
            });
        }
    });

    // 4. Indicador flotante de conectividad (Online / Offline)
    function notifyNetworkStatus(isOnline) {
        const toast = document.getElementById('pwa-network-toast');
        const msg = document.getElementById('pwa-network-toast-msg');
        const body = document.getElementById('pwa-network-toast-body');

        if (!toast || !msg || !body) return;

        if (isOnline) {
            toast.className = 'toast align-items-center border-0 rounded-pill shadow-lg text-white bg-success d-flex';
            body.innerHTML = '<i class="fas fa-wifi me-2"></i><span>Conexión a Internet restablecida</span>';
            setTimeout(function() {
                toast.classList.add('d-none');
            }, 3000);
        } else {
            toast.className = 'toast align-items-center border-0 rounded-pill shadow-lg text-white bg-danger d-flex';
            body.innerHTML = '<i class="fas fa-wifi-slash me-2"></i><span>Sin conexión a Internet (Modo Offline)</span>';
        }
    }

    window.addEventListener('online', function() {
        notifyNetworkStatus(true);
    });

    window.addEventListener('offline', function() {
        notifyNetworkStatus(false);
    });
})();
</script>
