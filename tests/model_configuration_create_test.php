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
 * Model configuration __create() test.
 *
 * @package     tool_laaudit
 * @copyright   2023 Linda Fernsel <fernsel@htw-berlin.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class model_configuration_create_test extends \advanced_testcase {
    /**
     * Check that __create() creates a model configuration.
     *
     * @covers ::tool_laaudit_model_configuration___create
     */
    public function test_model_configuration_create() {
        $this->resetAfterTest(true);

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

        // Get a valid config id.
        $valididconfigobject = [
                'modelid' => $modelid,
        ];
        $configid = $DB->insert_record('tool_laaudit_model_configs', $valididconfigobject);

        // Create a model configuration from a config with an existing model
        $config = new model_configuration($configid);
        $this->assertEquals($config->get_id(), $configid);
        $this->assertEquals($config->get_modelid(), $modelid);
        $this->assertEquals($config->get_modelname(), $name);
        $this->assertEquals($config->get_modeltarget(), $target);

        // Delete model and create a model configuration from a config with a now deleted model
        $DB->delete_records('analytics_models', ['id' => $modelid]);
        $config2 = new model_configuration($configid);
        $this->assertEquals($config2->get_id(), $configid);
        $this->assertEquals($config2->get_modelid(), $modelid);
    }

    /**
     * Check that __create() throws an error if the provided config id does not exist in tool_laaudit_model_configs.
     *
     * @covers ::tool_laaudit_model_configuration___create
     */
    public function test_model_configuration_create_error() {
        global $DB;
        // Get a non-existing id
        $existingconfigids = $DB->get_fieldset_select('tool_laaudit_model_configs', 'id', '1=1');
        $nonexistantconfigid = (sizeof($existingconfigids) > 0) ? (max($existingconfigids) + 1) : 1;

        $this->expectException(\dml_missing_record_exception::class);

        new model_configuration($nonexistantconfigid);
    }
}
