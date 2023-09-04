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

/**
 * Select context(s) form.
 *
 * @package     tool_lala
 * @copyright   2023 Linda Fernsel <fernsel@htw-berlin.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_lala\output\form;

use Exception;
use tool_lala\dataset_helper;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/lib/formslib.php');

/**
 * Model edit form.
 *
 * @package   tool_analytics
 * @copyright 2017 David Monllao {@link http://www.davidmonllao.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class upload_dataset extends \moodleform {

    /**
     * Form definition
     */
    public function definition() {
        $mform = $this->_form;

        // Copied and adapted from https://github.com/moodle/moodle/blob/master/admin/tool/analytics/classes/output/form/edit_model.php
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

        $mform->addElement('hidden', 'action', 'edit');
        $mform->setType('action', PARAM_ALPHANUMEXT);

        $this->add_action_buttons(false, get_string('savefile', 'tool_lala'));
    }

    /**
     * Form validation
     *
     * @param array $data data from the form.
     * @param array $files files uploaded.
     *
     * @return array of errors.
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if (!isset($this->_customdata['versionid'])) {
            throw new \LogicException('The id of the model version needs to be passed to the form as \'versionid\'');
        }

        // Load the CSV into a nested array.
        $tmpfilepath = $files['dataset'];
        $filehandle = fopen($tmpfilepath, 'r');
        $content = [];
        while ($row = fgetcsv($filehandle)) {
            $content[] = $row;
        }
        fclose($filehandle);

        // Validate the content.
        try {
            $dataset = dataset_helper::build_from_csv_file_content($content);
            dataset_helper::validate($dataset);
        } catch (Exception $e) {
            $errors['dataset'] = $e->getMessage();
        }

        return $errors;
    }
}