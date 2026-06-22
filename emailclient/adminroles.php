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
 * Admin page: which Moodle roles may use the e-mail client.
 *
 * Reads and writes the capability local/emailclient:use directly on the
 * system context, so the change takes effect immediately site-wide without
 * having to visit the general role management interface.
 *
 * @package     local_emailclient
 * @copyright   2026 Your Organisation
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

require_login();
$context = context_system::instance();
require_capability('moodle/site:config', $context);

$PAGE->set_url(new moodle_url('/local/emailclient/adminroles.php'));
$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('page:adminroles', 'local_emailclient'));
$PAGE->set_heading(get_string('pluginname', 'local_emailclient'));

// Settings breadcrumb.
$PAGE->navbar->add(
    get_string('administrationsite'),
    new moodle_url('/admin/index.php')
);
$PAGE->navbar->add(
    get_string('pluginname', 'local_emailclient'),
    new moodle_url('/local/emailclient/adminroles.php')
);

$capability = 'local/emailclient:use';

// Handle form submission.
if (data_submitted() && confirm_sesskey()) {
    // IDs of roles the admin wants to allow.
    $allowedroleids = optional_param_array('allowedroles', [], PARAM_INT);

    $allroles = get_all_roles();
    foreach ($allroles as $role) {
        if (in_array($role->id, $allowedroleids)) {
            // Grant the capability for this role at system context.
            assign_capability($capability, CAP_ALLOW, $role->id, $context->id, true);
        } else {
            // Remove any explicit grant (role falls back to its default).
            unassign_capability($capability, $role->id, $context->id);
        }
    }

    redirect(
        $PAGE->url,
        get_string('settings:rolessaved', 'local_emailclient'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

// Fetch all roles with localised names and current capability status.
$allroles     = role_fix_names(get_all_roles(), $context, ROLENAME_ORIGINAL);
$allowedroles = get_roles_with_capability($capability, CAP_ALLOW, $context);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('page:adminroles', 'local_emailclient'));

echo html_writer::tag('p', get_string('adminroles:desc', 'local_emailclient'));

echo html_writer::start_tag('form', [
    'method' => 'post',
    'action' => $PAGE->url->out(false),
    'class'  => 'mform',
]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);

echo html_writer::start_tag('table', ['class' => 'table generaltable']);
echo html_writer::start_tag('thead');
echo html_writer::start_tag('tr');
echo html_writer::tag('th', get_string('adminroles:role', 'local_emailclient'));
echo html_writer::tag('th', get_string('adminroles:access', 'local_emailclient'));
echo html_writer::tag('th', get_string('adminroles:roletype', 'local_emailclient'));
echo html_writer::end_tag('tr');
echo html_writer::end_tag('thead');
echo html_writer::start_tag('tbody');

// Sort: system roles first (archetype), then by name.
usort($allroles, function($a, $b) {
    $order = ['guest' => 0, 'user' => 1, 'student' => 2, 'teacher' => 3,
              'editingteacher' => 4, 'manager' => 5, 'coursecreator' => 6];
    $ai = $order[$a->archetype] ?? 99;
    $bi = $order[$b->archetype] ?? 99;
    return $ai !== $bi ? $ai - $bi : strcmp($a->localname, $b->localname);
});

foreach ($allroles as $role) {
    $checked  = isset($allowedroles[$role->id]);
    $archname = $role->archetype
        ? get_string('archetype_' . $role->archetype, 'role')
        : get_string('adminroles:customrole', 'local_emailclient');

    $checkboxattrs = [
        'type'  => 'checkbox',
        'name'  => 'allowedroles[]',
        'value' => $role->id,
        'id'    => 'role_' . $role->id,
        'class' => 'form-check-input',
    ];
    if ($checked) {
        $checkboxattrs['checked'] = 'checked';
    }
    $checkbox = html_writer::empty_tag('input', $checkboxattrs);
    $label = html_writer::tag('label', '', ['for' => 'role_' . $role->id]);

    echo html_writer::start_tag('tr');
    echo html_writer::tag('td', s($role->localname));
    echo html_writer::tag('td', $checkbox . $label, ['class' => 'text-center']);
    echo html_writer::tag('td', s($archname));
    echo html_writer::end_tag('tr');
}

echo html_writer::end_tag('tbody');
echo html_writer::end_tag('table');

echo html_writer::tag(
    'button',
    get_string('savechanges'),
    ['type' => 'submit', 'class' => 'btn btn-primary']
);

echo html_writer::end_tag('form');

echo $OUTPUT->footer();
