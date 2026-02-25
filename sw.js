// sw.js - Service Worker corretto per la gestione dell'inventario

const CACHE_NAME = 'tsservice-catalogo-v2'; // Nome nuovo per forzare l'aggiornamento
const urlsToCache = [
  // Risorse statiche dell'interfaccia
  '/gestionale_tsservice/index.html',
  // NOTA: Ho rimosso l'endpoint API da qui. Non si deve mai pre-cachare un file dinamico.
  '/gestionale_tsservice/style.css', 
  '/gestionale_tsservice/app.js',   
  '/gestionale_tsservice/images/logo.png',
  // Icone definite nel manifest.json
  '/gestionale_tsservice/images/icons/icon-72x72.png',
  '/gestionale_tsservice/images/icons/icon-96x96.png',
  '/gestionale_tsservice/images/icons/icon-128x128.png',
  '/gestionale_tsservice/images/icons/icon-144x144.png',
  '/gestionale_tsservice/images/icons/icon-152x152.png',
  '/gestionale_tsservice/images/icons/icon-192x192.png',
  '/gestionale_tsservice/images/icons/icon-384x384.png',
  '/gestionale_tsservice/images/icons/icon-512x512.png'
];

// Evento di installazione: mette in cache i file statici
self.addEventListener('install', (event) => {
  console.log('Service Worker: installazione in corso (versione 2)...');
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then((cache) => {
        console.log('Service Worker: cache aperta.');
        return cache.addAll(urlsToCache);
      })
      .then(() => {
        self.skipWaiting();
        console.log('Service Worker: risorse pre-cachate e skipWaiting() chiamato.');
      })
      .catch((error) => {
        console.error('Service Worker: errore durante il pre-caching:', error);
      })
  );
});

// Evento di attivazione: pulisce le vecchie cache
self.addEventListener('activate', (event) => {
  console.log('Service Worker: attivazione in corso (versione 2)...');
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((cacheName) => {
          if (cacheName !== CACHE_NAME) {
            console.log('Service Worker: eliminazione vecchia cache:', cacheName);
            return caches.delete(cacheName);
          }
        })
      );
    }).then(() => {
      return clients.claim();
    })
  );
});

// Evento fetch: intercetta le richieste di rete
self.addEventListener('fetch', (event) => {
  const requestUrl = new URL(event.request.url);

  // *** LA REGOLA FONDAMENTALE ***
  // Se la richiesta è per la nostra API (contiene 'api.php' o 'api_prodotti.php'),
  // ignoriamo SEMPRE la cache e andiamo direttamente in rete.
  // Questo è essenziale per POST, PUT, DELETE e per ottenere dati aggiornati con GET.
  if (requestUrl.pathname.includes('api.php') || requestUrl.pathname.includes('api_prodotti.php')) {
    console.log('Service Worker: Richiesta API, vado in rete ->', event.request.url);
    // Non usiamo la cache, andiamo direttamente alla rete.
    event.respondWith(fetch(event.request));
    return; // Interrompi l'esecuzione qui
  }

  // Per tutte le altre richieste (immagini, CSS, HTML), usiamo la strategia "Cache First".
  event.respondWith(
    caches.match(event.request).then((cachedResponse) => {
      // Se la risorsa è in cache, la restituiamo.
      if (cachedResponse) {
        console.log('Service Worker: Servito da cache ->', event.request.url);
        return cachedResponse;
      }
      
      // Altrimenti, la recuperiamo dalla rete e la mettiamo in cache per dopo.
      return fetch(event.request).then((networkResponse) => {
          if (!networkResponse || networkResponse.status !== 200 || networkResponse.type !== 'basic') {
            return networkResponse;
          }
          const responseToCache = networkResponse.clone();
          caches.open(CACHE_NAME).then((cache) => {
            cache.put(event.request, responseToCache);
          });
          return networkResponse;
        });
    })
  );
});

