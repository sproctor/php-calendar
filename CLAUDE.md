# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Overview

PHP-Calendar 3 is a web calendar built on Symfony 7.4 LTS (PHP 8.3+, `intl` and `pdo_mysql`), Doctrine
ORM 3 / DBAL 4 on MySQL or MariaDB, Twig and Bootstrap 5.3. It's meant for shared web hosting: people
upload a release zip, open it in a browser and the web installer does the rest, with no command line or
Composer needed. Keep that audience in mind: no build steps on the server, no background workers or cron
required, no third-party requests from pages (no CDNs, no Google Fonts).

Branches: `main` is development (3.x); `v2.0` maintains the old plain-PHP 2.0 line. When 3.1 work starts,
`v3.0` is branched from the `v3.0.0` tag for 3.0.x fixes. Fixes go on the oldest branch that needs them and
are merged forward. Tags are `vX.Y.Z`. GitHub: sproctor/php-calendar.

## Commands

The development stack is Docker Compose (`compose.yaml`): PHP 8.3 + Apache on http://localhost:8080,
MySQL 8.4, Mailpit on http://localhost:8025. Run PHP tooling inside the `php` container, since the
database host only resolves there:

- Tests: `docker compose exec php vendor/bin/phpunit` (one file: add its path; one test: `--filter name`).
  They use the `app_test` database, rebuilt from the migrations; mail goes to a `null://test` transport.
- Console: `docker compose exec php bin/console ...`, e.g. `cache:clear`, `lint:twig templates`,
  `lint:yaml translations config`, `lint:container`.
- Schema changes: edit the entities, then `bin/console doctrine:migrations:diff`, give the generated
  migration a description and doc comment and drop the "auto-generated" comments, then
  `doctrine:migrations:migrate`.
- Release: `bin/build-release X.Y.Z` builds `dist/php-calendar-X.Y.Z.{zip,tar.gz}` from HEAD (it refuses
  uncommitted changes), with production dependencies, a `VERSION` file, and build-only files removed (the
  Bootstrap and Bootswatch packages once copied, unused icons).

## Architecture

- **Controllers** (`src/Controller/`) use route attributes; pages live under `/{_locale}/...`.
  `LanguageController` handles URLs without a language, `FormattingLocaleSubscriber` picks one for them
  (`LocaleService::pickLanguage`: user setting, `phpc_locale` cookie, calendar default, Accept-Language).
- **Permissions**: `CalendarVoter` (`CALENDAR_READ/CREATE/UPDATE/MODERATE/ADMIN`) and `EventVoter`
  (`EVENT_VIEW/EDIT`), resolved by `PermissionResolver` from the calendar's anonymous permission, per-user
  permissions and roles. Use `EventVoter::canView($event, null)` for "can anyone see this" (feeds,
  structured data).
- **Events and dates**: an `Event` has many `Occurrence`s. Timed occurrences are stored in UTC; full-day
  and TBA ones as floating dates. `TimezoneSubscriber` sets PHP's timezone per request (user setting,
  `phpc_timezone` cookie, calendar, UTC). `OccurrenceGenerator` makes repeat series, and `update()` keeps
  existing occurrences (and their IDs, which feeds use as UIDs) when an event's dates change.
- **Install and config**: `InstallSubscriber` sends unconfigured sites to `/setup` and shows a maintenance
  page while migrations are pending. `InstallState` writes `.env.local.php` (database, mail settings).
  An empty `APP_SECRET` falls back to a generated `var/secret` (`SecretEnvVarProcessor`). Site-wide
  settings edited in the app go in the `settings` table (`SettingRepository`).
- **Mail**: `App\Mail\Mailing` sends templated emails (`templates/emails/`) synchronously, in the
  recipient's language; features that need mail check `isConfigured()`. Signed links (password reset,
  email confirmation) come from `AccountLinks`; calendar feed links from `CalendarFeedLinks`.
- **Themes**: `ThemeRegistry` lists Bootstrap, the Bootswatch themes and admins' own in `public/themes/`;
  a calendar's theme, else the site default. Dark-designed themes are detected from their CSS.
- **Front end**: Bootstrap and Bootswatch come from Composer and `bin/copy-assets` copies them into
  `public/static/lib/` (gitignored), stripping source maps and Google Fonts imports. Icons are Symfony UX
  Icons from `twbs/bootstrap-icons`; always write icon names in full (`'bi:gear'`), since releases only
  keep the icons `bin/used-icons` finds and it fails on names built from parts. App JS/CSS is plain
  `public/static/phpc.js` and `phpc.css`, versioned by file time.
- **Importing 2.0**: `App\Import\Import20` (web installer and `bin/console app:import-2.0`).

## Translations

Domains: `messages`, `validators`, `security`, `account`, as `translations/<domain>.<code>.yaml`, with
English as the source. 26 languages; a language appears in the menu once it has a `messages` file. Add new
strings to English and French at least, and keep keys in English order; other languages fall back to
English until translated (community translations come through Weblate). Placeholders are `%name%`.
Right-to-left languages get `dir="rtl"` and a theme's RTL stylesheet.

## Conventions

- Match the surrounding code: short doc comments that say why, sentence-case UI text, Bootstrap utility
  classes over custom CSS, theme colors via `var(--bs-...)` so dark mode and themes work.
- Every new feature gets functional tests (`tests/AppWebTestCase.php` helpers: `createUser`,
  `createCalendar`, `createEvent`, `login`, `submit`, `postWithToken`, `fresh`).
- State-changing requests are POST with a CSRF token; redirects go only to paths on this site.
- Other Claude sessions may share this checkout: stage the files you changed by name, never `git add -A`.
