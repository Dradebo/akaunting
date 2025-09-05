# Vercel Deployment Guide for Akaunting Mobile PWA

## Important: Deploy Mobile Client Only

⚠️ **Critical**: Do NOT deploy the entire Akaunting repository to Vercel. Only deploy the `mobile-client` directory to avoid node-sass compatibility issues.

## Option 1: Deploy Mobile Client Directory Only (Recommended)

### Using Vercel CLI
1. Install Vercel CLI: `npm i -g vercel`
2. Navigate to mobile client: `cd mobile-client`
3. Deploy: `vercel`
4. Follow prompts to link to your Vercel account

### Using Vercel Dashboard
1. Go to [vercel.com/new](https://vercel.com/new)
2. Import your GitHub repository
3. **Set Root Directory to `mobile-client`**
4. Framework Preset: Vite
5. Build Command: `npm run build` (default)
6. Output Directory: `dist` (default)
7. Install Command: `npm install` (default)

## Option 2: Separate Repository (Alternative)

If you want cleaner separation:

1. Create new repository for mobile client only
2. Copy `mobile-client/` contents to new repo root
3. Deploy new repository to Vercel normally

## Vercel Configuration

The `vercel.json` file is already configured with:
- PWA-optimized headers
- Service Worker caching rules
- SPA routing support
- Proper manifest.json headers

## Environment Variables

Set these in Vercel Dashboard → Settings → Environment Variables:
- `VITE_API_BASE_URL`: Your Akaunting API base URL
- `VITE_APP_NAME`: "Akaunting Mobile"

## Post-Deployment Checklist

1. ✅ Test PWA installability on mobile
2. ✅ Verify service worker loads properly
3. ✅ Check manifest.json accessibility
4. ✅ Test offline functionality
5. ✅ Verify API connectivity

## Troubleshooting

### Build Fails with node-sass Error
- **Cause**: Vercel is trying to build parent Akaunting project
- **Solution**: Ensure Root Directory is set to `mobile-client`

### Service Worker Not Loading
- Check `/sw.js` is accessible
- Verify HTTPS deployment
- Check browser console for errors

### PWA Not Installable
- Verify manifest.json loads properly
- Check icon files are accessible
- Ensure HTTPS deployment
- Test on mobile Chrome/Safari

## Performance Notes

Current build includes warnings about large chunks (585KB). Consider:
- Code splitting for better performance
- Lazy loading of routes
- Dynamic imports for heavy components

Build is production-ready but can be optimized further.