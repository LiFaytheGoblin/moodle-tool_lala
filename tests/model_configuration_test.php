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
     * Data provider for {@see test_model_configuration_create()}.
     *
     * @return array List of data sets - (string) data set name => (array) data
     */
    public function tool_laaudit_create_provider() {
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
        $validmodelid = $DB->insert_record('analytics_models', $validmodelobject);

        // Get a valid config id.
        $valididconfigobject = [
                'modelid' => $validmodelid,
        ];
        $validconfigid = $DB->insert_record('tool_laaudit_model_configs', $valididconfigobject);

        // Get another model id for a model that will be deleted after creating a config for it.
        $tobedeletedmodelid = $DB->insert_record('analytics_models', $validmodelobject);
        $valididconfigobject2 = [
                'modelid' => $tobedeletedmodelid,
        ];

        // Get another valid config id, but with a model id from a model that will be deleted after creating the config.
        $validconfigid2 = $DB->insert_record('tool_laaudit_model_configs', $valididconfigobject2);
        $DB->delete_records('analytics_models', ['id' => $tobedeletedmodelid]);

        return [
                'validconfigmodelid' => [
                        'configid' => $validconfigid,
                        'modelid' => $validmodelid,
                        'modelname' => $name,
                        'modeltarget' => $target,
                ],
                'validconfigmissingmodelid' => [
                        'country' => $validconfigid2,
                        'modelid' => $tobedeletedmodelid,
                        'modelname' => $name,
                        'modeltarget' => $target,
                ],
        ];
    }
    
    /**
     * Check that __create() creates a model configuration.
     *
     * @covers ::tool_laaudit_model_configuration_create
     *
     * @dataProvider tool_laaudit_create_provider
     * @param int $configid
     * @param int $modelid
     * @param string|null $modelname
     * @param string $modeltarget
     */
    public function test_model_configuration_create(int $configid, int $modelid, ?string $modelname, string $modeltarget) {
        $this->resetAfterTest(true);

        $config = new model_configuration($configid);
        $this->assertEquals($config->get_id(), $configid);
        $this->assertEquals($config->get_modelid(), $modelid);
        if (isset($modelname)) {
            $this->assertEquals($config->get_modelname(), $modelname);
        }
        $this->assertEquals($config->get_modeltarget(), $modeltarget);
    }

    /**
     * Check that __create() throws an error if the provided config id does not exist in tool_laaudit_model_configs.
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
