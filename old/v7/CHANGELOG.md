# Changelog

## 1.2.0 - 2026-10-18

- PHP parse error in the former front controller removed by rebuilding and linting all PHP sources.
- Added preflight detection for Apache, PHP, SQLite support and existing DocumentRoots.
- Added temporary, self-tested browser bootstrap instead of serving an unfinished installation through Apache.
- Added browser-selected, necessarily nonexistent installation directory with server-side and root-side validation.
- APT runs only when required software or PHP capabilities are missing.
- Added final Apache configuration and HTTP health checks before reporting success.

## 1.2.1 - 2026-10-18

- Restored the complete README structure and moved the installation command directly below the repository header.
- Temporary installer always uses an isolated PHP live server on a custom bootstrap port.
- Installation path selection is only displayed when the default path is unavailable.
- Final WAMS installation uses a dedicated free custom port by default.
- Ports 80 and 443 are optional only when free; 443 receives a replaceable self-signed bootstrap certificate.

## 1.2.2 - 2026-10-18

- Fixed SQLite `attempt to write a readonly database` during tenant creation.
- Writable runtime directories now belong to `www-data:www-data` with directory mode `0770` and file mode `0660`.
- Application code remains owned by `root:www-data` and is not writable by Apache.
- Added an explicit startup error when the state directory is not writable.

## 1.3.0 - 2026-10-18

- Rebuilt the administration as a consistent dark GUI.
- Added Tanum-style tile launcher with top-left tenant overlay.
- Replaced raw JSON app installation with visual CSV Reporting fields.
- Restored CSV Reporting as a complete live report with search, column filters, sorting and paging.
- New installations use automatically generated UUID root directories.
- Removed setup and login query flags from installation URLs.
- Added persistent management notice for detected legacy installations.

## 1.3.1 - 2026-10-18

- Reduced first-run setup to username and password only.
- Reuses the temporary setup port for the final encrypted Apache service.
- Creates a self-signed certificate during every installation.
- Redirects the browser from setup to the clean `/login` route after restart.
- Added clean Apache front-controller routes without setup, login or token query flags.
- Unified setup, login, launcher and management visual language.
