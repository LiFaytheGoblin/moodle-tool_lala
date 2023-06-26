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

/**
 * Dataset test.
 *
 * @package     tool_laaudit
 * @copyright   2023 Linda Fernsel <fernsel@htw-berlin.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class dataset_test extends \advanced_testcase {
    private $evidence;
    private $modelid;
    protected function setUp(): void {
        $this->resetAfterTest(true);

        $this->modelid = test_model::create();
        $configid = test_config::create($this->modelid);
        $versionid = test_version::create($configid);

        $this->evidence = dataset::create_scaffold_and_get_for_version($versionid);
    }
    /**
     * Data provider for {@see test_dataset_collect()}.
     *
     * @return array List of source data information
     */
    public function tool_laaudit_get_source_data_parameters_provider() {
        return [
                'Min user, min days' => [
                        'nstudents' => 1,
                        'createddaysago' => 3
                ],
                'Min user, some days' => [
                        'nstudents' => 1,
                        'createddaysago' => 10
                ],
                'Some users, min days' => [
                        'nstudents' => 10,
                        'createddaysago' => 3
                ]
        ];
    }
    /**
     * Check that collect gathers all necessary data
     *
     * @covers ::tool_laaudit_dataset_collect
     *
     * @dataProvider tool_laaudit_get_source_data_parameters_provider
     * @param int $nstudents amount of students
     * @param int $createddaysago how many days ago a sample course should have been started
     */
    public function test_dataset_collect($nstudents, $createddaysago) {
        test_course_with_students::create($this->getDataGenerator(), $nstudents, $createddaysago);

        $options=[
            'contexts' => [],
            'analyser' => test_analyser::create($this->modelid),
            'modelid' => $this->modelid,
        ];
        $this->evidence->collect($options);

        $rawdata = $this->evidence->get_raw_data();

        $res = $rawdata[test_model::ANALYSISINTERVAL];
        $resheader = array_slice($res, 0, 1, true)[0];
        $resdata = array_slice($res, 1, null, true);

        $expectedheadersize = sizeof(test_model::get_indicator_instances()) + 1; // The header should contain indicator and target names.
        $this->assertEquals($expectedheadersize, sizeof($resheader));

        $this->assertEquals(sizeof($resdata), $nstudents * floor($createddaysago / 3));
    }

    public function test_dataset_collect_error_again() {
        test_course_with_students::create($this->getDataGenerator(), 1, 3);

        $options=[
                'contexts' => [],
                'analyser' => test_analyser::create($this->modelid),
                'modelid' => $this->modelid,
        ];

        $this->evidence->collect($options);

        $this->expectException(\Exception::class); // Expect exception if trying to collect again.
        $this->evidence->collect($options);
    }

    public function test_dataset_collect_error_nodata() {
        $options=[
                'contexts' => [],
                'analyser' => test_analyser::create($this->modelid),
                'modelid' => $this->modelid,
        ];

        $this->expectException(\Exception::class); // Expect exception if trying to collect but no data exists.
        $this->evidence->collect($options);
    }

    public function test_dataset_collect_deletedmodel() {
        $nstudents = 1;
        $createddaysago = 3;
        test_course_with_students::create($this->getDataGenerator(), $nstudents, $createddaysago);

        test_model::delete($this->modelid);

        $options=[
                'contexts' => [],
                'analyser' => test_analyser::create($this->modelid),
                'modelid' => $this->modelid,
        ];

        $this->evidence->collect($options);

        $rawdata = $this->evidence->get_raw_data();
        $res = $rawdata[test_model::ANALYSISINTERVAL];
        $resdata = array_slice($res, 1, null, true);
        $this->assertEquals(sizeof($resdata), $nstudents * floor($createddaysago / 3));
    }

    /**
     * Check that serialize returns data
     *
     * @covers ::tool_laaudit_dataset_serialize
     *
     * @dataProvider tool_laaudit_get_source_data_parameters_provider
     * @param int $nstudents amount of students
     * @param int $createddaysago how many days ago a sample course should have been started
     */
    public function test_dataset_serialize($nstudents, $createddaysago) {
        test_course_with_students::create($this->getDataGenerator(), $nstudents, $createddaysago);

        $options=[
                'contexts' => [],
                'analyser' => test_analyser::create($this->modelid),
                'modelid' => $this->modelid,
        ];
        $this->evidence->collect($options);

        $this->evidence->serialize();

        $serializedstring = $this->evidence->get_serialized_data();

        $expectedheadersize = sizeof(test_model::get_indicator_instances()) + 1;
        $this->assertTrue(strlen($serializedstring) >= $expectedheadersize); // The string should contain at least a header.
        $this->assertTrue(str_contains($serializedstring, ',')); // the string should have commas.
    }

    public function test_dataset_serialize_error_nodata() {
        $this->expectException(\Exception::class); // Expect exception if no data collected yet.
        $this->evidence->serialize();
    }

    public function test_dataset_serialize_error_again() {
        $nstudents = 1;
        $createddaysago = 3;
        test_course_with_students::create($this->getDataGenerator(), $nstudents, $createddaysago);

        $options=[
                'contexts' => [],
                'analyser' => test_analyser::create($this->modelid),
                'modelid' => $this->modelid,
        ];
        $this->evidence->collect($options);

        $this->evidence->serialize();

        $this->expectException(\Exception::class); // Expect exception if no data collected yet.
        $this->evidence->serialize();
    }
}
