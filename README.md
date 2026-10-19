# Web Application Management System

```text
Projekt:        Web Application Management System
Kurzname:       WAMS
Version:        1.0.0
App-Repository: WebApp Foundry
```

WAMS ist eine einfache Zugangsverwaltung und ein Launcher für Web-Apps aus einem strukturierten Git-Repository. Der App-Code wird unverändert kopiert und eingebunden.

## Installation

```bash
curl -fsSL https://raw.githubusercontent.com/florianthepro/web-application-management-system/main/install.sh \
  | sudo bash -s -- \
  --repository https://github.com/florianthepro/web-application-management-system
```

Der Installer gibt anschließend eine HTTPS-URL für das Setup aus.

## Setup

Das Setup fragt nur:

```text
Benutzername
Passwort
App-Repository
```

Standard-App-Repository:

```text
https://github.com/florianthepro/webapp-foundry
```

Der erste Benutzer wird globaler Administrator.

## App-Repository

WAMS erwartet im Repository eine `catalog.json`:

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

Jeder Katalogeintrag enthält nur:

```text
id
name
directory
```

Die App-Struktur ist fest:

```text
apps/<app-id>/
├── index.php
├── admin.php
├── csv-reporting.json       falls von der App verwendet
└── weitere Originaldateien
```

`index.php` ist die normale Oberfläche. `admin.php` ist die Adminoberfläche. Beide werden ohne Umbau des App-Codes eingebunden.

## App Store

Der globale Administrator kann:

1. das App-Repository aktualisieren;
2. Apps aus `catalog.json` anzeigen;
3. eine App installieren.

Beim Installieren wird das in `directory` angegebene App-Verzeichnis vollständig in eine getrennte Instanz kopiert. WAMS prüft, dass `index.php` und `admin.php` vorhanden sind.

## Benutzer und Rechte

Es gibt nur zwei Benutzertypen:

```text
globaler Administrator
normaler Benutzer
```

Der globale Administrator kann weitere Benutzer anlegen.

Pro installierter App erhält ein Benutzer genau einen dieser Zustände:

```text
Kein Zugriff
App
App + Admin
```

Weitere Rollen, Gruppen, Tenants oder Einzelrechte gibt es nicht.

## App-Einbindung

WAMS setzt beim App-Aufruf folgende Variablen:

```php
$webapp_managed = true;
$webapp_admin = false;
$webapp_global_admin = false;
$webapp_user = [];
$webapp_instance = [];
$webapp_config_file = '/path/to/csv-reporting.json';
```

Für die Adminansicht gilt:

```php
$webapp_admin = true;
```

Apps können dieses Flag verwenden, müssen es aber nicht. Die Zugangsprüfung erfolgt bereits durch WAMS.

## Overlay

WAMS legt oben rechts ein dezentes Hover-Overlay über die unveränderte App.

Immer vorhanden:

```text
Zurück
```

Für App-Administratoren zusätzlich:

```text
Admin oder App
Export
Import
```

`Admin` wechselt zu `admin.php`. `App` wechselt zurück zu `index.php`.

## `csv-reporting.json`

Die Datei kann jederzeit exportiert werden. Der Export wird als `csv-reporting.json` heruntergeladen.

App-Administratoren können eine JSON-Datei importieren. Vor dem Schreiben wird geprüft, dass sie gültiges JSON enthält. Die Datei wird anschließend formatiert und atomar in die App-Instanz geschrieben.

Jede installierte App-Instanz besitzt ihre eigene Datei:

```text
state/instances/<instance-id>/app/csv-reporting.json
```

Damit bleiben App-Einstellungen zwischen Benutzern derselben Instanz gemeinsam, aber zwischen verschiedenen Installationen getrennt.

## Dateistruktur

```text
web-application-management-system/
├── config/
├── migrations/
├── public/
│   ├── assets/
│   ├── index.php
│   └── setup.php
├── resources/
├── src/
├── state/
├── install.sh
├── LICENSE
├── README.md
└── VERSION
```

## Sicherheit

- Argon2id-Passwort-Hashes
- CSRF-Prüfung für schreibende Aktionen
- serverseitige Zugangsprüfung
- Prepared Statements für SQLite
- Validierung der App-ID und Repositorypfade
- keine Pfadtraversierung aus `catalog.json`
- JSON-Prüfung beim Import
- HTTPS mit selbstsigniertem Startzertifikat
- App-Instanzen außerhalb des DocumentRoot
