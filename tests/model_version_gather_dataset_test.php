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
require_once(__DIR__ . '/fixtures/test_config.php');
require_once(__DIR__ . '/fixtures/test_model.php');
require_once(__DIR__ . '/fixtures/test_version.php');
require_once(__DIR__ . '/fixtures/test_course_with_students.php');

/**
 * Model version gather_dataset() test.
 *
 * @package     tool_laaudit
 * @copyright   2023 Linda Fernsel <fernsel@htw-berlin.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class model_version_gather_dataset_test extends \advanced_testcase {
    /**
     * Check that gather_dataset() sets the $dataset property.
     *
     * @covers ::tool_laaudit_model_version_gather_dataset
     */
    public function test_model_version_gather_dataset() {
        $this->resetAfterTest(true);

        global $DB;

        $modelid = test_model::create();
        $configid = test_config::create($modelid);
        $versionid = test_version::create($configid);

        // Create a model configuration from a config with an existing model.
        $version = new model_version($versionid);

        // Generate test data
        test_course_with_students::create($this->getDataGenerator());

        // Data is available for gathering
        $version->gather_dataset();
        $dataset = $version->get_dataset();
        $this->assertTrue(isset($dataset));
        $error = test_version::haserror($versionid) ; // An error has been registered
        $this->assertFalse($error);

        // Try to gather dataset again, even though it has already been gathered.
        $this->expectException(\moodle_exception::class);
        $version->gather_dataset();
        $error = test_version::haserror($versionid) ; // An error has been registered
        $this->assertFalse($error);
    }

    /**
     * Check that gather_dataset() throws and registers an error if no data is available.
     *
     * @covers ::tool_laaudit_model_version_gather_dataset
     */
    public function test_model_version_gather_dataset_error() {
        $this->resetAfterTest(true);

        $modelid = test_model::create();
        $configid = test_config::create($modelid);
        $versionid = test_version::create($configid);

        // Create a model configuration from a config with an existing model.
        $version = new model_version($versionid);

        // No data is available for gathering:
        try {
            $this->expectException(\moodle_exception::class);
            $version->gather_dataset();
        } finally {
            global $DB;
            $dataset = $version->get_dataset();
            $this->assertFalse(isset($dataset)); // $dataset has not been set
            $error = test_version::haserror($versionid) ; // An error has been registered
            $this->assertTrue($error);
        }
    }
}
