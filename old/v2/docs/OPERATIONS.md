# Operations

The platform records whether packages and Apache files existed before installation. Only entries marked `created-by-wams` are uninstall candidates. Existing sites are preserved because WAMS uses its own virtual host on a configurable port by default. Review `installation-ledger.tsv` before removal.
