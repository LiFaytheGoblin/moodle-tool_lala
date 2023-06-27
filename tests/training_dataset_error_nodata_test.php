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
class training_dataset_error_nodata_test extends \advanced_testcase {
    private $evidence;
    private $modelid;
    protected function setUp(): void {
        $this->resetAfterTest(true);

        $this->modelid = test_model::create();
        $configid = test_config::create($this->modelid);
        $versionid = test_version::create($configid);

        $this->evidence = training_dataset::create_scaffold_and_get_for_version($versionid);
    }
    /**
     * Data provider for {@see test_training_dataset_collect()}.
     *
     * @return array List of source data information
     */
    public function tool_laaudit_get_source_data_parameters_provider() {
        return [
                'No dataset, small testsize' => [
                        'data' => [],
                        'testsize' => 0.2
                ],
                'Just header, small testsize' => [
                        'data' => test_dataset_evidence::create(0),
                        'testsize' => 0.2
                ],
                'Too small dataset, small testsize' => [
                        'data' => test_dataset_evidence::create(2),
                        'testsize' => 0.2
                ]
        ];
    }
    /**
     * Check that collect throws error if not enough data available.
     *
     * @covers ::tool_laaudit_training_dataset_collect
     *
     * @dataProvider tool_laaudit_get_source_data_parameters_provider
     * @param array $data set
     * @param float $testsize portion of the dataset to be used as test data
     */
    public function test_training_dataset_error_nodata($data, $testsize) {
        $options=[
            'data' => $data,
            'testsize' => $testsize,
        ];
        $this->expectException(\Exception::class); // Expect exception if trying to collect but no data exists.
        $this->evidence->collect($options);
    }
}
