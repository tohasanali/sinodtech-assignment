# Developer Guide

Everything needed to get SinodTech running locally: repo layout, Docker services, environment
configuration, database setup, and seeded credentials. For the features checklist, architecture
rationale, and ERD, see [README.md](README.md).

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

| Service | Container | Purpose |
|---|---|---|
| `app` | `SINOD_APP` | PHP-FPM running the Laravel application |
| `nginx` | `SINOD_NGINX` | Web server, proxies PHP requests to `app`, exposed on `:${BACKEND_PORT}` |
| `mysql` | `SINOD_MYSQL` | MySQL 8 database |
| `frontend` | `SINOD_FRONTEND` | Vite dev server for the React SPA, exposed on `:${FRONTEND_PORT}` |
| `scheduler` | `SINOD_SCHEDULER` | `php artisan schedule:work` — runs the task scheduler (daily `customers:reengage` sweep) |
| `queue` | `SINOD_QUEUE` | `php artisan queue:work` — processes queued jobs (invoice emails, re-engagement notifications) |
| `phpmyadmin` | `SINOD_PHPMYADMIN` | phpMyAdmin, exposed on `:${PHPMYADMIN_PORT}` — not one of the six core services, included because it's genuinely part of the stack |

`queue` and `scheduler` run as separate containers from `app` so a crashed worker or scheduler
process doesn't take down the API, and vice versa. `app`/`queue`/`scheduler` gate their startup on
`mysql`'s healthcheck (`condition: service_healthy`), not just container start — otherwise they'd
race MySQL's first-boot initialization and crash with a connection error.

## Setup instructions

First-time setup — all four `.example` files need copying (the real `docker-compose.yml` and
every `.env` are git-ignored on purpose, so secrets/local config never land in the repo):

```bash
cp docker-compose.yml.example docker-compose.yml
cp .env.example .env
cp backend/.env.example backend/.env
cp frontend/.env.example frontend/.env
docker compose up -d --build
```

Host ports (root `.env`, defaults shown):

| Var | Default | Points to |
|---|---|---|
| `FRONTEND_PORT` | 8001 | React SPA (Vite dev server) |
| `BACKEND_PORT` | 8002 | Laravel API (via nginx) |
| `PHPMYADMIN_PORT` | 8003 | phpMyAdmin |

- API: http://localhost:8002 — health check at `/api/health`
- Frontend: http://localhost:8001
- phpMyAdmin: http://localhost:8003

## Environment configuration

Root `.env` only holds values consumed by `docker-compose.yml` itself (`DB_DATABASE`,
`DB_USERNAME`, `DB_PASSWORD` substituted into the `mysql`/`phpmyadmin` service blocks, plus the
three `*_PORT` host mappings) — it's separate from `backend/.env` and `frontend/.env`, which are
the actual Laravel/Vite runtime configs. Keep `DB_DATABASE`/`DB_USERNAME`/`DB_PASSWORD` in sync
between root `.env` and `backend/.env`; nothing enforces this automatically.

**`backend/.env`** — the variables that actually matter beyond Laravel's own boilerplate:

| Var | Example / default | Purpose |
|---|---|---|
| `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | `mysql` / `mysql` / `3306` / `sinodtech` / `sinodtech` / `secret` | MySQL connection — `DB_HOST=mysql` is the Compose service name, not `localhost` |
| `SESSION_DRIVER`, `QUEUE_CONNECTION`, `CACHE_STORE` | all `database` | All three backed by DB tables rather than local files — `app`, `queue`, and `scheduler` are separate containers, so state needs to live somewhere all of them can see, not on one container's local disk |
| `MAIL_MAILER` / `MAIL_HOST` / `MAIL_PORT` | `smtp` / `sandbox.smtp.mailtrap.io` / `2525` | Mailtrap sandbox SMTP |
| `MAIL_USERNAME` / `MAIL_PASSWORD` | *(blank — fill in)* | **Required**: your own Mailtrap sandbox inbox credentials, or invoice/re-engagement emails will fail to send |
| `FRONTEND_URL` | `http://localhost:8001` | The SPA's origin |
| `SANCTUM_STATEFUL_DOMAINS` | `localhost:8001` | Domains allowed to authenticate via cookie session (must match the SPA's origin) |
| `CORS_ALLOWED_ORIGINS` | `http://localhost:8001` | Comma-separated list; `config/cors.php` splits on `,` |
| `LOST_CUSTOMER_DAYS` | `90` | Days of purchase inactivity before a customer counts as "lost" |
| `REACTIVATION_KPI_POINTS` | `10` | Points awarded to an assigned employee when a lost customer buys again |
| `RECONTACT_COOLDOWN_DAYS` | `7` *(code default — not currently listed in `.env.example`, add it there if you want to override it)* | Minimum days between re-engagement notifications to the same customer |

**`frontend/.env`** — one variable:

| Var | Example | Purpose |
|---|---|---|
| `VITE_API_URL` | `http://localhost:8002` | Base URL the SPA's axios client talks to — the nginx-fronted API, not the Vite dev server's own port |

## Database migrations & seeders

```bash
# Run migrations against an already-set-up database
docker compose exec app php artisan migrate

# Fresh database + full seed data in one step (recommended for first run)
docker compose exec app php artisan migrate:fresh --seed
```

Seeders run in this order (each depends on the previous — `UserBranchSeeder` needs both users and
branches to exist first, for example) and can also be run individually:

```bash
docker compose exec app php artisan db:seed --class=UserSeeder
docker compose exec app php artisan db:seed --class=InventorySeeder
docker compose exec app php artisan db:seed --class=UserBranchSeeder
docker compose exec app php artisan db:seed --class=CustomerSeeder
docker compose exec app php artisan db:seed --class=SalesSeeder
```

What gets seeded: 7 users (1 admin, 5 employees, 1 API consumer), 3 branches, 15 products with
deliberately varied per-branch stock (fully stocked, low stock, never-stocked, and
explicitly-zero all represented), 10 customers (3 of them deterministically past the
lost-customer threshold — 2 pre-assigned to an employee, 1 left unassigned, so the admin's
assigned/unassigned filter has real data on both sides), and 43 historical sales spread over the
last ~150 days. The app is fully testable immediately after `migrate:fresh --seed` — no manual
data entry needed.

## Running it locally, end to end

```bash
docker compose up -d --build
docker compose exec app php artisan migrate:fresh --seed
```

Then open the frontend at http://localhost:8001 and log in with any of the [seeded
credentials](#seeded-credentials) below.

**Verifying the scheduler is actually registered:**

```bash
docker compose exec app php artisan schedule:list
```

should show `customers:reengage` scheduled `daily`. To watch it (or the queue) work without
waiting a full day, either trigger a manual re-engagement from the UI/API
(`POST /api/v1/admin/customers/{id}/reengage`, or the bulk variant) and tail the queue log, or run
the command directly:

```bash
docker compose exec app php artisan customers:reengage
```

**Verifying the queue is actually processing:**

```bash
docker compose logs -f queue
```

Trigger any action that queues a job — recording a sale for a customer (queues the invoice email)
or a re-engagement call (queues the notification) — and the log should show the job running and
completing. `docker compose logs -f scheduler` similarly shows the scheduler container's own
`schedule:work` loop ticking.

```bash
# Full backend test suite
docker compose exec app php artisan test

# Code style check
docker compose exec app ./vendor/bin/pint --test

# Frontend lint / build
docker compose exec frontend npm run lint
docker compose exec frontend npm run build
```

## Seeded credentials

| Role | Email | Password | Notes |
|---|---|---|---|
| Admin | `admin@sinodtech.test` | `password` | Full access everywhere |
| Employee | `employee@sinodtech.test` | `password` | Assigned to **Downtown Branch** and **Uptown Branch** — this is the account that shows the header branch switcher (2+ branches); log in as this one to see the multi-branch flow |
| Employee (×4 more) | random `fake()`-generated emails, all password `password` | `password` | Each assigned to exactly **one** branch — exercises the silent auto-select-on-login path instead of the switcher |
| API consumer | `api-consumer@sinodtech.test` | `password` | Not a login the SPA uses — see below for its scoped API token |

**Scoped API token for the e-commerce endpoint.** The API consumer's `products:read`-scoped
Sanctum token is generated fresh every time the seeders run and printed once to the console —
it is not stored anywhere retrievable afterward:

```bash
docker compose exec app php artisan migrate:fresh --seed
# ...
# API consumer token (products:read ability): 1|AbCdEf123...
```

If you've lost it, either re-seed, or mint a new one directly:

```bash
docker compose exec app php artisan tinker --execute="
  echo App\Models\User::where('email', 'api-consumer@sinodtech.test')
    ->first()
    ->createToken('ecommerce-api', ['products:read'])
    ->plainTextToken;
"
```

Example request against the scoped public endpoint (replace `{TOKEN}` with the printed value):

```bash
curl http://localhost:8002/api/v1/public/products \
  -H "Authorization: Bearer {TOKEN}" \
  -H "Accept: application/json"
```

which returns SKU/name/price/available-stock for every product — and, for contrast, the same
token used against any admin-only route (e.g. `GET /api/v1/admin/products`) correctly gets
rejected, since its ability scope is `products:read` on the public endpoint only.
