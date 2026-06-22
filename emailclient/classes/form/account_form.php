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

namespace local_emailclient\form;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');

use moodleform;
use local_emailclient\account_manager;

/**
 * Form used to create/edit a user's personal IMAP/SMTP account configuration.
 *
 * @package     local_emailclient
 * @copyright   2026 Your Organisation
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class account_form extends moodleform {

    /**
     * @inheritDoc
     */
    protected function definition(): void {
        $mform = $this->_form;
        $hasaccount = !empty($this->_customdata['hasaccount']);

        // Incoming mail (IMAP).
        $mform->addElement('header', 'imapheader', get_string('account:imapsection', 'local_emailclient'));
        $mform->setExpanded('imapheader');

        $mform->addElement('text', 'imaphost', get_string('account:imaphost', 'local_emailclient'), ['size' => 40]);
        $mform->setType('imaphost', PARAM_RAW_TRIMMED);
        $mform->addRule('imaphost', get_string('required'), 'required', null, 'client');

        $mform->addElement('text', 'imapport', get_string('account:imapport', 'local_emailclient'), ['size' => 6]);
        $mform->setType('imapport', PARAM_INT);
        $mform->setDefault('imapport', 993);
        $mform->addRule('imapport', get_string('required'), 'required', null, 'client');

        $securityoptions = [
            'ssl'  => get_string('account:security_ssl', 'local_emailclient'),
            'tls'  => get_string('account:security_tls', 'local_emailclient'),
            'none' => get_string('account:security_none', 'local_emailclient'),
        ];
        $mform->addElement('select', 'imapsecurity', get_string('account:imapsecurity', 'local_emailclient'), $securityoptions);
        $mform->setDefault('imapsecurity', 'ssl');

        $mform->addElement('text', 'imapusername', get_string('account:imapusername', 'local_emailclient'), ['size' => 40]);
        $mform->setType('imapusername', PARAM_RAW_TRIMMED);
        $mform->addRule('imapusername', get_string('required'), 'required', null, 'client');

        $mform->addElement('passwordunmask', 'imappassword', get_string('account:imappassword', 'local_emailclient'), ['size' => 40]);
        $mform->setType('imappassword', PARAM_RAW);
        if ($hasaccount) {
            $mform->addElement('static', 'imappasswordhint', '', get_string('account:passwordkepthint', 'local_emailclient'));
        }

        // Outgoing mail (SMTP).
        $mform->addElement('header', 'smtpheader', get_string('account:smtpsection', 'local_emailclient'));
        $mform->setExpanded('smtpheader');

        $mform->addElement('advcheckbox', 'samelogindata', '', get_string('account:samelogindata', 'local_emailclient'));
        $mform->setDefault('samelogindata', 1);

        $mform->addElement('text', 'smtphost', get_string('account:smtphost', 'local_emailclient'), ['size' => 40]);
        $mform->setType('smtphost', PARAM_RAW_TRIMMED);

        $mform->addElement('text', 'smtpport', get_string('account:smtpport', 'local_emailclient'), ['size' => 6]);
        $mform->setType('smtpport', PARAM_INT);
        $mform->setDefault('smtpport', 587);

        $smtpsecurityoptions = [
            'tls'  => get_string('account:security_tls', 'local_emailclient'),
            'ssl'  => get_string('account:security_ssl', 'local_emailclient'),
            'none' => get_string('account:security_none', 'local_emailclient'),
        ];
        $mform->addElement('select', 'smtpsecurity', get_string('account:smtpsecurity', 'local_emailclient'), $smtpsecurityoptions);
        $mform->setDefault('smtpsecurity', 'tls');

        $mform->addElement('text', 'smtpusername', get_string('account:smtpusername', 'local_emailclient'), ['size' => 40]);
        $mform->setType('smtpusername', PARAM_RAW_TRIMMED);

        $mform->addElement('passwordunmask', 'smtppassword', get_string('account:smtppassword', 'local_emailclient'), ['size' => 40]);
        $mform->setType('smtppassword', PARAM_RAW);
        if ($hasaccount) {
            $mform->addElement('static', 'smtppasswordhint', '', get_string('account:passwordkepthint', 'local_emailclient'));
        }

        // Hide the SMTP-specific fields while "same as IMAP" is ticked.
        $mform->hideIf('smtphost', 'samelogindata', 'checked');
        $mform->hideIf('smtpport', 'samelogindata', 'checked');
        $mform->hideIf('smtpsecurity', 'samelogindata', 'checked');
        $mform->hideIf('smtpusername', 'samelogindata', 'checked');
        $mform->hideIf('smtppassword', 'samelogindata', 'checked');
        if ($hasaccount) {
            $mform->hideIf('smtppasswordhint', 'samelogindata', 'checked');
        }

        // Identity.
        $mform->addElement('header', 'identityheader', get_string('account:identitysection', 'local_emailclient'));
        $mform->setExpanded('identityheader');

        $mform->addElement('text', 'fromname', get_string('account:fromname', 'local_emailclient'), ['size' => 40]);
        $mform->setType('fromname', PARAM_TEXT);

        $mform->addElement('text', 'fromemail', get_string('account:fromemail', 'local_emailclient'), ['size' => 40]);
        $mform->setType('fromemail', PARAM_EMAIL);
        $mform->addRule('fromemail', get_string('required'), 'required', null, 'client');

        $mform->addElement('textarea', 'signature', get_string('account:signature', 'local_emailclient'), ['rows' => 4, 'cols' => 60]);
        $mform->setType('signature', PARAM_TEXT);

        // Buttons: Test connection / Save / Cancel.
        $buttonarray = [];
        $buttonarray[] = $mform->createElement('submit', 'testconnection', get_string('account:testconnection', 'local_emailclient'));
        $buttonarray[] = $mform->createElement('submit', 'submitbutton', get_string('account:save', 'local_emailclient'));
        $buttonarray[] = $mform->createElement('cancel');
        $mform->addGroup($buttonarray, 'buttonar', '', [' '], false);
        $mform->closeHeaderBefore('buttonar');
    }

    /**
     * @inheritDoc
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);
        $hasaccount = !empty($this->_customdata['hasaccount']);

        if (!$hasaccount && empty($data['imappassword'])) {
            $errors['imappassword'] = get_string('required');
        }

        if (empty($data['fromemail']) || !validate_email($data['fromemail'])) {
            $errors['fromemail'] = get_string('invalidemail');
        }

        if (!empty($data['imaphost']) && !account_manager::is_host_allowed($data['imaphost'])) {
            $errors['imaphost'] = get_string('account:hostnotallowed', 'local_emailclient');
        }

        $smtphost = !empty($data['samelogindata']) ? ($data['imaphost'] ?? '') : ($data['smtphost'] ?? '');
        if ($smtphost !== '' && !account_manager::is_host_allowed($smtphost)) {
            $errors['smtphost'] = get_string('account:hostnotallowed', 'local_emailclient');
        }
        if (empty($data['samelogindata']) && empty($data['smtphost'])) {
            $errors['smtphost'] = get_string('required');
        }

        return $errors;
    }
}
