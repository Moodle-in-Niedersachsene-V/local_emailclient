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

use core\encryption;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Reads, writes and deletes the per-user IMAP/SMTP account record.
 *
 * Passwords are stored using Moodle's core\encryption class (libsodium
 * secretbox with a server-side key kept in moodledata), so they can be
 * decrypted again by the server in order to authenticate against the
 * external mail servers, but are not stored in plain text in the
 * database.
 *
 * @package     local_emailclient
 * @copyright   2026 Your Organisation
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class account_manager {

    /** @var string DB table name (without prefix). */
    const TABLE = 'local_emailclient_accounts';

    /**
     * Returns the decrypted account record for a user, or null if none exists.
     *
     * @param int $userid
     * @return stdClass|null
     */
    public static function get_for_user(int $userid): ?stdClass {
        global $DB;

        $record = $DB->get_record(self::TABLE, ['userid' => $userid]);
        if (!$record) {
            return null;
        }

        $record->imappassword = self::safe_decrypt($record->imappassword);
        $record->smtppassword = self::safe_decrypt($record->smtppassword);

        return $record;
    }

    /**
     * Whether a user has already configured an account.
     *
     * @param int $userid
     * @return bool
     */
    public static function has_account(int $userid): bool {
        global $DB;
        return $DB->record_exists(self::TABLE, ['userid' => $userid]);
    }

    /**
     * Creates or updates the account record for a user.
     *
     * If imappassword/smtppassword are empty strings, the previously
     * stored password is kept (used so the edit form does not need to
     * redisplay/require the password every time).
     *
     * @param int $userid
     * @param stdClass $data Expected fields: imaphost, imapport, imapsecurity,
     *                       imapusername, imappassword, smtphost, smtpport,
     *                       smtpsecurity, smtpusername, smtppassword,
     *                       fromname, fromemail, signature.
     * @return void
     */
    public static function save(int $userid, stdClass $data): void {
        global $DB;

        $existing = $DB->get_record(self::TABLE, ['userid' => $userid]);
        $now = time();

        $record = new stdClass();
        $record->userid       = $userid;
        $record->imaphost     = trim($data->imaphost);
        $record->imapport     = (int) $data->imapport;
        $record->imapsecurity = $data->imapsecurity;
        $record->imapusername = trim($data->imapusername);
        $record->smtphost     = trim($data->smtphost);
        $record->smtpport     = (int) $data->smtpport;
        $record->smtpsecurity = $data->smtpsecurity;
        $record->smtpusername = trim($data->smtpusername);
        $record->fromname     = trim((string) ($data->fromname ?? ''));
        $record->fromemail    = trim($data->fromemail);
        $record->signature    = (string) ($data->signature ?? '');
        $record->timemodified = $now;

        // Passwords: encrypt new value, or keep the old encrypted value
        // untouched if the form field was left empty.
        if ($data->imappassword !== '') {
            $record->imappassword = encryption::encrypt($data->imappassword);
        } else if ($existing) {
            $record->imappassword = $existing->imappassword;
        } else {
            $record->imappassword = encryption::encrypt('');
        }

        if ($data->smtppassword !== '') {
            $record->smtppassword = encryption::encrypt($data->smtppassword);
        } else if ($existing) {
            $record->smtppassword = $existing->smtppassword;
        } else {
            $record->smtppassword = encryption::encrypt('');
        }

        if ($existing) {
            $record->id = $existing->id;
            $record->timecreated = $existing->timecreated;
            $DB->update_record(self::TABLE, $record);
        } else {
            $record->timecreated = $now;
            $DB->insert_record(self::TABLE, $record);
        }
    }

    /**
     * Deletes the stored account configuration for a user.
     *
     * @param int $userid
     * @return void
     */
    public static function delete(int $userid): void {
        global $DB;
        $DB->delete_records(self::TABLE, ['userid' => $userid]);
    }

    /**
     * Decrypts a value, tolerating empty/invalid input instead of throwing.
     *
     * @param string|null $value
     * @return string
     */
    private static function safe_decrypt(?string $value): string {
        if ($value === null || $value === '') {
            return '';
        }
        try {
            return encryption::decrypt($value);
        } catch (\Throwable $e) {
            // Stored value could not be decrypted (e.g. key rotated
            // externally). Treat as empty rather than fatally erroring.
            return '';
        }
    }

    /**
     * Checks a host against the optional admin allow-list.
     *
     * @param string $host
     * @return bool true if allowed (or no allow-list is configured).
     */
    public static function is_host_allowed(string $host): bool {
        $raw = trim((string) get_config('local_emailclient', 'allowedimaphosts'));
        if ($raw === '') {
            return true;
        }

        $allowed = array_filter(array_map('trim', explode("\n", $raw)));
        if (empty($allowed)) {
            return true;
        }

        foreach ($allowed as $allowedhost) {
            if (strcasecmp($allowedhost, $host) === 0) {
                return true;
            }
        }
        return false;
    }
}
