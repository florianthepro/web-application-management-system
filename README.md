# Web Application Management System

```text
Projektname:    Web Application Management System
Repository:     web-application-management-system
Kurzname:       WAMS
Zielversion:    3.0.0
Zielsysteme:    Debian und Ubuntu Server
Web-Stack:      Apache HTTP Server, PHP und SQLite
Bedienmodell:   Ein Terminalbefehl, danach Weboberfläche
```

## Installation

```bash
curl -fsSL https://raw.githubusercontent.com/florianthepro/web-application-management-system/main/install.sh | sudo bash -s -- --repository https://github.com/florianthepro/web-application-management-system
```

> WAMS ist eine zentrale, mandantenfähige Plattform zum Installieren, Aktualisieren und Betreiben mehrfach installierbarer PHP-Webanwendungen. Anwendungscode, AppConfig und veränderliche AppData bleiben je App-Instanz konsequent getrennt. Benutzer, Gruppen, Tenant-Zugriffe und App-Rechte werden zentral verwaltet.

---

## 1. Zielbild

WAMS soll auf einem neuen oder bereits genutzten Debian- oder Ubuntu-Server mit einem einzigen Terminalbefehl installiert werden. Nach Aufruf des Browser-Setups sind keine weiteren Terminalarbeiten erforderlich.

Die Zielversion bietet:

- einen kompakten App-Launcher;
- eine einheitliche Administration;
- optionale Tenants mit eigenen Icons;
- mehrere Installationen derselben App;
- zentrale Benutzer und Gruppen;
- getrennte Tenant-Lese- und App-Rechte;
- App-Katalog und visuelle App-Installation;
- getrennte App-Dateien, AppConfig und AppData;
- Updates für WAMS und installierte Apps;
- Backups, Exporte und Wiederherstellung;
- Zertifikatsverwaltung einschließlich Let’s Encrypt;
- Erkennung und Verwaltung älterer WAMS-Installationen;
- eine vollständig integrierte CSV-Reporting-App.

WAMS darf vorhandene Websites, Apache-Sites, Ports, Zertifikate oder PHP-Anwendungen nicht ungefragt überschreiben oder deaktivieren.

---

## 2. Grundprinzipien

### 2.1 Nur notwendige Eingaben

Das Setup fragt ausschließlich:

```text
Benutzername des ersten Administrators
Passwort
```

Installationspfad, Ports, Paketdetails, Apache-Version und technische Systeminformationen sind keine Benutzereingaben.

### 2.2 Genau ein globaler Administrator

Der beim Setup angelegte Benutzer ist der einzige globale Administrator.

Weitere Benutzer sind normale Konten. Ihre Rechte werden später getrennt vergeben. Die Benutzeranlage vergibt weder Tenant-Rechte noch Gruppenmitgliedschaften oder App-Rechte.

### 2.3 Zwei sichtbare Produktbereiche

WAMS besteht aus:

```text
Launcher
Administration
```

Setup und Anmeldung sind vorgeschaltete Masken, keine zusätzlichen Verwaltungsbereiche.

### 2.4 Keine unnötigen Texte

Die Oberfläche enthält keine allgemeinen Erklärsätze wie:

```text
Globale Benutzer-, Sicherheits- und Systemeinstellungen.
Tenants und App-Instanzen verwalten.
```

Texte erscheinen nur, wenn sie für eine Entscheidung, Warnung, Fehlermeldung oder Dateneingabe erforderlich sind.

### 2.5 Bestehende Dienste schützen

WAMS darf:

- keinen belegten Port übernehmen;
- keine vorhandene Website deaktivieren;
- keinen bestehenden DocumentRoot ändern;
- keine fremden Zertifikate ersetzen;
- keine vorhandenen Pakete als WAMS-Eigentum markieren;
- keine fremden Installationsverzeichnisse löschen.

---

## 3. Installation

### 3.1 Temporäres Setup

Der Installationsbefehl:

1. prüft das Zielsystem;
2. installiert nur fehlende Komponenten;
3. lädt das angegebene Repository in einen zufälligen Ordner unter `/var/tmp`;
4. prüft alle PHP-, JSON-, SQL- und Shell-Dateien;
5. bereinigt sicher erkennbare fehlgeschlagene WAMS-Apache-Konfigurationen;
6. startet einen temporären PHP-Webserver;
7. zeigt die Setup-URL an;
8. wartet auf die Eingabe des ersten Administrators;
9. installiert WAMS endgültig;
10. startet Apache mit HTTPS;
11. prüft die Loginseite;
12. entfernt den temporären Setup-Ordner;
13. beendet den Terminalprozess.

Der temporäre Setup-Port beginnt bei:

```text
8099
```

Ist der Port belegt, werden `8100`, `8101`, `8102` und weitere Ports geprüft.

### 3.2 Dauerhafter HTTPS-Port

Die fertige WAMS-Installation verwendet einen separaten Port ab:

```text
8443
```

Ist `8443` nicht verfügbar, werden `8444`, `8445`, `8446` und weitere Ports geprüft.

Ein Port gilt nur dann als frei, wenn:

- kein Prozess auf IPv4 oder IPv6 darauf lauscht;
- keine Apache-`Listen`-Direktive den Port verwendet;
- keine vorhandene Apache-Site den Port belegt;
- er nicht dem temporären Setup-Port entspricht.

### 3.3 Installationsverzeichnis

Das Installationsverzeichnis wird automatisch erzeugt:

```text
/opt/wams-<uuid>
```

Beispiel:

```text
/opt/wams-79cda5fc-5df7-4a32-b335-67edf4763e89
```

Existiert der generierte Pfad bereits, wird eine neue UUID erzeugt. Der Benutzer wird nicht nach einem Pfad gefragt.

### 3.4 Selbstsigniertes Startzertifikat

Während der Installation erzeugt WAMS ein selbstsigniertes Zertifikat mit:

- RSA 2048 Bit;
- SHA-256;
- 365 Tagen Gültigkeit;
- Hostname als DNS-SAN;
- Server-IP als IP-SAN;
- restriktiven Rechten für den privaten Schlüssel.

Vor Aktivierung der Apache-Site werden Zertifikat und Schlüssel auf Existenz, Dateigröße und Gültigkeit geprüft.

Nach der Installation kann das Zertifikat in der Administration ersetzt werden durch:

- importiertes Zertifikat und privaten Schlüssel;
- Let’s Encrypt HTTP-01;
- Let’s Encrypt DNS-01 über einen konfigurierten Provideradapter.

### 3.5 Apache-Bereinigung und Rollback

Vor dem Setup prüft WAMS:

```text
/etc/apache2/sites-enabled/wams-*.conf
/etc/apache2/conf-enabled/wams-*-port.conf
```

Sicher erkennbare, verwaiste WAMS-Konfigurationen werden deaktiviert, wenn beispielsweise:

- der DocumentRoot fehlt;
- das Zertifikat fehlt oder leer ist;
- eine Portkonfiguration keine zugehörige Site besitzt;
- mehrere WAMS-`Listen`-Direktiven denselben Port deklarieren.

Vorhandene Dateien werden zur Prüfung kopiert nach:

```text
/var/lib/wams-installer/quarantine/
```

Vor dem Browser-Setup und vor dem Apache-Reload muss gelten:

```bash
apache2ctl configtest
```

Erwartete Ausgabe:

```text
Syntax OK
```

Schlägt eine neue Installation fehl, werden ausschließlich die von diesem Versuch erzeugten Apache-Dateien, Symlinks und systemd-Units zurückgerollt.

### 3.6 Paketinstallation

WAMS installiert nur fehlende Komponenten, unter anderem:

```text
git
apache2
libapache2-mod-php
php-cli
php-sqlite3
php-zip
openssl
rsync
```

Vorhandene Komponenten bleiben unverändert und werden im Installationsjournal als `preexisting` behandelt.

### 3.7 Installationsprüfungen

Vor Anzeige des Setups werden ausgeführt:

```text
PHP-Lint aller PHP-Dateien
Shell-Syntaxprüfung
JSON-Prüfung
SQL-Migrationsprüfung
Repository-Vertragstest
Portprüfung
Apache-Preflight
```

Ein fehlerhafter Repositorystand darf die Setup-Oberfläche nicht erreichen.

---

## 4. Anmeldung

### 4.1 Erster Schritt

Die Anmeldung zeigt nur:

```text
Benutzername
Passwort
```

### 4.2 TOTP als zweiter Schritt

TOTP erscheint nur, wenn es für das erkannte Benutzerkonto aktiviert ist.

Ablauf:

```text
/login
  -> Benutzername und Passwort gültig
  -> TOTP nicht aktiv: Launcher
  -> TOTP aktiv: /totp
  -> Code gültig: Launcher
```

Das TOTP-Feld wird niemals gleichzeitig mit Benutzername und Passwort eingeblendet.

### 4.3 Sitzungen

Sitzungen verwenden mindestens:

- HttpOnly-Cookies;
- SameSite=Lax oder strenger;
- Secure unter HTTPS;
- Session-ID-Regeneration nach erfolgreicher Anmeldung;
- serverseitige Prüfung bei jedem geschützten Aufruf.

---

## 5. Oberfläche und Design

### 5.1 Gestaltungsstil

Die Oberfläche orientiert sich am kompakten Stil von Network Asset Documentation Audit.

```css
--bg: #080c11;
--side: #0c1219;
--panel: #111923;
--line: #293747;
--text: #e7edf4;
--muted: #8c9bad;
--accent: #56a5ff;
--warning: #e8b84b;
--danger: #ff6677;
--success: #55bf7b;
```

Verbindliche Regeln:

- kleine Radien von ungefähr 4 bis 6 Pixeln;
- klare Rahmen und Abstände;
- keine Glas- oder Blur-Effekte;
- keine dekorativen Verläufe;
- keine übergroßen Karten;
- keine doppelten Überschriften;
- keine unnötigen Untertitel;
- Blau für normale Aktionen;
- Gelb nur für Warnungen;
- Rot nur für Fehler und kritische Aktionen;
- Grün nur für erfolgreiche Vorgänge.

### 5.2 Responsive Darstellung

Desktop:

```text
linke Navigation
oberer Seitenkopf
Inhaltsbereich
```

Mobil:

- Navigation einklappbar;
- Formulare einspaltig;
- Tabellen horizontal scrollbar;
- wichtige Aktionen erreichbar, ohne seitlich abgeschnitten zu werden.

---

## 6. Launcher

Der Launcher zeigt ausschließlich Anwendungen, für die der aktuelle Benutzer das Recht `use` besitzt.

### 6.1 Tenant-Auswahl

- kein lesbarer Tenant: keine Tenant-Auswahl;
- genau ein lesbarer Tenant: Tenant wird direkt verwendet, keine Wechselschaltfläche;
- mindestens zwei lesbare Tenants: Schaltfläche **Tenant wechseln** mit Overlayliste.

Der aktive Tenant wird mit Name und Icon angezeigt.

### 6.2 App-Kacheln

Jede App-Instanz erscheint als klare Kachel mit:

- App-Icon;
- Instanzname.

Der Launcher enthält keine App-Konfiguration, Rechteverwaltung oder Systemverwaltung.

### 6.3 Rückkehr aus Apps

Jede integrierte App zeigt links oben einen transparenten Rückkehrpfeil zum Launcher.

---

## 7. Administration

Die Administration verwendet eine einzige, zusammenhängende Navigation:

```text
Übersicht
Apps
Benutzer & Gruppen
Updates
System
Launcher
Abmelden
```

### 7.1 Übersicht

Die Übersicht zeigt kompakt:

- Anzahl installierter App-Instanzen;
- Anzahl Benutzer;
- Anzahl Gruppen;
- Anzahl Tenants, sofern Tenants verwendet werden;
- offene Updateaufträge;
- erkannte alte Installationen;
- wesentliche Fehlerzustände.

### 7.2 Tenant-Funktionen nur bei Bedarf

Tenants sind optional.

Existiert kein Tenant, zeigt die Administration keine dauerhafte Tenant-Navigation. Stattdessen steht bei Bedarf eine Aktion **Tenant anlegen** zur Verfügung.

Sobald mindestens ein Tenant existiert, können Tenant-Funktionen in der passenden Verwaltung angezeigt werden.

---

## 8. Tenants

Ein Tenant enthält mindestens:

```text
ID
Name
Icon
Erstellungszeitpunkt
```

### 8.1 Tenant-Icons

Beim Anlegen und späteren Bearbeiten kann ein Icon hinterlegt werden.

Unterstützt:

```text
PNG
JPEG
WebP
```

Maximalgröße:

```text
1 MiB
```

Die Prüfung erfolgt anhand der Dateisignatur. Icons werden gespeichert unter:

```text
state/icons/
```

Sie werden ausschließlich über eine autorisierte WAMS-Route ausgeliefert.

### 8.2 Apps ohne Tenant

Wenn keine Tenants existieren, darf eine App-Instanz ohne Tenant installiert werden.

Sobald Tenants verwendet werden, kann die Installation einem Tenant zugeordnet werden.

---

## 9. Benutzer & Gruppen

Benutzer, Gruppen, Mitgliedschaften und Rechte liegen in einem gemeinsamen Verwaltungsbereich.

### 9.1 Benutzer

Ein Benutzerkonto enthält:

```text
Benutzername
Passwort-Hash
Status
TOTP-Status
Erstellungszeitpunkt
```

Funktionen:

- Benutzer anlegen;
- Benutzer bearbeiten;
- Passwort ändern;
- Benutzer aktivieren oder deaktivieren;
- Benutzer löschen;
- TOTP aktivieren, zurücksetzen oder deaktivieren.

Beim Anlegen eines Benutzers werden keine Rechte automatisch vergeben.

### 9.2 Gruppen

Funktionen:

- Gruppe anlegen;
- Gruppe umbenennen;
- Gruppe löschen;
- Benutzer hinzufügen;
- Benutzer entfernen;
- Tenant-Leserechte vergeben;
- App-Rechte vergeben.

### 9.3 Genau ein globaler Administrator

Das Setup-Konto ist der einzige globale Administrator. Die Oberfläche bietet keine Möglichkeit, einen weiteren globalen Administrator anzulegen.

---

## 10. Rechtekonzept

### 10.1 Tenant-Leserecht

Das Tenant-Leserecht kann vergeben werden:

- direkt an einen Benutzer;
- über eine Gruppe.

Ohne Tenant-Leserecht sieht der Benutzer den Tenant und dessen Instanzen nicht.

### 10.2 App-Rechte

Jede App-Instanz besitzt mindestens:

```text
use
edit
```

`use` erlaubt:

- Anzeige im Launcher;
- Öffnen und Verwenden der App.

`edit` erlaubt:

- Öffnen des App-Editors;
- Bearbeiten der AppConfig;
- app-spezifische Verwaltungsfunktionen.

Rechte können vergeben werden an:

- Benutzer;
- Gruppen.

Ein Tenant-Leserecht ersetzt kein App-Recht.

### 10.3 Serverseitige Prüfung

Rechte werden nicht nur in der Oberfläche ausgeblendet. Jede Route und jede schreibende Aktion prüft die Berechtigung serverseitig.

---

## 11. App-Katalog

Der App-Katalog zeigt jede installierbare App als quadratische Kachel.

Die Kachel enthält ausschließlich:

```text
Icon
Titel
```

Keine Beschreibung, keine Versionsplakette und keine zusätzlichen Erklärungstexte auf der Kachel.

Ein Klick öffnet die App-Installation.

Die Example Application bleibt vollständig unsichtbar:

```json
{
  "discoverable": false,
  "installable": false,
  "downloadable": false
}
```

---

## 12. App-Installation

Die Installation ist vollständig visuell. Apps erhalten keine manuell eingegebenen Serverpfade.

WAMS erzeugt automatisch:

```text
Instanz-ID
AppConfig-Pfad
AppData-Pfad
Versionsreferenz
Berechtigungsobjekte
```

Nach der Installation besitzt zunächst nur der globale Administrator Zugriff. Weitere Rechte werden anschließend unter **Benutzer & Gruppen** oder in der App-Verwaltung vergeben.

---

## 13. Trennung von App-Dateien, AppConfig und AppData

Für jede App-Instanz gilt:

### App-Dateien

```text
applications/<app-id>/
```

Enthalten versionsgebundenen PHP-, CSS- und JavaScript-Code.

### AppConfig

```text
state/instances/<instance-id>/config.json
```

Enthält die installationsspezifische Konfiguration.

### AppData

```text
state/instances/<instance-id>/data/
```

Enthält veränderliche fachliche Daten der App.

Ein App-Update ersetzt App-Dateien, aber nicht automatisch AppConfig oder AppData.

---

## 14. CSV Reporting

CSV Reporting wird funktional auf Grundlage des ursprünglichen Projekts neu integriert. Relevante Bereiche des Originals sind:

```text
index.php
map.php
rules.php
check.php
Remote-Abruf
```

Die WAMS-Integration übernimmt den Funktionsumfang, passt aber das Speichermodell an WAMS an.

### 14.1 Installation

Das Installationsformular enthält nur:

```text
Tenant             nur wenn mindestens ein Tenant existiert
Instanzname
Seitentitel
Überschrift
CSV-URL
```

Alle weiteren Einstellungen liegen im späteren Editor.

### 14.2 AppConfig

```json
{
  "remoteUrl": "",
  "title": "CSV Reporting",
  "heading": "CSV Reporting",
  "editorTitle": "Editor",
  "delimiter": ",",
  "refreshSeconds": 0,
  "timeoutSeconds": 20,
  "hasHeader": true,
  "showColumns": [],
  "columnRenames": {},
  "columnLinks": {},
  "filters": [],
  "rules": [],
  "map": {
    "enabled": false,
    "latitudeColumn": "",
    "longitudeColumn": "",
    "labelColumn": ""
  }
}
```

### 14.3 Live-Abruf

Die Remote-CSV wird:

- bei jedem Aufruf live abgerufen;
- nur im Arbeitsspeicher verarbeitet;
- nicht in AppData gespeichert;
- nicht als lokale CSV-Datei abgelegt;
- nicht persistent gecacht.

Prüfungen:

- URL ist gültig;
- nur HTTP und HTTPS;
- HTTP-Status ist erfolgreich;
- Antwort ist nicht leer;
- Antwort ist als CSV verarbeitbar;
- Timeout wird eingehalten;
- bei möglicher DNS-Blockierung erscheint ein sachlicher Hinweis.

### 14.4 Ansichten

CSV Reporting besitzt:

```text
Auswertung
Map
Regeln
Editor
```

#### Auswertung

- globale Suche;
- Filter je Spalte;
- Sortierung je Spalte;
- natürliche Sortierung für Zahlen und Texte;
- Paging;
- 25, 50, 100 oder 250 Zeilen pro Seite;
- sichtbare Spalten;
- Spaltenumbenennungen;
- konfigurierbare Links;
- Trefferanzahl;
- Datenmenge;
- optionaler automatischer Refresh.

#### Map

- nur sichtbar, wenn in AppConfig aktiviert;
- Latitude- und Longitude-Spalte konfigurierbar;
- Beschriftungsspalte konfigurierbar;
- berücksichtigt aktive Filter und Regeln.

#### Regeln

Regeln können Datensätze auswählen, hervorheben, klassifizieren oder ausblenden. Sie liegen ausschließlich in der AppConfig der jeweiligen Instanz.

#### Editor

Nur Benutzer oder Gruppen mit `edit` sehen den Editor.

Der Editor verwaltet:

- Trennzeichen;
- Kopfzeile;
- Timeout;
- Refresh;
- sichtbare Spalten;
- Umbenennungen;
- Links;
- Filter;
- Regeln;
- Map-Konfiguration.

### 14.5 Technische Trennung

```text
applications/csv-reporting/public/index.php
applications/csv-reporting/public/map.php
applications/csv-reporting/public/rules.php
applications/csv-reporting/public/assets/app.js
applications/csv-reporting/styles/default/<version>/style.css
```

PHP, CSS und JavaScript bleiben getrennt.

---

## 15. Updates

### 15.1 WAMS-Update

Die Administration zeigt:

- installierte Version;
- verfügbare Version;
- Updatequelle;
- Status des letzten Updates;
- Updatehistorie;
- Aktion **Update installieren**.

Die Weboberfläche reiht den Auftrag ein. Ein root-eigener systemd-Worker führt ihn aus.

### 15.2 App-Updates

App-Datei-Updates verwenden denselben Lebenszyklus:

1. Updatequelle prüfen;
2. neue Dateien separat herunterladen;
3. Manifest und Version prüfen;
4. Integrität prüfen;
5. AppConfig und AppData sichern;
6. Migrationen ausführen;
7. Health Check ausführen;
8. atomar umschalten;
9. bei Fehler zurückrollen.

### 15.3 Unveränderte Daten

Bei Updates bleiben erhalten:

```text
state/
runtime/
backups/
exports/
AppConfig
AppData
Zertifikate
Benutzer
Gruppen
Rechte
Tenants
```

---

## 16. Alte und weitere Installationen

Der Installer sucht in den vorgesehenen Bereichen nach vorhandenen WAMS-Datenbanken, insbesondere unter:

```text
/opt
/var/www
```

Erkannte Installationen werden in der Administration unter **System** dauerhaft angezeigt.

Eine alte Installation darf nur entfernt werden, wenn:

- der globale Administrator angemeldet ist;
- dessen Passwort erneut eingegeben wurde;
- der reale Pfad unter `/opt` oder `/var/www` liegt;
- eine WAMS-Datenbank vorhanden ist;
- die Löschung ausdrücklich bestätigt wurde.

Wird eine erkannte Installation nicht entfernt, bleibt der Hinweis sichtbar.

---

## 17. Backups und Exporte

Unterstützte Sicherungen:

- AppConfig einzelner Instanzen;
- AppData einzelner Instanzen;
- AppConfig und AppData gemeinsam;
- Tenant-Export;
- Benutzer-, Gruppen- und Rechtekonfiguration;
- vollständiges Systembackup;
- Offline-Backup einschließlich App-Dateien.

Standardexporte enthalten keine:

- Klartextpasswörter;
- privaten Schlüssel;
- API-Tokens;
- Sitzungscookies.

---

## 18. Zertifikate

Die Administration unterstützt:

- Anzeige des aktiven Zertifikats;
- verbleibende Gültigkeit;
- Import von Zertifikat und Schlüssel;
- Let’s Encrypt HTTP-01;
- Let’s Encrypt DNS-01;
- automatische Erneuerung;
- Apache-Konfigurationstest vor Reload;
- Rollback bei fehlerhaftem Zertifikat.

---

## 19. Verzeichnisstruktur

```text
<installationspfad>/
├── VERSION
├── README.md
├── install.sh
├── uninstall.sh
├── update-worker.sh
├── applications/
│   ├── csv-reporting/
│   └── example-application/
├── config/
├── migrations/
├── public/
├── resources/
├── src/
├── runtime/
├── state/
│   ├── database.sqlite
│   ├── icons/
│   ├── instances/
│   │   └── <instance-id>/
│   │       ├── config.json
│   │       ├── history/
│   │       └── data/
│   ├── tls/
│   ├── legacy-installations.json
│   └── installation-ledger.tsv
├── backups/
├── exports/
└── tests/
```

Direkter Webzugriff ist nur auf `public/` erlaubt.

---

## 20. Berechtigungen im Dateisystem

### Anwendungscode

```text
Besitzer: root
Gruppe:   www-data
Ordner:   0750
Dateien:  0640
```

### Schreibbereiche

```text
state/
runtime/
backups/
exports/
```

```text
Besitzer: www-data
Gruppe:   www-data
Ordner:   0770
Dateien:  0660
```

SQLite-Datenbank, WAL- und SHM-Dateien müssen für `www-data` schreibbar sein.

---

## 21. Sicherheitsregeln

- vorbereitete SQLite-Anweisungen;
- Argon2id-Passwort-Hashes;
- CSRF-Schutz für schreibende Aktionen;
- serverseitige Rechteprüfung;
- Ausgabe-Escaping;
- keine Ausführung hochgeladener Dateien;
- Dateisignaturprüfung bei Icons;
- keine freien Serverpfade aus Formularen;
- keine Geheimnisse in URLs;
- keine Token im sichtbaren Setup-Link;
- keine direkte Auslieferung aus `state`;
- keine Installation bei fehlerhaftem Apache;
- keine Erfolgsmeldung ohne HTTPS-Selbsttest.

---

## 22. Fehlerverhalten

WAMS meldet eine Installation nur dann als erfolgreich, wenn:

- Repository-Prüfungen bestanden sind;
- Datenbankmigrationen erfolgreich waren;
- Zertifikat und Schlüssel gültig sind;
- `apache2ctl configtest` erfolgreich ist;
- Apache neu geladen wurde;
- die endgültige HTTPS-Loginseite erreichbar ist.

Bei Fehlern:

- keine falsche Erfolgsmeldung;
- konkrete Fehlermeldung;
- Angabe des relevanten Logpfads;
- Rollback der neuen Apache-Konfiguration;
- bestehende Websites bleiben aktiv;
- temporäre Dateien werden entfernt.

---

## 23. Tests und Abnahmekriterien

Vor Veröffentlichung einer Version müssen mindestens erfolgreich sein:

```text
PHP-Lint aller PHP-Dateien
Shell-Syntax aller Shellskripte
JSON-Validierung
SQL-Migrationstest auf leerer Datenbank
SQL-Migrationstest auf bestehender Datenbank
Repository-Vertragstest
Installationsprüfung auf sauberem Debian
Installationsprüfung auf sauberem Ubuntu
Installation neben vorhandener Apache-Site
Portkonflikttest
Test verwaister WAMS-Apache-Konfiguration
Zertifikatstest
SQLite-Schreibtest als www-data
Login ohne TOTP
Login mit TOTP
Tenant-Icon-Upload
Benutzer- und Gruppenrechte
App-use- und App-edit-Rechte
CSV-Remote-Abruf
CSV-Suche, Filter, Sortierung und Paging
CSV-Regeln
CSV-Map
WAMS-Update
App-Update
Rollbacktest
Backup und Wiederherstellung
ZIP-Integritätsprüfung
```

### Abnahme

Eine Version gilt erst als freigabefähig, wenn:

- Setup ohne manuelle Serverkorrektur durchläuft;
- Login erreichbar ist;
- der Benutzer keine Dateirechte setzen muss;
- kein belegter Port übernommen wird;
- Launcher und Administration vollständig bedienbar sind;
- optionale Funktionen nicht als leere Navigation erscheinen;
- CSV Reporting mit einer echten Remote-CSV funktioniert;
- Updates AppConfig und AppData erhalten;
- Altinstallationen erkannt werden;
- sämtliche automatisierten Prüfungen erfolgreich sind.

---

## 24. Repository-Regeln

1. README-Struktur nicht ohne ausdrückliche Anforderung umgestalten.
2. Nur angeforderte Funktionen ändern, bestehende Funktionen nicht stillschweigend entfernen.
3. Keine Anwendung als fertig bezeichnen, wenn nur Strukturprüfungen durchgeführt wurden.
4. Keine Demo- oder Example-App im Katalog anzeigen.
5. Keine Remote-CSV lokal speichern.
6. Keine AppConfig in App-Dateien einbetten.
7. Keine AppData durch Updates ersetzen.
8. Keine globalen Administratoren nach dem Setup erzeugen.
9. Keine Rechte bei der Benutzeranlage automatisch vergeben.
10. Keine unnötigen Navigationseinträge oder Erklärungstexte anzeigen.

---

## 25. Zielstatus

Diese README beschreibt den verbindlichen Zielzustand von WAMS. Sie ist keine Behauptung, dass jeder Punkt bereits im aktuellen Quellstand umgesetzt ist.

Eine Implementierung entspricht dieser Zielversion erst, wenn alle beschriebenen Funktionen vorhanden sind und die Abnahmekriterien aus Abschnitt 23 vollständig bestanden wurden.


## 26. Launcher und privates Repository ab Version 3.1.0

Der Launcher zeigt ausschließlich verwendbare App-Instanzen, blendet die Tenant-Auswahl bei weniger als zwei lesbaren Tenants aus und zeigt den aktiven Tenant einschließlich Icon kompakt im Kopf. Leere Launcher verwenden einen einzelnen neutralen Leerzustand.

Unter **System → Privates Repository** kann ein HTTPS-Git-Repository mit Branch und Repository-Schlüssel hinterlegt werden. Wählbar sind `Nur AppConfig`, `Nur AppData` oder `AppConfig und AppData`. Der Schlüssel wird mit AES-256-GCM und einem installationsspezifischen 32-Byte-Schlüssel verschlüsselt gespeichert. Die Synchronisierung wird als Auftrag eingereiht und durch einen getrennten systemd-Worker ausgeführt. Der Git-Schlüssel steht nicht in der Prozessliste und wird über `GIT_ASKPASS` bereitgestellt.


## 27. Upstream-Integration von CSV Reporting ab Version 4.0.0

Während der Installation wird `https://github.com/florianthepro/csv-reporting` direkt geklont. `index.php`, `map.php`, `rules.php`, `check.php`, `auto-download.php`, README und Lizenz werden unverändert nach `applications/csv-reporting/upstream/` übernommen und der verwendete Commit in `UPSTREAM_COMMIT` festgehalten.

WAMS verändert den Upstream-Code nicht. Ein Adapter stellt Authentifizierung, `use`- und `edit`-Rechte, den Instanzkontext und die Live-CSV bereit. Ein getrenntes Overlay ergänzt ausschließlich den Rückkehrbutton und passt die relativen Links und Formularziele an den WAMS-Router an. Die Remote-CSV wird nur für den aktuellen Request als `csv.csv` bereitgestellt und anschließend entfernt. Regeln und Original-Appdateien bleiben im AppData-Verzeichnis der Instanz bestehen.
