# Web Application Management System

```text
Projektname:    Web Application Management System
Repository:     web-application-management-system
Kurzname:       WAMS
Dokumenttyp:    Technisches Zielbild und verbindliche Entwicklungsspezifikation
Zielsysteme:    Debian und Ubuntu Server
Web-Stack:      Apache HTTP Server, PHP und SQLite
Bedienmodell:   Ein Terminalbefehl, danach ausschließlich Weboberfläche
Status:         Zielbild / noch keine Behauptung einer fertigen Implementierung
```

> Eine zentrale, mandantenfähige Verwaltungsplattform zum Installieren, Aktualisieren, Sichern und Betreiben mehrfach installierbarer PHP-Webanwendungen. WAMS trennt Anwendungscode, Installationskonfiguration und veränderliche Anwendungsdaten konsequent voneinander und stellt Benutzer, Gruppen, Tenants, Sitzungen und gemeinsam verwendete Icons für integrierte Apps bereit.

---

## 1. Ziel

WAMS soll einen frisch bereitgestellten oder bereits genutzten Debian-/Ubuntu-Server in eine zentral verwaltete Plattform für PHP-, HTML-, CSS- und JavaScript-Anwendungen verwandeln.

Nach genau einem Terminalbefehl wird die Installation im Browser fortgesetzt. Dort werden Systemprüfung, Zielpfad, erster globaler Administrator, Webserver-Anbindung, Domain, TLS-Zertifikate und alle weiteren Einstellungen vorgenommen. Nach Abschluss der Webinstallation beendet sich der wartende Terminalprozess selbst. Alle späteren Vorgänge erfolgen über die Weboberfläche.

Die Plattform muss insbesondere ermöglichen:

- mehrere voneinander getrennte Organisationen als Tenants zu verwalten;
- dieselbe App mehrfach und in mehreren Tenants zu installieren;
- Benutzer einzeln oder über optionale Gruppen zu berechtigen;
- Benutzer nur den ihnen zugewiesenen Tenants auszusetzen;
- Apps aus freigegebenen Git-Repositories zu installieren und zu aktualisieren;
- App-Dateien, App-Konfiguration und App-Daten strikt getrennt zu behandeln;
- App-Versionen und unabhängig davon bereitgestellte Oberflächenvarianten zu wechseln;
- Konfigurationen, Daten oder vollständige Tenant-Pakete zu importieren und zu exportieren;
- Domains, lokale IP-/Port-Zugriffe und TLS-Zertifikate zentral zu verwalten;
- Änderungen am Betriebssystem vollständig zu protokollieren und kontrolliert zurückzunehmen;
- das Gesamtsystem einschließlich der von WAMS erzeugten Systemintegration sauber zu entfernen.

---

## 2. Verbindliche Grundsätze

### 2.1 Ein Terminalbefehl, danach Weboberfläche

Der Installationsaufruf ist der einzige regulär erforderliche Terminalbefehl. Updates, App-Installationen, Benutzerverwaltung, Zertifikate, Backups, Wiederherstellungen und Deinstallation werden anschließend über die Weboberfläche ausgeführt.

### 2.2 Bestehende Dienste nicht beeinträchtigen

WAMS darf auf einem bereits genutzten Server vorhandene Websites, Virtual Hosts, PHP-Versionen, Zertifikate, Datenbanken, Ports oder Dienste nicht ungefragt verändern, ersetzen oder deaktivieren.

Vor jeder Änderung wird der Ausgangszustand ermittelt und im Installationsjournal gespeichert. Für jede geplante Änderung muss die Oberfläche anzeigen:

- ob die benötigte Komponente bereits vorhanden ist;
- welche Version und Konfiguration erkannt wurde;
- ob WAMS sie nur verwendet oder selbst installiert;
- welche Dateien, Pakete, Benutzer, Gruppen, Dienste und Berechtigungen angelegt oder geändert werden;
- ob ein Port-, Paket- oder Konfigurationskonflikt besteht;
- wie die Änderung bei einer Deinstallation zurückgenommen wird.

### 2.3 Besitznachweis für Änderungen

WAMS darf bei der Deinstallation nur Ressourcen entfernen, die im Installationsjournal als von WAMS angelegt gekennzeichnet sind.

- Bereits vorhandene Pakete werden niemals deinstalliert.
- Von WAMS installierte Pakete werden nur entfernt, wenn sie nicht mehr von einem anderen Paket oder erkannten Dienst benötigt werden und der Administrator dies bestätigt.
- Bereits vorhandene Dateien werden vor einer Änderung gesichert und bei der Deinstallation nur dann wiederhergestellt, wenn sie seitdem nicht außerhalb von WAMS verändert wurden.
- Gemeinsam genutzte Apache- oder PHP-Konfigurationen werden nicht pauschal überschrieben.
- WAMS verwendet einen eigenen Apache-Virtual-Host beziehungsweise eine eigene, klar markierte Include-Datei.
- Jede Änderung besitzt Vorher-Hash, Nachher-Hash, Zeitstempel, Aktion, Ursache und Rücknahmeverfahren.

Eine wortwörtliche Wiederherstellung des früheren Zustands darf nur versprochen werden, wenn WAMS den Vorzustand eindeutig erfasst hat und seitdem keine fremde Änderung kollidiert. Andernfalls stoppt die Deinstallation mit einer verständlichen Konfliktanzeige und bietet eine sichere manuelle Entscheidung in der Weboberfläche an.

### 2.4 Strikte Trennung je App-Instanz

Jede installierte App-Instanz besteht logisch aus drei unabhängigen Bereichen:

1. **App-Dateien**: unveränderlicher, versionsgebundener Anwendungscode aus einem freigegebenen Repository oder Versionspaket;
2. **App-Konfiguration**: installationsbezogene Angaben, beispielsweise URL einer entfernten CSV-Datei, API-Endpunkte oder App-spezifische Pfade;
3. **App-Daten**: laufend erzeugte oder hochgeladene Inhalte, Cache-unabhängige Nutzdaten und app-spezifische Datenbanken.

Ein App-Update ersetzt ausschließlich App-Dateien. App-Konfiguration und App-Daten bleiben erhalten und dürfen nur über explizite, versionierte Migrationen verändert werden.

---

## 3. Begriffe

### Management-Plattform

Der zentrale Kontrollbereich für Systeminstallation, Benutzer, Gruppen, Tenants, App-Instanzen, Updates, Backups, Zertifikate und Deinstallation.

### Tenant

Eine getrennte Organisation, typischerweise ein Kunde. Ein Tenant besitzt eigene App-Instanzen, Konfigurationen, Daten und Benutzerzuordnungen. Ein Benutzer sieht und verwendet ausschließlich die ihm zugewiesenen Tenants.

### App

Eine im App-Katalog registrierte Anwendung mit stabiler App-ID, Repository, Manifest und verfügbaren Versionen.

### App-Instanz

Eine konkrete Installation einer App. Dieselbe App kann mehrfach im selben oder in verschiedenen Tenants installiert sein. Jede Instanz besitzt eine unveränderliche Instanz-ID.

### Management-Konfiguration

Die zentrale Konfiguration der Plattform. Sie enthält oder referenziert Benutzer, Gruppen, Tenants, App-Instanzen, Rechte, Icon-Zuordnungen, Repositories, Domains und Systemeinstellungen. Geheimnisse werden nicht im Klartext in derselben exportierbaren Datei gespeichert.

### App-Konfiguration

Die bei Installation oder späterer Konfiguration eingegebenen Werte, die von einer bestimmten App-Instanz verwendet werden. Sie ist weder Teil des App-Codes noch Bestandteil des zentralen App-Manifests.

---

## 4. Zielarchitektur

```text
Browser
  |
  +-- WAMS Management UI
  |     +-- Installation und Systemstatus
  |     +-- Benutzer, Gruppen und Tenants
  |     +-- Apps, Instanzen, Versionen und Styles
  |     +-- Domains und Zertifikate
  |     +-- Importe, Exporte und Backups
  |     +-- Audit und Deinstallation
  |
  +-- App Launcher
  |     +-- nur berechtigte Tenants
  |     +-- nur verwendbare App-Instanzen
  |     +-- gemeinsamer Login und gemeinsame Sitzung
  |
  +-- Integrierte App
        +-- eigener Funktions- und Rechtebereich
        +-- lesender Kontextzugriff auf Benutzer, Gruppen und Tenant
        +-- WAMS-Rückkehrschaltfläche als reservierte UI-Ebene

Apache
  +-- eigener WAMS Virtual Host oder konfliktfreier Pfadbetrieb
  +-- PHP Runtime
  +-- WAMS Front Controller
  +-- kontrollierte App-Routen

Persistenz
  +-- Management-Datenbank
  +-- verschlüsselter Secret Store
  +-- App-Konfigurationen
  +-- App-Daten
  +-- gemeinsam genutzter Icon Store
  +-- Backups und Exporte
  +-- unveränderliches Audit- und Installationsjournal
```

SQLite ist die Standarddatenbank, weil sie auf einem einzelnen Debian-/Ubuntu-Server ohne zusätzlichen Datenbankdienst betrieben werden kann. Der Datenzugriff muss jedoch hinter einer internen Repository-Schicht liegen, damit später ein weiterer Datenbanktreiber ergänzt werden kann.

---

## 5. Vorgesehene Serverstruktur

Standardinstallationspfad:

```text
/opt/web-application-management-system
```

Verbindliche Struktur:

```text
/opt/web-application-management-system/
├── current -> releases/<wams-version>/
├── releases/
│   └── <wams-version>/
│       ├── public/
│       ├── src/
│       ├── templates/
│       ├── migrations/
│       ├── resources/
│       └── VERSION
├── runtime/
│   ├── bootstrap/
│   ├── cache/
│   ├── locks/
│   ├── sessions/
│   └── tmp/
├── state/
│   ├── database/
│   │   └── management.sqlite
│   ├── management/
│   │   ├── config.json
│   │   ├── repositories.json
│   │   └── schema-version
│   ├── secrets/
│   │   └── secrets.enc
│   ├── icons/
│   │   ├── apps/
│   │   ├── groups/
│   │   ├── tenants/
│   │   └── users/
│   ├── apps/
│   │   └── <app-id>/
│   │       ├── code/
│   │       │   └── <app-version>/
│   │       ├── styles/
│   │       │   └── <style-id>/<style-version>/
│   │       └── packages/
│   ├── instances/
│   │   └── <instance-id>/
│   │       ├── identity.json
│   │       ├── config/
│   │       │   ├── current.json
│   │       │   └── history/
│   │       ├── data/
│   │       ├── logs/
│   │       └── migrations/
│   ├── audit/
│   └── installation-ledger/
│       ├── baseline.json
│       ├── actions.jsonl
│       ├── file-backups/
│       └── package-state.json
├── backups/
├── exports/
└── installer/
```

### Regeln für Pfade

- App-Code ist versionsgebunden und wird nach erfolgreicher Prüfung schreibgeschützt bereitgestellt.
- Ein Codebestand kann von mehreren App-Instanzen derselben Version verwendet werden.
- Veränderliche Daten liegen niemals im Codeverzeichnis.
- Jede App-Instanz besitzt ein eigenes Daten- und Konfigurationsverzeichnis.
- Direkter Webzugriff auf `state`, `backups`, `exports`, `runtime`, Journale und Geheimnisse ist durch Apache-Konfiguration und zusätzliche Laufzeitprüfungen gesperrt.
- Temporäre Exporte erhalten eine Ablaufzeit und werden danach automatisiert gelöscht.
- Dateien werden atomar über temporäre Datei, `fsync`, Umbenennung und vorherige Sicherung geschrieben.

---

## 6. Empfohlene Repository-Struktur

```text
web-application-management-system/
├── README.md
├── LICENSE
├── SECURITY.md
├── CONTRIBUTING.md
├── CHANGELOG.md
├── VERSION
├── install.sh
├── uninstall.sh
├── composer.json
├── app-catalog/
│   ├── schema/
│   │   ├── app-manifest.schema.json
│   │   ├── app-config.schema.json
│   │   └── export-manifest.schema.json
│   └── examples/
├── config/
│   ├── defaults.php
│   ├── permissions.php
│   └── routes.php
├── installer/
│   ├── bootstrap.php
│   ├── system-probe.php
│   ├── transaction-ledger.php
│   ├── package-manager.php
│   ├── apache-manager.php
│   └── completion-waiter.php
├── public/
│   ├── index.php
│   ├── assets/
│   └── .htaccess
├── src/
│   ├── AppCatalog/
│   ├── AppRuntime/
│   ├── Audit/
│   ├── Auth/
│   ├── Backup/
│   ├── Certificate/
│   ├── Config/
│   ├── Export/
│   ├── Identity/
│   ├── Installer/
│   ├── Repository/
│   ├── System/
│   ├── Tenant/
│   ├── Update/
│   └── Web/
├── templates/
├── migrations/
├── resources/
│   ├── apache/
│   ├── systemd/
│   └── styles/
├── tests/
│   ├── unit/
│   ├── integration/
│   ├── migration/
│   ├── security/
│   └── uninstall/
└── docs/
    ├── ARCHITECTURE.md
    ├── APP-INTEGRATION.md
    ├── APP-MANIFEST.md
    ├── BACKUP-RESTORE.md
    ├── INSTALLATION-TRANSACTIONS.md
    ├── PERMISSIONS.md
    ├── SECURITY-MODEL.md
    └── UI-INTEGRATION.md
```

Das Hauptrepository enthält die Management-Plattform und die Spezifikationen. Apps bleiben eigenständige Repositories. Dadurch kann ein Betreiber den App-Katalog austauschen, Apps entfernen oder eigene Apps hinzufügen, ohne den Plattformkern umzubauen.

---

## 7. App-Repository und Versionen

Jedes freigegebene App-Repository muss eine stabile App-ID besitzen und diese Mindeststruktur unterstützen:

```text
example-app/
├── app.json
├── README.md
├── versions/
│   ├── 1.0.0.json
│   └── 1.1.0.json
├── bundles/
│   ├── example-app-1.0.0.zip
│   └── example-app-1.1.0.zip
├── styles/
│   ├── default/
│   │   ├── 1.0.0/
│   │   │   ├── style.css
│   │   │   └── style.json
│   │   └── 1.1.0/
│   └── compact/
│       └── 1.0.0/
└── src/
```

`app.json` beschreibt die App als Produkt. Dateien unter `versions` beschreiben veröffentlichte Versionen und deren Bundle, Prüfsumme, Kompatibilität und Migrationen. Styles sind separat versioniert, damit ein Administrator nur die Oberfläche wechseln oder auf eine ältere Style-Version zurückgehen kann, ohne den PHP-Code oder die App-Daten zurückzusetzen.

### Beispiel für `app.json`

```json
{
  "schemaVersion": 1,
  "appId": "com.example.data-evaluator",
  "name": "Data Evaluator",
  "description": "Wertet tenantbezogene Datenquellen aus.",
  "repository": "https://github.com/example/data-evaluator",
  "defaultChannel": "stable",
  "entryPoint": "public/index.php",
  "icon": "assets/icon.svg",
  "tenantMode": "required",
  "multiInstance": true,
  "runtime": {
    "php": ">=8.2",
    "extensions": ["json", "pdo_sqlite", "openssl"]
  },
  "integration": {
    "sharedSession": true,
    "identityContext": ["user", "groups", "tenant"],
    "managementOverlay": true
  },
  "configurationSchema": "schemas/installation-config.schema.json",
  "dataPath": "data",
  "stylesIndex": "styles/index.json",
  "versionsIndex": "versions/index.json"
}
```

### Beispiel für eine Versionsdefinition

```json
{
  "appId": "com.example.data-evaluator",
  "version": "1.1.0",
  "releasedAt": "2026-10-18T00:00:00Z",
  "bundle": "bundles/example-app-1.1.0.zip",
  "sha256": "<sha256-of-bundle>",
  "minimumWamsVersion": "1.0.0",
  "configurationSchemaVersion": 2,
  "dataSchemaVersion": 3,
  "migrations": [
    "migrations/config/1-to-2.php",
    "migrations/data/2-to-3.php"
  ],
  "rollback": {
    "supported": true,
    "minimumDataSchemaVersion": 2
  }
}
```

### Unveränderliche Identitäten

- `appId` identifiziert das Produkt und darf nach Veröffentlichung nicht geändert werden.
- `instanceId` identifiziert eine konkrete Installation und wird als UUID erzeugt.
- `tenantId`, `userId` und `groupId` sind ebenfalls stabile UUIDs.
- Anzeigenamen dürfen geändert werden, IDs nicht.
- Pfade werden aus IDs statt aus frei eingegebenen Namen gebildet.

---

## 8. App-Konfiguration

App-Konfiguration enthält ausschließlich Werte, die während der Installation oder späteren Verwaltung einer App-Instanz festgelegt wurden. Beispiel: die entfernte URL einer CSV-Datei, ein Abrufintervall oder die Auswahl eines Datenprofils.

Aktive Konfiguration:

```text
state/instances/<instance-id>/config/current.json
```

Historische und exportierte Konfigurationen verwenden folgenden Dateinamen:

```text
<app-id>__<app-version>__<download-zeit-utc>.appconfig.json
```

Beispiel:

```text
com.example.data-evaluator__1.1.0__2026-10-18T172000Z.appconfig.json
```

Jede Konfigurationsdatei enthält mindestens:

```json
{
  "format": "wams-app-config",
  "formatVersion": 1,
  "appId": "com.example.data-evaluator",
  "appVersion": "1.1.0",
  "instanceId": "00000000-0000-4000-8000-000000000000",
  "tenantId": "00000000-0000-4000-8000-000000000000",
  "exportedAt": "2026-10-18T17:20:00Z",
  "configurationSchemaVersion": 2,
  "values": {
    "remoteCsvUrl": "https://example.invalid/data.csv",
    "refreshMinutes": 30
  }
}
```

Geheimnisse wie Passwörter, private Schlüssel, API-Token oder Client-Secrets werden nicht in `values` gespeichert. Die Konfiguration enthält dafür nur Secret-Referenzen. Der Secret Store ist verschlüsselt, besitzt eine restriktive Dateiberechtigung und wird nur bei ausdrücklich gewähltem, passwortgeschütztem Secret-Export ausgegeben.

---

## 9. Management-Konfiguration

Die Management-Konfiguration wird in normalisierte Datenbanktabellen und wenige, versionierte Systemdateien aufgeteilt. Eine einzelne frei editierbare Datei ist nicht die primäre Datenquelle, da konkurrierende Änderungen, referenzielle Integrität und sichere Migrationen sonst unnötig fehleranfällig wären.

Sie umfasst:

- globale Systemeinstellungen;
- bekannte App-Repositories und Vertrauensstatus;
- Benutzerkonten und Authentifizierungsmerkmale;
- Gruppen und Mitgliedschaften;
- Tenants und Tenant-Zuweisungen;
- App-Instanzen, Versionen, Styles und Routen;
- Rollen und granulare Management-Berechtigungen;
- Icon-Referenzen;
- Domains und Zertifikatsreferenzen;
- Backup-, Export- und Aufbewahrungsregeln;
- Integrations- und Identitätsmetadaten.

Nicht enthalten sind App-Daten und Klartext-Geheimnisse.

---

## 10. Benutzer, Gruppen, Tenants und Rechte

### 10.1 Erster Administrator

Der erste im Browser angelegte Benutzer erhält immer die Rolle `Global Administrator` mit allen Management-Rechten. Diese Zuweisung kann erst entfernt werden, wenn mindestens ein weiterer aktiver globaler Administrator existiert.

### 10.2 Benutzer und Gruppen

Rechte können direkt an Benutzer oder an manuell angelegte Gruppen vergeben werden. Gruppen sind optional. Effektive Rechte ergeben sich aus der Vereinigung aller direkten und gruppenbasierten Erlaubnisse innerhalb des jeweiligen Geltungsbereichs. Explizite Verweigerungsregeln sind in Version 1 nicht vorgesehen, damit Rechte nachvollziehbar bleiben.

### 10.3 Geltungsbereiche

Jede Berechtigung besitzt einen Scope:

- `global`: gilt für die gesamte Plattform;
- `tenant:<tenant-id>`: gilt nur für einen Tenant;
- `instance:<instance-id>`: gilt nur für eine konkrete App-Instanz.

Ein Benutzer ohne globale Tenant-Rechte kann niemals eine Ansicht oder Aktion für „alle Tenants“ verwenden. Das gilt auch für Export, Backup, Suche und Löschung.

### 10.4 Vorgesehene Management-Berechtigungen

```text
system.view
system.configure
system.audit.view
system.uninstall

identity.users.view
identity.users.manage
identity.groups.view
identity.groups.manage
identity.assignments.manage

tenants.view
tenants.create
tenants.configure
tenants.delete

apps.catalog.view
apps.repositories.manage
apps.instances.use
apps.instances.install
apps.instances.configure
apps.instances.update
apps.instances.style.change
apps.instances.remove

transfers.appconfig.import
transfers.appconfig.export
transfers.appdata.import
transfers.appdata.export
transfers.management.import
transfers.management.export
transfers.backup.create
transfers.backup.restore

certificates.view
certificates.manage
```

Diese Punkte sind absichtlich funktionsbezogen: nicht so grob, dass ein App-Nutzer automatisch Installationen löschen kann, und nicht so kleinteilig, dass Berechtigungen unverständlich werden.

### 10.5 App-interne Rechte

WAMS bestimmt nicht, was ein Benutzer innerhalb einer App fachlich tun darf. Eine integrierte App darf über eine versionierte, lesende Schnittstelle nur folgende Identitätsinformationen des aktuellen Kontexts abrufen:

- Benutzer-ID und freigegebene Profildaten;
- aktuelle Tenant-ID;
- IDs und Namen der Gruppen innerhalb dieses Tenants;
- App-Instanz-ID;
- Sitzungs- und CSRF-Kontext.

Die App verwaltet ihre fachlichen Rechte selbst in ihrem eigenen Datenbereich. WAMS-Gruppen können von der App als Eingabe verwendet werden, erzeugen aber nicht automatisch App-Rechte.

---

## 11. Anmeldung und Single Sign-on innerhalb der Plattform

Version 1 unterstützt:

- Benutzername oder E-Mail-Adresse mit Passwort;
- TOTP als optionale oder administrativ erzwingbare Zwei-Faktor-Authentifizierung;
- QR-Code zur Einrichtung eines TOTP-Authenticators;
- Wiederherstellungscodes, die nur einmal angezeigt und gehasht gespeichert werden;
- gemeinsame serverseitige Sitzung für Management, Launcher und integrierte Apps;
- CSRF-Schutz, Session-Rotation nach Anmeldung und Rechteänderung sowie konfigurierbare Leerlaufzeit.

Kostenfrei nutzbare, standardbasierte Integrationen sollen ergänzend vorgesehen werden:

- LDAP für vorhandene LDAP-Verzeichnisse;
- Active Directory über LDAP/LDAPS;
- OpenID Connect für kompatible Identity Provider.

WAMS setzt keine kostenpflichtige Identitätsplattform voraus. Die Nutzung eines konkreten externen Providers und dessen mögliche Lizenzkosten liegen außerhalb von WAMS.

Eine App darf niemals das Passwort, TOTP-Secret oder externe Provider-Token eines Benutzers erhalten. Sie bekommt nur den serverseitig bestätigten Identitätskontext.

---

## 12. Installation

### 12.1 Voraussetzungen

- Debian oder Ubuntu Server;
- `root` oder ein Benutzer mit `sudo`;
- ausgehender HTTPS-Zugriff auf das gewählte Git-Repository und Paketquellen;
- ein freier Installationspfad;
- bei öffentlichem TLS eine korrekt auflösende Domain und erreichbare Challenge-Schnittstelle.

### 12.2 Direkter Installationsbefehl

Der produktive Installer wird aus dem offiziellen Repository bezogen. Bis `install.sh` implementiert und veröffentlicht ist, ist der folgende Befehl ausschließlich die verbindliche Zielschnittstelle und noch keine Zusage einer funktionsfähigen Installation.

```bash
curl -fsSL https://raw.githubusercontent.com/florianthepro/web-application-management-system/main/install.sh | sudo bash -s -- --repository https://github.com/florianthepro/web-application-management-system
```

### 12.3 Generischer Installationsbefehl für Forks oder Klone

Dieser Aufruf fragt das zu verwendende Repository im Terminal ab und übergibt keine Zugangsdaten:

```bash
curl -fsSL https://raw.githubusercontent.com/florianthepro/web-application-management-system/main/install.sh | sudo bash
```

Der Terminalteil darf nur:

1. Voraussetzungen prüfen;
2. eine minimale, signierte oder per SHA-256 geprüfte Installer-Version laden;
3. einen zufälligen, kurzlebigen Bootstrap-Token erzeugen;
4. einen temporären lokalen Installationsdienst starten;
5. eine URL und einen klickbaren Link ausgeben;
6. Installationsmeldungen live anzeigen;
7. auf den erfolgreichen oder abgebrochenen Abschluss warten;
8. Token und Bootstrap-Dienst entfernen;
9. sich selbst beenden.

Administratorname und Passwort werden ausschließlich im Browser festgelegt. Sie dürfen weder als Befehlsargument noch in der Shell-History erscheinen.

### 12.4 Webbasierter Installationsablauf

1. Sprache und Systemprüfung
2. Auswahl oder Bestätigung des Git-Repositories
3. Branch, Tag oder Release-Kanal
4. Zielpfad, standardmäßig `/opt/web-application-management-system`
5. Prüfung, dass der Zielpfad frei beziehungsweise sicher wiederaufnehmbar ist
6. Erkennung vorhandener Apache-, PHP- und Modulinstallationen
7. Konfliktanalyse für Ports, Virtual Hosts, Domains und PHP-Versionen
8. Vorschau aller Paket-, Datei-, Benutzer-, Gruppen- und Dienständerungen
9. Wahl zwischen eigener Domain, bestehender Domain mit Pfad, Subdomain oder IP/Port
10. erster globaler Administrator und Passwort
11. optionale TOTP-Einrichtung
12. TLS-Modus
13. verbindliche Zusammenfassung
14. transaktionale Installation
15. Integritäts- und Erreichbarkeitstest
16. Abschluss, Sperrung des Installers und automatische Beendigung des Terminalprozesses

### 12.5 Apache und PHP

Standard ist Apache mit PHP auf Debian/Ubuntu. WAMS bevorzugt eine konfliktarme Einbindung über einen eigenen Virtual Host und, sofern verfügbar, eine eigene PHP-FPM-Pool-Konfiguration. Ist bereits eine kompatible Installation vorhanden, wird sie verwendet und im Journal als `preexisting` markiert. Fehlende Software darf nach angezeigter Änderungsvorschau über den Systempaketmanager installiert werden.

Die Plattform darf eine bestehende Standardwebsite nicht ersetzen. Ist Port 80 oder 443 bereits durch Apache belegt, ergänzt WAMS einen eigenen namensbasierten Virtual Host oder bietet einen abweichenden Port an. Läuft dort ein anderer Webserver, muss WAMS Reverse-Proxy-, Pfad- oder Alternativport-Optionen anzeigen, statt den Dienst zu stoppen.

### 12.6 Berechtigungsreparatur

Apps können deklarieren, dass ihr Webroot bei Installation oder Reparatur definierte Besitz- und Zugriffsrechte benötigt. Die sinngemäße Operation lautet:

```bash
sudo bash -c 'WEBROOT=/var/www/html; USER=www-data; chown -R "$USER:$USER" "$WEBROOT" && chmod -R u+rwX,g+rwX,o-rwx "$WEBROOT"'
```

WAMS darf diesen Befehl nicht blind auf `/var/www/html` oder einen fremden Webroot anwenden. `WEBROOT` muss auf das eindeutig zu WAMS beziehungsweise zur App-Instanz gehörende Verzeichnis aufgelöst werden. Vorherige Eigentümer, Modi und ACLs werden protokolliert. Symlinks aus dem erlaubten App-Bereich heraus führen zum Abbruch. Die Oberfläche zeigt Umfang und Ziel vor Ausführung an.

---

## 13. App-Installation

Ein berechtigter Benutzer installiert eine App vollständig über die Weboberfläche:

1. Tenant auswählen;
2. App aus einem vom globalen Administrator freigegebenen Repository wählen;
3. eindeutigen Instanznamen festlegen;
4. Version und optional Style-Version auswählen;
5. Manifest, Signatur beziehungsweise SHA-256 und Laufzeitanforderungen prüfen;
6. App-spezifische Installationsmaske aus dem Konfigurationsschema rendern;
7. alternativ eine passende App-Konfiguration importieren;
8. Änderungsvorschau bestätigen;
9. Code in ein neues versionsgebundenes Verzeichnis entpacken;
10. Konfiguration und Datenverzeichnis getrennt erzeugen;
11. Migrationen in einer kontrollierten Transaktion ausführen;
12. Health Check durchführen;
13. Route aktivieren und App im Launcher anzeigen.

Der Benutzer gibt keinen Serverpfad an. Pfade werden aus App-ID, Instanz-ID und Tenant-ID sicher abgeleitet.

Apps dürfen Installationsfelder definieren, beispielsweise eine entfernte CSV-URL oder benötigte Begleitdateien. Diese Maske erscheint als eingebetteter Bestandteil der einheitlichen WAMS-Oberfläche. Frei ausführbare Shell-Befehle aus App-Repositories sind standardmäßig verboten. Benötigte Operationen müssen über begrenzte, dokumentierte Installer-Aktionen erfolgen.

---

## 14. Updates, Versionen und Styles

WAMS prüft die registrierten App-Repositories periodisch und manuell auf neue freigegebene Versionen.

Vor einem Update werden angezeigt:

- installierte und verfügbare Version;
- Quelle, Commit oder Release und SHA-256;
- Kompatibilität mit der WAMS- und PHP-Version;
- Änderungen an Konfigurations- und Datenschema;
- erforderliche Migrationen;
- Backup- und Rollback-Fähigkeit;
- betroffene Tenants und Instanzen.

Updateablauf:

1. neues Bundle in Quarantäne laden;
2. Prüfsumme und Manifest validieren;
3. Archivpfade gegen Path Traversal prüfen;
4. Snapshot von Konfiguration und Daten erzeugen;
5. neuen Code parallel bereitstellen;
6. Migrationen durchführen;
7. Health Check ausführen;
8. Instanz atomar auf neue Codeversion umschalten;
9. bei Fehlern auf vorherigen Code und unterstützten Datenstand zurückrollen.

Eine Auswahl einer anderen `style.css`-Version verändert nur die referenzierte Style-Version. App-Code, Konfiguration und Daten bleiben unverändert. Styles dürfen keine PHP- oder JavaScript-Dateien enthalten, sofern der Manifesttyp ausdrücklich `css-only` ist.

---

## 15. Einheitliche Benutzeroberfläche und App-Integration

Management und Apps sollen visuell zusammengehören, ohne dass WAMS die fachliche Oberfläche einer App vollständig vorgibt.

Verbindliche Integrationspunkte:

- WAMS reserviert eine klar definierte Ebene für eine transparente oder dezente Rückkehrschaltfläche zur App-Übersicht.
- Die Schaltfläche muss per Tastatur erreichbar und auf Mobilgeräten bedienbar sein.
- Eine App darf den reservierten Bereich nicht überdecken oder abfangen.
- Titel und Favicon stammen vorrangig aus der aktiven App. Fehlen sie, verwendet WAMS App-Metadaten und anschließend den Plattformstandard.
- App-, Tenant-, Gruppen- und Benutzericons liegen im gemeinsamen Icon Store und werden über stabile IDs referenziert.
- Tenant-Wechsel ist nur über kontrollierte WAMS-Navigation möglich.
- Eine App darf keinen frei gewählten Tenant aus Query-Parametern übernehmen.

### Routen- und Kontextparameter

Es dürfen dokumentierte Query- und Fragmentparameter verwendet werden, beispielsweise für Instanz und Rückkehrziel. Sicherheitsrelevanter Kontext wird jedoch nicht aus `?`- oder `&`-Parametern vertraut.

Der aktuelle Tenant und die Instanz werden serverseitig aus Route, Sitzung und Berechtigungen bestimmt. Jeder Aufruf validiert erneut, dass Benutzer, Tenant und App-Instanz zusammengehören. Manipulierte IDs führen zu `403 Forbidden`, nicht zu einem automatischen Tenant-Wechsel.

---

## 16. Icons

WAMS führt einen gemeinsamen Icon Store für:

- Apps;
- Benutzer;
- Gruppen;
- Tenants;
- Plattformbranding.

Ein Icon wird einmal gespeichert, per SHA-256 dedupliziert und über eine interne Icon-ID referenziert. Uploads werden nach tatsächlichem Dateityp geprüft, auf zulässige Formate und Größe begrenzt und außerhalb direkt ausführbarer Webpfade gespeichert.

Priorität für Browser-Titel und Favicon:

1. aktive App-Instanz;
2. App-Manifest;
3. Tenant-Branding;
4. WAMS-Standard.

---

## 17. Import, Export und Backup

### 17.1 Exportarten

Berechtigte Benutzer können für alle sichtbaren oder gezielt ausgewählte Tenants exportieren:

- nur App-Konfigurationen;
- nur App-Daten;
- App-Konfigurationen und App-Daten als Gesamt-ZIP;
- Management-Konfiguration;
- vollständiges Systembackup;
- eingeschränktes Tenant-Transferpaket.

### 17.2 Tenant-Transferpaket

Ein Transferpaket für einen oder mehrere Tenants kann enthalten:

```text
tenant-transfer.zip
├── manifest.json
├── checksums.sha256
├── management/
│   └── scoped-management-config.json
├── tenants/
│   └── <tenant-id>/
│       ├── tenant.json
│       ├── identities.json
│       └── instances/
│           └── <instance-id>/
│               ├── identity.json
│               ├── appconfig.json
│               └── appdata.zip
└── optional-admin/
    └── bootstrap.json
```

Die eingeschränkte Management-Konfiguration enthält nur die exportierten Tenants, ihre benötigten Benutzer, Gruppen, Zuweisungen, App-Instanzen und Referenzen. Optional kann beim Export ein neuer globaler Administrator für das Zielsystem vorbereitet werden. Es wird kein Passwort exportiert. Stattdessen enthält das Paket einen einmaligen, kurzlebigen Aktivierungsvorgang; das Passwort wird nach dem Import im Browser gesetzt.

### 17.3 Berechtigungsgrenzen

- Ein Benutzer kann nur Tenants exportieren, die er sehen darf und für die er das jeweilige Exportrecht besitzt.
- Nur ein global berechtigter Benutzer darf eine vollständige Management-Konfiguration oder alle Tenants exportieren.
- Importrechte sind von Exportrechten getrennt.
- Ein Import darf keine globalen Rechte erzeugen, sofern der Importierende nicht selbst die entsprechende globale Berechtigung besitzt.
- IDs werden vor Import auf Kollisionen geprüft; die Oberfläche bietet Zuordnung, Ersetzen oder Erzeugung neuer IDs an.

### 17.4 Geheimnisse

Standardexporte enthalten keine Klartext-Geheimnisse. Optionen:

- Geheimnisse auslassen und bei Wiederherstellung neu eingeben;
- verschlüsselten Secret-Export mit separatem Exportpasswort erzeugen;
- Referenzen auf externe Secret-Systeme beibehalten.

### 17.5 Backups

Backups sind konsistente, versionierte ZIP-Archive mit Manifest, Prüfsummen und Schema-Versionen. Vor dem Packen wird die SQLite-Datenbank über die SQLite-Backup-Schnittstelle beziehungsweise einen konsistenten Snapshot gesichert. Ein Restore wird zuerst vollständig validiert und anschließend transaktional eingespielt.

App-Code wird bei einem normalen Backup als App-ID, Version, Repository und Prüfsumme referenziert. Ein auswählbares Offline-Backup nimmt zusätzlich die benötigten App- und Style-Bundles auf.

---

## 18. TLS, Domains und lokale Nutzung

WAMS unterstützt:

- Domains mit pfadbasierten App-Routen;
- Subdomains je App oder Instanz;
- lokale Nutzung über IP-Adresse und konfigurierbaren Port;
- Import bestehender Zertifikate und privater Schlüssel;
- Let’s Encrypt über HTTP-01;
- Let’s Encrypt über DNS-01 mit austauschbaren DNS-Provider-Modulen;
- automatische Erneuerung und kontrollierten Apache-Reload.

Private Schlüssel werden getrennt geschützt gespeichert, niemals in normalen Exporten ausgegeben und nur dem erforderlichen Dienst zugänglich gemacht.

Die automatische Erneuerung muss:

1. rechtzeitig vor Ablauf geplant werden;
2. die Challenge durchführen;
3. das neue Zertifikat validieren;
4. es atomar aktivieren;
5. die Apache-Konfiguration vor Reload testen;
6. bei Fehlern das bisherige Zertifikat aktiv lassen;
7. Fehler sichtbar protokollieren und Administratoren informieren.

DNS-01-Provider werden als begrenzte Adapter implementiert. API-Zugangsdaten liegen im Secret Store. Ein Provider-Modul darf nur die für die Challenge erforderlichen DNS-Einträge verwalten.

---

## 19. Löschen

### 19.1 App-Instanz löschen

Vor dem Löschen zeigt WAMS Tenant, App-ID, Instanz-ID, betroffene Datenmenge, Backups und abhängige Routen. Optional wird unmittelbar ein Export erzeugt. Code wird nur entfernt, wenn keine andere Instanz dieselbe Version verwendet.

### 19.2 Tenant löschen

Nur ein Benutzer mit `tenants.delete` für den konkreten Tenant darf ihn löschen. Ein tenantbeschränkter Benutzer erhält niemals eine Funktion zum Löschen aller Tenants oder des Gesamtsystems.

Löschen umfasst nach separater Bestätigung:

- App-Instanzen des Tenants;
- App-Konfigurationen;
- App-Daten;
- Tenant-spezifische Icons;
- Zuweisungen von Benutzern und Gruppen;
- Tenant-Metadaten.

Globale Benutzerkonten werden nicht gelöscht, solange sie weiteren Tenants zugewiesen sind.

### 19.3 Gesamtsystem deinstallieren

Nur ein globaler Administrator mit `system.uninstall` kann die Deinstallation starten. Vorher sind erneute Authentifizierung, TOTP falls aktiv, Eingabe des Systemnamens und eine Änderungsvorschau erforderlich.

Ablauf:

1. optionales vollständiges Backup;
2. Wartungsmodus;
3. Stoppen WAMS-eigener Jobs und Dienste;
4. Entfernen ausschließlich WAMS-eigener Apache-Konfigurationen;
5. Wiederherstellen eindeutig gesicherter, unverändert gebliebener Dateien;
6. Entfernen WAMS-eigener Systembenutzer, Gruppen und Dienste, soweit konfliktfrei;
7. Behandlung von durch WAMS installierten Paketen nach Besitz- und Abhängigkeitsprüfung;
8. Entfernen des Plattformordners;
9. abschließender Bericht über entfernte, beibehaltene und konfliktbehaftete Ressourcen.

Der Abschlussbericht muss außerhalb des zu löschenden Ordners zum Download angeboten werden, bevor die letzte Entfernung erfolgt.

---

## 20. Installationsjournal

Das Installationsjournal ist Voraussetzung für sichere Updates und Deinstallation.

Beispiel eines Eintrags:

```json
{
  "id": "01J...",
  "timestamp": "2026-10-18T17:20:00Z",
  "actor": "installer",
  "action": "file.create",
  "target": "/etc/apache2/sites-available/wams.conf",
  "ownership": "created-by-wams",
  "before": null,
  "afterSha256": "<sha256>",
  "rollback": {
    "action": "file.delete-if-hash-matches"
  }
}
```

Unterstützte Aktionsklassen:

```text
package.observe
package.install
file.create
file.modify
file.permission.change
user.create
group.create
service.create
service.enable
apache.module.enable
apache.site.enable
certificate.create
port.reserve
```

Journalzeilen werden append-only gespeichert und zusätzlich in der Management-Datenbank indiziert. Sensible Werte werden redigiert. Kritische Aktionen enthalten Hash-Verkettung, damit nachträgliche Manipulation erkennbar ist.

---

## 21. Sicherheit

### Verbindliche Maßnahmen

- serverseitige Autorisierung bei jeder Aktion;
- standardmäßig keine Schreibrechte auf App-Code;
- Prepared Statements für sämtliche Datenbankzugriffe;
- Ausgabe-Escaping und Content Security Policy;
- CSRF-Token für zustandsändernde Anfragen;
- sichere Session-Cookies mit `HttpOnly`, `Secure` und geeignetem `SameSite`;
- Passwort-Hashing mit Argon2id, sofern verfügbar;
- TOTP-Secrets verschlüsselt speichern;
- Rate Limits und zeitweise Sperren für Anmeldeversuche;
- Uploadprüfung nach Inhalt, Größe, Endung und Zielpfad;
- Schutz gegen Zip Slip, Symlink Escape und Path Traversal;
- keine Ausführung hochgeladener Dateien;
- Prüfsummen für App-, Style-, Backup- und Exportpakete;
- Auditprotokoll für Anmeldung, Rechteänderungen, Installation, Update, Import, Export, Restore und Löschung;
- keine frei ausführbaren Shell-Hooks aus Apps;
- minimale Dateirechte und getrennte Schreibbereiche;
- Health Checks vor Apache-Reload oder Versionsumschaltung.

### Vertrauensmodell für App-Repositories

Nur globale Administratoren dürfen vertrauenswürdige App-Repositories registrieren. Der Vertrauenseintrag bindet Repository-URL, erlaubte Branches oder Release-Kanäle und optional Signaturschlüssel. Repository-Weiterleitungen, geänderte Ursprünge und Prüfsummenabweichungen erfordern eine erneute Freigabe.

---

## 22. Fehler- und Transaktionsmodell

Jede Installation, Aktualisierung, Wiederherstellung und Deinstallation ist eine nachvollziehbare Transaktion mit den Zuständen:

```text
planned -> validated -> running -> committed
                            |
                            +-> rollback-running -> rolled-back
                            |
                            +-> intervention-required
```

Ein Abbruch darf keine halb aktivierte App-Route hinterlassen. Fehler werden mit einer stabilen Vorgangs-ID protokolliert. Die Oberfläche zeigt eine klare Ursache, betroffene Ressourcen und sichere nächste Schritte, ohne Geheimnisse auszugeben.

---

## 23. Entwicklungsreihenfolge

### Phase 1: sicherer Plattformkern

- Bootstrap-Installer und Webinstallation
- Systemprobe und Installationsjournal
- Apache-/PHP-Integration
- Management-Datenbank und Migrationen
- erster globaler Administrator
- lokaler Login, TOTP und Sitzungen
- Tenants, Benutzer, Gruppen und Berechtigungen
- Auditlog

### Phase 2: App-Lebenszyklus

- App-Manifest und JSON-Schemas
- vertrauenswürdige Repositories
- App-Installation und Mehrfachinstanzen
- Trennung von Code, Konfiguration und Daten
- App Launcher und gemeinsamer Identitätskontext
- Updates, Rollback und Style-Versionen

### Phase 3: Transport und Betrieb

- AppConfig-/AppData-Export
- Tenant-Transferpakete
- vollständige und Offline-Backups
- Restore und Konfliktauflösung
- Zertifikatsimport
- Let’s Encrypt HTTP-01 und DNS-01
- automatische Erneuerung

### Phase 4: Verzeichnisdienste und Härtung

- LDAP und Active Directory über LDAP/LDAPS
- OpenID Connect
- Benachrichtigungen
- Wiederherstellungstests
- Paket- und Deinstallationstests auf unterstützten Debian-/Ubuntu-Versionen

---

## 24. Definition of Done

Eine Funktion gilt nicht allein durch eine sichtbare Schaltfläche als fertig. Sie benötigt mindestens:

- serverseitige Berechtigungsprüfung;
- Validierung sämtlicher Eingaben;
- Audit-Eintrag;
- Fehler- und Rollback-Verhalten;
- automatisierte Tests;
- dokumentiertes Datenformat;
- Migrationsstrategie;
- Backup-/Restore-Auswirkung;
- Deinstallationsauswirkung;
- verständliche Oberfläche ohne erforderlichen Terminalzugriff.

Die Plattform als Ganzes gilt erst dann als produktionsreif, wenn mindestens folgende Szenarien automatisiert getestet sind:

1. Installation auf einem frischen Debian-System;
2. Installation auf einem frischen Ubuntu-System;
3. Installation neben einem bestehenden Apache-Virtual-Host;
4. Installation bei bereits vorhandenem PHP;
5. Abbruch in jeder Installationsphase mit erfolgreichem Rollback;
6. zwei Installationen derselben App im selben Tenant;
7. dieselbe App in mehreren Tenants;
8. Benutzer mit genau einem von mehreren Tenants;
9. Gruppenrechte und direkte Rechte;
10. TOTP-Anmeldung und Wiederherstellungscode;
11. App-Update mit unveränderten Daten und Konfigurationen;
12. separater Style-Wechsel;
13. Tenant-Export und Restore auf einem zweiten System;
14. vollständiges Offline-Backup und Restore;
15. Zertifikatserneuerung mit fehlgeschlagener Challenge;
16. Deinstallation ohne Veränderung vorher vorhandener Websites;
17. Konflikterkennung bei außerhalb von WAMS geänderter Systemdatei.

---

## 25. Nichtziele

WAMS ist nicht:

- die fachliche Rechteverwaltung innerhalb jeder App;
- ein Ersatz für das Betriebssystem-Paketmanagement;
- ein unbeschränktes Shell-Ausführungssystem für App-Repositories;
- ein Mechanismus, der fremde Apache-Konfigurationen überschreibt;
- eine Garantie, beliebige unsichere PHP-Projekte ohne Anpassung sicher zu isolieren;
- eine Plattform, die Benutzer zwischen nicht zugewiesenen Tenants wechseln lässt.

---

## 26. Entwicklerregeln

1. Keine App darf direkt auf die Management-Datenbank zugreifen. Identitätskontext wird über eine versionierte interne Schnittstelle bereitgestellt.
2. Keine Systemänderung ohne Journal- und Rollback-Eintrag.
3. Keine veränderlichen Dateien im App-Codeverzeichnis.
4. Keine globalen Aktionen aus einem Tenant-Scope.
5. Keine Passwörter, Tokens oder privaten Schlüssel in Logs, Befehlszeilen, URLs oder normalen Exporten.
6. Keine App-Aktivierung vor Integritätsprüfung und Health Check.
7. Keine Löschung gemeinsam verwendeter Code- oder Iconobjekte.
8. Keine automatische Übernahme von Tenant-IDs aus untrusted Query-Parametern.
9. Keine Behauptung einer vollständigen Rücknahme, wenn ein externer Konflikt erkannt wurde.
10. Jede Schemaänderung benötigt Vorwärtsmigration, dokumentierte Rückwärtsgrenze und Test.

---

## 27. Projektstatus

Dieses Dokument beschreibt das verbindliche Zielbild und die erforderlichen Repository- und Serverstrukturen. Es ist keine Behauptung, dass Installer, Management-Oberfläche oder App-Runtime bereits implementiert sind.

Die erste Implementierung muss mit dem sicheren Bootstrap-Installer, dem Installationsjournal und dem Identitäts-/Tenant-Kern beginnen. App-Installation oder Deinstallation darf erst freigegeben werden, wenn Trennung, Berechtigungsprüfung, Audit, Backup und Rollback technisch durchgesetzt werden.
