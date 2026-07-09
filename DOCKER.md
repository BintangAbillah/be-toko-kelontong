# Docker Deployment

This project can run as a Docker stack with:

- Laravel PHP-FPM app
- Nginx web server
- MySQL database

## Start

```bash
docker compose up -d --build
docker compose exec app php artisan migrate --force
```

The API is available at:

```text
http://localhost:8000/api
```

Health check:

```text
http://localhost:8000/up
```

## Database

Inside Docker, the app uses these defaults:

```text
DB_HOST=mysql
DB_DATABASE=toko_kelontong
DB_USERNAME=toko
DB_PASSWORD=secret
```

The MySQL port is exposed to the host as:

```text
127.0.0.1:3307
```

You can override Docker database credentials in your shell or `.env`:

```text
DOCKER_DB_USERNAME=toko
DOCKER_DB_PASSWORD=secret
DOCKER_DB_ROOT_PASSWORD=root
```

## Useful Commands

```bash
docker compose ps
docker compose logs -f app
docker compose logs -f nginx
docker compose exec app php artisan migrate
docker compose exec app php artisan route:list --path=api
docker compose down
```

To remove containers and database volume:

```bash
docker compose down -v
```
