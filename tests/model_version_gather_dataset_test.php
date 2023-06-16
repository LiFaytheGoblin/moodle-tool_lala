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

        // No data is available for gathering:
        $this->expectException(\Exception::class);
        $version->gather_dataset();

        $dataset = $version->get_dataset();
        $this->assertFalse(isset($dataset)); // $dataset has not been set
        $error = $DB->get_fieldset_select('tool_laaudit_model_versions', 'error', 'id='.$versionid); // An error has been registered
        assert(isset($error));

        // Todo: Generate data

        // Data is available for gathering
        $version->gather_dataset();
        $dataset = $version->get_dataset();
        $this->assertTrue(isset($dataset));

        // Try to gather dataset again, even though it has already been gathered.
        $this->expectException(\Exception::class);
        $version->gather_dataset();
    }
}
