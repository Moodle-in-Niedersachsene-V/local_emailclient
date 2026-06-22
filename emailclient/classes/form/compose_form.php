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

/**
 * Form used to compose a new message, reply or forward.
 *
 * @package     local_emailclient
 * @copyright   2026 Your Organisation
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class compose_form extends moodleform {

    /**
     * @inheritDoc
     */
    protected function definition(): void {
        $mform = $this->_form;
        $maxbytes = (int) $this->_customdata['maxbytes'];

        $mform->addElement('text', 'to', get_string('compose:to', 'local_emailclient'), ['size' => 60]);
        $mform->setType('to', PARAM_RAW_TRIMMED);
        $mform->addRule('to', get_string('required'), 'required', null, 'client');

        $mform->addElement('text', 'cc', get_string('compose:cc', 'local_emailclient'), ['size' => 60]);
        $mform->setType('cc', PARAM_RAW_TRIMMED);

        $mform->addElement('text', 'bcc', get_string('compose:bcc', 'local_emailclient'), ['size' => 60]);
        $mform->setType('bcc', PARAM_RAW_TRIMMED);

        $mform->addElement('text', 'subject', get_string('compose:subject', 'local_emailclient'), ['size' => 60]);
        $mform->setType('subject', PARAM_TEXT);
        $mform->addRule('subject', get_string('required'), 'required', null, 'client');

        $editoroptions = [
            'maxfiles'  => 0,
            'maxbytes'  => $maxbytes,
            'trusttext' => false,
            'noclean'   => false,
        ];
        $mform->addElement('editor', 'message', get_string('compose:message', 'local_emailclient'), null, $editoroptions);
        $mform->setType('message', PARAM_RAW);
        $mform->addRule('message', get_string('required'), 'required', null, 'client');

        $filemanageroptions = [
            'subdirs'        => 0,
            'maxfiles'       => 20,
            'maxbytes'       => $maxbytes,
            'areamaxbytes'   => $maxbytes,
            'accepted_types' => '*',
        ];
        $mform->addElement(
            'filemanager',
            'attachments',
            get_string('compose:attachments', 'local_emailclient'),
            null,
            $filemanageroptions
        );

        // Carried over between requests so we can build proper threading
        // headers and route the send action correctly.
        $mform->addElement('hidden', 'action');
        $mform->setType('action', PARAM_ALPHA);
        $mform->addElement('hidden', 'origfolder');
        $mform->setType('origfolder', PARAM_RAW);
        $mform->addElement('hidden', 'origuid');
        $mform->setType('origuid', PARAM_INT);
        $mform->addElement('hidden', 'inreplyto');
        $mform->setType('inreplyto', PARAM_RAW);

        $this->add_action_buttons(true, get_string('compose:send', 'local_emailclient'));
    }

    /**
     * @inheritDoc
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);

        $recipients = preg_split('/[,;]+/', $data['to'] ?? '');
        $hasvalid = false;
        foreach ($recipients as $addr) {
            if (validate_email(trim($addr))) {
                $hasvalid = true;
                break;
            }
        }
        if (!$hasvalid) {
            $errors['to'] = get_string('compose:invalidrecipient', 'local_emailclient');
        }

        return $errors;
    }
}
