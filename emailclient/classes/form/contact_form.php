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

/**
 * Form for creating / editing an address book contact.
 *
 * @package     local_emailclient
 * @copyright   2026 Your Organisation
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class contact_form extends \moodleform {

    protected function definition(): void {
        $mform   = $this->_form;
        $shared  = (bool) get_config('local_emailclient', 'allowsharedcontacts');

        $mform->addElement('text', 'firstname', get_string('contact:firstname', 'local_emailclient'), ['size' => 40]);
        $mform->setType('firstname', PARAM_TEXT);

        $mform->addElement('text', 'lastname', get_string('contact:lastname', 'local_emailclient'), ['size' => 40]);
        $mform->setType('lastname', PARAM_TEXT);
        $mform->addRule('lastname', get_string('required'), 'required', null, 'client');

        $mform->addElement('text', 'email', get_string('contact:email', 'local_emailclient'), ['size' => 50]);
        $mform->setType('email', PARAM_EMAIL);
        $mform->addRule('email', get_string('required'), 'required', null, 'client');

        $mform->addElement('text', 'phone', get_string('contact:phone', 'local_emailclient'), ['size' => 25]);
        $mform->setType('phone', PARAM_TEXT);

        $mform->addElement('text', 'organisation', get_string('contact:organisation', 'local_emailclient'), ['size' => 50]);
        $mform->setType('organisation', PARAM_TEXT);

        $mform->addElement('textarea', 'notes', get_string('contact:notes', 'local_emailclient'), ['rows' => 3, 'cols' => 50]);
        $mform->setType('notes', PARAM_TEXT);

        if ($shared) {
            $mform->addElement('advcheckbox', 'shared', '', get_string('contact:shared', 'local_emailclient'));
            $mform->addHelpButton('shared', 'contact:shared', 'local_emailclient');
        }

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $this->add_action_buttons(true, get_string('contact:save', 'local_emailclient'));
    }

    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);
        if (empty($data['email']) || !validate_email($data['email'])) {
            $errors['email'] = get_string('invalidemail');
        }
        if (empty($data['lastname']) && empty($data['firstname'])) {
            $errors['lastname'] = get_string('required');
        }
        return $errors;
    }
}
