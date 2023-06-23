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
 * Dataset test.
 *
 * @package     tool_laaudit
 * @copyright   2023 Linda Fernsel <fernsel@htw-berlin.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class training_dataset_test extends \advanced_testcase {
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
     * Data provider for {@see test_dataset_collect()}.
     *
     * @return array List of source data information
     */
    public function tool_laaudit_get_source_data_parameters_provider() {
        return [
                'Min dataset, min testsize' => [
                        'data' => test_dataset_evidence::create(),
                        'testsize' => 0.3,
                        'expectedressize' => 2
                ],
                'Small dataset, some testsize' => [
                        'data' => test_dataset_evidence::create(7),
                        'testsize' => 0.3,
                        'expectedressize' => 5
                ],
                'Small dataset, smaller testsize' => [
                        'data' => test_dataset_evidence::create(7),
                        'testsize' => 0.2,
                        'expectedressize' => 6
                ],
                'Some dataset, some testsize' => [
                        'data' => test_dataset_evidence::create(10),
                        'testsize' => 0.2,
                        'expectedressize' => 8
                ],
        ];
    }
    /**
     * Check that collect gathers all necessary data
     *
     * @covers ::tool_laaudit_dataset_collect
     *
     * @dataProvider tool_laaudit_get_source_data_parameters_provider
     * @param string|null $country User country
     * @param string $langstring Greetings message language string
     */
    public function test_dataset_collect($data, $testsize, $expectedressize) {
        $options=[
            'data' => $data,
            'testsize' => $testsize,
        ];
        $this->evidence->collect($options);

        $rawdata = $this->evidence->get_raw_data();

        $res = $rawdata[test_model::ANALYSISINTERVAL];
        $resheader = array_slice($res, 0, 1, true)[0];
        $resdata = array_slice($res, 1, null, true);

        $expectedheadersize = sizeof(test_model::get_indicator_instances()) + 1; // The header should contain indicator and target names.
        $this->assertEquals($expectedheadersize, sizeof($resheader));

        $this->assertEquals($expectedressize, sizeof($resdata));
    }

    public function test_dataset_collect_error_again() {
        $options=[
                'data' => test_dataset_evidence::create(3),
                'testsize' => 0.2,
        ];
        $this->evidence->collect($options);

        $this->expectException(\Exception::class); // Expect exception if trying to collect again.
        $this->evidence->collect($options);
    }
}
