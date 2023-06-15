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

namespace tool_laaudit;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/admin/tool/laaudit/classes/model_configuration.php');

/**
 * Metadata registry tests.
 *
 * @package     tool_laaudit
 * @copyright   2023 Linda Fernsel <fernsel@htw-berlin.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class model_configuration_test extends \advanced_testcase {
    /**
     * Check that __create() throws an error if the provided config id does not exist in tool_laaudit_model_configs.
     */
    public function test_do_not_create_model_configuration_from_nonexistant_id() {
        global $DB;

        # Select a non-existing id
        $existingids = $DB->get_fieldset_select('tool_laaudit_model_configs', 'id', '1=1');
        $nonexistantid = (sizeof($existingids) > 0) ? (max($existingids) + 1) : 1;

        $this->expectException(\dml_missing_record_exception::class);

        new model_configuration($nonexistantid);
    }


}
