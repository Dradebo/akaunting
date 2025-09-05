import { defineConfig } from 'vite'
import { VitePWA } from 'vite-plugin-pwa'

export default defineConfig(async () => {
  // Dynamically import the ESM-only plugin to avoid require()/CJS loader errors
  const reactPlugin = (await import('@vitejs/plugin-react')).default

  return {
    plugins: [
      reactPlugin(),
      VitePWA({
        registerType: 'autoUpdate',
        includeAssets: ['icons/icon.svg', 'icons/icon-192x192.svg'],
        manifest: {
          name: 'Akaunting Mobile',
          short_name: 'Akaunting',
          description: 'Mobile accounting app for managing your finances',
          theme_color: '#3b82f6',
          background_color: '#f9fafb',
          display: 'standalone',
          orientation: 'portrait-primary',
          scope: '/',
          start_url: '/',
          icons: [
            {
              src: '/icons/icon-192x192.svg',
              sizes: '192x192',
              type: 'image/svg+xml',
              purpose: 'maskable any'
            },
            {
              src: '/icons/icon-512x512.svg',
              sizes: '512x512',
              type: 'image/svg+xml',
              purpose: 'maskable any'
            }
          ],
          categories: ['finance', 'business', 'productivity'],
          shortcuts: [
            {
              name: 'New Transaction',
              short_name: 'New',
              description: 'Create a new transaction',
              url: '/transactions/create',
              icons: [{ src: '/icons/icon-96x96.svg', sizes: '96x96' }]
            },
            {
              name: 'Analytics',
              short_name: 'Charts',
              description: 'View financial analytics',
              url: '/analytics',
              icons: [{ src: '/icons/icon-96x96.svg', sizes: '96x96' }]
            }
          ]
        },
        workbox: {
          globPatterns: ['**/*.{js,css,html,ico,png,svg,woff2}'],
          runtimeCaching: [
            {
              urlPattern: /^https:\/\/api\./i,
              handler: 'NetworkFirst',
              options: {
                cacheName: 'api-cache',
                expiration: {
                  maxEntries: 100,
                  maxAgeSeconds: 60 * 60 * 24 // 24 hours
                },
                networkTimeoutSeconds: 10
              }
            },
            {
              urlPattern: /\/api\//,
              handler: 'NetworkFirst',
              options: {
                cacheName: 'local-api-cache',
                expiration: {
                  maxEntries: 50,
                  maxAgeSeconds: 60 * 60 // 1 hour for local API
                }
              }
            }
          ]
        },
        devOptions: {
          enabled: true
        }
      })
    ],
    server: {
      port: 5173,
      proxy: {
        '/api': {
          target: 'http://127.0.0.1:8001',
          changeOrigin: true,
        }
      }
    },
  }
})
