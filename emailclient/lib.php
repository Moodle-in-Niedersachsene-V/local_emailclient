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
 * This file is intentionally kept small. All real logic lives in
 * autoloaded classes under classes/.
 *
 * @package     local_emailclient
 * @copyright   2026 Your Organisation
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Adds the plugin's entry point to the main Moodle navigation, so that
 * it shows up as its own menu item once a user is logged in (and not
 * a guest) and has the required capability.
 *
 * @param global_navigation $navigation
 * @return void
 */
function local_emailclient_extend_navigation(global_navigation $navigation): void {
    if (!isloggedin() || isguestuser()) {
        return;
    }

    $context = context_system::instance();
    if (!has_capability('local/emailclient:use', $context)) {
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

    // Attach at the top level so it is visible regardless of which
    // page/context the user is currently viewing.
    $navigation->add_node($node);
}

/**
 * Injects the plugin link into Moodle's custom menu items so it appears
 * in the primary navigation bar (top bar in Boost) for eligible users.
 *
 * before_http_headers() is called by Moodle core on every page request,
 * before output starts, making it the correct place to modify $CFG for
 * local plugins. We prepend our entry to $CFG->custommenuitems; Boost
 * renders these as top-navigation tabs alongside Dashboard / My courses.
 *
 * @return void
 */
function local_emailclient_before_http_headers(): void {
    global $CFG;

    // Reuse the same two-step access check as the page scripts so that
    // the menu item appears for course-level trainers as well.
    if (!\local_emailclient\page_helper::has_access()) {
        return;
    }

    $label = get_string('pluginname', 'local_emailclient');
    $url   = (new moodle_url('/local/emailclient/index.php'))->out(false);
    $entry = "{$label}|{$url}\n";

    // Prepend to the existing custom menu items so we don't overwrite
    // entries the site administrator has configured manually.
    $CFG->custommenuitems = $entry . ($CFG->custommenuitems ?? '');
}
