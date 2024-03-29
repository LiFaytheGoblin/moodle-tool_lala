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

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/admin/tool/lala/classes/model_version.php');
require_once(__DIR__ . '/fixtures/test_config.php');
require_once(__DIR__ . '/fixtures/test_model.php');
require_once(__DIR__ . '/fixtures/test_version.php');
require_once(__DIR__ . '/fixtures/test_course_with_students.php');

/**
 * Model version gather_dataset() test.
 *
 * @package     tool_lala
 * @copyright   2023 Linda Fernsel <fernsel@htw-berlin.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class model_version_complete_creation_test extends advanced_testcase {
    /** @var int $versionid the id of the created model version */
    private int $versionid;
    /** @var model_version $version the created model version */
    private model_version $version;
    /** @var int $modelid the id of the used model */
    private int $modelid;

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
    public function tool_lala_model_creation_parameters_provider() : array {
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
     * @covers ::tool_lala_model_version
     *
     * @dataProvider tool_lala_model_creation_parameters_provider
     * @param bool $anonymous
     * @throws \Exception
     * @throws \Exception
     */
    public function test_model_version_complete_creation(bool $anonymous): void {
        // Generate test data.
        $nstudents = 10;
        test_course_with_students::create($this->getDataGenerator(), $nstudents, 3);

        // Data is available for gathering.
        $this->version->gather_dataset($anonymous);
        $evidencetype = $anonymous ? 'dataset_anonymized' : 'dataset';
        $dataset = $this->version->get_single_evidence($evidencetype);
        $this->assertTrue(isset($dataset));
        $this->assertTrue(count($dataset[test_model::ANALYSISINTERVAL]) == $nstudents + 1); // Add 1 for the header.

        if ($anonymous) {
            // Check that dataset does not contain the original userids but new userids.
            $originalids = test_course_with_students::get_ids('user_enrolments');
            $newids = dataset_helper::get_ids_used_in_dataset($dataset);
            $identicalids = array_intersect($originalids, $newids);
            $this->assertEquals(0, count($identicalids));

            // Check that datasets allocate the correct values to the correct users.
            $this->version->gather_dataset(false);
            $originaldataset = $this->version->get_single_evidence('dataset');
            $this->assertTrue(isset($originaldataset));

            $originalrows = $originaldataset[test_model::ANALYSISINTERVAL];
            $newrows = $dataset[test_model::ANALYSISINTERVAL];
            $idmaps = $this->version->get_idmaps();
            $idmap = $idmaps['user_enrolments'];
            $originalidsinorder = [];
            $newidsinorder = [];
            foreach ($originalrows as $originasamplelid => $originalvalues) {
                if ($originasamplelid == '0') {
                    continue; // Skip header.
                }

                $newsampleid = $idmap->get_pseudonym_sampleid($originasamplelid);
                $newvalues = $newrows[$newsampleid];
                $this->assertEquals(json_encode($originalvalues), json_encode($newvalues));

                $originalid = intval(dataset_helper::get_id_part($originasamplelid));
                $newid = $idmap->get_pseudonym($originalid);

                if (!in_array($originalid, $originalidsinorder)) {
                    $originalidsinorder[] = $originalid;
                }
                if (!in_array($newid, $newidsinorder)) {
                    $newidsinorder[] = $newid;
                }
            }

            // Check that the order of new ids does not give away the original id.
            $possibleidmap = new idmap($originalidsinorder, $newidsinorder);

            $this->assertFalse($idmap->contains($possibleidmap));
            $this->assertFalse($possibleidmap->contains($idmap));
        }

        // Now get split data.
        $this->version->split_training_test_data();
        $testdataset = $this->version->get_single_evidence('test_dataset');
        $this->assertTrue(isset($testdataset));
        $trainingdataset = $this->version->get_single_evidence('training_dataset');
        $this->assertTrue(isset($trainingdataset));

        // Train the model.
        $this->version->train();
        $model = $this->version->get_single_evidence('model');
        $this->assertTrue(isset($model));

        // Get predictions.
        $this->version->predict();
        $predictionsdataset = $this->version->get_single_evidence('predictions_dataset');
        $this->assertTrue(isset($predictionsdataset));

        // Get related data.
        $this->version->gather_related_data();
        $evidencetype = $anonymous ? 'related_data_anonymized' : 'related_data';
        $relateddatasets = $this->version->get_array_of_evidences($evidencetype);
        $this->assertEquals(5, count($relateddatasets));

        if ($anonymous) {
            // Get all original ids.
            $originalids = [
                    'user_enrolments' => test_course_with_students::get_ids('user_enrolments'),
                    'user' => test_course_with_students::get_ids_for_referenced_by('user', 'user_enrolments'),
                    'course' => test_course_with_students::get_ids_for_referenced_by('course', 'enrol'),
                    'enrol' => test_course_with_students::get_ids_for_referenced_by('enrol', 'user_enrolments'),
                    'role' => test_course_with_students::get_ids_for_referenced_by('role', 'enrol'),
            ];

            foreach ($relateddatasets as $evidenceid => $dataset) {
                // Gather pseudonyms / actually used ids.
                $newids = [];
                $newreferenceduserids = [];
                $newreferencedenrolids = [];
                $newreferencedroleids = [];
                $newreferencedcourseids = [];

                foreach ($dataset as $entry) {
                    $newids[] = $entry->id;
                    if (isset($entry->userid)) {
                        $newreferenceduserids[] = $entry->userid;
                    }
                    if (isset($entry->enrolid)) {
                        $newreferencedenrolids[] = $entry->enrolid;
                    }
                    if (isset($entry->courseid)) {
                        $newreferencedcourseids[] = $entry->courseid;
                    }
                    if (isset($entry->roleid)) {
                        $newreferencedroleids[] = $entry->roleid;
                    }
                }

                // Check that the original primary id is not used.
                $tablename = related_data_helper::get_tablename_from_evidenceid($evidenceid);

                $identicalids = array_intersect($originalids[$tablename], $newids);
                $this->assertEquals(0, count($identicalids));

                // Check that datasets do not reference the original ids.
                if (count($newreferenceduserids) > 0) {
                    $identicaluserids = array_intersect($originalids['user'], $newreferenceduserids);
                    $this->assertEquals(0, count($identicaluserids));
                }
                if (count($newreferencedenrolids) > 0) {
                    $identicalenrolids = array_intersect($originalids['enrol'], $newreferencedenrolids);
                    $this->assertEquals(0, count($identicalenrolids));
                }
                if (count($newreferencedcourseids) > 0) {
                    $identicalcourseids = array_intersect($originalids['course'], $newreferencedcourseids);
                    $this->assertEquals(0, count($identicalcourseids));
                }
                if (count($newreferencedroleids) > 0) {
                    $identicalroleids = array_intersect($originalids['role'], $newreferencedroleids);
                    $this->assertEquals(0, count($identicalroleids));
                }
            }
        }

        $error = test_version::haserror($this->versionid);
        $this->assertFalse($error); // An error has not been registered.
    }

    /**
     * Check the happy path of the automatic model creation process still works of model was deleted
     *
     * @covers ::tool_lala_model_version
     */
    public function test_model_version_complete_creation_modeldeleted(): void {
        // Generate test data.
        test_course_with_students::create($this->getDataGenerator());
        test_model::delete($this->modelid);

        // Data is available for gathering.
        $this->version->gather_dataset(false);
        $dataset = $this->version->evidence_in_cache('dataset');
        $this->assertTrue(isset($dataset) && $dataset !== false);

        // Now get split data.
        $this->version->split_training_test_data();
        $testdataset = $this->version->evidence_in_cache('test_dataset');
        $this->assertTrue(isset($testdataset) && $testdataset !== false);
        $trainingdataset = $this->version->evidence_in_cache('training_dataset');
        $this->assertTrue(isset($trainingdataset) && $trainingdataset !== false);

        // Train the model.
        $this->version->train();
        $model = $this->version->evidence_in_cache('model');
        $this->assertTrue(isset($model) && $model !== false);

        // Get predictions.
        $this->version->predict();
        $predictionsdataset = $this->version->evidence_in_cache('predictions_dataset');
        $this->assertTrue(isset($predictionsdataset)  && $predictionsdataset !== false);

        $error = test_version::haserror($this->versionid);
        $this->assertFalse($error); // An error has not been registered.
    }

    /**
     * Check that a model version can be completed automatically after data gathering was done.
     *
     * @dataProvider tool_lala_model_creation_parameters_provider
     * @covers ::tool_lala_model_version
     * @param bool $anonymous
     */
    public function test_model_version_complete_creation_resume_after_data_gathering(bool $anonymous): void {
        // Generate test data.
        test_course_with_students::create($this->getDataGenerator());

        // Gather data.
        $this->version->gather_dataset($anonymous);

        // No training and test data, model and predictions yet.
        $datasettype = $anonymous ? 'dataset_anonymized' : 'dataset';
        $hasdataset = $this->version->has_evidence($datasettype);
        $this->assertTrue($hasdataset);
        $hastestdataset = $this->version->has_evidence('test_dataset');
        $this->assertFalse($hastestdataset);
        $hastrainingdataset = $this->version->has_evidence('training_dataset');
        $this->assertFalse($hastrainingdataset);
        $hasmodel = $this->version->has_evidence('model');
        $this->assertFalse($hasmodel);
        $haspredictionsdataset = $this->version->has_evidence('predictions_dataset');
        $this->assertFalse($haspredictionsdataset);

        // Finish the model version automatically.
        $this->version = model_version::create($this->versionid, null, null, $anonymous);

        // An error has not been registered.
        $error = test_version::geterror($this->versionid);
        $this->assertEquals(null, $error);

        // The data is all there.
        $hastestdataset = $this->version->evidence_in_cache('test_dataset');
        $this->assertTrue($hastestdataset);
        $hastrainingdataset = $this->version->evidence_in_cache('training_dataset');
        $this->assertTrue($hastrainingdataset);
        $hasmodel = $this->version->evidence_in_cache('model');
        $this->assertTrue($hasmodel);
        $haspredictionsdatasetincache = $this->version->evidence_in_cache('predictions_dataset');
        $this->assertTrue($haspredictionsdatasetincache);
        $predictionsdataset = $this->version->get_single_evidence('predictions_dataset');
        $this->assertTrue(isset($predictionsdataset));
        $this->assertTrue(count($predictionsdataset) > 0);
    }

    /**
     * Check that a model version can be completed automatically after training data was split.
     *
     * @dataProvider tool_lala_model_creation_parameters_provider
     * @covers ::tool_lala_model_version
     * @param bool $anonymous
     */
    public function test_model_version_complete_creation_resume_after_data_split(bool $anonymous): void {
        // Generate test data.
        test_course_with_students::create($this->getDataGenerator());

        // Data is available for gathering.
        $this->version->gather_dataset($anonymous);
        $this->version->split_training_test_data();

        // No model and predictions yet.
        $hasmodel = $this->version->has_evidence('model');
        $this->assertFalse($hasmodel);
        $haspredictionsdataset = $this->version->has_evidence('predictions_dataset');
        $this->assertFalse($haspredictionsdataset);

        // Finish the model version automatically.
        $this->version = model_version::create($this->versionid, null, null, $anonymous);

        // An error has not been registered.
        $error = test_version::geterror($this->versionid);
        $this->assertEquals(null, $error);

        // Now the data is all there.
        $hasmodel = $this->version->has_evidence('model');
        $this->assertTrue($hasmodel);
        $haspredictionsdataset = $this->version->has_evidence('predictions_dataset');
        $this->assertTrue($haspredictionsdataset);
        $predictionsdataset = $this->version->get_single_evidence('predictions_dataset');
        $this->assertTrue(isset($predictionsdataset));
        $this->assertTrue(count($predictionsdataset) > 0);
    }

    /**
     * Check that a model version can be completed automatically after model was trained.
     *
     * @dataProvider tool_lala_model_creation_parameters_provider
     * @covers ::tool_lala_model_version
     * @param bool $anonymous
     */
    public function test_model_version_complete_creation_resume_after_training(bool $anonymous): void {
        // Generate test data.
        test_course_with_students::create($this->getDataGenerator());

        // Data is available for gathering.
        $this->version->gather_dataset($anonymous);
        $this->version->split_training_test_data();

        $this->version->train();

        // No predictions yet.
        $haspredictionsdataset = $this->version->has_evidence('predictions_dataset');
        $this->assertFalse($haspredictionsdataset);

        // Finish the model version automatically.
        $this->version = model_version::create($this->versionid, null, null, $anonymous);

        // An error has not been registered.
        $error = test_version::geterror($this->versionid);
        $this->assertEquals(null, $error);

        // Now the data is all there.
        $haspredictionsdataset = $this->version->has_evidence('predictions_dataset');
        $this->assertTrue($haspredictionsdataset);
        $predictionsdataset = $this->version->get_single_evidence('predictions_dataset');
        $this->assertTrue(isset($predictionsdataset));
        $this->assertTrue(count($predictionsdataset) > 0);
    }
}
