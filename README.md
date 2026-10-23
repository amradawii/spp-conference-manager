# SPP Conference Manager

A shared schedule board for the **International Conference for Science Park Publisher (ICSPP-2026)**, 24–25 October 2026 (online).
It replaces the PDF drafts that were edited and re-posted after every change: the team keeps one live schedule, and speakers and attendees always see the current programme.

## Features

**Public programme** (the domain root)

- Full two-day programme with session chairs, journal titles, the daily Zoom links and every time shown in Cairo and Prague, including the 25 October clock change
- Filter by day or session, and search by speaker, topic, journal or session chair
- **Save PDF**: a clean A4 programme with page numbers, generated on the server
- No statuses, notes or internal details

**Team board** (private link)

- Speaker confirmation and assets status in one or two clicks
- Session chairs, notes, and add / edit / remove sessions and talks
- Every change shows who made it and when; the editor's name is remembered per device
- Summary counters and filters for what still needs work (pending, warning, not attending)
- "Copy read-only link" to share the public programme

There are no accounts: the team link is a secret URL, and only that link can edit.

## Tech stack

Laravel 12 · Inertia v3 · Svelte 5 · TypeScript · Tailwind CSS v4 · MySQL 8 · dompdf

## Local setup

Requirements: PHP 8.2+, Composer, Node 22+, MySQL 8 (or MariaDB 10.11+).

```bash
composer setup                 # install, .env, app key, migrate, build
php artisan db:seed            # the SPP 2026 programme
php artisan conference:link    # prints the team link and the public link
composer dev                   # http://127.0.0.1:8000
```

Set the `DB_*` values in `.env` before `composer setup`. Tests use a separate `schedule_app_test` database.

## Tests

```bash
php artisan test        # feature tests (PHPUnit)
npm test                # unit tests (Vitest)
npm run check           # type check (svelte-check)
vendor/bin/pint --test  # code style
```

## Deployment

Every push to `main` runs the tests on GitHub Actions and, if they pass, deploys to the server over SSH (`.github/workflows/deploy.yml`, `scripts/deploy.sh`). The server only needs PHP and MySQL; assets are built in CI.

First-time server setup (CloudPanel), required secrets and production rules are in [docs/system-design.md](docs/system-design.md#53-production-cloudpanel-vps--auto-deploy-from-github).

## Documentation

[docs/system-design.md](docs/system-design.md) covers the goals, UX, data model, architecture, decisions and implementation phases.
