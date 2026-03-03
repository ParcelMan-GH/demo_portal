/**
 * Firebase Web Push helper — used by all four portals.
 *
 * Expects these globals to be set before this script runs:
 *   window.__fcmConfig   = { apiKey, authDomain, projectId, messagingSenderId, appId, vapidKey }
 *   window.__fcmEndpoint = { url: '...', headers: { 'X-CSRF-TOKEN': '...' | 'Authorization': 'Bearer ...' } }
 *
 * Optionally set window.__fcmToast = function(title, body) { ... } to customise the foreground toast.
 */

(function () {
    console.log('[FCM] Script loaded. Config present:', !!window.__fcmConfig, '| Endpoint present:', !!window.__fcmEndpoint);

    if (!window.__fcmConfig || !window.__fcmEndpoint) {
        console.warn('[FCM] Aborting: __fcmConfig or __fcmEndpoint not set.');
        return;
    }

    const config = window.__fcmConfig;
    const endpoint = window.__fcmEndpoint;

    if (!config.apiKey || !config.messagingSenderId) {
        console.warn('[FCM] Aborting: apiKey or messagingSenderId missing. apiKey=' + config.apiKey + ' senderId=' + config.messagingSenderId);
        return;
    }

    console.log('[FCM] Config OK. Starting Firebase SDK load...');

    // ----------------------------------------------------------------
    // Dynamically load Firebase SDK (compat CDN)
    // ----------------------------------------------------------------
    function loadScript(src) {
        return new Promise((resolve, reject) => {
            if (document.querySelector(`script[src="${src}"]`)) {
                resolve();
                return;
            }
            const s = document.createElement('script');
            s.src = src;
            s.onload = resolve;
            s.onerror = reject;
            document.head.appendChild(s);
        });
    }

    const SDK_BASE = 'https://www.gstatic.com/firebasejs/10.12.0';

    Promise.all([
        loadScript(`${SDK_BASE}/firebase-app-compat.js`),
    ]).then(() => {
        return loadScript(`${SDK_BASE}/firebase-messaging-compat.js`);
    }).then(() => {
        console.log('[FCM] Firebase SDK loaded. Initialising...');
        initFirebase();
    }).catch(err => {
        console.warn('[FCM Push] Failed to load Firebase SDK:', err);
    });

    function initFirebase() {
        // Avoid re-initialising if app already exists
        let app;
        try {
            app = firebase.app('parcelman-push');
        } catch (_) {
            app = firebase.initializeApp({
                apiKey: config.apiKey,
                authDomain: config.authDomain,
                projectId: config.projectId,
                messagingSenderId: config.messagingSenderId,
                appId: config.appId,
            }, 'parcelman-push');
        }

        const messaging = firebase.messaging(app);

        // Register service worker with encoded config
        if (!('serviceWorker' in navigator)) {
            return;
        }

        const swConfig = btoa(JSON.stringify({
            apiKey: config.apiKey,
            authDomain: config.authDomain,
            projectId: config.projectId,
            messagingSenderId: config.messagingSenderId,
            appId: config.appId,
        }));

        // Expose a callable for on-demand token capture (used by test push button)
        window.__fcmGetToken = function () {
            return navigator.serviceWorker.getRegistration('/firebase-messaging-sw.js').then(existing => {
                const reg = existing || navigator.serviceWorker.register(`/firebase-messaging-sw.js?config=${swConfig}`);
                return Promise.resolve(reg).then(r => {
                    return messaging.getToken({ vapidKey: config.vapidKey, serviceWorkerRegistration: r });
                }).then(token => {
                    if (token) { saveToken(token); }
                    return token;
                });
            });
        };

        console.log('[FCM] Registering service worker...');
        navigator.serviceWorker
            .register(`/firebase-messaging-sw.js?config=${swConfig}`)
            .then(registration => {
                console.log('[FCM] Service worker registered. Requesting permission...');
                requestPermissionAndGetToken(messaging, registration, swConfig);
            })
            .catch(err => {
                console.warn('[FCM Push] Service worker registration failed:', err);
            });

        // Handle foreground messages
        messaging.onMessage(payload => {
            const title = payload.notification?.title || 'Notification';
            const body = payload.notification?.body || '';

            // Show native OS notification even when the tab is focused
            navigator.serviceWorker.ready.then(reg => {
                reg.showNotification(title, {
                    body,
                    icon: '/favicon.jpg',
                    badge: '/favicon.jpg',
                    data: payload.data || {},
                });
            }).catch(() => {});

            // Also show in-app toast
            if (typeof window.__fcmToast === 'function') {
                window.__fcmToast(title, body);
            } else {
                showDefaultToast(title, body);
            }
        });
    }

    function requestPermissionAndGetToken(messaging, registration, swConfig) {
        Notification.requestPermission().then(permission => {
            console.log('[FCM] Notification permission:', permission);
            if (permission !== 'granted') {
                return;
            }

            messaging.getToken({
                vapidKey: config.vapidKey,
                serviceWorkerRegistration: registration,
            }).then(token => {
                if (token) {
                    console.log('[FCM] Token obtained. Saving...');
                    saveToken(token);
                }
            }).catch(err => {
                console.warn('[FCM Push] Failed to get FCM token:', err);

                // Stale token from a previous config — unregister SW to clear IndexedDB
                // and retry once with a fresh registration.
                if (err.code === 'messaging/token-unsubscribe-failed' || String(err).includes('token-unsubscribe-failed')) {
                    console.log('[FCM] Stale token detected. Clearing and retrying...');
                    registration.unregister().then(() => {
                        return navigator.serviceWorker.register(`/firebase-messaging-sw.js?config=${swConfig}`);
                    }).then(freshReg => {
                        return messaging.getToken({
                            vapidKey: config.vapidKey,
                            serviceWorkerRegistration: freshReg,
                        });
                    }).then(token => {
                        if (token) {
                            console.log('[FCM] Token obtained after retry. Saving...');
                            saveToken(token);
                        }
                    }).catch(retryErr => {
                        console.warn('[FCM Push] Retry after stale token also failed:', retryErr);
                    });
                }
            });
        });
    }

    function saveToken(token) {
        const headers = Object.assign({ 'Content-Type': 'application/json' }, endpoint.headers || {});

        fetch(endpoint.url, {
            method: 'POST',
            headers: headers,
            body: JSON.stringify({ fcm_token: token }),
        }).then(() => {
            window.__fcmInitialised = true;
        }).catch(err => {
            console.warn('[FCM Push] Failed to save FCM token:', err);
        });
    }

    function showDefaultToast(title, body) {
        // Minimal fallback toast — portals override via window.__fcmToast
        const toast = document.createElement('div');
        toast.style.cssText = [
            'position:fixed', 'top:20px', 'right:20px', 'z-index:9999',
            'background:#1e293b', 'color:#f8fafc', 'padding:14px 18px',
            'border-radius:12px', 'max-width:320px', 'box-shadow:0 4px 20px rgba(0,0,0,0.3)',
            'font-family:inherit', 'font-size:14px', 'line-height:1.4',
            'animation:fadeIn 0.3s ease',
        ].join(';');

        toast.innerHTML = `<strong style="display:block;margin-bottom:4px">${escapeHtml(title)}</strong><span style="color:#94a3b8">${escapeHtml(body)}</span>`;
        document.body.appendChild(toast);

        setTimeout(() => toast.remove(), 6000);
    }

    function escapeHtml(str) {
        return String(str).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);
    }
})();
