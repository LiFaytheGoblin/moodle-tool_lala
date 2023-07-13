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

use Exception;
use LogicException;

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
    /** @var int $versionid the id of the created model version */
    private int $versionid;
    /** @var model_version $version the created model version */
    private model_version $version;
    protected function setUp(): void {
        $this->resetAfterTest(true);

        $this->modelid = test_model::create();
        $configid = test_config::create($this->modelid);
        $this->versionid = test_version::create($configid);
        // Create a model configuration from a config with an existing model.
        $this->version = new model_version($this->versionid);
    }
    /**
     * Data provider for {@see test_model_version_complete_creation()}.
     *
     * @return array with the info whether the data should be anonymized.
     */
    public function tool_laaudit_model_creation_parameters_provider() : array {
        return [
                'Anonymous' => [
                        'anonymous' => true,
                ],
                'Not anonymous' => [
                        'anonymous' => false,
                ]
        ];
    }
    /**
     * Check the happy path of the automatic model creation process
     *
     * @covers ::tool_laaudit_model_version
     *
     * @dataProvider tool_laaudit_model_creation_parameters_provider
     * @param bool $anonymous
     */
    public function test_model_version_complete_creation(bool $anonymous) {
        // Generate test data
        $nstudents = 10;
        test_course_with_students::create($this->getDataGenerator(), $nstudents, 3);

        // Data is available for gathering
        $this->version->gather_dataset($anonymous);
        $evidencetype = $anonymous ? 'dataset_anonymized' : 'dataset';
        $dataset = $this->version->get_single_evidence($evidencetype);
        $this->assertTrue(isset($dataset));
        $this->assertTrue(sizeof($dataset[test_model::ANALYSISINTERVAL]) == $nstudents + 1); // +1 for the header.

        if ($anonymous) {
            // Check that dataset does not contain the original userids but new userids
            $originalids = test_course_with_students::get_ids('user_enrolments');
            $newids = dataset_helper::get_ids_used_in_dataset($dataset);
            //print(json_encode($newids));
            $identicalids = array_intersect($originalids, $newids);
            $this->assertEquals(0, sizeof($identicalids));

            // Check that datasets allocate the correct values to the correct users
            $this->version->gather_dataset(!$anonymous);
            $originaldataset = $this->version->get_single_evidence('dataset');

            $originalrows = $originaldataset[test_model::ANALYSISINTERVAL];
            $newrows = $dataset[test_model::ANALYSISINTERVAL];
            $idmap = $this->version->get_idmap();
            $originalidsinorder = [];
            $newidsinorder = [];
            foreach ($originalrows as $originasamplelid => $originalvalues) {
                if ($originasamplelid == '0') continue; // skip header

                $newsampleid = $idmap->get_pseudonym_sampleid($originasamplelid);
                $newvalues = $newrows[$newsampleid];
                $this->assertEquals(json_encode($originalvalues), json_encode($newvalues));

                $originalid = dataset_helper::get_id_part($originasamplelid);
                $newid = $idmap->get_pseudonym($originalid);

                if (!in_array($originalid, $originalidsinorder)) $originalidsinorder[] = $originalid;
                if (!in_array($newid, $newidsinorder)) $newidsinorder[] = $newsampleid;
            }

            // Check that the order of new ids does not give away the original id
            $possibleidmap = new idmap($originalidsinorder, $newidsinorder, 'user_enrolments');

            $this->assertFalse($idmap->contains($possibleidmap));
            $this->assertFalse($possibleidmap->contains($idmap));
        }

        // Now get split data
        $this->version->split_training_test_data($anonymous);
        $testdataset = $this->version->get_single_evidence('test_dataset');
        $this->assertTrue(isset($testdataset));
        $trainingdataset = $this->version->get_single_evidence('training_dataset');
        $this->assertTrue(isset($trainingdataset));

        // Train the model
        $this->version->train();
        $model = $this->version->get_single_evidence('model');
        $this->assertTrue(isset($model));

        // Get predictions
        $this->version->predict();
        $predictionsdataset = $this->version->get_single_evidence('predictions_dataset');
        $this->assertTrue(isset($predictionsdataset));

        // Get related data
        $this->version->gather_related_data($anonymous);
        $evidencetype = $anonymous ? 'related_data_anonymized' : 'related_data';
        $relateddatasets = $this->version->get_array_of_evidences($evidencetype);
        $this->assertEquals(5, sizeof($relateddatasets));

        if ($anonymous) {
            // Check that the datasets do not contain the original userids but new userids
            $originalids = test_course_with_students::get_ids('user');
            foreach ($relateddatasets as $data) {
                $newids = []; //todo: get new ids
                // check that datasets do not contain the original userids but new userids (check analysisinterval)
                $identicalids = array_intersect($originalids, $newids);
                $this->assertEquals(0, sizeof($identicalids));
                // check that datasets allocate the correct values to the correct users
            }
        }

        $error = test_version::haserror($this->versionid);
        $this->assertFalse($error); // An error has not been registered
    }
    /**
     * Check the happy path of the automatic model creation process still works of model was deleted
     *
     * @covers ::tool_laaudit_model_version
     */
    public function test_model_version_complete_creation_modeldeleted() {
        // Generate test data
        test_course_with_students::create($this->getDataGenerator(), 10);
        test_model::delete($this->modelid);

        // Data is available for gathering
        $this->version->gather_dataset(false);
        $dataset = $this->version->get_single_evidence('dataset');
        $this->assertTrue(isset($dataset));

        // Now get split data
        $this->version->split_training_test_data(false);
        $testdataset = $this->version->get_single_evidence('test_dataset');
        $this->assertTrue(isset($testdataset));
        $trainingdataset = $this->version->get_single_evidence('training_dataset');
        $this->assertTrue(isset($trainingdataset));

        // Train the model
        $this->version->train();
        $model = $this->version->get_single_evidence('model');
        $this->assertTrue(isset($model));

        // Get predictions
        $this->version->predict();
        $predictionsdataset = $this->version->get_single_evidence('predictions_dataset');
        $this->assertTrue(isset($predictionsdataset));

        $error = test_version::haserror($this->versionid);
        $this->assertFalse($error); // An error has not been registered
    }
}
