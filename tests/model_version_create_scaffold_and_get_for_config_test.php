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
require_once($CFG->dirroot . '/admin/tool/laaudit/classes/model_version.php');

/**
 * Model version create_scaffold_and_get_for_config() test.
 *
 * @package     tool_laaudit
 * @copyright   2023 Linda Fernsel <fernsel@htw-berlin.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class model_version_create_scaffold_and_get_for_config_test extends \advanced_testcase {
    /**
     * Check that create_scaffold_and_get_for_config() references and creates correct model version scaffolds.
     *
     * @covers ::tool_laaudit_model_version_create_scaffold_and_get_for_config
     */
    public function test_model_version_create_scaffold_and_get_for_config() {
        //create_scaffold_and_get_for_config($configid) returns a new model version id
        global $DB;
        // Define some standard model data
        $name = 'testmodel';
        $target = '\core_course\analytics\target\course_dropout';
        // Get a valid model id.
        $validmodelobject = [
                'name' => $name,
                'target' => $target,
                'indicators' => "[\"\\\\core\\\\analytics\\\\indicator\\\\any_access_after_end\"]",
                'timesplitting' => '\core\analytics\time_splitting\deciles',
                'version' => time(),
                'timemodified' => time(),
                'usermodified' => 1,
        ];
        $modelid = $DB->insert_record('analytics_models', $validmodelobject);

        // create a config
        $configid = model_configuration::get_or_create_and_get_for_model($modelid);

        // for valid config & model
        $existingversionids = $DB->get_fieldset_select('tool_laaudit_model_configs', 'id', '1=1');
        $maxidbeforenewversioncreation = (sizeof($existingversionids) > 0) ? max($existingversionids) : 0;
        $versionid = model_version::create_scaffold_and_get_for_config($configid);
        $this->assertGreaterThan($maxidbeforenewversioncreation, $versionid);

        // for valid config & deleted model
        $DB->delete_records('analytics_models', ['id' => $modelid]);
        $versionid2 = model_version::create_scaffold_and_get_for_config($configid);
        $this->assertGreaterThan($maxidbeforenewversioncreation, $versionid2);
    }

    /**
     * Check that create_scaffold_and_get_for_config() throws an error if the provided config id does not exist.
     *
     * @covers ::tool_laaudit_model_version_create_scaffold_and_get_for_config
     */
    public function test_model_version_create_scaffold_and_get_for_config_error() {
        global $DB;
        // for invalid config
        $existingconfigids = $DB->get_fieldset_select('tool_laaudit_model_configs', 'id', '1=1');
        $maxconfigid = (sizeof($existingconfigids) > 0) ? max($existingconfigids) : 0;
        $this->expectException(\Exception::class);
        model_version::create_scaffold_and_get_for_config($maxconfigid + 1);
    }
}
