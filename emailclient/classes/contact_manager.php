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

defined('MOODLE_INTERNAL') || die();

/**
 * CRUD operations for the address book.
 *
 * A contact can be personal (shared=0, visible only to the owner) or shared
 * (shared=1, visible to every user with local/emailclient:use). Whether
 * shared contacts are enabled at all is controlled by the admin setting
 * local_emailclient/allowsharedcontacts.
 *
 * @package     local_emailclient
 * @copyright   2026 Your Organisation
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class contact_manager {

    /** @var string DB table name. */
    const TABLE = 'local_emailclient_contacts';

    /**
     * Returns all contacts visible to a user: their own personal contacts
     * plus shared contacts (if shared contacts are enabled in settings).
     * Results are sorted by lastname, firstname.
     *
     * @param int $userid
     * @return array of stdClass records
     */
    public static function get_all_for_user(int $userid): array {
        global $DB;

        $shared = get_config('local_emailclient', 'allowsharedcontacts');

        if ($shared) {
            $records = $DB->get_records_select(
                self::TABLE,
                'userid = :uid OR shared = 1',
                ['uid' => $userid],
                'shared ASC, lastname ASC, firstname ASC'
            );
        } else {
            $records = $DB->get_records(
                self::TABLE,
                ['userid' => $userid],
                'lastname ASC, firstname ASC'
            );
        }
        return array_values($records);
    }

    /**
     * Returns a single contact record, or null if not found / not accessible.
     *
     * @param int $id contact id
     * @param int $userid requesting user id
     * @return stdClass|null
     */
    public static function get(int $id, int $userid): ?\stdClass {
        global $DB;
        $record = $DB->get_record(self::TABLE, ['id' => $id]);
        if (!$record) {
            return null;
        }
        // Accessible if own, or shared (and shared contacts are enabled).
        if ((int) $record->userid === $userid) {
            return $record;
        }
        if ($record->shared && get_config('local_emailclient', 'allowsharedcontacts')) {
            return $record;
        }
        return null;
    }

    /**
     * Creates or updates a contact.
     *
     * @param int      $userid Owner (for new contacts) or current user (for edits).
     * @param \stdClass $data  Form data; must contain firstname, lastname, email.
     *                         If $data->id is set and non-zero, the existing record
     *                         is updated (only if the user is the owner).
     * @return int             ID of the saved contact.
     * @throws \moodle_exception if user tries to edit someone else's contact.
     */
    public static function save(int $userid, \stdClass $data): int {
        global $DB;

        $now = time();

        if (!empty($data->id)) {
            $existing = $DB->get_record(self::TABLE, ['id' => $data->id], '*', MUST_EXIST);
            if ((int) $existing->userid !== $userid) {
                throw new \moodle_exception('error:nopermission', 'local_emailclient');
            }
            $record = clone $existing;
            $record->shared       = (int) !empty($data->shared);
            $record->firstname    = $data->firstname;
            $record->lastname     = $data->lastname;
            $record->email        = $data->email;
            $record->phone        = $data->phone ?? '';
            $record->organisation = $data->organisation ?? '';
            $record->notes        = $data->notes ?? '';
            $record->timemodified = $now;
            $DB->update_record(self::TABLE, $record);
            return (int) $record->id;
        }

        $record = new \stdClass();
        $record->userid       = $userid;
        $record->shared       = (int) !empty($data->shared);
        $record->firstname    = $data->firstname;
        $record->lastname     = $data->lastname;
        $record->email        = $data->email;
        $record->phone        = $data->phone ?? '';
        $record->organisation = $data->organisation ?? '';
        $record->notes        = $data->notes ?? '';
        $record->timecreated  = $now;
        $record->timemodified = $now;
        return (int) $DB->insert_record(self::TABLE, $record);
    }

    /**
     * Deletes a contact. Only the owner may delete.
     *
     * @param int $id
     * @param int $userid
     * @return void
     * @throws \moodle_exception
     */
    public static function delete(int $id, int $userid): void {
        global $DB;
        $record = $DB->get_record(self::TABLE, ['id' => $id]);
        if (!$record) {
            return;
        }
        if ((int) $record->userid !== $userid) {
            throw new \moodle_exception('error:nopermission', 'local_emailclient');
        }
        $DB->delete_records(self::TABLE, ['id' => $id]);
    }

    /**
     * Returns contacts matching a search string (for autocomplete).
     * Searches firstname, lastname and email.
     *
     * @param int    $userid
     * @param string $query  partial name or e-mail
     * @return array of {name, email} objects, max 20 results
     */
    public static function search(int $userid, string $query): array {
        global $DB;

        $query = trim($query);
        if ($query === '') {
            return [];
        }

        $like  = $DB->sql_like_escape($query);
        $all   = self::get_all_for_user($userid);
        $lower = mb_strtolower($query, 'UTF-8');

        $results = [];
        foreach ($all as $c) {
            $haystack = mb_strtolower($c->firstname . ' ' . $c->lastname . ' ' . $c->email, 'UTF-8');
            if (mb_strpos($haystack, $lower) !== false) {
                $name = trim($c->firstname . ' ' . $c->lastname);
                $results[] = (object) [
                    'id'    => (int) $c->id,
                    'name'  => $name,
                    'email' => $c->email,
                    'label' => $name !== '' ? $name . ' <' . $c->email . '>' : $c->email,
                ];
            }
            if (count($results) >= 20) {
                break;
            }
        }
        return $results;
    }
}
