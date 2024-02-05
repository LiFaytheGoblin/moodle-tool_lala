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

namespace tool_lala;

use advanced_testcase;
use dml_missing_record_exception;
use Exception;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/admin/tool/lala/classes/model_version.php');
require_once(__DIR__ . '/fixtures/test_config.php');
require_once(__DIR__ . '/fixtures/test_model.php');
require_once(__DIR__ . '/fixtures/test_version.php');
require_once(__DIR__ . '/fixtures/test_course_with_students.php');

/**
 * Model version __construct() and create_scaffold_and_get_for_config() test.
 *
 * @package     tool_lala
 * @copyright   2023 Linda Fernsel <fernsel@htw-berlin.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class model_version_test extends advanced_testcase {
    /**
     * Check that __construct() creates a model version.
     *
     * @covers ::tool_lala_model_version___construct
     */
    public function test_model_version_create(): void {
        $this->resetAfterTest(true);

        $modelid = test_model::create();
        $configid = test_config::create($modelid);
        $versionid = test_version::create($configid);

        // Create a model configuration from a config with an existing model.
        $version = new model_version($versionid);
        $this->assertEquals($version->get_id(), $versionid);

        // Delete model and create a model configuration from a config with a now deleted model.
        test_model::delete($modelid);

        $version2 = new model_version($versionid);
        $this->assertEquals($version2->get_id(), $versionid);
    }

    /**
     * Check that __construct() throws an error if the provided version id does not exist in tool_lala_model_versions.
     *
     * @covers ::tool_lala_model_version___construct
     */
    public function test_model_version_create_error(): void {
        $this->expectException(dml_missing_record_exception::class);
        new model_version(test_version::get_highest_id() + 1);
    }

    /**
     * Check that create_scaffold_and_get_for_config() references and creates correct model version scaffolds.
     *
     * @covers ::tool_lala_model_version_create_scaffold_and_get_for_config
     */
    public function test_model_version_create_scaffold_and_get_for_config(): void {
        $this->resetAfterTest(true);
        $modelid = test_model::create();

        // Create a config.
        $configid = test_config::create($modelid);

        // For valid config & model...
        $maxidbeforenewversioncreation = test_version::get_highest_id();
        $versionid = model_version::create_scaffold_and_get_for_config($configid);
        $this->assertGreaterThan($maxidbeforenewversioncreation, $versionid);

        // For valid config & deleted model...
        test_model::delete($modelid);
        $versionid2 = model_version::create_scaffold_and_get_for_config($configid);
        $this->assertGreaterThan($maxidbeforenewversioncreation, $versionid2);
    }

    /**
     * Check that create_scaffold_and_get_for_config() throws an error if the provided config id does not exist.
     *
     * @covers ::tool_lala_model_version_create_scaffold_and_get_for_config
     */
    public function test_model_version_create_scaffold_and_get_for_config_error(): void {
        $this->expectException(Exception::class);
        model_version::create_scaffold_and_get_for_config(test_config::get_highest_id() + 1);
    }

    /**
     * Check that a model version can be completed automatically after data gathering was done.
     *
     * @covers ::tool_lala_model_version
     */
    public function test_model_version_has_evidence(): void {
        $this->resetAfterTest(true);

        // Generate test data.
        test_course_with_students::create($this->getDataGenerator());

        // Create a model version for testing.
        $modelid = test_model::create();
        $configid = test_config::create($modelid);
        $versionid = test_version::create($configid);
        $version = new model_version($versionid);

        // Data becomes available in cache and db.
        $hasdatasetincache = $version->has_evidence('dataset');
        $this->assertFalse($hasdatasetincache);

        $version->gather_dataset(false);

        $hasdatasetincache = $version->evidence_in_cache('dataset');
        $this->assertTrue($hasdatasetincache);
        $hasdataindb = $version->evidence_in_db('dataset');
        $this->assertTrue($hasdataindb);

        // Finish the model version automatically.
        $version = new model_version($versionid);

        // Data becomes available in db but not in cache.
        $hasdataset = $version->has_evidence('dataset');
        $this->assertTrue($hasdataset);
        $hasdatasetincache = $version->evidence_in_cache('dataset');
        $this->assertFalse($hasdatasetincache);
        $hasdataindb = $version->evidence_in_db('dataset');
        $this->assertTrue($hasdataindb);
    }

    /**
     * Check that a model version can be completed automatically after data gathering was done.
     *
     * @covers ::tool_lala_model_version
     */
    public function test_model_version_get_single_evidence(): void {
        $this->resetAfterTest(true);
        // Generate test data.
        test_course_with_students::create($this->getDataGenerator());

        // Create a model version for testing.
        $modelid = test_model::create();
        $configid = test_config::create($modelid);
        $versionid = test_version::create($configid);
        $version = new model_version($versionid);

        // Data becomes available.
        $dataset = $version->get_single_evidence('dataset');
        $this->assertFalse(isset($dataset));

        $version->gather_dataset(false);

        $dataset = $version->get_single_evidence('dataset');
        $this->assertTrue(isset($dataset));
        $this->assertTrue(count($dataset) > 0);

        $version = new model_version($versionid);

        // Data becomes available again.
        $dataset = $version->get_single_evidence('dataset');
        $this->assertTrue(isset($dataset));
        $this->assertTrue(count($dataset) > 0);
    }
}
