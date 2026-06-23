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

namespace local_emailclient;

use stdClass;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

defined('MOODLE_INTERNAL') || die();

/**
 * Sends mail via SMTP using the credentials stored in a user's account
 * record, reusing the PHPMailer library already bundled with Moodle core
 * (rather than the site-wide $CFG->smtp* configuration, which is used for
 * Moodle's own notification e-mails and is not relevant here).
 *
 * @package     local_emailclient
 * @copyright   2026 Your Organisation
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mail_sender {

    /** @var stdClass Decrypted account record. */
    private stdClass $account;

    /**
     * @param stdClass $account Decrypted account record (see account_manager::get_for_user()).
     */
    public function __construct(stdClass $account) {
        $this->account = $account;
    }

    /**
     * Sends a message.
     *
     * @param array $params {
     *     to: string (comma separated addresses),
     *     cc: string,
     *     bcc: string,
     *     subject: string,
     *     bodyhtml: string,
     *     bodyplain: string,
     *     attachments: array of {filename, mimetype, content} (raw binary content),
     *     inreplyto: string Optional Message-ID header value to set In-Reply-To/References.
     * }
     * @return string Raw MIME message (headers + body) for optional
     *                  saving to the IMAP Sent folder by the caller.
     * @throws \moodle_exception on failure (the original PHPMailer error is included).
     */
    public function send(array $params): string {
        require_once($GLOBALS['CFG']->libdir . '/phpmailer/moodle_phpmailer.php');

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = $this->account->smtphost;
            $mail->Port       = $this->account->smtpport;
            $mail->SMTPAuth   = true;
            $mail->Username   = $this->account->smtpusername;
            $mail->Password   = $this->account->smtppassword;
            $mail->CharSet    = 'UTF-8';

            switch ($this->account->smtpsecurity) {
                case 'ssl':
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                    break;
                case 'tls':
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    break;
                default:
                    $mail->SMTPSecure = '';
                    $mail->SMTPAutoTLS = false;
                    break;
            }

            $fromname = $this->account->fromname !== '' ? $this->account->fromname : $this->account->fromemail;
            $mail->setFrom($this->account->fromemail, $fromname);
            $mail->Sender = $this->account->fromemail;

            foreach ($this->split_addresses($params['to'] ?? '') as $addr) {
                $mail->addAddress($addr);
            }
            foreach ($this->split_addresses($params['cc'] ?? '') as $addr) {
                $mail->addCC($addr);
            }
            foreach ($this->split_addresses($params['bcc'] ?? '') as $addr) {
                $mail->addBCC($addr);
            }

            if ($mail->getToAddresses() === [] && $mail->getCcAddresses() === [] && $mail->getBccAddresses() === []) {
                throw new \moodle_exception('compose:invalidrecipient', 'local_emailclient');
            }

            $mail->Subject = $params['subject'] ?? '';

            $bodyhtml = $params['bodyhtml'] ?? '';
            $bodyplain = $params['bodyplain'] ?? '';
            if ($bodyhtml !== '') {
                $mail->isHTML(true);
                $mail->Body = $bodyhtml;
                $mail->AltBody = $bodyplain !== '' ? $bodyplain : trim(html_to_text($bodyhtml));
            } else {
                $mail->isHTML(false);
                $mail->Body = $bodyplain;
            }

            foreach ($params['attachments'] ?? [] as $attachment) {
                $mail->addStringAttachment(
                    $attachment['content'],
                    $attachment['filename'],
                    PHPMailer::ENCODING_BASE64,
                    $attachment['mimetype'] ?? 'application/octet-stream'
                );
            }

            if (!empty($params['inreplyto'])) {
                $mail->addCustomHeader('In-Reply-To', $params['inreplyto']);
                $mail->addCustomHeader('References', $params['inreplyto']);
            }

            $mail->send();
            // getSentMIMEMessage() is PHPMailer's public API for retrieving
            // the full raw RFC 2822 message after a successful send().
            // MIMEHeader/MIMEBody are protected and must not be accessed
            // directly from outside the class.
            return $mail->getSentMIMEMessage();
        } catch (PHPMailerException $e) {
            throw new \moodle_exception('compose:senderror', 'local_emailclient', '', $mail->ErrorInfo ?: $e->getMessage());
        } catch (\Exception $e) {
            throw new \moodle_exception('compose:senderror', 'local_emailclient', '', $e->getMessage());
        }
        return ''; // unreachable, satisfies static analysis
    }

    /**
     * Opens an SMTP connection, performs STARTTLS if configured and
     * authenticates, then disconnects again. Used by the "test connection"
     * button - does not send any mail.
     *
     * @return void
     * @throws \moodle_exception on failure.
     */
    public function test_connection(): void {
        require_once($GLOBALS['CFG']->libdir . '/phpmailer/moodle_phpmailer.php');

        $smtp = new \PHPMailer\PHPMailer\SMTP();
        $timeout = (int) (get_config('local_emailclient', 'connectiontimeout') ?: 15);

        try {
            $host = ($this->account->smtpsecurity === 'ssl' ? 'ssl://' : '') . $this->account->smtphost;
            if (!$smtp->connect($host, (int) $this->account->smtpport, $timeout)) {
                throw new \moodle_exception(
                    'error:connectionfailed',
                    'local_emailclient',
                    '',
                    implode('; ', $smtp->getError())
                );
            }

            $localname = gethostname() ?: 'localhost';
            $smtp->hello($localname);

            if ($this->account->smtpsecurity === 'tls') {
                if (!$smtp->startTLS()) {
                    throw new \moodle_exception('error:connectionfailed', 'local_emailclient', '', 'STARTTLS failed');
                }
                $smtp->hello($localname);
            }

            if (!$smtp->authenticate($this->account->smtpusername, $this->account->smtppassword)) {
                throw new \moodle_exception('error:connectionfailed', 'local_emailclient', '', 'Authentication failed');
            }

            $smtp->quit(true);
        } finally {
            $smtp->close();
        }
    }

    /**
     * Splits a comma/semicolon separated address list into trimmed, non-empty entries.
     *
     * @param string $value
     * @return string[]
     */
    private function split_addresses(string $value): array {
        if (trim($value) === '') {
            return [];
        }
        $parts = preg_split('/[,;]+/', $value);
        return array_values(array_filter(array_map('trim', $parts), fn($v) => $v !== ''));
    }
}
