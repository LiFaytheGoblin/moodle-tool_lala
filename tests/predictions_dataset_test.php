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
require_once($CFG->dirroot . '/admin/tool/laaudit/classes/dataset.php');
require_once(__DIR__ . '/fixtures/test_config.php');
require_once(__DIR__ . '/fixtures/test_model.php');
require_once(__DIR__ . '/fixtures/test_version.php');
require_once(__DIR__ . '/fixtures/test_course_with_students.php');
require_once(__DIR__ . '/fixtures/test_analyser.php');
require_once(__DIR__ . '/fixtures/test_dataset_evidence.php');


/**
 * Training dataset test.
 *
 * @package     tool_laaudit
 * @copyright   2023 Linda Fernsel <fernsel@htw-berlin.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class predictions_dataset_test extends \advanced_testcase {
    private $evidence;
    private $predictor;
    protected function setUp(): void {
        $this->resetAfterTest(true);

        $modelid = test_model::create();
        $configid = test_config::create($modelid);
        $versionid = test_version::create($configid);

        $this->evidence = predictions_dataset::create_scaffold_and_get_for_version($versionid);

        $this->predictor = test_version::get_predictor($versionid);
    }
    /**
     * Data provider for {@see test_training_dataset_collect()}.
     *
     * @return array List of source data information
     */
    public function tool_laaudit_get_source_data_parameters_provider() {
        return [
                'Min datapoints' => [
                        'ndatapoints' => 1
                ],
                'Some datapoints' => [
                        'ndatapoints' => 3,
                ]
        ];
    }
    /**
     * Check that collect gathers all necessary data
     *
     * @covers ::tool_laaudit_training_dataset_collect
     *
     * @dataProvider tool_laaudit_get_source_data_parameters_provider
     * @param $ndatapoints  amount of datapoints in training data
     */
    public function test_predictions_dataset_collect($ndatapoints) {
        $options=[
                'model' => $this->predictor, //Nope, need to hand over a trained model (LogisticRegression), not a predictor
                'data' => test_dataset_evidence::create($ndatapoints),
        ];
        $this->evidence->collect($options);

        $rawdata = $this->evidence->get_raw_data();

        // todo: verify rawdata
    }
    /**
     * Check that collect throws an error if trying to call it twice for the same object.
     *
     * @covers ::tool_laaudit_training_dataset_collect
     */
    public function test_predictions_dataset_collect_error_again() {
        $options=[
                'model' => $this->predictor,
                'data' => test_dataset_evidence::create(3),
        ];
        $this->assertEquals('Phpml\Classification\Linear\LogisticRegression', get_class($this->predictor));
        $this->evidence->collect($options);

        $this->expectException(\Exception::class); // Expect exception if trying to collect again.
        $this->evidence->collect($options);
    }
}
