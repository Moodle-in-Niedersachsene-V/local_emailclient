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
 * Set up / edit the personal IMAP/SMTP account configuration.
 *
 * @package     local_emailclient
 * @copyright   2026 Your Organisation
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

use local_emailclient\account_manager;
use local_emailclient\imap_client;
use local_emailclient\mail_sender;
use local_emailclient\page_helper;
use local_emailclient\form\account_form;

page_helper::require_access();

$context = context_system::instance();
$PAGE->set_url(new moodle_url('/local/emailclient/account.php'));
$PAGE->set_context($context);
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('page:account', 'local_emailclient'));
$PAGE->set_heading(get_string('pluginname', 'local_emailclient'));

$delete = optional_param('delete', 0, PARAM_BOOL);
$confirm = optional_param('confirm', 0, PARAM_BOOL);

if ($delete) {
    require_sesskey();
    if ($confirm) {
        account_manager::delete($USER->id);
        redirect(
            new moodle_url('/local/emailclient/account.php'),
            get_string('account:deleted', 'local_emailclient'),
            null,
            \core\output\notification::NOTIFY_SUCCESS
        );
    }
    echo $OUTPUT->header();
    echo $OUTPUT->confirm(
        get_string('account:deleteconfirm', 'local_emailclient'),
        new moodle_url('/local/emailclient/account.php', ['delete' => 1, 'confirm' => 1, 'sesskey' => sesskey()]),
        new moodle_url('/local/emailclient/account.php')
    );
    echo $OUTPUT->footer();
    exit;
}

$existing = account_manager::get_for_user($USER->id);
$hasaccount = $existing !== null;

$mform = new account_form(new moodle_url('/local/emailclient/account.php'), ['hasaccount' => $hasaccount]);

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/local/emailclient/index.php'));

} else if ($data = $mform->get_data()) {

    if (!empty($data->samelogindata)) {
        $data->smtphost = $data->imaphost;
        $data->smtpport = $data->imapport;
        $data->smtpsecurity = $data->imapsecurity;
        $data->smtpusername = $data->imapusername;
        $data->smtppassword = $data->imappassword;
    }

    if (!empty($data->testconnection)) {
        // Build a temporary, fully resolved account object for testing
        // (falling back to the already-stored password when the field was
        // left blank, so the user does not have to retype it just to test).
        $testaccount = new stdClass();
        $testaccount->imaphost     = $data->imaphost;
        $testaccount->imapport     = $data->imapport;
        $testaccount->imapsecurity = $data->imapsecurity;
        $testaccount->imapusername = $data->imapusername;
        $testaccount->imappassword = $data->imappassword !== '' ? $data->imappassword : ($existing->imappassword ?? '');
        $testaccount->smtphost     = $data->smtphost;
        $testaccount->smtpport     = $data->smtpport;
        $testaccount->smtpsecurity = $data->smtpsecurity;
        $testaccount->smtpusername = $data->smtpusername;
        $testaccount->smtppassword = $data->smtppassword !== '' ? $data->smtppassword : ($existing->smtppassword ?? '');

        $messages = [];
        $allok = true;
        try {
            (new imap_client($testaccount))->test_connection();
            $messages[] = 'IMAP: ' . get_string('account:testok', 'local_emailclient', $testaccount->imaphost);
        } catch (Throwable $e) {
            $allok = false;
            $messages[] = 'IMAP: ' . get_string('account:testfailed', 'local_emailclient', $e->getMessage());
        }
        try {
            (new mail_sender($testaccount))->test_connection();
            $messages[] = 'SMTP: ' . get_string('account:testok', 'local_emailclient', $testaccount->smtphost);
        } catch (Throwable $e) {
            $allok = false;
            $messages[] = 'SMTP: ' . get_string('account:testfailed', 'local_emailclient', $e->getMessage());
        }

        echo $OUTPUT->header();
        foreach ($messages as $message) {
            echo $OUTPUT->notification($message, $allok ? 'success' : 'error');
        }
        $mform->set_data($data);
        $mform->display();
        echo $OUTPUT->footer();
        exit;
    }

    account_manager::save($USER->id, $data);
    redirect(
        new moodle_url('/local/emailclient/index.php'),
        get_string('account:saved', 'local_emailclient'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );

} else if ($existing) {
    $prefill = clone $existing;
    $prefill->imappassword = '';
    $prefill->smtppassword = '';
    $prefill->samelogindata = (
        $existing->imaphost === $existing->smtphost
        && $existing->imapusername === $existing->smtpusername
    ) ? 1 : 0;
    $mform->set_data($prefill);
} else {
    // No account yet: pre-fill with admin-configured server defaults so
    // users only have to enter their personal username and password.
    $cfg = get_config('local_emailclient');
    $prefill = new stdClass();
    $prefill->imaphost     = $cfg->default_imaphost     ?? '';
    $prefill->imapport     = $cfg->default_imapport     ?? 993;
    $prefill->imapsecurity = $cfg->default_imapsecurity ?? 'ssl';
    $prefill->smtphost     = $cfg->default_smtphost     ?? '';
    $prefill->smtpport     = $cfg->default_smtpport     ?? 587;
    $prefill->smtpsecurity = $cfg->default_smtpsecurity ?? 'tls';
    // If IMAP and SMTP host are the same (or SMTP not set), tick "same login".
    $prefill->samelogindata = (
        $prefill->smtphost === '' || $prefill->smtphost === $prefill->imaphost
    ) ? 1 : 0;
    if ($mform->get_data() === null) {
        $mform->set_data($prefill);
    }
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('page:account', 'local_emailclient'));

if (!$hasaccount) {
    echo $OUTPUT->notification(get_string('account:nosettings', 'local_emailclient'), 'info');
}

$mform->display();

if ($hasaccount) {
    $deleteurl = new moodle_url('/local/emailclient/account.php', ['delete' => 1, 'sesskey' => sesskey()]);
    echo html_writer::div(
        html_writer::link($deleteurl, get_string('account:delete', 'local_emailclient'), ['class' => 'btn btn-link text-danger']),
        'mt-3'
    );
}

echo $OUTPUT->footer();
