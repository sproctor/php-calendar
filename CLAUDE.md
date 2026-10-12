# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Overview

PHP-Calendar is a plain-PHP web calendar backed by MySQL (via the `mysqli` extension). It requires PHP 8.1 or later (declared in `composer.json` and checked at runtime in `install.php` and `src/setup.php`). It uses no framework and has no build step, test suite, or linter. Users install it by dropping the files on a PHP webserver, running `install.php` (which writes `config.php` with `SQL_*` constants and creates or upgrades the schema), then loading `index.php`. Installation docs live on the GitHub wiki (Installation-Guide, FAQ).

`master` is the development line for the 2.0.x releases and is merged into the `v2.0.x` branch. Releases are tagged `vX.Y.Z`. The `v3.0` and `symfony` branches hold separate 3.0 work.

## Commands

- Syntax-check a file: `php -l src/<file>.php`.
- Formatting: PHP-CS-Fixer with PSR-12 (`.php-cs-fixer.dist.php`). Check with `vendor/bin/php-cs-fixer fix --dry-run --diff` and apply with `vendor/bin/php-cs-fixer fix`. To limit it to specific files, add `--config=.php-cs-fixer.dist.php --path-mode=intersection <files...>`. The whole codebase has been reformatted to PSR-12, so keep files clean by running the fixer on files you change. Add any future bulk-formatting commit to `.git-blame-ignore-revs`.
- Local dev VM: Laravel Homestead through Vagrant (`vagrant up`). See `Homestead.yaml` and `Vagrantfile`; the site is served at http://php-calendar-1.localhost:8000/ (`*.localhost` resolves to 127.0.0.1 and Homestead forwards port 8000 to the VM's port 80, so no `/etc/hosts` entry is needed). `composer install` pulls Homestead as a dev dependency. Composer is used for nothing else, and its PSR-4 autoload for `src/` is not used by the code.
- Release: add a `X.Y.Z:` entry to `NEWS.md`, then run `./release.sh X.Y.Z`. It checks syntax and formatting, sets `$phpc_version`, dates the NEWS entry, regenerates `messages.po`, commits, tags `vX.Y.Z` and builds `dist/php-calendar-X.Y.Z.{zip,tar.gz}` with compiled `.mo` files. `.gitattributes` keeps development files out of the packages. Nothing is pushed until `./release.sh X.Y.Z --publish`, which pushes master and the tag and creates a draft GitHub release.
- Regenerate the translation template: `./generate_messages.sh`. It runs `xgettext` over `*.php src/*.php` with the `__` and `__p` keywords and writes `messages.po`. Translations are synced through Transifex (`.tx/config`) into `locale/<lang>/LC_MESSAGES/messages.po`. The `.mo` files are gitignored; admins compile them in the app (the `translate` action uses `src/msgfmt-functions.php`), or you can run `msgfmt`.

## Architecture

**Request flow.** `index.php` sets path globals (`$phpc_root_path`, `$phpc_includes_path`, `$phpc_script`, …), defines `IN_PHPC`, then requires `src/calendar.php` (core helpers) and `src/setup.php`. `setup.php` does the following:
1. Loads `config.php` and connects `PhpcDatabase` as `$phpcdb`.
2. Builds `$vars` by merging the escaped `$_GET` and `$_POST`.
3. Resolves the current calendar (`$phpc_cal`), the user (`$phpc_user`, or an anonymous `PhpcUser`), the language, the timezone, and the date (`$year`, `$month`, `$day`).
4. Returns JSON directly when `contentType=json`.

**Action dispatch.** `?action=foo` maps to `src/foo.php`, which must define a function `foo()` that returns an `Html` object (or a string). `do_action()` in `src/calendar.php` checks that the name is `\w+` and that the file exists, then requires the file and calls the function. The default action is `display_month`. To add a page, create a new `src/<action>.php` with a matching function. Every action that changes data must call `verify_token()`. It checks the per-session CSRF token `$phpc_token` (a random value, separate from the login cookies) and accepts it only from `$_POST`, so forms embed it as a hidden `phpc_token` field and links can never change data. Delete actions show a confirmation page whose Confirm button is built with `create_confirm_form()`.

**Every file in `src/`** begins with `if (!defined('IN_PHPC')) die("Hacking attempt");`. State is shared through globals (`$vars`, `$phpcdb`, `$phpc_cal`, `$phpc_user`, `$phpc_script`, `$phpc_prefix`, …) declared with `global` inside functions.

**HTML generation.** Output is built as a tree, not with templates. `tag('div', attrs('class="x"'), $child, ...)` in `src/html.php` returns `Html` objects that are serialized with `->toString()`. `src/form.php` provides a small form-builder class hierarchy (`FormPart`, `FormGroup`, questions) used by the `*_form.php` actions. Link and URL helpers (`create_action_url`, `create_action_link`, `create_event_link`, menu helpers) are in `src/calendar.php`.

**Data layer.** `PhpcDatabase` (`src/phpcdatabase.class.php`) holds all SQL as hand-built `mysqli` queries. Every table name is prefixed with `SQL_PREFIX`. It returns model objects: `PhpcCalendar`, `PhpcEvent`, `PhpcOccurrence`, `PhpcUser`. An event can have many occurrences (dated instances). Calendars have categories, groups, and per-user permissions. Schema creation and upgrades are in `install.php` and are versioned by `$phpc_db_version` and the `version` table.

**i18n.** Wrap user-facing strings in `__('...')`, or in `__p('context', '...')` when they need context. These use the bundled pure-PHP `Gettext_PHP` reader, not the gettext extension. `src/globals.php` defines translated arrays (month and day names, …) and must be loaded after translation is set up.

**Security.** Recent commits address XSS. Escape output with `phpc_html_escape()` and use `htmlspecialchars` for request-derived values such as `$phpc_script`. `$vars` is SQL-escaped (`real_escape_r`) but not HTML-escaped. The app is deployed with its whole directory under the web root. Direct access to `src/` and `config.php` is blocked by `.htaccess` files on Apache only (nginx needs equivalent `location` rules from the admin), with the `IN_PHPC` guard as the fallback.

**Frontend.** `static/` holds `phpc.js`, `phpc.css`, and vendored jQuery plugins (timepicker, hoverIntent, spectrum). jQuery and jQuery UI load from a CDN through `get_header_tags()`.
