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
