// Define o nome do cache e a página de fallback offline
const CACHE_NAME = "te-controla-cache-v1";
const OFFLINE_FALLBACK_PAGE = "offline.html";

// Lista de ficheiros a serem cacheados na instalação
const FILES_TO_CACHE = [
  OFFLINE_FALLBACK_PAGE,
  '/', // O mesmo que index.php
  'index.php',
  'css/style.css',
  'js/script.js',
  'manifest.json',
  'icons/icon-192x192.png',
  'icons/icon-512x512.png'
];

self.addEventListener("install", (event) => {
  console.log("[Service Worker] Instalando...");
  
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      console.log("[Service Worker] Pré-cache de ficheiros da aplicação");
      return cache.addAll(FILES_TO_CACHE);
    })
  );
  
  self.skipWaiting();
});

self.addEventListener("activate", (event) => {
  console.log("[Service Worker] Ativando...");

  // Remove caches antigos
  event.waitUntil(
    caches.keys().then((keyList) => {
      return Promise.all(
        keyList.map((key) => {
          if (key !== CACHE_NAME) {
            console.log("[Service Worker] Removendo cache antigo", key);
            return caches.delete(key);
          }
        })
      );
    })
  );

  self.clients.claim();
});

self.addEventListener("fetch", (event) => {
  // Ignora os pedidos da API para não cachear os dados
  if (event.request.url.includes('/api/')) {
    return;
  }

  // Estratégia de cache para outros pedidos
  event.respondWith(
    caches.match(event.request).then((response) => {
      // Se o recurso estiver em cache, retorna-o
      if (response) {
        return response;
      }
      
      // Caso contrário, tenta buscar na rede
      return fetch(event.request).catch(() => {
        // Se a busca na rede falhar, retorna a página de fallback offline
        if (event.request.mode === 'navigate') {
          return caches.match(OFFLINE_FALLBACK_PAGE);
        }
      });
    })
  );
});

