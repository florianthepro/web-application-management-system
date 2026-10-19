# Web Application Management System

```text
Projekt:       Web Application Management System
Kurzname:      WAMS
Version:       5.0.0
Zielsysteme:   Debian und Ubuntu Server
Web-Stack:     Apache, PHP und SQLite
```

## Installation

```bash
curl -fsSL https://raw.githubusercontent.com/florianthepro/web-application-management-system/main/install.sh \
  | sudo bash -s -- \
  --repository https://github.com/florianthepro/web-application-management-system
```

WAMS ist eine zentrale Plattform für mehrfach installierbare PHP-Webanwendungen.

## Oberfläche

WAMS besitzt zwei sichtbare Bereiche:

```text
Launcher
Administration
```

Die Administration enthält:

```text
Übersicht
Apps
App installieren
Benutzer & Gruppen
System
Launcher
Abmelden
```

Die Oberfläche verwendet Schwarz, Grau, Weiß und normales Blau. Sie verwendet keine Verläufe, Glasflächen oder unnötigen Erklärungstexte.

## Setup und Anmeldung

Das Web-Setup fragt ausschließlich:

```text
Benutzername
Passwort
```

Der erste Benutzer ist der einzige globale Administrator. TOTP wird bei aktivierten Konten erst nach erfolgreicher Prüfung von Benutzername und Passwort angezeigt.

## Ports

```text
Temporäres HTTP-Setup: ab 8099
Dauerhafte HTTPS-App:  ab 8443
```

Der Installer prüft laufende Listener und vorhandene Apache-`Listen`-Direktiven. Setup und fertige Anwendung verwenden getrennte Ports.

## Installation

Der Installer:

1. installiert nur fehlende Pakete;
2. prüft die bestehende Apache-Konfiguration;
3. klont das angegebene Repository;
4. führt `php -l` für alle PHP-Dateien aus;
5. prüft die Shellskripte;
6. startet das temporäre Setup;
7. erzeugt `/opt/wams-<uuid>`;
8. erzeugt ein selbstsigniertes Zertifikat;
9. richtet eine eigene Apache-Site ein;
10. prüft die HTTPS-Loginseite;
11. entfernt temporäre Dateien.

Bei einem Fehler werden die durch diesen Versuch erzeugten Apache-Dateien zurückgerollt.

## Launcher

Der Launcher zeigt nur App-Instanzen, für die der Benutzer das Recht `use` besitzt.

- App-Kacheln enthalten Iconkürzel und Instanzname.
- Der aktive Tenant wird mit Name und Icon angezeigt.
- „Tenant wechseln“ erscheint nur bei mindestens zwei lesbaren Tenants.
- Ohne Apps wird ein einzelner neutraler Leerzustand angezeigt.
- Administration ist nur für den globalen Administrator sichtbar.

## Tenants

Tenants sind optional. Eine App kann ohne Tenant installiert werden.

Tenant-Icons:

```text
PNG oder JPEG
Speicherort: state/icons/
Auslieferung nur über eine autorisierte Route
```

## Benutzer, Gruppen und Rechte

Die Benutzeranlage erzeugt nur das Konto. Rechte werden anschließend getrennt vergeben.

Unterstützt werden:

```text
Tenant-Leserecht an Benutzer
Tenant-Leserecht an Gruppe
App-Recht use an Benutzer oder Gruppe
App-Recht edit an Benutzer oder Gruppe
Gruppenmitgliedschaften
```

`use` erlaubt das Öffnen einer App. `edit` erlaubt app-spezifische Verwaltungsfunktionen. Jede Prüfung erfolgt serverseitig.

## App-Katalog

Apps liegen unter:

```text
applications/<app-id>/
```

Ein installierbares Manifest benötigt mindestens:

```json
{
  "schemaVersion": 1,
  "appId": "example",
  "name": "Example",
  "version": "1.0.0",
  "icon": "EX",
  "catalog": {
    "discoverable": true,
    "installable": true,
    "downloadable": true
  }
}
```

Der App-Katalog zeigt nur Icon und Titel. Nicht installierbare Apps erscheinen nicht.

Eine App erhält beim Aufruf den Kontext `$wams` mit:

```text
authorized
canManage
instanceId
dataDir
configFile
```

Der App-Einstiegspunkt ist:

```text
applications/<app-id>/public/index.php
```

## App-Dateien, AppConfig und AppData

```text
App-Dateien: applications/<app-id>/
AppConfig:   state/instances/<instance-id>/config.json
AppData:     state/instances/<instance-id>/data/
Historie:    state/instances/<instance-id>/history/
```

Diese Bereiche bleiben getrennt. Ein Systemupdate schließt `state`, `runtime`, `backups` und `exports` aus.

## Privates Repository

Unter **System → Privates Repository** können gespeichert werden:

```text
HTTPS-Repository
Branch
Repository-Schlüssel
Nur AppConfig, nur AppData oder beides
```

Der Repository-Schlüssel wird mit AES-256-GCM verschlüsselt. Der installationsspezifische Schlüssel liegt unter:

```text
state/secret.key
```

Git erhält den Schlüssel über `GIT_ASKPASS`. Er wird nicht in der Repository-URL oder Prozessliste ausgegeben.

Synchronisierte Struktur:

```text
wams/
├── manifest.json
├── config/
│   └── <instance-id>/config.json
└── appdata/
    └── <instance-id>/...
```

Die Synchronisierung läuft als separater systemd-Dienst und wird über SQLite-Aufträge gesteuert.

## Updates

Systemupdates werden in `update_jobs` eingereiht und durch einen root-eigenen systemd-Worker ausgeführt.

Erhalten bleiben:

```text
state/
runtime/
backups/
exports/
```

Damit bleiben Benutzer, Gruppen, Rechte, Tenants, AppConfig, AppData, Zertifikate und Sicherungen erhalten.

## Backups

Unter **System** kann ein ZIP-Backup des gesamten `state`-Verzeichnisses erzeugt werden. Die Datei wird unter `backups/` gespeichert.

## Alte Installationen

Der Installer sucht unter `/opt` und `/var/www` nach weiteren WAMS-Datenbanken und speichert erkannte Pfade in:

```text
state/legacy-installations.json
```

## Dateirechte

Anwendungscode:

```text
root:www-data
Ordner 0750
Dateien 0640
```

Schreibbereiche:

```text
www-data:www-data
Ordner 0770
Dateien 0660
```

## Verzeichnisstruktur

```text
web-application-management-system/
├── applications/
├── backups/
├── config/
├── exports/
├── migrations/
├── public/
├── resources/
├── runtime/
├── src/
├── state/
├── tests/
├── bootstrap.php
├── install.sh
├── repository-sync-worker.php
├── uninstall.sh
├── update-worker.sh
└── VERSION
```

## Prüfung

Vor Veröffentlichung müssen erfolgreich sein:

```text
PHP-Lint aller PHP-Dateien
Shell-Syntax aller Shellskripte
JSON-Validierung
Repository-Vertragstest
Apache-Preflight
HTTPS-Selbsttest
ZIP-Integritätstest
```

Der Installer wiederholt PHP- und Shell-Prüfungen auf dem Zielserver, bevor das Web-Setup startet.
