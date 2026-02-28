# Docker Deployment Guide

## What was added
- `Dockerfile`
- `docker-compose.yml`
- `.dockerignore`
- `docker/entrypoint.sh`
- `docker/php/php.ini`

This setup runs:
- `app`: Laravel API on port `8000`
- `queue`: queue worker (`queue:work`)
- `scheduler`: Laravel scheduler loop (`schedule:run` every minute)

## 1) Prepare `.env`
Minimum required:
- `APP_KEY` must be set
- `MAIL_*` must be valid
- `QUEUE_CONNECTION=database`

For SQLite (default in compose):
```env
DB_CONNECTION=sqlite
DB_DATABASE=/var/www/html/database/database.sqlite
```

## 2) Build and start
```bash
docker compose up -d --build
```

## 3) Check containers
```bash
docker compose ps
docker compose logs -f app
docker compose logs -f queue
```

## 4) Useful operations
Restart all:
```bash
docker compose restart
```

Run migrations manually:
```bash
docker compose exec app php artisan migrate --force
```

Clear/rebuild Laravel caches:
```bash
docker compose exec app php artisan optimize:clear
```

Restart workers after deploy:
```bash
docker compose exec app php artisan queue:restart
```

## 5) Deploy update flow
```bash
git pull
docker compose up -d --build
docker compose exec app php artisan optimize:clear
docker compose exec app php artisan queue:restart
```

## Notes
- `RUN_MIGRATIONS=true` is enabled for `app` container startup by default.
- Data persists in Docker volumes:
  - `app_storage`
  - `app_database`
