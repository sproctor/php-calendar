# php-calendar

[![Translation status](https://hosted.weblate.org/widget/php-calendar/svg-badge.svg)](https://hosted.weblate.org/engage/php-calendar/)

by Sean Proctor

## Intent

php-calendar is an open source web-based calendar. It was originally written to facilitate
easy sharing of events for a student group at Northeastern University in 2003.

## Usage

### Installing

You need PHP 8.3 or later with the `intl` and `pdo_mysql` extensions, and a MySQL or MariaDB
database. On Apache, `mod_rewrite` and `.htaccess` files must be enabled.

1. Download `php-calendar-<version>.zip` from the releases and unpack it.
2. Upload the `php-calendar-<version>` folder to your web host. Either:
   - point your web server at its `public/` folder (best, when your host lets you), or
   - upload it into the folder your web server serves, e.g. as `public_html/calendar/`. The
     `.htaccess` file in it keeps everything except `public/` from being downloaded. This only works
     on Apache; on nginx, serve `public/`.
3. Make sure the web server can write to the folder and to `var/` inside it.
4. Open the calendar in your browser. The installer asks for the database, creates the tables, and
   then either creates your first calendar and admin account or imports a PHP-Calendar 2.0
   installation from the same database.

The installer saves its settings in `.env.local.php`. To install again, delete that file.

To let people reset forgotten passwords and confirm their email addresses, set up email under
Admin → Email (an SMTP server, or the web server's sendmail) and send yourself a test.

Finish the installer soon after uploading: until then, anyone who finds the site could complete it.

### Upgrading

Upload the new version over the old one, keeping `.env.local.php`, then open `/setup` as a site
admin and update the database. Visitors see a maintenance page until you do.

This program is licensed under the Apache License, Version 2.0 located at
http://www.apache.org/licenses/LICENSE-2.0.html

Any questions, contact me at the email address found in the AUTHORS files or
on the various pages on the website.

FAQ: https://github.com/sproctor/php-calendar/wiki/Frequently-Asked-Questions

See the demos live at https://demo.php-calendar.org/ (latest release) and https://dev.php-calendar.org/ (development version)

Translations are in `translations/`, one YAML file per language for each of `messages`, `validators`
and `security`, with English (`*.en.yaml`) as the source. Besides English and French, they were
ported from the 2.0 community translations and machine-translated where 3.0's wording is new, so
corrections from native speakers are welcome. A language appears in the language menu once it has
a `messages` file. Translate on [Weblate](https://hosted.weblate.org/engage/php-calendar/), which
opens pull requests with the changes.

### Upgrading from 2.0

3.0 uses a new database schema, in tables without a prefix, so it can share the 2.0 database (whose
tables start with `phpc_` by default). When it does, the installer offers to import the 2.0 calendar.
The 2.0 tables aren't changed. If your 2.0 tables have no prefix, give 3.0 its own database and use the
command below.

With a command line, you can instead import from any database:

    php bin/console doctrine:migrations:migrate
    php bin/console app:import-2.0 "mysql://user:password@host/old_database" --prefix=phpc_

Use the table prefix from 2.0's `config.php` (`SQL_PREFIX`). Add `--dry-run` to check the import
without saving anything. Users keep their passwords, and 2.0 groups become roles.

### Subscribing from a calendar app

Each calendar has a Subscribe button that gives an iCalendar (`.ics`) link for Google Calendar, Apple
Calendar, Outlook and other calendar apps, which then keep the calendar up to date themselves. A
calendar anyone can read has a public link, `/calendar/<name>/feed.ics`; logged-in users also get a
personal link that includes events only they can see, and can reset it if it leaks.

### Embedding a calendar

To show a calendar on another website, add this where it should appear:

    <script src="https://calendar.example.com/static/embed.js"
            data-src="https://calendar.example.com/en/calendar/club-events/"></script>

`data-src` can be any month or day view, event, or search URL. The calendar appears in an
iframe that grows to fit it.

## Supporting PHP-Calendar

If PHP-Calendar is useful to you, you can support its development through
[GitHub Sponsors](https://github.com/sponsors/sproctor) or a one-time donation with
[PayPal](https://www.paypal.com/donate/?hosted_button_id=2N7QSB55AEEZL). Any donations are much
appreciated.

## Development

Source code can be found at https://github.com/sproctor/php-calendar

### Building a release

    bin/build-release 3.0.0

builds `dist/php-calendar-3.0.0.zip` and `.tar.gz` from the committed files (leaving out what
`.gitattributes` marks `export-ignore`), with production dependencies in `vendor/`.

### Local environment

The development stack runs in Docker Compose (`compose.yaml`): PHP 8.3 with Apache, MySQL 8.4,
and Mailpit to catch outgoing mail.

    docker compose up -d --build
    docker compose exec php composer install
    docker compose exec php bin/console doctrine:migrations:migrate

The calendar is served at http://localhost:8080 (set `HTTP_PORT` to change it) and Mailpit at
http://localhost:8025. On first visit the app redirects to `/setup` to create the first calendar
and admin account. Run `bin/console` and `composer` inside the `php` container, since the database
host `database` only resolves there. The container runs as UID/GID 1000 by default; if your user
differs, build with `UID=$(id -u) GID=$(id -g) docker compose build`.

### Tests

    docker compose exec php vendor/bin/phpunit

The tests use the `app_test` database, which they rebuild from the migrations on every run.

### Modifying schema

See instructions on creating migrations here: https://symfony.com/bundles/DoctrineMigrationsBundle/current/index.html

When changing entities/schema run

`docker compose exec php bin/console doctrine:migrations:diff`

To update the schema in the DB, run

`docker compose exec php bin/console doctrine:migrations:migrate`

(this will be added to a migration script eventually)