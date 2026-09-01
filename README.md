# Hookline

[![Tests](https://github.com/nsvetozarevic/hookline/actions/workflows/tests.yml/badge.svg?branch=main)](https://github.com/nsvetozarevic/hookline/actions/workflows/tests.yml)

Durable webhook relay built with Laravel. Inbound webhooks are verified, stored, and forwarded to your destinations with retries, exponential backoff, dead-lettering, and manual replay.

Portfolio demo, not a hosted product.

## What it does

| Area | Behavior |
| --- | --- |
| **Capture** | `POST /capture/{token}` - [Standard Webhooks](https://www.standardwebhooks.com/) v1 HMAC, per-token rate limit, body size cap |
| **Idempotency** | `webhook-id` is the deduplication key; duplicate returns `200`, first capture returns `202` |
| **Fan-out** | One stored event creates one delivery row per active destination |
| **Delivery** | Queue worker POSTs the original payload; signs outbound requests the same way |
| **Retries** | Exponential backoff (configurable cap); scheduler dispatches due rows every minute |
| **Dead letter** | Max attempts exhausted, terminal state; replay from the panel |
| **Safety** | SSRF guard on destination URLs; stuck in-flight deliveries released on a schedule |
| **Panel** | Register, manage endpoints and destinations, inspect events, attempts, and replay |

For how the pieces connect and why key decisions were made, see [ARCHITECTURE.md](ARCHITECTURE.md).

## Stack

| Layer | Choice |
| --- | --- |
| Runtime | PHP 8.5, Laravel 13 |
| UI | Livewire 4, Alpine, Tailwind v4, Fortify (register/login) |
| Data | PostgreSQL 16 |
| Queue / cache / sessions | Redis 7 |
| HTTP hardening | `cboxdk/laravel-ssrf` on outbound delivery |
| Local dev | Docker Compose (app, nginx, worker, scheduler, node, postgres, redis) |
| Quality | PHPUnit, PHP CS Fixer, Larastan; CI on push/PR |

Domain logic lives under `Domain/`; HTTP and panel adapters under `Interfaces/`.

## Run locally

**Requirements:** Docker Desktop (Compose v2).

```bash
cp -n .env.docker.example .env.docker.local   # first time only
docker compose up --build
```

Wait until the stack is up, then seed inside the **app** container:

```bash
docker compose exec app php artisan db:seed
```

The seed command loads the demo user `user@example.com` / `password123`.

On first start, the **app** container runs `composer install` (if `vendor/` is missing), `APP_KEY` generation (if empty), and `php artisan migrate`. The **node** container runs `npm ci && npm run build`. No local PHP, Composer, or Node install required.


| Service | URL / access |
| --- | --- |
| App | http://localhost:8085 |
| Health | http://localhost:8085/up |
| Postgres | `localhost:5434` - user/pass/db: `hookline` / `secret` / `hookline` |
| Redis | `localhost:6380` |
| Worker | `docker compose logs -f worker` |
| Scheduler | `docker compose logs -f scheduler` |

Host ports are offset from the usual defaults so Hookline is less likely to clash with other local services. If one is still in use, change the mapping in `compose.yaml` (e.g. `8085:80` → `9085:80`) and update `APP_URL` in `.env.docker.local` to match.

Stop: `docker compose down`. Wipe DB volume: `docker compose down -v`.

### Frontend assets

With the stack running, use the **node** container (same pattern as `app` for PHP):

```bash
docker compose exec node npm run build   # rebuild after CSS changes
docker compose exec node npm run dev     # Vite dev server on port 5173
```

Run `dev` in a second terminal for live reload while editing CSS. Run `build` when you want compiled assets without the dev server.

## Demo walkthrough

1. Log in at http://localhost:8085/login with `user@example.com` / `password123` (from the Docker seed step above).
2. **Endpoints**, then **Create endpoint**. Copy the capture token and signing secret from the show page.
3. Add a **destination** URL (e.g. [webhook.site](https://webhook.site) or a local listener).
4. Send a signed capture request. The panel show page includes a curl recipe; signing follows Standard Webhooks (`webhook-id`, `webhook-timestamp`, `webhook-signature` over `id.timestamp.body`). Official [client libraries](https://github.com/standard-webhooks/standard-webhooks) work for generating signatures.
5. Open **Events** to see the payload, delivery status, attempt log, and **Replay** on failed or dead deliveries.

First capture returns `202` with `{ "deduplication_key": "..." }`. Sending the same `webhook-id` again returns `200` without a new row.

## Tests and static analysis

With the stack running (see [Run locally](#run-locally)):

```bash
docker compose exec app composer test       # php artisan test
docker compose exec app composer cs-check   # PHP CS Fixer dry-run
docker compose exec app composer analyse    # PHPStan / Larastan
docker compose exec app composer cs         # apply fixes
```

No local PHP or Composer install required - commands run inside the `app` container. Postgres includes a `hookline_testing` database for the test suite.

GitHub Actions runs the same suite on every push and pull request to `main`.

## Configuration

Delivery and capture tuning live in `config/hookline.php` (timeouts, backoff, rate limits, header allowlist). Hookline-specific log level and retention: `HOOKLINE_LOG_LEVEL`, `HOOKLINE_LOG_DAYS` in `.env`.
