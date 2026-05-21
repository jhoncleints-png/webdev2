# Railway Deployment Guide

## Overview

This Symfony backend can be deployed to Railway as a service. Your React Native mobile app will consume this backend via API calls.

## Deployment Architecture

- **Backend (Symfony)**: Deployed as a Railway service
- **Frontend (React Native)**: Mobile app that runs on phones (Android/iOS), NOT deployed to Railway
- **Connection**: React Native app makes HTTP requests to the Railway backend URL

## Deployment Files Created

1. `railway.json` - Railway configuration
2. `.env.example` - Environment variables template

**Note**: You do NOT need nginx, entrypoint.sh, or Dockerfile. Railway handles containerization automatically using PHP buildpacks.

## Deployment Steps

### 1. Push to GitHub
```bash
git add .
git commit -m "Add Railway deployment files"
git push
```

### 2. Connect to Railway
1. Go to [railway.app](https://railway.app)
2. Click "New Project" → "Deploy from GitHub repo"
3. Select this repository
4. Railway will automatically detect Symfony and configure it

### 3. Configure Environment Variables
In Railway dashboard, set these variables:
- `DATABASE_URL` (Railway provides this automatically if you add a PostgreSQL service)
- `JWT_SECRET_KEY` - Path to your JWT private key
- `JWT_PUBLIC_KEY` - Path to your JWT public key
- `JWT_PASSPHRASE` - Your JWT passphrase
- `GOOGLE_CLIENT_ID` - Your Google OAuth client ID
- `GOOGLE_CLIENT_SECRET` - Your Google OAuth client secret
- `APP_ENV=prod`
- `APP_SECRET` - Generate a random secret
- `APP_URL` - Your Railway app URL

### 4. Generate JWT Keys
Before deployment, generate JWT keys:
```bash
mkdir -p config/jwt
openssl genpkey -out config/jwt/private.pem -aes256 -algorithm rsa -pkeyopt rsa_keygen_bits:4096
openssl pkey -in config/jwt/private.pem -out config/jwt/public.pem -pubout
```

Add `config/jwt/` to `.gitignore` and upload the keys manually to Railway's filesystem or use Railway's secrets.

### 5. Run Database Migrations
Railway will automatically run migrations on deploy if configured, or you can run manually via Railway console:
```bash
php bin/console doctrine:migrations:migrate
```

## Connecting React Native App

Once deployed, your React Native app will use:
```
https://your-backend.railway.app/api
```

Update your React Native app's API base URL to the Railway URL.

## Troubleshooting

- **Build fails**: Check Railway logs for errors
- **Database connection**: Ensure DATABASE_URL is set correctly
- **JWT errors**: Verify JWT keys are uploaded and configured
- **CORS issues**: Add your mobile app's domain to CORS_ALLOW_ORIGIN (or use wildcard for development)
