[Direkt zum Installationsbefehl](#122-direkter-installationsbefehl)

# Web Application Management System

```text
Projektname: Web Application Management System
Repository: web-application-management-system
Kurzname: WAMS
Version: 1.2.0
Zielsysteme: Debian und Ubuntu
```

WAMS erkennt vorhandenen Apache, PHP, aktive DocumentRoots und erforderliche PHP-Funktionen. Nur fehlende Komponenten werden per APT installiert und als `created-by-wams` protokolliert. Der Browser-Installer schlägt einen noch nicht vorhandenen Unterordner eines erkannten DocumentRoots vor. Der Benutzer kann jeden anderen absoluten Pfad mit vorhandenem, beschreibbarem Elternordner wählen. Bestehende Verzeichnisse und DocumentRoots werden abgelehnt.

## Installation

```bash
curl -fsSL https://raw.githubusercontent.com/florianthepro/web-application-management-system/main/install.sh | sudo bash -s -- --repository https://github.com/florianthepro/web-application-management-system
```

Der Bootstrap läuft temporär über den eingebauten PHP-Webserver, führt einen HTTP-Selbsttest durch und zeigt erst danach den Browserlink. Der Root-Terminalprozess bleibt offen, validiert die Browserauswahl erneut, richtet einen isolierten Apache-VirtualHost auf einem freien Port ein und beendet sich erst nach erfolgreichem finalen HTTP-Test.

## Integrierte Apps

- `example-application`: nicht sichtbar, nicht installierbar, nicht herunterladbar; leere `.gitkeep`-Referenzstruktur.
- `csv-reporting`: live verarbeitete Remote-CSV ohne lokale Speicherung, getrennte Anzeige- und Verwaltungsrechte, Remote-Prüfung mit DNS-Hinweis und transparenter Rückkehrpfeil links oben.

## Zustands- und Deinstallationsmodell

Vorhandene Software wird als `preexisting` behandelt. Fehlende Software wird installiert und im Journal vermerkt. Die Deinstallation entfernt nur eindeutig WAMS-eigene Dateien und den gewählten WAMS-Ordner; vorhandene Websites, Apache und PHP bleiben erhalten.
