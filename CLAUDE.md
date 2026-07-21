# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

SinodTech technical assessment: a Sales, Inventory & CRM system. Laravel 13 API (`/backend`,
API-only — no Blade/Vite frontend assets) + a decoupled React SPA (`/frontend`, Vite), run via
Docker Compose. The full build plan, commit-by-commit, lives in `PLAN.md` (git-ignored, local
only — read it for what's already done vs. still ahead, and the architectural rationale to give
in the interview).

**Work one `PLAN.md` step at a time.** Each step is one commit. Don't jump ahead to a later
step's scope even if it looks convenient from where you are.

## Running the stack

First-time setup (all four `.example` files must be copied — `docker-compose.yml` and every
`.env` are git-ignored on purpose, see below):

```bash
cp docker-compose.yml.example docker-compose.yml
cp .env.example .env
cp backend/.env.example backend/.env
cp frontend/.env.example frontend/.env
docker compose up -d --build
```

- API: `http://localhost:${BACKEND_PORT}` (default 8002), health check at `/api/health`
- Frontend: `http://localhost:${FRONTEND_PORT}` (default 8001)
- phpMyAdmin: `http://localhost:${PHPMYADMIN_PORT}` (default 8003)
- MySQL: exposed on host `3306` directly (not env-parameterized)

Root `.env` only holds values consumed by `docker-compose.yml` itself (DB_* substituted into
the `mysql`/`phpmyadmin` service blocks, and the three `*_PORT` host mappings) — it is separate
from `backend/.env` and `frontend/.env`, which are the actual Laravel/Vite runtime configs.
Keep `DB_DATABASE`/`DB_USERNAME`/`DB_PASSWORD` in sync between root `.env` and `backend/.env`;
nothing enforces this automatically.

Containers: `SINOD_APP`, `SINOD_NGINX`, `SINOD_MYSQL`, `SINOD_PHPMYADMIN`, `SINOD_QUEUE`,
`SINOD_SCHEDULER`, `SINOD_FRONTEND`, all on a custom `sinodtech` bridge network. `app`/`queue`/
`scheduler` gate their startup on `mysql`'s healthcheck (`condition: service_healthy`), not just
container start — `depends_on` without that condition only waits for the container process to
launch, and `queue`/`scheduler` will race MySQL's first-boot initialization and crash with
`Connection refused` otherwise.

`docker/php/entrypoint.sh` chmods `storage/` and `bootstrap/cache/` to 777 on every container
start before handing off to the base image's `docker-php-entrypoint`. This is required because
bind-mounted files show up owned by `root` inside the Linux containers regardless of Windows
host permissions, and php-fpm's workers run as `www-data` — without this, anything Laravel
writes at runtime (compiled views, session/cache files) gets a permission-denied.

## Common commands

Backend (run inside the `app` container — no local PHP/Composer needed on the host):
```bash
docker compose exec app php artisan migrate
docker compose exec app php artisan migrate:fresh --seed
docker compose exec app php artisan test                    # full suite
docker compose exec app php artisan test --filter=TestName   # single test
docker compose exec app composer install
docker compose exec app ./vendor/bin/pint                    # code style (Laravel Pint)
```

Frontend (run inside the `frontend` container, or locally with Node — both share the same
`node_modules` via an anonymous volume, see below):
```bash
docker compose exec frontend npm run build
docker compose exec frontend npm run lint   # oxlint
```

## Architecture notes specific to this repo

- **`routes/api.php` is hand-wired, not scaffolded.** Laravel 13 doesn't ship `routes/api.php`
  or Sanctum by default; it's registered manually in `bootstrap/app.php`'s `withRouting()` call.
  Don't run `php artisan install:api` — it would pull in Sanctum, which is deliberately deferred
  to `PLAN.md` Step 2 so the auth work lands as its own reviewable commit.
- **`routes/web.php` is a JSON placeholder, not a real web frontend** — this backend is API-only
  by design (see PLAN.md's "API-first, decoupled React SPA" decision). Don't add Blade views.
- **CORS/Sanctum env vars are pre-stubbed but Sanctum isn't installed yet**: `config/cors.php`,
  `SANCTUM_STATEFUL_DOMAINS`, `FRONTEND_URL`, `CORS_ALLOWED_ORIGINS` in `backend/.env` already
  point at the SPA's dev origin so Step 2 doesn't need to hunt these down.
- **Frontend container node_modules**: `docker-compose.yml`'s `frontend` service bind-mounts
  `./frontend:/app` but layers an anonymous volume on top of `/app/node_modules` — this is
  intentional, not incidental. Windows-installed `node_modules` contain platform-specific native
  binaries (esbuild/rollup) that won't run in the Linux container, so `node_modules` must stay
  the one `npm install` produced at image build time (`docker/node/Dockerfile`), not whatever's
  on the Windows host.
- Read `PLAN.md`'s "Architectural decisions" section before touching Sales, Inventory, or CRM
  logic in later steps — it documents the *why* behind several non-obvious schema choices
  (branch-scoped stock table instead of a `stock_quantity` column, KPI as an append-only ledger
  instead of a mutable counter, `wasLost` computed before the sale insert rather than after,
  pessimistic locking on stock deduction) that are easy to get subtly wrong if re-derived from
  scratch.
