<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

/**
 * Deutsche Sprachdatei für local_emailclient.
 *
 * @package     local_emailclient
 * @copyright   2026 Your Organisation
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Allgemein.
$string['pluginname'] = 'E-Mail-Client';
$string['emailclient:use'] = 'Den E-Mail-Client verwenden';
$string['emailclient:manage'] = 'E-Mail-Client-Einstellungen verwalten';
$string['privacy:metadata:local_emailclient_accounts'] = 'Informationen über das persönliche IMAP/SMTP-Postfach, das ein/e Nutzer/in eingerichtet hat.';
$string['privacy:metadata:local_emailclient_accounts:userid'] = 'Die ID des Nutzers, dem das Konto gehört.';
$string['privacy:metadata:local_emailclient_accounts:imaphost'] = 'Der Hostname des IMAP-Servers.';
$string['privacy:metadata:local_emailclient_accounts:imapusername'] = 'Der IMAP-Anmeldename.';
$string['privacy:metadata:local_emailclient_accounts:imappassword'] = 'Das (verschlüsselte) IMAP-Passwort.';
$string['privacy:metadata:local_emailclient_accounts:smtphost'] = 'Der Hostname des SMTP-Servers.';
$string['privacy:metadata:local_emailclient_accounts:smtpusername'] = 'Der SMTP-Anmeldename.';
$string['privacy:metadata:local_emailclient_accounts:smtppassword'] = 'Das (verschlüsselte) SMTP-Passwort.';
$string['privacy:metadata:local_emailclient_accounts:fromemail'] = 'Die als Absender verwendete E-Mail-Adresse.';
$string['privacy:metadata:local_emailclient_accounts:fromname'] = 'Der als Absender angezeigte Name.';
$string['privacy:metadata:local_emailclient_accounts:signature'] = 'Die persönliche E-Mail-Signatur.';
$string['privacy:metadata:local_emailclient_accounts:timecreated'] = 'Zeitpunkt der Erstellung des Kontos.';
$string['privacy:metadata:local_emailclient_accounts:timemodified'] = 'Zeitpunkt der letzten Änderung des Kontos.';
$string['privacy:metadata:imapserver'] = 'Um Ihr Postfach anzuzeigen, werden Ihre E-Mail-Adresse, Ihr Benutzername und Ihr Passwort an den von Ihnen konfigurierten externen IMAP-Server übermittelt.';
$string['privacy:metadata:smtpserver'] = 'Um E-Mails im Ihrem Namen zu versenden, werden Ihre E-Mail-Adresse, Ihr Benutzername und Ihr Passwort an den von Ihnen konfigurierten externen SMTP-Server übermittelt.';

// Einstellungen.
$string['settings:heading'] = 'Einstellungen des E-Mail-Clients';
$string['settings:enableplugin'] = 'E-Mail-Client aktivieren';
$string['settings:enableplugin_desc'] = 'Wenn deaktiviert, wird das Plugin aus der Navigation ausgeblendet und der Zugriff für alle außer Administratoren/Administratorinnen gesperrt.';
$string['settings:maxattachmentsize'] = 'Maximale Anhanggröße (Verfassen)';
$string['settings:maxattachmentsize_desc'] = 'Maximale Gesamtgröße der Anhänge, die mit einer einzelnen Nachricht gesendet werden dürfen, in Byte.';
$string['settings:allowedimaphosts'] = 'Erlaubte IMAP/SMTP-Server';
$string['settings:allowedimaphosts_desc'] = 'Optional. Ein Hostname pro Zeile. Wenn gesetzt, dürfen Nutzer/innen sich nur mit Servern aus dieser Liste verbinden. Leer lassen, um beliebige Server zu erlauben.';
$string['settings:messagesperpage'] = 'Nachrichten pro Seite';
$string['settings:messagesperpage_desc'] = 'Anzahl der Nachrichten, die pro Seite in der Nachrichtenliste angezeigt werden.';
$string['settings:connectiontimeout'] = 'Verbindungs-Timeout (Sekunden)';
$string['settings:connectiontimeout_desc'] = 'Timeout für IMAP- und SMTP-Verbindungsversuche.';

// Navigation / Seitentitel.
$string['nav:emailclient'] = 'E-Mail';
$string['page:inbox'] = 'Postfach';
$string['page:message'] = 'Nachricht';
$string['page:compose'] = 'Neue Nachricht';
$string['page:reply'] = 'Antworten';
$string['page:replyall'] = 'Allen antworten';
$string['page:forward'] = 'Weiterleiten';
$string['page:account'] = 'E-Mail-Kontoeinstellungen';

// Kontoformular.
$string['account:nosettings'] = 'Es wurde noch kein E-Mail-Konto eingerichtet.';
$string['account:setupnow'] = 'E-Mail-Konto einrichten';
$string['account:edit'] = 'Kontoeinstellungen bearbeiten';
$string['account:imapsection'] = 'Posteingang (IMAP)';
$string['account:smtpsection'] = 'Postausgang (SMTP)';
$string['account:identitysection'] = 'Identität';
$string['account:imaphost'] = 'IMAP-Server';
$string['account:imapport'] = 'IMAP-Port';
$string['account:imapsecurity'] = 'IMAP-Verschlüsselung';
$string['account:imapusername'] = 'IMAP-Benutzername';
$string['account:imappassword'] = 'IMAP-Passwort';
$string['account:smtphost'] = 'SMTP-Server';
$string['account:smtpport'] = 'SMTP-Port';
$string['account:smtpsecurity'] = 'SMTP-Verschlüsselung';
$string['account:smtpusername'] = 'SMTP-Benutzername';
$string['account:smtppassword'] = 'SMTP-Passwort';
$string['account:samelogindata'] = 'Gleiche Zugangsdaten wie IMAP verwenden';
$string['account:fromname'] = 'Anzeigename';
$string['account:fromemail'] = 'E-Mail-Adresse';
$string['account:signature'] = 'Signatur';
$string['account:security_none'] = 'Keine';
$string['account:security_ssl'] = 'SSL/TLS';
$string['account:security_tls'] = 'STARTTLS';
$string['account:testconnection'] = 'Verbindung testen';
$string['account:save'] = 'Konto speichern';
$string['account:saved'] = 'Die E-Mail-Kontoeinstellungen wurden gespeichert.';
$string['account:deleted'] = 'Die E-Mail-Kontoeinstellungen wurden gelöscht.';
$string['account:delete'] = 'Kontoeinstellungen löschen';
$string['account:deleteconfirm'] = 'Sollen die gespeicherten E-Mail-Kontoeinstellungen wirklich gelöscht werden? Ihre E-Mails auf dem Server bleiben davon unberührt.';
$string['account:testok'] = 'Verbindung erfolgreich: {$a}';
$string['account:testfailed'] = 'Verbindung fehlgeschlagen: {$a}';
$string['account:passwordkepthint'] = 'Leer lassen, um das aktuell gespeicherte Passwort zu behalten.';
$string['account:hostnotallowed'] = 'Dieser Server steht nicht auf der von der Administration freigegebenen Liste und kann nicht verwendet werden.';

// Ordner / Postfach.
$string['folders:heading'] = 'Ordner';
$string['folders:refresh'] = 'Aktualisieren';
$string['folders:unreadcount'] = '{$a} ungelesen';
$string['folders:inbox'] = 'Posteingang';
$string['folders:sent'] = 'Gesendet';
$string['folders:drafts'] = 'Entwürfe';
$string['folders:trash'] = 'Papierkorb';
$string['folders:junk'] = 'Spam';

// Nachrichtenliste.
$string['messages:empty'] = 'Dieser Ordner ist leer.';
$string['messages:from'] = 'Von';
$string['messages:subject'] = 'Betreff';
$string['messages:date'] = 'Datum';
$string['messages:size'] = 'Größe';
$string['messages:attachment'] = 'Hat Anhang';
$string['messages:search'] = 'Suchen';
$string['messages:searchplaceholder'] = 'Betreff, Absender oder Inhalt durchsuchen…';
$string['messages:searchresultsfor'] = 'Suchergebnisse für „{$a}“';
$string['messages:clearsearch'] = 'Suche zurücksetzen';
$string['messages:noresults'] = 'Keine Nachrichten entsprechen Ihrer Suche.';
$string['messages:page'] = 'Seite {$a->page} von {$a->totalpages}';
$string['messages:selectall'] = 'Alle auswählen';
$string['messages:markread'] = 'Als gelesen markieren';
$string['messages:markunread'] = 'Als ungelesen markieren';
$string['messages:delete'] = 'Löschen';
$string['messages:deleteconfirm'] = 'Ausgewählte Nachricht(en) löschen? Sie werden auf dem Mailserver in den Papierkorb verschoben (bzw. endgültig gelöscht, wenn sie sich bereits im Papierkorb befinden).';
$string['messages:nooneselected'] = 'Bitte wählen Sie mindestens eine Nachricht aus.';
$string['messages:compose'] = 'Neue Nachricht';
$string['messages:noaccount'] = 'Sie müssen zunächst Ihr E-Mail-Konto einrichten, um den E-Mail-Client nutzen zu können.';

// Einzelansicht einer Nachricht.
$string['view:reply'] = 'Antworten';
$string['view:replyall'] = 'Allen antworten';
$string['view:forward'] = 'Weiterleiten';
$string['view:delete'] = 'Löschen';
$string['view:back'] = 'Zurück zum Postfach';
$string['view:to'] = 'An';
$string['view:cc'] = 'Cc';
$string['view:bcc'] = 'Bcc';
$string['view:date'] = 'Datum';
$string['view:attachments'] = 'Anhänge ({$a})';
$string['view:download'] = 'Herunterladen';
$string['view:showimages'] = 'Externe Bilder anzeigen';
$string['view:plaintextonly'] = 'Diese Nachricht enthält keinen HTML-Inhalt.';
$string['view:loaderror'] = 'Die Nachricht konnte nicht geladen werden. Sie wurde möglicherweise auf dem Server verschoben oder gelöscht.';

// Verfassen.
$string['compose:to'] = 'An';
$string['compose:cc'] = 'Cc';
$string['compose:bcc'] = 'Bcc';
$string['compose:subject'] = 'Betreff';
$string['compose:message'] = 'Nachricht';
$string['compose:attachments'] = 'Anhänge';
$string['compose:send'] = 'Senden';
$string['compose:savedraft'] = 'Als Entwurf speichern';
$string['compose:discard'] = 'Verwerfen';
$string['compose:sent'] = 'Ihre Nachricht wurde gesendet.';
$string['compose:senderror'] = 'Die Nachricht konnte nicht gesendet werden: {$a}';
$string['compose:invalidrecipient'] = 'Bitte geben Sie mindestens eine gültige Empfängeradresse ein.';
$string['compose:toolarge'] = 'Die Anhänge sind zu groß. Die maximal erlaubte Größe beträgt {$a}.';
$string['compose:replyprefix'] = 'Re: {$a}';
$string['compose:forwardprefix'] = 'Fwd: {$a}';
$string['compose:forwardedmessage'] = '---------- Weitergeleitete Nachricht ----------';
$string['compose:originalmessage'] = '----- Ursprüngliche Nachricht -----';

// Fehler.
$string['error:imapextensionmissing'] = 'Die PHP-IMAP-Erweiterung (ext-imap) ist auf diesem Server nicht aktiviert. Bitten Sie Ihre Administration, sie zu aktivieren.';
$string['error:connectionfailed'] = 'Es konnte keine Verbindung zum Mailserver aufgebaut werden: {$a}';
$string['error:folderlistfailed'] = 'Die Ordnerliste konnte nicht abgerufen werden: {$a}';
$string['error:messagelistfailed'] = 'Die Nachrichtenliste konnte nicht abgerufen werden: {$a}';
$string['error:disabled'] = 'Der E-Mail-Client wurde von der Administration deaktiviert.';
$string['error:nopermission'] = 'Sie haben keine Berechtigung, den E-Mail-Client zu verwenden.';
$string['error:invalidfolder'] = 'Ungültiger oder unbekannter Ordner.';
$string['error:invalidmessage'] = 'Ungültige oder unbekannte Nachricht.';

// Admin-Rollenverwaltung.
$string['page:adminroles'] = 'Zugriffsverwaltung';
$string['adminroles:desc'] = 'Legen Sie hier fest, welche Rollen den E-Mail-Client nutzen dürfen. Ein Häkchen bei einer Rolle erteilt die Berechtigung <code>local/emailclient:use</code> auf Systemebene. Rollen ohne Häkchen haben keinen Zugriff. Änderungen gelten sofort.';
$string['adminroles:role'] = 'Rolle';
$string['adminroles:access'] = 'Zugriff erlaubt';
$string['adminroles:roletype'] = 'Rollentyp';
$string['adminroles:customrole'] = 'Benutzerdefinierte Rolle';
$string['settings:rolessaved'] = 'Die Rollenzuweisungen wurden gespeichert.';

// Server-Standardwerte.
$string['settings:defaultsheading'] = 'Voreinstellungen für den Mailserver';
$string['settings:defaultsheading_desc'] = 'Diese Werte werden beim ersten Öffnen des Kontoformulars automatisch eingetragen. Nutzer/innen müssen dann nur noch ihren Benutzernamen und ihr Passwort eingeben. Felder die leer bleiben werden nicht vorausgefüllt.';
$string['settings:default_imaphost'] = 'Standard-IMAP-Server';
$string['settings:default_imaphost_desc'] = 'z.B. mail.schule.de';
$string['settings:default_imapport'] = 'Standard-IMAP-Port';
$string['settings:default_imapport_desc'] = 'Standard: 993 (SSL)';
$string['settings:default_imapsecurity'] = 'Standard-IMAP-Verschlüsselung';
$string['settings:default_smtphost'] = 'Standard-SMTP-Server';
$string['settings:default_smtphost_desc'] = 'Leer lassen wenn gleich wie IMAP.';
$string['settings:default_smtpport'] = 'Standard-SMTP-Port';
$string['settings:default_smtpport_desc'] = 'Standard: 587 (STARTTLS)';
$string['settings:default_smtpsecurity'] = 'Standard-SMTP-Verschlüsselung';
