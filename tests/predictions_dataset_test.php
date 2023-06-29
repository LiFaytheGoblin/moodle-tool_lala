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

require_once(__DIR__ . '/fixtures/test_model.php');
require_once(__DIR__ . '/fixtures/test_version.php');
require_once(__DIR__ . '/fixtures/test_dataset_evidence.php');
require_once(__DIR__ . '/evidence_testcase.php');

/**
 * Training dataset test.
 *
 * @package     tool_laaudit
 * @copyright   2023 Linda Fernsel <fernsel@htw-berlin.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class predictions_dataset_test extends evidence_testcase {
    private $classifier;
    protected function setUp(): void {
        parent::setUp();

        $this->evidence = predictions_dataset::create_scaffold_and_get_for_version($this->versionid);
        $this->classifier = test_version::get_classifier($this->versionid);
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
    public function test_evidence_collect($ndatapoints) {
        $options=[
                'model' => $this->classifier,
                'data' => test_dataset_evidence::create($ndatapoints),
        ];
        $this->evidence->collect($options);

        $rawdata = $this->evidence->get_raw_data();

        $res = $rawdata[test_model::ANALYSISINTERVAL];
        $resheader = array_slice($res, 0, 1, true)[0];
        $resdata = array_slice($res, 1, null, true);

        $expectedheadersize = 2; // The header should have target (=truth) and prediction, the sampleid is the index.
        $this->assertEquals($expectedheadersize, sizeof($resheader));

        $this->assertEquals($ndatapoints, sizeof($resdata));
    }

    function get_options(): array {
        return [
                'model' => $this->classifier,
                'data' => test_dataset_evidence::create(3),
        ];
    }
}
