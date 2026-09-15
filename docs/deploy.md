# Deploying Bargain (live demo)

Bargain is a PHP/Yii2 application with separate **frontend** and **backend** entry points and a **PostgreSQL** database. For interviewers or stakeholders, the easiest way to run a public preview is Docker locally, or a small cloud VM / PaaS with PHP + Postgres.

## Option A — Docker (recommended for local demo)

Requirements: [Docker Desktop](https://www.docker.com/products/docker-desktop/)

```bash
git clone https://github.com/Apollosankii/bargain.git
cd bargain
docker compose up -d --build
docker compose exec frontend composer install --no-interaction
docker compose exec frontend php /app/init --env=Development --overwrite=All
```

Update `common/config/main-local.php` to use the MySQL service hostname (`mysql`) if using the bundled Docker MySQL container, or point to your PostgreSQL instance.

```bash
docker compose exec frontend php /app/yii migrate --interactive=0
docker compose exec frontend php /app/yii rbac/init
docker compose exec frontend php /app/yii rbac/sync-all
```

Access:

- **Public site:** http://127.0.0.1:20080
- **Admin console:** http://127.0.0.1:21080

## Option B — Share a hosted preview

To give interviewers a URL without asking them to install anything:

1. Deploy frontend and backend to a PHP-capable host (Render, Railway, Fly.io, or a VPS with Nginx + PHP-FPM).
2. Provision **PostgreSQL** (Supabase, Neon, or Render Postgres).
3. Set environment-specific config in `common/config/main-local.php` on the server (never commit secrets).
4. Run migrations and RBAC sync on deploy.
5. Add the public URL to the repository **About → Website** field and the README **Live demo** section.

> **Note:** GitHub Pages cannot run Yii2/PHP. Use screenshots in the README plus either a hosted URL or Docker instructions for a full interactive preview.

## Demo accounts

Do not commit real passwords. For a public demo instance, create dedicated test users and share credentials privately (e.g. email request), or document them only on the deployed environment.

Suggested roles to seed:

| Role        | Purpose                          |
|-------------|----------------------------------|
| Admin       | Backend moderation & payments    |
| Auctioneer  | Create auctions, subscriptions   |
| Bidder      | Browse auctions and place bids   |
