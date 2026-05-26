# JWT keys (local development)

Private and public PEM files are **not** committed to git.

Generate a key pair once on your machine:

```bash
mkdir -p config/jwt
openssl genpkey -out config/jwt/private.pem -algorithm rsa -pkeyopt rsa_keygen_bits:4096
openssl pkey -in config/jwt/private.pem -out config/jwt/public.pem -pubout
```

Then in `.env` or `.env.local`:

```dotenv
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=
```

On Railway/production, use environment variables or platform secrets instead of committing keys.
