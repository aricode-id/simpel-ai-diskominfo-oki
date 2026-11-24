const CACHE_NAME = 'simpel-ai-v1';

// Saat aplikasi diinstal di HP
self.addEventListener('install', (event) => {
    console.log('Service Worker SIMPEL-AI terinstal.');
    self.skipWaiting();
});

// Saat aplikasi dijalankan
self.addEventListener('activate', (event) => {
    console.log('Service Worker SIMPEL-AI aktif.');
    event.waitUntil(clients.claim());
});

// Bypass cache agar data laporan (CRUD) selalu realtime
self.addEventListener('fetch', (event) => {
    event.respondWith(fetch(event.request));
});