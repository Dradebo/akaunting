// Lightweight service worker registration helper. Adapt project root serviceworker.js if desired.
export function register() {
  if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/serviceworker.js').catch((err) => {
      console.warn('SW registration failed', err)
    })
  }
}
