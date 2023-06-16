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
        $modelid = test_model::create();

        // create a config
        $configid = test_config::create($modelid);

        // for valid config & model
        $maxidbeforenewversioncreation = test_version::get_highest_id();
        $versionid = model_version::create_scaffold_and_get_for_config($configid);
        $this->assertGreaterThan($maxidbeforenewversioncreation, $versionid);

        // for valid config & deleted model
        test_model::delete($modelid);
        $versionid2 = model_version::create_scaffold_and_get_for_config($configid);
        $this->assertGreaterThan($maxidbeforenewversioncreation, $versionid2);
    }

    /**
     * Check that create_scaffold_and_get_for_config() throws an error if the provided config id does not exist.
     *
     * @covers ::tool_laaudit_model_version_create_scaffold_and_get_for_config
     */
    public function test_model_version_create_scaffold_and_get_for_config_error() {
        $this->expectException(\Exception::class);
        model_version::create_scaffold_and_get_for_config(test_config::get_highest_id() + 1);
    }
}