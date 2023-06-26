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
class predictions_dataset_error_nodata_test extends \advanced_testcase {
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
                'Empty dataset' => [
                        'data' => []
                ],
                'Some datapoints' => [
                        'data' => test_dataset_evidence::create(0),
                ]
        ];
    }
    /**
     * Check that collect throws an error if input is insufficient.
     *
     * @covers ::tool_laaudit_training_dataset_collect
     *
     * @dataProvider tool_laaudit_get_source_data_parameters_provider
     * @param array $data to get predictions for
     */
    public function test_predictions_dataset_collect_error_nodata($data) {
        $options=[
                'model' => $this->predictor,
                'data' => $data
        ];
        $this->expectException(\Exception::class); // Expect exception if no data collected yet.
        $this->evidence->collect($options);
    }
}
