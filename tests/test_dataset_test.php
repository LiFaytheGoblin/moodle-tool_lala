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

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/fixtures/test_model.php');
require_once(__DIR__ . '/fixtures/test_dataset_evidence.php');
require_once(__DIR__ . '/evidence_testcase.php');

/**
 * Test dataset test.
 *
 * @package     tool_laaudit
 * @copyright   2023 Linda Fernsel <fernsel@htw-berlin.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class test_dataset_test extends evidence_testcase {
    protected function setUp(): void {
        parent::setUp();

        $this->evidence = test_dataset::create_scaffold_and_get_for_version($this->versionid);
    }
    /**
     * Data provider for {@see test_test_dataset_collect()}.
     *
     * @return array List of source data information
     */
    public function tool_laaudit_get_source_data_parameters_provider() : array {
        return [
                'Min dataset, min testsize' => [
                        'ndatapoints' => 2,
                        'testsize' => 0.3,
                        'expectedressize' => 1
                ],
                'Small dataset, some testsize' => [
                        'ndatapoints' => 7,
                        'testsize' => 0.3,
                        'expectedressize' => 2
                ],
                'Small dataset, smaller testsize' => [
                        'ndatapoints' => 7,
                        'testsize' => 0.2,
                        'expectedressize' => 1
                ],
                'Some dataset, some testsize' => [
                        'ndatapoints' => 10,
                        'testsize' => 0.2,
                        'expectedressize' => 2
                ],
        ];
    }

    /**
     * Check that collect gathers all necessary data.
     *
     * @covers ::tool_laaudit_test_dataset_collect
     *
     * @dataProvider tool_laaudit_get_source_data_parameters_provider
     * @param int $ndatapoints
     * @param float $testsize portion of the dataset to be used for testing
     * @param int $expectedressize absolute nr. of datapoints to be expected for training
     * @throws Exception
     * @throws Exception
     */
    public function test_evidence_collect(int $ndatapoints, float $testsize, int $expectedressize): void {
        $options = [
                'data' => test_dataset_evidence::create($ndatapoints),
                'testsize' => $testsize,
        ];
        $this->evidence->collect($options);

        $rawdata = $this->evidence->get_raw_data();

        $res = $rawdata[test_model::ANALYSISINTERVAL];
        $resheader = array_slice($res, 0, 1, true)[0];
        $resdata = array_slice($res, 1, null, true);

        $expectedheadersize = count(test_model::get_indicator_instances()) + 1; // Header should contain indicator and target names.
        $this->assertEquals($expectedheadersize, count($resheader));

        $this->assertEquals($expectedressize, count($resdata));
    }

    /**
     * Data provider for {@see test_dataset_collect()}.
     *
     * @return array List of source data information
     * @throws Exception
     * @throws Exception
     */
    public function tool_laaudit_get_source_data_error_parameters_provider(): array {
        return [
                'No dataset, small testsize' => [
                        'data' => [],
                        'testsize' => 0.2
                ],
                'Just header, small testsize' => [
                        'datapoints' => test_dataset_evidence::create(0),
                        'testsize' => 0.2
                ],
                'Too small dataset, small testsize' => [
                        'datapoints' => test_dataset_evidence::create(2),
                        'testsize' => 0.2
                ]
        ];
    }
    /**
     * Check that collect throws error if not enough data available.
     *
     * @covers ::tool_laaudit_test_dataset_collect
     *
     * @dataProvider tool_laaudit_get_source_data_error_parameters_provider
     * @param array $data
     * @param float $testsize portion of the dataset to be used as test data
     */
    public function test_evidence_error_nodata(array $data, float $testsize): void {
        $options = [
                'data' => $data,
                'testsize' => $testsize,
        ];
        $this->expectException(Exception::class); // Expect exception if trying to collect but no data exists.
        $this->evidence->collect($options);
    }

    /** Get the options object needed for collecting this evidence.
     *
     * @return array
     * @throws Exception
     * @throws Exception
     */
    public function get_options(): array {
        return [
                'data' => test_dataset_evidence::create(),
                'testsize' => 0.2,
        ];
    }
}
