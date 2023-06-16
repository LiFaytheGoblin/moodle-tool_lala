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
 * Model version __create() test.
 *
 * @package     tool_laaudit
 * @copyright   2023 Linda Fernsel <fernsel@htw-berlin.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class model_version_create_test extends \advanced_testcase {
    /**
     * Check that __create() creates a model version.
     *
     * @covers ::tool_laaudit_model_version___create
     */
    public function test_model_version_create() {
        $this->resetAfterTest(true);

        global $DB;

        // Define some standard model data
        $name = 'testmodel';
        $target = '\core_course\analytics\target\course_dropout';
        $indicators = "[\"\\\\core\\\\analytics\\\\indicator\\\\any_access_after_end\"]";
        $analysisinterval = '\core\analytics\time_splitting\deciles';

        // Get a valid model id.
        $validmodelobject = [
                'name' => $name,
                'target' => $target,
                'indicators' => $indicators,
                'timesplitting' => $analysisinterval,
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

        // Get a valid version id.
        $valididversionobject = [
                'timecreationstarted' => time(),
                'analysisinterval' => $analysisinterval,
                'predictionsprocessor' => '\mlbackend_php\processor',
                'configid' => $configid,
                'indicators' => $indicators,
                'relativetestsetsize' => 0.2,
        ];
        $versionid = $DB->insert_record('tool_laaudit_model_versions', $valididversionobject);

        // Create a model configuration from a config with an existing model
        $version = new model_version($versionid);
        $this->assertEquals($version->get_id(), $versionid);

        // Delete model and create a model configuration from a config with a now deleted model
        $DB->delete_records('analytics_models', ['id' => $modelid]);
        $version2 = new model_version($versionid);
        $this->assertEquals($version2->get_id(), $versionid);
    }

    /**
     * Check that __create() throws an error if the provided version id does not exist in tool_laaudit_model_versions.
     *
     * @covers ::tool_laaudit_model_version___create
     */
    public function test_model_version_create_error() {
        global $DB;
        // Get a non-existing id
        $existingversionids = $DB->get_fieldset_select('tool_laaudit_model_versions', 'id', '1=1');
        $nonexistantversionid = (sizeof($existingversionids) > 0) ? (max($existingversionids) + 1) : 1;

        $this->expectException(\dml_missing_record_exception::class);

        new model_configuration($nonexistantversionid);
    }
}
