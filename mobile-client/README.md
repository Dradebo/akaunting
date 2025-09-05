# Akaunting Mobile Client (PWA scaffold)

This is a lightweight React + Vite progressive web app scaffold intended as the Phase B mobile client starting point.

Goals:
- Smartphone-first UI
- Offline-first sync using the server `POST /api/mobile/sync` endpoint
- IndexedDB local store (using `idb`) for queued records

Quick start:
1. cd mobile-client
2. npm install
3. npm run dev

Next steps:
- Wire the OpenAPI client or generate a typed client from `docs/openapi/mobile-sync.yaml`.
- Build UI flows for onboarding (phone number), offline record creation, and background sync.
- Add a service worker for offline caching (there is already `serviceworker.js` in repo root; adapt if needed).
