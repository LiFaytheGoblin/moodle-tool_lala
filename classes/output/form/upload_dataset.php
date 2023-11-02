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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace tool_lala\output\form;

defined('MOODLE_INTERNAL') || die();

use moodleform;

require_once($CFG->dirroot.'/lib/formslib.php');

/**
 * Upload dataset evidence form.
 *
 * @package     tool_lala
 * @copyright   2023 Linda Fernsel <fernsel@htw-berlin.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class upload_dataset extends moodleform {

    /**
     * Form definition
     *
     * @return void
     */
    public function definition() {
        $mform = $this->_form;

        $options = [
                'maxfiles' => 1,
                'accepted_types' => ['.csv'],
        ];
        $mform->addElement(
                'filemanager',
                'dataset',
                get_string('file'),
                null,
                $options
        );

        $mform->addElement('hidden', 'versionid', $this->_customdata['versionid']);
        $mform->setType('versionid', PARAM_INT);

        $mform->addElement('hidden', 'configid', $this->_customdata['configid']);
        $mform->setType('configid', PARAM_INT);

        // For now, after setting a context, always continue with the automatic version creation.
        $mform->addElement('hidden', 'manual', false);
        $mform->setType('manual', PARAM_ALPHANUMEXT);

        $this->add_action_buttons(false, get_string('savefile', 'tool_lala'));
    }

    /**
     * Form validation. This does not appear to be executed, therefore the content validation happens in model_version.
     *
     * @param array $data data from the form.
     * @param array $files files uploaded.
     * @return array of errors.
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if (!isset($this->_customdata['versionid'])) {
            throw new \LogicException('The id of the model version needs to be passed to the form as \'versionid\'');
        }

        if (!isset($this->_customdata['configid'])) {
            throw new \LogicException('The id of the model configuration needs to be passed to the form as \'configid\'');
        }

        return $errors;
    }
}
