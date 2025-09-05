# Akaunting Mobile - PWA Deployment Guide

This is a Progressive Web App (PWA) version of Akaunting Mobile that can be deployed to Vercel and installed on mobile devices like a native app.

## Features

✅ **Progressive Web App (PWA)**
- Installable on mobile devices ("Add to Home Screen")
- Offline support with service worker caching
- Native-like experience with standalone display mode
- App shortcuts for quick actions

✅ **Mobile-First Design**  
- Responsive design optimized for mobile screens
- Touch-friendly interface
- Mobile navigation patterns

✅ **Production Ready**
- Optimized build with Vite
- Service worker for caching and offline support
- Proper PWA manifest configuration

## Quick Deploy to Vercel

### Option 1: Deploy via Vercel CLI
```bash
# Install Vercel CLI globally
npm i -g vercel

# Deploy from the mobile-client directory
cd mobile-client
vercel

# Follow the prompts to link your project
```

### Option 2: Deploy via Git Integration
1. Push your code to GitHub/GitLab/Bitbucket
2. Go to [vercel.com](https://vercel.com) and sign in
3. Click "New Project" and import your repository
4. Set the **Root Directory** to `mobile-client`
5. Framework will auto-detect as "Vite"
6. Click "Deploy"

### Option 3: Deploy via Vercel Web Interface
1. Zip the `mobile-client` folder
2. Go to [vercel.com](https://vercel.com)
3. Drag and drop the zip file
4. Your app will be deployed instantly

## Environment Configuration

For production deployment, you'll need to update the API endpoint:

### Update API Base URL for Production

1. Create `mobile-client/.env.production`:
```env
VITE_API_BASE_URL=https://your-akaunting-domain.com
```

2. Or update the `vite.config.ts` proxy configuration to point to your production Akaunting instance.

## PWA Installation

Once deployed, users can install the app by:

### On Mobile (iOS/Android):
1. Open the app in their mobile browser
2. Look for the install prompt banner at the bottom
3. Tap "Install" to add to home screen
4. The app will appear like a native app with your custom icon

### On Desktop:
1. Visit the app URL
2. Look for the install icon in the address bar (Chrome/Edge)
3. Click to install as a desktop app

## Testing PWA Features

### Test Install Prompt
1. Open Chrome DevTools
2. Go to Application tab → Manifest
3. Click "Add to homescreen" to test install flow

### Test Offline Functionality  
1. Open Network tab in DevTools
2. Check "Offline" checkbox
3. Navigate around the app - cached pages should still work

### Test Service Worker
1. Go to Application tab → Service Workers
2. You should see the registered service worker
3. Check Application → Storage to see cached resources

## Production Checklist

- [ ] **API Endpoint**: Update to production Akaunting instance
- [ ] **HTTPS**: Ensure your domain uses HTTPS (required for PWA)
- [ ] **Icons**: Update app icons if needed (`public/icons/`)
- [ ] **App Name**: Update app name in manifest and meta tags
- [ ] **Performance**: Run Lighthouse audit for PWA score
- [ ] **Testing**: Test install flow on different devices

## Custom Domain

To use a custom domain with Vercel:

1. Go to your Vercel project dashboard
2. Click "Settings" → "Domains" 
3. Add your custom domain
4. Update DNS records as shown
5. Your PWA will be available at your custom domain

## Monitoring

After deployment, you can monitor:
- **Performance**: Use Vercel Analytics
- **PWA Features**: Chrome DevTools Lighthouse audit
- **Usage**: Service worker cache hits in browser DevTools
- **Install Rates**: Track via Analytics events

## Troubleshooting

**Service Worker Issues:**
- Clear browser cache and reload
- Check Console for SW registration errors
- Verify HTTPS is enabled

**Install Prompt Not Showing:**
- Ensure HTTPS is enabled
- Check PWA manifest is valid
- Verify all required icons are present

**Offline Mode Not Working:**
- Check Network tab for cached resources
- Verify service worker is registered and active
- Check workbox cache configuration

Your Akaunting Mobile PWA is now ready for production! 🚀