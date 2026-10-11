# php-calendar

by Sean Proctor

## Intent

php-calendar is an open source web-based calendar. It was originally written to facilitate
easy sharing of events for a student group at Northeastern University in 2003.

## Usage

Installation and upgrade instructions:
https://github.com/sproctor/php-calendar/wiki/Installation-Guide

This program is licensed under the Apache License, Version 2.0 located at
http://www.apache.org/licenses/LICENSE-2.0.html

Any questions, contact me at the email address found in the AUTHORS files or
on the various pages on the website.

FAQ: https://github.com/sproctor/php-calendar/wiki/Frequently-Asked-Questions

See the demo live at http://www.php-calendar.org/php-calendar-dev/

Translations can be done at https://www.transifex.com/projects/p/php-calendar/

## Development

Source code can be found at https://github.com/sproctor/php-calendar

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

### Modifying schema

See instructions on creating migrations here: https://symfony.com/bundles/DoctrineMigrationsBundle/current/index.html

When changing entities/schema run

`docker compose exec php bin/console doctrine:migrations:diff`

To update the schema in the DB, run

`docker compose exec php bin/console doctrine:migrations:migrate`

(this will be added to a migration script eventually)