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
class model_version_complete_creation_test extends \advanced_testcase {
    private $versionid;
    private $version;
    protected function setUp(): void {
        $this->resetAfterTest(true);

        $modelid = test_model::create();
        $configid = test_config::create($modelid);
        $this->versionid = test_version::create($configid);
        // Create a model configuration from a config with an existing model.
        $this->version = new model_version($this->versionid);
    }
    /**
     * Check the happy path of the automatic model creation process
     *
     * @covers ::tool_laaudit_model_version
     */
    public function test_model_version_complete_creation() {
        // Generate test data
        test_course_with_students::create($this->getDataGenerator(), 10);

        // Data is available for gathering
        $this->version->gather_dataset();
        $dataset = $this->version->get_dataset();
        $this->assertTrue(isset($dataset));

        // Now get split data
        $this->version->split_training_test_data();
        $testdataset = $this->version->get_testdataset();
        $this->assertTrue(isset($testdataset));
        $trainingdataset = $this->version->get_trainingdataset();
        $this->assertTrue(isset($trainingdataset));

        // Train the model
        $this->version->train();
        $model = $this->version->get_model();
        $this->assertTrue(isset($model));

        // Get predictions
        $this->version->predict();
        $predictionsdataset = $this->version->get_predictionsdataset();
        $this->assertTrue(isset($predictionsdataset));

        $error = test_version::haserror($this->versionid);
        $this->assertFalse($error); // An error has not been registered
    }

    /**
     * Check that during gather_dataset errors are thrown and registered.
     *
     * @covers ::tool_laaudit_model_version_gather_dataset
     */
    public function test_model_version_gather_dataset_error() {
        $this->expectException(\moodle_exception::class); // No data is available for gathering.
        $this->version->gather_dataset();
    }

    /**
     * Check that during faulty dataset splitting errors are thrown and registered.
     *
     * @covers ::tool_laaudit_model_version_split_training_test_data
     */
    public function test_model_version_split_training_test_data_error() {
        $this->expectException(\Exception::class); // No dataset has been gathered.
        $this->version->split_training_test_data();
    }

    /**
     * Check that during faulty training errors are thrown and registered.
     *
     * @covers ::tool_laaudit_model_version_train
     */
    public function test_model_version_train_error() {
        $this->expectException(\Exception::class); // No training and test data is available.
        $this->version->train();
    }

    /**
     * Check that during faulty predicting errors are thrown and registered.
     *
     * @covers ::tool_laaudit_model_version_predict
     */
    public function test_model_version_predict_error() {
        $this->expectException(\Exception::class); // No trained model is available.
        $this->version->predict();
    }
}
