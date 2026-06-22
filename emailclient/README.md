# local_emailclient

Ein lokales Moodle-Plugin, das einen vollwertigen E-Mail-Client (IMAP/SMTP)
direkt in Moodle integriert. Jede:r Nutzer:in verbindet sich mit den
eigenen Zugangsdaten zu einem beliebigen IMAP/SMTP-Server (z. B. Postfix/
Dovecot, ein Firmen-Mailserver o. Ä.) – es gibt keine Abhängigkeit zu
Gmail/OAuth oder einem externen Webmail-Iframe.

## Voraussetzungen

- Moodle **5.0 oder neuer** (entwickelt und getestet gegen Moodle 5.1,
  Build 2025100600). `version.php` setzt `$plugin->supported = [501, 999]`.
- PHP **8.2+** (von Moodle 5.0+ ohnehin vorausgesetzt).
- Die PHP-Erweiterung **`imap`** muss aktiviert sein
  (`php -m | grep imap` bzw. `extension=imap` in der php.ini). Ohne diese
  Erweiterung zeigt das Plugin einen Fehlerhinweis und funktioniert nicht.
- Die PHP-Erweiterung **`sodium`** (für die Passwortverschlüsselung über
  `\core\encryption`) – ist seit Moodle 5.0 ohnehin Pflichtvoraussetzung
  für den Moodle-Core selbst, sollte also bereits vorhanden sein.
- Ausgehender Netzwerkzugriff vom Webserver zu den IMAP/SMTP-Ports der
  Mailserver, die die Nutzer:innen eintragen (Firewall/Sicherheitsgruppen
  beachten).

## Installation

Das Plugin ist ein **Local Plugin** mit Frankenstyle-Namen
`local_emailclient` und Ordnername `emailclient`.

1. ZIP-Datei entpacken. Der oberste Ordner heißt `emailclient`.
2. Je nach Moodle-Version an die richtige Stelle kopieren:
   - **Klassische Verzeichnisstruktur** (Moodle-Installationen, die vor
     dem 5.x-Umbau aktualisiert wurden):
     `{moodleroot}/local/emailclient/`
   - **Neue Moodle-5.x-Struktur** (Code liegt unter `/public`):
     `{moodleroot}/public/local/emailclient/`

   Im Zweifel: prüfen, ob unter `{moodleroot}/public/local/` bereits
   andere Plugins liegen – wenn ja, dorthin kopieren, sonst nach
   `{moodleroot}/local/`.
3. Als Moodle-Administrator einloggen und zu
   *Website-Administration → Benachrichtigungen* gehen, um die
   Installation/das Datenbank-Upgrade auszulösen (legt die Tabelle
   `local_emailclient_accounts` an).
4. Optional unter *Website-Administration → Plugins → Lokale Plugins →
   E-Mail-Client* die Admin-Einstellungen prüfen/anpassen (siehe unten).

## Admin-Einstellungen

Unter `/admin/settings.php?section=local_emailclient`:

- **Plugin aktivieren** – globaler Schalter, Standard: aktiviert.
- **Maximale Anhanggröße** – Limit in Byte für einzelne Anhänge bzw. die
  gesamte Anhang-Dateifläche pro E-Mail beim Verfassen.
- **Erlaubte Mailserver** – optionale Allow-List (ein Hostname pro Zeile).
  Wenn leer, dürfen Nutzer:innen jeden beliebigen Host eintragen. Wenn
  gefüllt, werden sowohl der IMAP- als auch der SMTP-Host beim Speichern
  gegen diese Liste geprüft (nützlich, um z. B. nur den internen
  Firmenserver zuzulassen und damit das Risiko von SSRF/Datenabfluss zu
  begrenzen).
- **Nachrichten pro Seite** – Pagination-Größe der Nachrichtenliste.
- **Verbindungs-Timeout** – Timeout in Sekunden für IMAP/SMTP-Verbindungsversuche.

## Berechtigungen

- `local/emailclient:use` – Plugin nutzen (Postfach öffnen, Konto
  einrichten, E-Mails senden/lesen). Standardmäßig für alle angemeldeten,
  nicht-Gast-Rollen erlaubt.
- `local/emailclient:manage` – aktuell ungenutzt reserviert für künftige,
  site-weite Verwaltungsfunktionen (Standard: nur Manager).

Beide Capabilities können wie gewohnt über
*Website-Administration → Nutzer/innen → Berechtigungen* pro Rolle/Kontext
angepasst werden.

## Erste Einrichtung (pro Nutzer:in)

1. Über das Hauptmenü (Punkt „E-Mail-Client“) oder direkt
   `/local/emailclient/account.php` öffnen.
2. IMAP-Zugangsdaten eintragen (Host, Port, Verschlüsselung, Benutzername,
   Passwort).
3. Falls SMTP dieselben Zugangsdaten wie IMAP nutzt (typischer Fall),
   einfach die Checkbox „Gleiche Zugangsdaten wie IMAP“ aktiviert lassen.
   Andernfalls Häkchen entfernen und die SMTP-Felder separat ausfüllen.
4. Absendername/-adresse und optional eine Signatur eintragen.
5. Mit „Verbindung testen“ prüfen, ob IMAP und SMTP erreichbar sind und
   die Zugangsdaten akzeptiert werden, bevor gespeichert wird.
6. Speichern – danach öffnet sich automatisch das Postfach.

Das gespeicherte Passwort wird mit der Moodle-Core-Verschlüsselung
(`\core\encryption`, libsodium) in der Datenbank abgelegt und beim Laden
des Kontos wieder entschlüsselt; es wird niemals im Klartext im Formular
angezeigt (das Passwortfeld bleibt beim Bearbeiten leer und wird nur bei
erneuter Eingabe überschrieben).

## Funktionsumfang

- Ordnerliste mit Ungelesen-Zählern, automatische Erkennung von
  Posteingang/Gesendet/Entwürfe/Papierkorb/Spam (EN- und DE-Namen).
- Nachrichtenliste mit Volltextsuche (Betreff/Absender/Body über die
  IMAP-SEARCH-Befehle), Pagination, Markieren als gelesen/ungelesen,
  Löschen (einzeln oder als Mehrfachauswahl).
- Nachrichtenansicht mit sanitiertem HTML-Body (externe Bilder werden
  standardmäßig blockiert und können per Klick nachgeladen werden),
  Klartext-Fallback, Anhangsliste mit Direktdownload.
- Verfassen, Antworten, Allen antworten, Weiterleiten – inklusive
  automatischer Betreffs-Präfixe, Zitat des Originaltextes,
  In-Reply-To/References-Header für korrekte Thread-Zuordnung im
  E-Mail-Client der Empfänger:innen, Dateianhänge über den
  Moodle-Dateimanager.
- Anhänge werden nie dauerhaft auf dem Moodle-Server gespeichert:
  eingehende Anhänge werden bei Bedarf live aus dem IMAP-Postfach
  gestreamt, ausgehende Anhänge nur kurzfristig im Moodle-Entwurfsbereich
  gehalten und nach erfolgreichem Versand gelöscht.

## Sicherheits- und Datenschutzhinweise

- **HTML-Sanitizer**: E-Mail-Inhalte werden über einen eigenen,
  DOMDocument-basierten Sanitizer gefiltert (entfernt `<script>`,
  `<iframe>`, `<object>`, `<embed>`, Event-Handler-Attribute,
  `javascript:`-URLs; blockiert externe Bilder standardmäßig). Das ist
  eine pragmatische, aber **keine umfassend geprüfte** Lösung. Für
  Installationen mit hohen Sicherheitsanforderungen sollte erwogen
  werden, stattdessen eine dedizierte, aktiv gepflegte
  HTML-Sanitizer-Bibliothek einzubinden.
- **Passwortverschlüsselung**: IMAP-/SMTP-Passwörter werden reversibel
  verschlüsselt (nicht gehasht, da sie für den Verbindungsaufbau im
  Klartext benötigt werden) über die Moodle-Core-API `\core\encryption`
  gespeichert. Der Datenbankzugriff selbst muss wie bei jeder
  Moodle-Installation entsprechend abgesichert sein.
- **Datenschutz (Privacy API)**: Das Plugin implementiert die
  Moodle-Privacy-API (`classes/privacy/provider.php`). Der einzige
  personenbezogene Datensatz pro Nutzer:in ist die Kontokonfiguration
  (Hostnamen, Benutzernamen, verschlüsseltes Passwort, Absenderidentität).
  E-Mail-Inhalte selbst werden **nicht** von Moodle gespeichert – sie
  verbleiben ausschließlich auf dem externen Mailserver der Nutzer:innen
  und werden nur live angezeigt/gestreamt. Bei einem Datenexport/
  -löschantrag wird entsprechend nur dieser Kontodatensatz exportiert
  bzw. gelöscht.
- **Allow-List für Mailserver**: siehe Admin-Einstellung oben – empfohlen,
  wenn nur ein bestimmter (interner) Mailserver zugelassen werden soll.

## Bekannte Grenzen

- Es gibt keine Push-Benachrichtigungen oder IDLE-Unterstützung; die
  Nachrichtenliste wird bei jedem Seitenaufruf neu vom IMAP-Server
  abgefragt.
- Es werden keine Mustache-Templates/eine eigene Renderer-Klasse
  verwendet; die Seiten erzeugen ihr HTML direkt über
  `html_writer`/`$OUTPUT`, um den Plugin-Umfang überschaubar zu halten.
- Das Plugin wurde sorgfältig nach Moodle-/PHP-Dokumentation entwickelt
  und mittels `php -l` auf Syntaxfehler geprüft, konnte aber in dieser
  Umgebung **nicht gegen eine echte Moodle-Instanz oder einen echten
  IMAP/SMTP-Server laufzeitgetestet werden**. Vor dem Produktiveinsatz
  wird ein Test in einer Staging-Umgebung empfohlen.
