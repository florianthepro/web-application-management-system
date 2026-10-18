# Web Application Management System

```text
Projektname:    Web Application Management System
Repository:     web-application-management-system
Kurzname:       WAMS
Version:        2.0.0
Zielsysteme:    Debian und Ubuntu Server
Web-Stack:      Apache HTTP Server, PHP und SQLite
Bedienmodell:   Ein Terminalbefehl, danach ausschließlich Weboberfläche
```

## Installation

```bash
curl -fsSL https://raw.githubusercontent.com/florianthepro/web-application-management-system/main/install.sh | sudo bash -s -- --repository https://github.com/florianthepro/web-application-management-system
```

Für einen Fork oder ein anderes WAMS-Repository kann das Repository interaktiv eingegeben werden:

```bash
curl -fsSL https://raw.githubusercontent.com/florianthepro/web-application-management-system/main/install.sh | sudo bash
```

> Eine zentrale, mandantenfähige Verwaltungsplattform zum Installieren, Aktualisieren, Sichern und Betreiben mehrfach installierbarer PHP-Webanwendungen. WAMS trennt Anwendungscode, Installationskonfiguration und veränderliche Anwendungsdaten konsequent voneinander und stellt Benutzer, Gruppen, Tenants, Sitzungen und gemeinsam verwendete Icons für integrierte Apps bereit.

---

## 1. Ziel

WAMS verwandelt einen neuen oder bereits genutzten Debian-/Ubuntu-Server in eine zentral verwaltete Plattform für PHP-, HTML-, CSS- und JavaScript-Anwendungen. Nach einem einzigen Terminalbefehl wird die Installation im Browser fortgesetzt. Alle späteren Aktionen erfolgen über die Weboberfläche.

Die Plattform ermöglicht:

- getrennte Kundenorganisationen als Tenants;
- mehrere Installationen derselben App im selben oder in verschiedenen Tenants;
- direkte Benutzerrechte und optionale Gruppenrechte;
- Trennung von App-Dateien, AppConfig und AppData;
- gemeinsame Benutzer-, Gruppen-, Tenant-, Sitzungs- und Icon-Integration;
- App-Updates und getrennte Style-Versionen;
- Importe, Exporte, Tenant-Transfers und Backups;
- App-eigene fachliche Rechte bei gemeinsamem WAMS-Login;
- TLS-Zertifikate, Let’s Encrypt und automatische Erneuerung;
- protokollierte Systemänderungen und kontrollierte Deinstallation.

---

## 2. Installations- und Portmodell

### 2.1 Temporärer Browser-Installer

Der Terminalbefehl lädt das gewählte Repository zunächst in einen zufälligen temporären Ordner unter `/var/tmp`. Der Installationsassistent läuft über den in PHP integrierten temporären Webserver auf einem eigenen Bootstrap-Port. Dadurch ist der Assistent unabhängig von vorhandenen Apache-Sites, DocumentRoots, PHP-VirtualHosts und bestehenden Anwendungen.

Vor Ausgabe des Browserlinks wird der temporäre Server lokal per HTTP geprüft. Erst wenn dieser Selbsttest erfolgreich ist, zeigt das Terminal die URL. Der privilegierte Terminalprozess bleibt bis zum Abschluss geöffnet, führt die vom Browser angeforderte Installation aus und beendet sich danach automatisch.

### 2.2 Installationspfad

Der normale Installationspfad lautet:

```text
/opt/web-application-management-system
```

Ist dieser Pfad frei, wird er verwendet und im Browser nur informativ angezeigt. Eine Pfadauswahl erscheint ausschließlich, wenn der Standardpfad bereits existiert oder anderweitig nicht verwendet werden kann.

Ein alternativ gewählter Zielpfad muss:

- absolut sein;
- einen vorhandenen, beschreibbaren Elternordner besitzen;
- als Zielordner noch nicht existieren;
- darf kein Symlink sein;
- darf kein Systemstamm oder bestehender DocumentRoot sein.

Die Prüfung erfolgt im Browser und unmittelbar vor dem Anlegen erneut im privilegierten Terminalprozess.

### 2.3 Dauerhafter WAMS-Port

WAMS verwendet standardmäßig einen eigenen freien Port, beginnend bei:

```text
8088
```

Ist `8088` belegt, wird `8089`, danach `8090` und so weiter geprüft. WAMS erhält einen eigenen Apache-VirtualHost und bleibt damit unabhängig von bestehenden Websites.

Im Installationsassistenten werden zusätzlich angeboten:

- **Port 80**, aber nur wenn er tatsächlich frei ist;
- **Port 443**, aber nur wenn er tatsächlich frei ist.

Für Port 443 wird zunächst ein lokales, selbstsigniertes Startzertifikat erzeugt. Nach der Anmeldung kann es über die Zertifikatsverwaltung durch ein importiertes Zertifikat oder Let’s Encrypt ersetzt werden. Ein belegter Port 80 oder 443 wird nicht angeboten und niemals übernommen, umgeleitet oder von einem vorhandenen Dienst gelöst.

### 2.4 Vorhandene Software und fehlende Komponenten

Vor einer Paketaktion prüft WAMS:

- Apache-Verfügbarkeit und Version;
- PHP-Verfügbarkeit und Version;
- Git-Verfügbarkeit;
- PDO-SQLite-Unterstützung;
- OpenSSL-Unterstützung;
- Portbelegung.

Sind alle benötigten Komponenten vorhanden, wird APT nicht aufgerufen. Fehlt Software oder eine erforderliche PHP-Funktion, wird nur die fehlende Komponente installiert. Vorhandene Komponenten werden als `preexisting` behandelt; von WAMS ergänzte Komponenten werden im Installationsjournal als `created-by-wams` vermerkt.

---

## 3. Verbindliche Grundsätze

### Ein Terminalbefehl

Der Installationsbefehl ist der einzige regulär erforderliche Terminalzugriff. Apps, Benutzer, Rechte, Zertifikate, Backups und Systemverwaltung werden danach über die Weboberfläche verwaltet.

### Bestehende Dienste nicht beeinträchtigen

WAMS darf vorhandene Websites, VirtualHosts, PHP-Versionen, Zertifikate oder Dienste nicht ungefragt ersetzen oder deaktivieren. WAMS verwendet eine eigene Apache-Site, einen eigenen Port und einen eigenen DocumentRoot.

### Besitznachweis

Nur eindeutig von WAMS erstellte Ressourcen gelten bei der Deinstallation als entfernbar. Vorhandene Pakete werden nicht als WAMS-Eigentum erfasst.

### Trennung je App-Instanz

Jede App-Instanz besteht aus:

1. **App-Dateien**: versionsgebundener Code;
2. **AppConfig**: Installations- und Betriebsangaben;
3. **AppData**: laufend erzeugte oder hochgeladene Daten.

Ein App-Update ersetzt App-Dateien, nicht automatisch AppConfig oder AppData.

---

## 4. Begriffe

### Tenant

Eine getrennte Kundenorganisation mit eigenen App-Instanzen, Daten, Konfigurationen und Benutzerzuweisungen.

### App

Ein Produkt mit stabiler App-ID, Manifest, Versionen und optionalen Style-Varianten.

### App-Instanz

Eine konkrete Installation einer App in einem Tenant. Dieselbe App darf mehrfach installiert werden.

### AppConfig

Werte, die bei der Installation oder späteren Verwaltung einer Instanz festgelegt werden, beispielsweise die URL einer entfernten CSV-Quelle.

### AppData

Veränderliche fachliche Daten der jeweiligen App-Instanz.

---

## 5. Zielarchitektur

```text
Browser
  +-- WAMS Management UI
  +-- App Launcher
  +-- integrierte Apps
        +-- gemeinsamer Login
        +-- aktueller Benutzer
        +-- aktueller Tenant
        +-- Gruppeninformationen
        +-- transparenter Rückkehrpfeil links oben

Apache
  +-- eigener WAMS VirtualHost
  +-- eigener freier Port
  +-- WAMS Front Controller
  +-- kontrollierte App-Routen

Persistenz
  +-- SQLite Management-Datenbank
  +-- AppConfig je Instanz
  +-- AppData je Instanz
  +-- gemeinsam genutzte Icons
  +-- Audit- und Installationsjournal
  +-- Exporte und Backups
```

---

## 6. Repository-Struktur

```text
web-application-management-system/
├── README.md
├── LICENSE
├── CHANGELOG.md
├── VERSION
├── install.sh
├── uninstall.sh
├── bootstrap.php
├── composer.json
├── applications/
│   ├── example-application/
│   └── csv-reporting/
├── config/
├── migrations/
├── public/
├── resources/
│   └── apache/
├── src/
├── runtime/
├── state/
├── backups/
├── exports/
└── tests/
```

`runtime`, `state`, `backups` und `exports` enthalten im Repository nur Platzhalter. Tatsächliche System- und Kundendaten werden nicht eingecheckt.

---

## 7. Serverstruktur

Die Installation wird vollständig unter dem gewählten WAMS-Zielpfad abgelegt. Apache erhält ausschließlich eine kleine, eindeutig WAMS zugeordnete Site- und Port-Konfiguration außerhalb dieses Ordners.

Die Installation setzt die Codebereiche auf `root:www-data` mit schreibgeschützten Dateien. Nur `state`, `runtime`, `backups` und `exports` gehören `www-data:www-data` und sind für den Webserver schreibbar. Das umfasst auch `database.sqlite` sowie die SQLite-Dateien `database.sqlite-wal` und `database.sqlite-shm`. Dadurch können Tenant-, Benutzer-, Gruppen- und App-Daten geschrieben werden, ohne den gesamten Installationsordner für Apache beschreibbar zu machen.

```text
<installationspfad>/
├── applications/
├── public/
├── src/
├── migrations/
├── runtime/
├── state/
│   ├── database.sqlite
│   ├── instances/
│   ├── tls/
│   └── installation-ledger.tsv
├── backups/
└── exports/
```

Direkter Webzugriff auf `state`, `runtime`, `backups` und `exports` ist nicht erlaubt. Apache veröffentlicht ausschließlich `public`.

---

## 8. App-Manifest

Eine installierbare App besitzt eine stabile `appId` und ein `app.json`. Das Manifest legt unter anderem Version, Katalogsichtbarkeit, Mehrfachinstallation, Tenant-Modell, Rechte und Remote-Datenverhalten fest.

```json
{
  "schemaVersion": 1,
  "appId": "csv-reporting",
  "name": "CSV Reporting",
  "version": "1.0.0",
  "catalog": {
    "discoverable": true,
    "installable": true,
    "downloadable": true
  },
  "permissions": [
    "csv-reporting.view",
    "csv-reporting.manage"
  ]
}
```

---

## 9. Example Application

`applications/example-application` ist ausschließlich eine leere Entwicklerreferenz. Sie wird nicht im Launcher und nicht im installierbaren App-Katalog angezeigt.

```json
{
  "discoverable": false,
  "installable": false,
  "downloadable": false
}
```

Die `.gitkeep`-Dateien benennen die Dateien, die eine echte App an dieser Stelle bereitstellen soll, beispielsweise `index.php`, `style.css`, `app.js`, `current.json` und `appdata.sqlite`.

---

## 10. CSV Reporting

Die integrierte App `csv-reporting` verarbeitet CSV-Daten live von einer entfernten Quelle.

- Die Remote-Quelle wird bei Installation und Konfigurationsänderung geprüft.
- Bei Nichterreichbarkeit erscheint ein Hinweis auf mögliche DNS-Blockierung und auf den Versuch mit einer zulässigen IP-Adresse.
- Die CSV-Antwort wird nicht lokal gespeichert.
- Es gibt keinen Remote-Loader und keine persistente Download-Datei.
- Ein Seitenrefresh ruft die Daten erneut live ab.
- CSS und JavaScript sind vom PHP-Code getrennt.
- Der transparente Rückkehrpfeil liegt links oben über der App.

Die App besitzt getrennte Rechte:

```text
csv-reporting.view
csv-reporting.manage
```

Globale Administratoren besitzen beide Rechte automatisch. Andere Benutzer erhalten Zugriff über Tenant-, Gruppen- oder Einzelzuweisungen.

---

## 11. Benutzer, Gruppen und Rechte

Der erste Benutzer ist globaler Administrator. Rechte können direkt oder über optionale Gruppen vergeben werden. Tenantbeschränkte Benutzer sehen ausschließlich ihre zugewiesenen Tenants.

App-interne fachliche Rechte bleiben Verantwortung der App. WAMS stellt nur den bestätigten Benutzer-, Gruppen-, Tenant- und Sitzungskontext bereit.

---

## 12. App Launcher und Integration

Der Launcher zeigt nur Instanzen, für die der aktuelle Benutzer ein Verwendungsrecht besitzt. Direkte URLs umgehen die Prüfung nicht.

In einer App stellt WAMS bereit:

- bestätigte Benutzeridentität;
- aktuelle Tenant-ID;
- Gruppeninformationen;
- Instanz-ID;
- gemeinsame Sitzung;
- geschützte Asset-Auslieferung;
- Rückkehrpfeil zur App-Übersicht.

Tenant-IDs aus frei manipulierbaren Query-Parametern werden nicht als Sicherheitskontext akzeptiert.

---

## 13. App-Installation und Updates

Ein berechtigter Benutzer wählt Tenant, App, Instanzname, AppConfig und Zugriffsmodell. Serverpfade werden nicht frei durch Apps vorgegeben.

Updates müssen:

1. neue App-Dateien getrennt bereitstellen;
2. Version und Integrität prüfen;
3. AppConfig und AppData sichern;
4. erforderliche Migrationen ausführen;
5. einen Health Check durchführen;
6. erst danach atomar umschalten.

Style-Versionen können unabhängig von der App-Code-Version gewechselt werden.

---

## 14. Import, Export und Backup

Vorgesehene Exportarten:

- AppConfig ausgewählter oder aller berechtigten Tenants;
- AppData als ZIP;
- AppConfig und AppData als Gesamtpaket;
- eingeschränkte Management-Konfiguration für ausgewählte Tenants;
- vollständiges Systembackup;
- Offline-Backup einschließlich App-Bundles.

Standardexporte enthalten keine Klartextpasswörter, privaten Schlüssel oder API-Tokens.

---

## 15. TLS und Zertifikate

WAMS unterstützt dauerhaft:

- importierte Zertifikate;
- Let’s Encrypt HTTP-01;
- Let’s Encrypt DNS-01 über Provideradapter;
- automatische Erneuerung;
- kontrollierten Apache-Reload nach erfolgreichem Konfigurationstest.

Wird bei der Erstinstallation der freie Port 443 gewählt, verwendet WAMS zunächst ein 30 Tage gültiges selbstsigniertes Startzertifikat. Dieses ist nur eine technisch sichere Startkonfiguration und sollte anschließend durch ein vertrauenswürdiges Zertifikat ersetzt werden.

---

## 16. Installationsjournal und Deinstallation

Das Installationsjournal unterscheidet:

```text
preexisting
created-by-wams
```

Erfasst werden insbesondere:

- ergänzte Systempakete;
- gewählter Installationspfad;
- WAMS-Apache-Site;
- WAMS-Portkonfiguration;
- TLS-Startmaterial, wenn Port 443 gewählt wurde.

Die Deinstallation entfernt nur eindeutig WAMS-eigene Dateien und Konfigurationen. Bereits vorhandene Apache-, PHP- und Website-Komponenten bleiben erhalten.

---

## 17. Sicherheitsregeln

- serverseitige Autorisierung bei jeder Aktion;
- Argon2id-Passwort-Hashes;
- HttpOnly- und SameSite-Sitzungscookies;
- CSRF-Schutz für verändernde Aktionen;
- SQLite Prepared Statements;
- HTML-Ausgabe-Escaping;
- kein direkter Webzugriff auf Zustandsdaten;
- keine Ausführung hochgeladener Dateien;
- keine Übernahme vorhandener Ports;
- zweifache Pfadvalidierung;
- HTTP-Selbsttest vor Ausgabe des Bootstraplinks;
- Apache-Konfigurationstest und finaler HTTP-Test vor Erfolgsmeldung.

---

## 18. Fehlerverhalten

WAMS meldet eine Installation erst als abgeschlossen, wenn die endgültige Loginseite erreichbar ist. Schlägt der Test fehl, zeigt das Terminal den konkreten WAMS-Logpfad.

Jede Installation durchläuft:

```text
Systemprüfung
  -> temporärer Bootstrap
  -> Browserentscheidung
  -> erneute privilegierte Prüfung
  -> Zielinstallation
  -> Apache-Konfigurationstest
  -> Apache-Reload
  -> finaler HTTP-Test
  -> Abschluss und Entfernen des Bootstrapordners
```

---

## 19. Entwicklungsregeln

1. Keine veränderlichen Dateien im App-Codeverzeichnis.
2. Keine Systemänderung ohne Journalzuordnung.
3. Keine globalen Aktionen aus einem Tenant-Scope.
4. Keine Geheimnisse in URLs, Befehlsargumenten oder normalen Exporten.
5. Keine App-Aktivierung ohne serverseitige Zugriffsprüfung.
6. Keine Übernahme belegter Ports.
7. Keine Überschreibung bestehender Installationsordner.
8. Keine sichtbare Example Application im Katalog oder Launcher.
9. Keine Speicherung der abgerufenen CSV-Antwort durch CSV Reporting.
10. Keine Erfolgsmeldung vor einem realen HTTP-Test.

---

## 20. Projektstatus

Version `2.0.0` enthält den temporären installationsunabhängigen Browser-Bootstrap, die bedingte Pfadauswahl, einen dauerhaften eigenen WAMS-Port sowie die optionale Verwendung der freien Ports 80 oder 443. Die bisherige Zielarchitektur, App-Trennung, Example Application und CSV-Reporting-Integration bleiben unverändert erhalten.

## 21. Oberfläche ab Version 1.3.0

Die Administration verwendet eine einheitliche dunkle Oberfläche für Übersicht, Tenants, Apps, Benutzer, Gruppen und System. Der Launcher orientiert sich am Tanum-Launcher-Kachelmodell und besitzt links oben eine Tenant-Auswahl als Overlay. App-Installationen werden ausschließlich über beschriftete GUI-Felder durchgeführt; Roh-JSON ist kein Installationsschritt mehr.

Neue Installationen erhalten automatisch einen freien UUID-basierten Root-Ordner unter `/opt`. Der Benutzer wird nicht nach einem Dateipfad gefragt. Setup und Anmeldung verwenden saubere Pfade ohne Setup-, Login- oder Token-Queryparameter; einzig fachlicher Kontext wie `tenant` darf in geteilten Links vorkommen. Erkannte ältere WAMS-Installationen werden im Management dauerhaft als Hinweis geführt, bis sie geprüft oder entfernt wurden.

CSV Reporting bleibt eine live arbeitende WAMS-App ohne lokale CSV-Kopie und bietet globale Suche, Spaltenfilter, Sortierung, Seitenlänge und Paging.


## 22. Einrichtung und einheitliche Oberfläche ab Version 1.3.1

Die Ersteinrichtung zeigt ausschließlich Benutzername und Passwort des ersten globalen Administrators. Port, Zertifikate, Tenants, Apps und weitere Systemeinstellungen werden nach der Anmeldung im Management verwaltet. Der temporäre Einrichtungsserver verwendet einen freien Port ab `8099`. Nach dem Absenden wird derselbe Port freigegeben, Apache mit einem selbstsignierten Zertifikat gestartet und der Browser auf `/login` weitergeleitet.

Setup und Login verwenden keine sichtbaren Setup-, Login- oder Token-Queryparameter. Die Oberfläche besteht aus drei klar verbundenen Bereichen: schlichte Einrichtung und Anmeldung, Tanum-inspirierter Tenant-App-Launcher sowie eine einheitliche dunkle Managementoberfläche.


## 23. Klare Oberflächenbereiche ab Version 1.4.0

WAMS trennt die Oberfläche sichtbar in drei Bereiche: **Launcher** für die tägliche App-Nutzung, **Management** für Tenants und App-Instanzen sowie **Admin** für globale Benutzer, Gruppen, Sicherheit und System. Jeder Bereich besitzt eine eigene Kennzeichnung und Navigation.

Die Gestaltung verwendet Schwarz, Grau und Weiß als Basis, normales Blau für primäre Aktionen, Gelb für Warnungen, Rot für Fehler und kritische Aktionen sowie Grün für Erfolgsmeldungen. Panels, Formulare und Navigation sind flach, eindeutig abgegrenzt und ohne dekorative Verläufe oder unscharfe Ebenen aufgebaut.

TOTP wird bei der Anmeldung nur als zweiter Schritt angezeigt, wenn es für das erkannte Benutzerkonto aktiviert ist. Der App-Katalog zeigt installierbare Apps als Karten mit Name, Versionsangabe, Iconfläche und Installationsaktion. Erst nach Auswahl einer App erscheint deren visuelles Installationsformular.


## 24. Konsolidierte Anforderungen ab Version 1.5.0

Launcher und Administration sind die beiden sichtbaren Produktbereiche. Die frühere Trennung zwischen Management und Admin wurde aufgehoben; Tenants, App-Katalog, App-Instanzen, Benutzer, Gruppen, Sicherheit und System liegen in einer gemeinsamen Adminoberfläche. Einrichtung und Anmeldung bilden ausschließlich den vorgeschalteten Authentifizierungsablauf und verwenden die kompakte NADA-Gestaltung mit `#080c11`, `#111923`, `#293747`, `#e7edf4`, `#8c9bad` und `#56a5ff`.

CSV Reporting speichert je App-Instanz eine eigenständige `state/instances/<instance-id>/config.json`. Diese AppConfig ist unabhängig von App-Dateien und AppData und enthält Remote-URL, Überschriften, Darstellung, Spaltenauswahl, Umbenennungen, Links, Filter und Regeln. Remote-CSV-Antworten werden weiterhin nur im Speicher verarbeitet.

Die Adminoberfläche enthält wieder Tenant-Erstellung, Benutzeranlage, globale Administratoren, Tenant-Zuweisungen, Gruppenanlage, App-Katalog, App-Installation, App-Verwaltung und die dauerhaft sichtbare Erkennung weiterer oder älterer Installationen. Eine erkannte Altinstallation wird nur nach erneuter Passworteingabe und sicherer Pfadprüfung entfernt.


## 25. Berechtigungs- und Update-Modell ab Version 2.0.0

Es existiert genau ein globaler Administrator: das bei der Ersteinrichtung angelegte Konto. Weitere Konten sind normale Benutzer. Benutzeranlage, Tenant-Leserechte, Gruppenmitgliedschaften und App-Rechte sind getrennte Vorgänge.

Tenant-Leserechte können direkt an Benutzer oder über Gruppen vergeben werden. App-Instanzen besitzen unabhängig davon die Rechte `use` und `edit`. `use` erlaubt das Öffnen der Instanz; `edit` erlaubt den App-Editor beziehungsweise die App-Konfiguration. Beide Rechte können direkt an einen Benutzer oder an eine Gruppe vergeben werden. Der globale Administrator besitzt alle Rechte automatisch.

Das Management-System besitzt denselben Update-Lebenszyklus wie App-Dateien: Updatequelle, installierter Stand, verfügbarer Stand und Updateauftrag. Ein root-eigener systemd-Timer verarbeitet die von der Weboberfläche eingereihten Aufträge, während `state`, AppConfig, AppData, Backups und Exporte erhalten bleiben.

Die Zertifikatserstellung wird vor Aktivierung der Apache-Site auf Existenz, Dateigröße und Gültigkeit geprüft. Apache wird nicht konfiguriert, wenn Zertifikat oder Schlüssel fehlen.
