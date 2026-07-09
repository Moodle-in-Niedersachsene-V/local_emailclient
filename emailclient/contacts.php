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
 * Address book: list, add, edit and delete contacts.
 *
 * @package     local_emailclient
 * @copyright   2026 Your Organisation
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

use local_emailclient\contact_manager;
use local_emailclient\page_helper;
use local_emailclient\form\contact_form;

page_helper::require_access();

$id     = optional_param('id', 0, PARAM_INT);
$delete = optional_param('delete', 0, PARAM_INT);

$context = context_system::instance();
$PAGE->set_url(new moodle_url('/local/emailclient/contacts.php'));
$PAGE->set_context($context);
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('page:contacts', 'local_emailclient'));
$PAGE->set_heading(get_string('pluginname', 'local_emailclient'));
$PAGE->requires->css('/local/emailclient/styles.css');

// ── Delete ───────────────────────────────────────────────────────────────────
if ($delete) {
    require_sesskey();
    try {
        contact_manager::delete($delete, $USER->id);
        redirect(new moodle_url('/local/emailclient/contacts.php'),
            get_string('contact:deleted', 'local_emailclient'), null,
            \core\output\notification::NOTIFY_SUCCESS);
    } catch (\Throwable $e) {
        redirect(new moodle_url('/local/emailclient/contacts.php'),
            $e->getMessage(), null, \core\output\notification::NOTIFY_ERROR);
    }
}

// ── Add / Edit form ──────────────────────────────────────────────────────────
$editmode = ($id !== 0);
$formurl  = new moodle_url('/local/emailclient/contacts.php');
$mform    = new contact_form($formurl);

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/local/emailclient/contacts.php'));

} else if ($data = $mform->get_data()) {
    try {
        contact_manager::save($USER->id, $data);
        redirect(new moodle_url('/local/emailclient/contacts.php'),
            get_string('contact:saved', 'local_emailclient'), null,
            \core\output\notification::NOTIFY_SUCCESS);
    } catch (\Throwable $e) {
        echo $OUTPUT->header();
        echo $OUTPUT->notification($e->getMessage(), 'error');
        $mform->display();
        echo $OUTPUT->footer();
        exit;
    }

} else if ($editmode) {
    $record = contact_manager::get($id, $USER->id);
    if (!$record || (int) $record->userid !== $USER->id) {
        // Only owner may edit.
        redirect(new moodle_url('/local/emailclient/contacts.php'),
            get_string('error:nopermission', 'local_emailclient'), null,
            \core\output\notification::NOTIFY_ERROR);
    }
    $mform->set_data($record);
}

// ── Output ───────────────────────────────────────────────────────────────────
echo $OUTPUT->header();

echo html_writer::start_div('row');

// Left: back to inbox + contact list.
echo html_writer::start_div('col-md-7');
echo $OUTPUT->heading(get_string('page:contacts', 'local_emailclient'));
echo html_writer::link(
    new moodle_url('/local/emailclient/index.php'),
    '&laquo; ' . get_string('page:inbox', 'local_emailclient'),
    ['class' => 'btn btn-link mb-2']
);

$contacts = contact_manager::get_all_for_user($USER->id);
$sharedEnabled = !empty(get_config('local_emailclient', 'allowsharedcontacts'));

if (empty($contacts)) {
    echo $OUTPUT->notification(get_string('contact:empty', 'local_emailclient'), 'info');
} else {
    // Personal contacts.
    $personal = array_filter($contacts, fn($c) => (int) $c->userid === (int) $USER->id);
    $shared   = array_filter($contacts, fn($c) => (int) $c->shared === 1 && (int) $c->userid !== (int) $USER->id);

    foreach ([
        [get_string('contact:personal', 'local_emailclient'), $personal],
        [get_string('contact:shared',   'local_emailclient'), $shared],
    ] as [$heading, $list]) {
        if (empty($list)) {
            continue;
        }
        echo $OUTPUT->heading($heading, 4);
        echo html_writer::start_tag('table', ['class' => 'table table-hover table-sm emailclient-contacts']);
        echo html_writer::start_tag('thead');
        echo html_writer::start_tag('tr');
        echo html_writer::tag('th', get_string('contact:name', 'local_emailclient'));
        echo html_writer::tag('th', get_string('contact:email', 'local_emailclient'));
        echo html_writer::tag('th', get_string('contact:organisation', 'local_emailclient'));
        echo html_writer::tag('th', get_string('contact:phone', 'local_emailclient'));
        echo html_writer::tag('th', '');
        echo html_writer::end_tag('tr');
        echo html_writer::end_tag('thead');
        echo html_writer::start_tag('tbody');

        foreach ($list as $c) {
            $name     = trim(s($c->firstname) . ' ' . s($c->lastname));
            $editurl  = new moodle_url('/local/emailclient/contacts.php', ['id' => $c->id]);
            $delurl   = new moodle_url('/local/emailclient/contacts.php',
                ['delete' => $c->id, 'sesskey' => sesskey()]);
            $composeurl = new moodle_url('/local/emailclient/compose.php', ['to' => $c->email]);

            $actions = html_writer::link(
                $composeurl, get_string('contact:compose', 'local_emailclient'),
                ['class' => 'btn btn-sm btn-outline-primary mr-1']
            );
            if ((int) $c->userid === (int) $USER->id) {
                $actions .= html_writer::link($editurl, get_string('edit'), ['class' => 'btn btn-sm btn-outline-secondary mr-1']);
                $actions .= html_writer::link($delurl, get_string('delete'), [
                    'class'   => 'btn btn-sm btn-outline-danger',
                    'onclick' => 'return confirm(' . json_encode(get_string('contact:deleteconfirm', 'local_emailclient')) . ')',
                ]);
            }

            echo html_writer::start_tag('tr');
            echo html_writer::tag('td', $name);
            echo html_writer::tag('td', html_writer::link('mailto:' . s($c->email), s($c->email)));
            echo html_writer::tag('td', s($c->organisation ?? ''));
            echo html_writer::tag('td', s($c->phone ?? ''));
            echo html_writer::tag('td', $actions);
            echo html_writer::end_tag('tr');
        }

        echo html_writer::end_tag('tbody');
        echo html_writer::end_tag('table');
    }
}

echo html_writer::end_div();

// Right: add/edit form.
echo html_writer::start_div('col-md-5 emailclient-contactform');
echo $OUTPUT->heading(
    $editmode ? get_string('contact:edit', 'local_emailclient') : get_string('contact:add', 'local_emailclient'),
    4
);
$mform->display();
echo html_writer::end_div();

echo html_writer::end_div();
echo $OUTPUT->footer();
