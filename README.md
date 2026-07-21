# SinodTech — Sales, Inventory & CRM System

Laravel API + React SPA, orchestrated with Docker Compose.

> This README is a scaffolding skeleton (Step 1). Full setup instructions, environment
> configuration, seeded credentials, and architecture rationale are filled in as later steps land
> (see `PLAN.md`).

## Repo structure

```
/backend    → Laravel API (routes/api.php is the primary route file)
/frontend   → React SPA (Vite)
/docker     → Dockerfiles and nginx config for the containers below
docker-compose.yml.example   → copy to docker-compose.yml before running (git-ignored, local only)
```

## Docker

Services defined in `docker-compose.yml`, all joined to a custom `sinodtech` bridge network with
explicit `SINOD_*` container names:

| Service      | Container          | Purpose                                              |
|--------------|--------------------|-------------------------------------------------------|
| `app`        | `SINOD_APP`        | PHP-FPM running the Laravel application                |
| `nginx`      | `SINOD_NGINX`      | Web server, proxies PHP requests to `app`, exposed on `:${BACKEND_PORT}` |
| `mysql`      | `SINOD_MYSQL`      | MySQL 8 database                                       |
| `phpmyadmin` | `SINOD_PHPMYADMIN` | phpMyAdmin, exposed on `:${PHPMYADMIN_PORT}`            |
| `queue`      | `SINOD_QUEUE`      | `php artisan queue:work` — processes queued jobs (invoice emails, notifications) |
| `scheduler`  | `SINOD_SCHEDULER`  | `php artisan schedule:work` — runs the task scheduler (daily re-engagement command) |
| `frontend`   | `SINOD_FRONTEND`   | Vite dev server for the React SPA, exposed on `:${FRONTEND_PORT}` |

`queue` and `scheduler` run as separate containers from `app` so a crashed worker or scheduler
process doesn't take down the API, and vice versa. `app`/`queue`/`scheduler` wait on `mysql`'s
healthcheck (not just container start) before booting, since Laravel needs a live connection.

### Running it

```bash
cp docker-compose.yml.example docker-compose.yml
cp .env.example .env
cp backend/.env.example backend/.env
cp frontend/.env.example frontend/.env
docker compose up -d --build
```

Host ports (`.env`, defaults shown):

| Var                 | Default | Points to        |
|---------------------|---------|-------------------|
| `FRONTEND_PORT`      | 8001    | React SPA (Vite)  |
| `BACKEND_PORT`       | 8002    | Laravel API (nginx) |
| `PHPMYADMIN_PORT`    | 8003    | phpMyAdmin        |

API: http://localhost:8002 (health check at `/api/health`)
Frontend: http://localhost:8001
phpMyAdmin: http://localhost:8003
