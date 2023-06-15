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
 * Model configuration get_or_create_and_get_for_model() test.
 *
 * @package     tool_laaudit
 * @copyright   2023 Linda Fernsel <fernsel@htw-berlin.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class model_configuration_get_or_create_and_get_for_model_test extends \advanced_testcase {
    /**
     * Data provider for {@see test_model_configuration_get_or_create_and_get_for_model()}.
     *
     * @return array List of data sets - (string) data set name => (array) data
     */
    public function tool_laaudit_get_or_create_and_get_for_model_provider() {
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
        $existingconfigid = $DB->insert_record('tool_laaudit_model_configs', $valididconfigobject);

        // Get a valid model id but no existing model config.
        $validmodelid2 = $DB->insert_record('analytics_models', $validmodelobject);



        // Get a model id and model config, then delete the model id.
        $tobedeletedmodelid = $DB->insert_record('analytics_models', $validmodelobject);
        $valididconfigobject2 = [
                'modelid' => $tobedeletedmodelid,
        ];
        $existingconfigid2 = $DB->insert_record('tool_laaudit_model_configs', $valididconfigobject2);

        // Store existing modelids for later, before deleting the last one
        $existingmodelids = $DB->get_fieldset_select('analytics_models', 'id', '1=1');
        $DB->delete_records('analytics_models', ['id' => $tobedeletedmodelid]);

        // Get a valid config id but with a non-existing model.
        $falsemodelid = max($existingmodelids) + 1;
        $invalididconfigobject = [
                'modelid' => $falsemodelid,
        ];
        $existingconfigid3 = $DB->insert_record('tool_laaudit_model_configs', $invalididconfigobject);

        return [
                'existingconfigexistingmodel' => [
                        'modelid' => $validmodelid,
                        'configid' => $existingconfigid,
                        'throws' => false,
                ],
                'newconfigexistingmodel' => [
                        'modelid' => $validmodelid2,
                        'configid' => null,
                        'throws' => false,
                ],
                'newconfigfalsemodel' => [
                        'modelid' => $falsemodelid,
                        'configid' => $existingconfigid3,
                        'throws' => true,
                ],
                'existingconfigdeletedmodel' => [
                        'modelid' => $tobedeletedmodelid,
                        'configid' => $existingconfigid2,
                        'throws' => false,
                ],
        ];
    }

    /**
     * Check that get_model_config_obj() returns all the necessary properties
     *
     * @covers ::tool_laaudit_model_configuration_get_or_create_and_get_for_model
     *
     * @dataProvider tool_laaudit_get_or_create_and_get_for_model_provider
     * @param int $modelid
     * @param int|null $configid
     */
    public function test_model_configuration_get_or_create_and_get_for_model(int $modelid, ?int $configid, bool $throws) {
        $this->resetAfterTest(true);
        global $DB;

        if ($throws) {
            $this->expectException(\Exception::class); // No config is referenced or created.
            model_configuration::get_or_create_and_get_for_model($modelid);
        } else if (isset($configid)) {
            $returnedconfigid = model_configuration::get_or_create_and_get_for_model($modelid);
            $this->assertEquals($configid, $returnedconfigid); // An existing config is referenced.
        } else {
            $existingconfigids = $DB->get_fieldset_select('tool_laaudit_model_configs', 'id', '1=1');
            $maxidbeforenewconfigcreation = (sizeof($existingconfigids) > 0) ? max($existingconfigids) : 0;
            $returnedconfigid = model_configuration::get_or_create_and_get_for_model($modelid);
            $this->assertGreaterThan($maxidbeforenewconfigcreation, $returnedconfigid); // A new config has been created and is referenced.
        }
    }
}
