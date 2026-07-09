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
 * Hook callbacks for local_emailclient.
 *
 * Replaces the legacy local_emailclient_before_http_headers() function in
 * lib.php which generated a debugging notice in Moodle 5.x. Registered
 * via db/hooks.php.
 *
 * @package     local_emailclient
 * @copyright   2026 Your Organisation
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hook_callbacks {

    /**
     * Injects the plugin link into $CFG->custommenuitems so it appears in
     * Boost's primary navigation bar (top tab row) for eligible users.
     *
     * Called by Moodle's hook dispatcher before HTTP headers are sent,
     * which is the correct point to modify $CFG for output.
     *
     * @param \core\hook\output\before_http_headers $hook
     * @return void
     */
    public static function before_http_headers(\core\hook\output\before_http_headers $hook): void {
        global $CFG;

        if (!page_helper::has_access()) {
            return;
        }

        $label = get_string('pluginname', 'local_emailclient');
        $url   = (new \moodle_url('/local/emailclient/index.php'))->out(false);

        $inboxlabel   = get_string('folders:inbox',   'local_emailclient');
        $clabel       = get_string('page:contacts',   'local_emailclient');
        $curl         = (new \moodle_url('/local/emailclient/contacts.php'))->out(false);

        // In Boost, a parent item with sub-items becomes a dropdown toggle
        // and is no longer directly clickable. We therefore add "Posteingang"
        // as the first sub-item so the mailbox is reachable from the dropdown.
        $block = "{$label}|{$url}\n-{$inboxlabel}|{$url}\n-{$clabel}|{$curl}\n";

        $CFG->custommenuitems = $block . ($CFG->custommenuitems ?? '');
    }
}
