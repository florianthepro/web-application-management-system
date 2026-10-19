# Web Application Management System

WAMS ist ein minimaler geschützter Launcher für Apps aus einem Git-Repository.

## Installation

```bash
curl -fsSL https://raw.githubusercontent.com/florianthepro/web-application-management-system/main/install.sh \
  | sudo bash -s -- \
  --repository https://github.com/florianthepro/web-application-management-system
```

Der Installer ignoriert alte und sonstige Repositoryordner vollständig. Er prüft und installiert ausschließlich die aktuelle, explizit definierte Dateiliste.

## Setup

Das Setup fragt nur:

```text
Benutzername
Passwort
App-Repository
```

Voreinstellung:

```text
https://github.com/florianthepro/webapp-foundry
```

Der erste Benutzer ist globaler Administrator.

## App-Repository

`catalog.json`:

```json
{
  "apps": [
    {
      "id": "csv-reporting",
      "name": "CSV Reporting",
      "directory": "apps/csv-reporting"
    }
  ]
}
```

Eine App liegt flach in ihrem Ordner:

```text
apps/csv-reporting/
├── index.php
├── admin.php
├── csv-reporting.json
└── weitere Originaldateien
```

WAMS kopiert den vollständigen Ordner. `index.php` und `admin.php` müssen vorhanden sein.

## Zugriff

Pro Benutzer und App gibt es nur:

```text
Kein Zugriff
App
App + Admin
```

- `App` erlaubt `index.php`.
- `App + Admin` erlaubt zusätzlich `admin.php`.
- Der globale Administrator darf alles.

Apps werden ohne Wrapper und ohne hinzugefügtes HTML ausgeführt:

```text
/app/<instanz-id>/index.php
/app/<instanz-id>/admin.php
```

## Verwaltung

Der globale Administrator kann:

- das App-Repository aktualisieren;
- Apps aus `catalog.json` installieren;
- Benutzer anlegen;
- pro Benutzer den App-Zugriff setzen;
- `admin.php` öffnen;
- `csv-reporting.json` importieren und exportieren.

Der Import akzeptiert gültiges JSON bis 5 MiB. Die Datei bleibt pro installierter App-Instanz getrennt.
