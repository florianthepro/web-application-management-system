# Changelog

## 1.2.0 - 2026-10-18

- PHP parse error in the former front controller removed by rebuilding and linting all PHP sources.
- Added preflight detection for Apache, PHP, SQLite support and existing DocumentRoots.
- Added temporary, self-tested browser bootstrap instead of serving an unfinished installation through Apache.
- Added browser-selected, necessarily nonexistent installation directory with server-side and root-side validation.
- APT runs only when required software or PHP capabilities are missing.
- Added final Apache configuration and HTTP health checks before reporting success.
