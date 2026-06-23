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
 * Library callbacks for local_emailclient.
 *
 * Only the extend_navigation() legacy callback remains here (still
 * documented and supported in Moodle 5.x for the nav drawer).
 * The before_http_headers logic has been moved to the proper Hooks API:
 * see classes/hook_callbacks.php and db/hooks.php.
 *
 * @package     local_emailclient
 * @copyright   2026 Your Organisation
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Adds the plugin's entry point to the Moodle navigation drawer.
 *
 * @param global_navigation $navigation
 * @return void
 */
function local_emailclient_extend_navigation(global_navigation $navigation): void {
    if (!isloggedin() || isguestuser()) {
        return;
    }

    if (!\local_emailclient\page_helper::has_access()) {
        return;
    }

    $node = navigation_node::create(
        get_string('pluginname', 'local_emailclient'),
        new moodle_url('/local/emailclient/index.php'),
        navigation_node::TYPE_CUSTOM,
        null,
        'local_emailclient',
        new pix_icon('i/email', '')
    );
    $node->showinflatnavigation = true;

    $navigation->add_node($node);
}
