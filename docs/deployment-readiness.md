# Production deployment readiness

Audit date: 2026-08-26  
Repository: `C:\Users\tanxi\Desktop\living-heritage-malaysia`  
Audited branch: `main` (no branch switch, commit, push, or reset performed)

## Decision

**NOT DEPLOYMENT READY**

The code builds and the main public pages respond, but the current checkout is
still configured as a local development environment and the full test suite is
not green. There is also an effective unauthenticated trip-planner POST route
that must be corrected before exposing the application publicly.

Required before production:

1. Supply a production `.env` with `APP_ENV=production`, `APP_DEBUG=false`, a
   real HTTPS `APP_URL`, and the existing persistent `APP_KEY`. Never generate
   a new key during a normal deployment.
2. Configure and verify the production Supabase PostgreSQL, Auth, and Storage
   settings. `SUPABASE_SERVICE_ROLE_KEY` is required by profile/community
   uploads and must remain server-only; it must never be placed in a `VITE_*`
   variable or sent to the browser. Set PostgreSQL TLS deliberately (normally
   `DB_SSLMODE=require`).
3. Configure a production mail transport and sender. The local values must not
   be used as the production mail contract; send a real test message after
   deployment.
4. Resolve the festival-reminder request contract mismatch (the controller
   requires `selected_date` while three existing feature tests omit it), then
   rerun the complete suite.
5. Protect the effective `trip-planner/plan` and `trip-planner/add` POST routes
   with `auth`. `trip-planner/plan` is declared twice and the later declaration
   currently wins in the route list without `auth` middleware. This is a
   security/data-ownership issue, not a deployment-only configuration choice.
6. Create the `public/storage` link on the host and make the Laravel writable
   directories persistent and writable by the web/worker user.
7. Register the production OAuth callback URLs, scheduler, and (if using the
   database queue) a continuously running queue worker.

No database rows or schema were changed by this audit. The current Supabase
migration status showed all migrations in this repository as `Ran`; do not run
migrations or seeders against production until the target database has been
  compared with the repository and backed up.

## Runtime and dependency requirements

### PHP and Laravel

- `composer.json` requires PHP `^8.2` and Laravel `^12.0`.
- Tested locally with PHP 8.2.12 and Laravel Framework 12.64.0.
- Composer 2.10.2 was used locally. Use Composer 2.x on the deployment host.
- The non-development platform check passed for the locked vendor tree.
- The application uses PostgreSQL, so the host must provide `pdo_pgsql` and
  `pgsql` in addition to the framework extensions.
- Required/used PHP capabilities include PDO, `pdo_pgsql`, cURL, OpenSSL,
  mbstring, fileinfo, JSON, DOM/XML/libxml, tokenizer, ctype, hash, iconv,
  PCRE, session, and standard PHP date/filter support. Keep these native
  extensions enabled on the host rather than relying on development shims.

### Composer

`composer.lock` is present and `composer validate --strict` passed. The safe
production install on a deployment artifact or host is:

```text
composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader
```

This command was only dry-run validated here. Running it in this development
checkout would remove development packages, so it was intentionally not run
for real.

### Node, Vite, and static assets

- `package-lock.json` (lockfile version 3) is present and `npm ci --dry-run`
  passed.
- The installed Vite/Laravel Vite plugin require Node `^20.19.0 || >=22.12.0`.
- Node 24.18.0 and npm 11.16.0 were tested locally. Pin a supported LTS
  version in the deployment environment.
- Build with:

```text
npm ci
npm run build
```

- `public/build` is intentionally ignored by Git. The generated
  `public/build/manifest.json` and assets must be created as part of the build
  and copied into the released artifact; do not run the Vite development server
  in production.
- The production build completed successfully in this audit (Vite 7.3.6).

## Environment-variable checklist (names only)

Do not copy local secret values into this document or into a browser bundle.

### Application

- `APP_NAME`
- `APP_ENV=production`
- `APP_KEY` (persistent, non-empty)
- `APP_DEBUG=false`
- `APP_URL` (public HTTPS origin)
- `APP_LOCALE` and `APP_FALLBACK_LOCALE` as required by hosting. The current
  `config/app.php` keeps the application timezone explicitly at UTC.

### Database / Supabase

- `DB_CONNECTION=pgsql`
- `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
- `DB_SSLMODE` (use the Supabase/TLS setting approved for the host)
- `SUPABASE_URL`
- `SUPABASE_ANON_KEY` (server configuration; the browser receives only the
  corresponding public `VITE_*` values)
- `SUPABASE_JWT_SECRET`
- `SUPABASE_SERVICE_ROLE_KEY` (server-only; required for profile/community
  storage uploads)
- `VITE_SUPABASE_URL`
- `VITE_SUPABASE_ANON_KEY`

The login flow calls Supabase Auth in the browser, then `/auth/callback` and
`/auth/sync` validate/synchronise the session in Laravel. Supabase Auth and the
Google provider must allow the exact production origin and
`<APP_URL>/auth/callback`; the Google provider must also contain Supabase's
provider callback URL. The frontend builds the callback from
`window.location.origin`, so serving the app from an unconfigured subdirectory
will not work without an explicit application change.

### Sessions, cache, and queues

- `SESSION_DRIVER` (currently file; this requires persistent local storage and
  is suitable for one web instance only)
- `SESSION_LIFETIME`, `SESSION_DOMAIN`, `SESSION_PATH`
- `SESSION_SECURE_COOKIE=true` for HTTPS
- `SESSION_HTTP_ONLY=true`, `SESSION_SAME_SITE=lax` (adjust only for the
  approved OAuth topology)
- `CACHE_STORE` (currently database; `cache` and `cache_locks` tables must
  exist, or select a shared Redis/cache service)
- `QUEUE_CONNECTION` (currently database; `jobs` and failed-job tables must
  exist if this remains selected)
- `DB_QUEUE_CONNECTION`, `DB_QUEUE_TABLE`, `DB_QUEUE`,
  `DB_QUEUE_RETRY_AFTER` if customised

### Mail

- `MAIL_MAILER`
- `MAIL_SCHEME`, `MAIL_HOST`, `MAIL_PORT`
- `MAIL_USERNAME`, `MAIL_PASSWORD`
- `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME`
- `RESEND_API_KEY` if the Resend transport is selected instead of SMTP

Festival alerts and the alert test/controller paths send mail. A production
mail transport, verified sender/domain, and outbound SMTP/API access are
required; logging mail is not a delivery solution.

### Optional AI assistant

- `DISCOVERY_AI_ENABLED`
- `DISCOVERY_AI_PROVIDER`
- `DISCOVERY_AI_ENDPOINT`
- `DISCOVERY_AI_API_KEY`
- `DISCOVERY_AI_MODEL`
- `DISCOVERY_AI_TIMEOUT`

The current default is disabled. With AI disabled, the Cultural Discovery
Assistant uses rule-based intent parsing and deterministic grounded messages,
so it does not need an AI provider. If AI is enabled, the OpenAI-compatible
endpoint is used server-side for intent interpretation and natural response
generation. Configure the endpoint, model, key, timeout, DNS, and outbound
HTTPS access; never expose the API key through Vite.

Only the current user question, detected intent, deterministic fallback
message, relevant experience fields, comparison fields, active filters, and
existing recommendation reasons are sent for response generation. The
integration does not send authentication tokens, API keys, email addresses,
passwords, private user identifiers, image URLs, details URLs, full profiles,
complete activity histories, or database dumps. Provider responses are
validated and may replace only the plain-text `message`; cards, IDs, URLs,
images, comparisons, and suggestions remain application-controlled.

## Storage and filesystem

The configured default disk is `local`; profile/community uploads explicitly
use Supabase Storage. For local/public Laravel files, the following directories
must exist, be writable by PHP and workers, and be persistent across releases:

```text
storage/app/private
storage/app/public
storage/framework/cache
storage/framework/sessions
storage/framework/views
storage/logs
bootstrap/cache
```

Run once per host/release as appropriate:

```text
php artisan storage:link
```

The current checkout has no `public/storage` link. On an ephemeral platform,
use a persistent/object-backed disk for user uploads instead of relying on a
release filesystem.

## Queues, scheduler, and external services

- `routes/console.php` schedules `php artisan alerts:send` every minute. Add a
  host cron entry (or the platform equivalent):

```text
* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
```

- If `QUEUE_CONNECTION=database` remains selected, run a managed worker, for
  example `php artisan queue:work --tries=3`, and monitor failed jobs. The
  database queue/cache/jobs migrations must already exist in Supabase.
- Supabase PostgreSQL, Supabase Auth, Supabase Storage, Google OAuth, and the
  Malaysian weather endpoint (`data.gov.my`) are external dependencies that
  require DNS, outbound HTTPS, timeouts, and monitoring.
- The Discovery Assistant's external AI provider is optional as described
  above.
- Leaflet and the Malaysia state GeoJSON are bundled by Vite. The map requests
  OpenStreetMap tiles at runtime; provide outbound browser access and honour
  that provider's attribution/usage policy. No map API key is currently
  configured in this repository.

## Routes, health checks, and security observations

- `php artisan route:cache` and `php artisan config:cache` both succeeded.
  Rebuild them after loading the production `.env`; local-only alert test routes
  are registered when `APP_ENV` is local.
- The built-in `/up` route returned HTTP 200 and is suitable as a liveness
  check. It does not prove PostgreSQL, Supabase Auth/Storage, mail, queue, or
  external API readiness; monitor those dependencies separately.
- The app's relative auth endpoints (`/login`, `/auth/callback`, `/auth/sync`)
  are correct when deployed at the domain root. The OAuth allow-lists must match
  the public HTTPS origin.
- `routes/web.php` declares `trip-planner/plan` twice. The effective route list
  shows the later route with only `web` middleware; `trip-planner/add` is also
  registered without `auth`, while both controllers use `auth()->id()`. Protect
  these writes and remove the duplicate before production.
- Development defaults and documentation still contain `localhost`/
  `127.0.0.1`; these are harmless only when the corresponding environment
  variable is explicitly set in production. The current local `.env` has
  `APP_URL=http://localhost` and debug enabled.
- The `env()` search found environment reads in Laravel configuration files,
  not in application controllers/views. This is compatible with config
  caching, provided the production `.env` is loaded before caching.
- Festival Blade templates contain `console.log` statements. They do not print
  secrets in this audit, but they should be removed or gated before release to
  reduce production console noise.
- No application `dd()`, `dump()`, `var_dump()`, or `print_r()` calls were found
  in the audited source paths.

## Database and migration policy

`php artisan migrate:status` connected to the configured PostgreSQL database and
reported every migration in `database/migrations` as `Ran` at audit time. This
does not authorize running migrations on another production database. Before a
release:

1. Compare the target Supabase schema and its migration table with the commit.
2. Take/verify a backup and obtain the team's migration approval.
3. Run `php artisan migrate --force` only if there are approved pending
   migrations and the schema diff is understood.
4. Do not run seeders or demo-data commands against the existing Supabase data.

Existing Supabase data is the system of record and must remain untouched by the
deployment preparation.

## Commands validated in this checkout

- `composer validate --no-check-publish --strict` — passed.
- `composer check-platform-reqs --no-dev` — passed for the installed vendor
  tree.
- `composer install --no-dev --optimize-autoloader --dry-run --no-interaction`
  — validated only; Packagist was intermittently unreachable, so no real
  no-dev install was performed in the development tree.
- `php artisan optimize:clear` — passed.
- `php artisan route:cache` — passed.
- `php artisan config:cache` — passed.
- `php artisan view:cache` — passed.
- `npm ci --dry-run --ignore-scripts` — passed.
- `npm run build` — passed.
- `git diff --check` — passed.
- `php artisan migrate:status` — all repository migrations reported `Ran`.
- `php artisan schedule:list` — `alerts:send` is scheduled every minute.
- HTTP smoke checks via the local Laravel server:
  `/`, `/experiences`, `/experiences/map`, `/recommendations`, and `/up` all
  returned HTTP 200.
- `php artisan test` — **154 passed, 3 failed (647 assertions)**. All three
  failures are in `FestivalReminderExperienceIntegrationTest` and receive
  HTTP 422 because `selected_date` is required by
  `NotificationController::storeReminder` but is omitted by those tests.

## Compatible deployment sequence

On a clean deployment artifact, after installing the approved production `.env`
and confirming the database policy:

```text
composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader
npm ci
npm run build
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan storage:link
```

Use PHP-FPM/Apache/Nginx (document root `public`) or the hosting platform's
managed PHP runtime. `php artisan serve --host=0.0.0.0 --port=8000` is only a
basic single-process fallback, not a production web server. Start a managed
queue worker and the scheduler separately when those services are enabled.

## Post-deployment verification checklist

- [ ] `APP_ENV=production`, `APP_DEBUG=false`, HTTPS `APP_URL`, and persistent
      `APP_KEY` are confirmed without logging secret values.
- [ ] `/up`, `/`, `/experiences`, `/experiences/map`, and
      `/recommendations` return expected status codes and rendered assets.
- [ ] `public/build/manifest.json` is present and browser assets load without
      404s; browser console has no relevant errors.
- [ ] Supabase experience queries, Auth callback/sync, and server-side Storage
      uploads work with least-privilege keys.
- [ ] Google/Supabase redirect allow-lists match the production origin.
- [ ] A real mail delivery test succeeds and failures are observable.
- [ ] Queue worker processes a test job (if enabled), and `schedule:run` runs
      once without errors.
- [ ] Leaflet tiles and coordinate-bearing experience markers render; records
      without coordinates do not crash the map.
- [ ] Logs, failed jobs, database connections, storage capacity, and uptime
      health checks are monitored.
- [ ] The full automated test suite is green and the trip-planner route access
      is verified as authenticated.
