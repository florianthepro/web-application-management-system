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

## 1.4.0 - 2026-10-18

- Clearly separated Launcher, Management and Admin interfaces.
- Replaced decorative AI-like styling with a flat black, gray, white and standard-blue admin design.
- Added conventional yellow warnings, red errors and critical actions, and green success states.
- TOTP is now shown only as a second authentication step when enabled for the account.
- Added a visual app catalog with app cards, names, versions, icon areas and explicit install actions.
- App-specific installation forms open only after selecting an app from the catalog.

## 1.5.0 - 2026-10-18

- Consolidated Management and Admin into one complete administration area.
- Matched setup and login styling to the compact NADA visual system.
- Restored user creation, tenant assignment and group creation interfaces.
- Restored persistent legacy-installation detection and protected removal.
- Expanded CSV Reporting AppConfig schema for columns, renames, links, filters and rules.
- Kept AppConfig, application files and application data physically separate.

## 2.0.0 - 2026-10-18

- Enforced exactly one global administrator: the initial setup account.
- Separated user creation, tenant read access, group membership and app permissions.
- Added per-instance `use` and `edit` grants for users and groups.
- Rebuilt CSV Reporting AppConfig and live renderer with columns, renames and links.
- Added management-system update queue and privileged systemd update worker.
- Hardened certificate generation and verification before Apache activation.
- Added additive database migration for access grants and update jobs.


## 2.1.0 - 2026-10-18

- Added target-side PHP linting before setup is exposed.
- Added executable repository contract validation.
- Added group-based tenant read assignment interface.
- Fixed update worker row processing.
- Added PHP ZIP dependency detection for exports and archives.


## 2.1.1 - 2026-10-18

- Separated temporary setup port and permanent HTTPS application port.
- Setup starts at 8099; the final login starts at a free port beginning with 8443.
- Port selection now checks both active sockets and Apache Listen declarations.
- Prevented duplicate Apache listeners and final-login reuse of the setup port.


## 2.1.2 - 2026-10-18

- Automatically disables stale or orphaned WAMS Apache site and port configurations.
- Detects and quarantines duplicate WAMS Listen declarations before setup.
- Requires a clean Apache preflight before starting installation.
- Rolls back newly created Apache files when installation fails.


## 2.2.0 - 2026-10-18

- Added tenant icon upload during creation and for existing tenants.
- Hid tenant switching when fewer than two readable tenants exist.
- Removed nonessential administrative subtitles.
- Grouped navigation into Platform, Access and System.
- Rebuilt CSV Reporting renderer, controls, styling and client-side interaction.
