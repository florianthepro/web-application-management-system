# CSV Reporting

WAMS-native integration of CSV Reporting. Remote CSV content is fetched live for each page request and parsed only in memory. No loader, downloaded CSV, cache file or remote response is persisted.

Installation variables are defined in `app-config.schema.json`. WAMS validates reachability before installation. `csv-reporting.view` controls use and `csv-reporting.manage` controls the app-specific configuration page. Global administrators always receive both rights.
