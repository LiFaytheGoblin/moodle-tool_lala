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
require_once(__DIR__ . '/fixtures/test_course_with_students.php');
require_once(__DIR__ . '/fixtures/test_analyser.php');
require_once(__DIR__ . '/evidence_testcase.php');

/**
 * Dataset test.
 *
 * @package     tool_laaudit
 * @copyright   2023 Linda Fernsel <fernsel@htw-berlin.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class dataset_test extends evidence_testcase {
    /**
     * Set up resources before each test.
     */
    protected function setUp(): void {
        parent::setUp();

        $this->evidence = $this->get_evidence_instance();
    }

    /**
     * Get an evidence instance for the version id.
     * @return evidence
     * @throws Exception
     */
    protected function get_evidence_instance() : evidence {
        return dataset::create_scaffold_and_get_for_version($this->versionid);
    }
    /**
     * Data provider for {@see test_dataset_collect()}.
     *
     * @return array List of source data information
     */
    public function tool_laaudit_get_source_data_parameters_provider() : array {
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
     * @throws Exception
     * @throws Exception
     */
    public function test_evidence_collect(int $nstudents, int $createddaysago): void {
        $this->create_test_data($nstudents, $createddaysago);

        $options = $this->get_options();
        $this->evidence->collect($options);

        $rawdata = $this->evidence->get_raw_data();
        $this->assertTrue(isset($rawdata));
        $this->assertEquals(1, count($rawdata));

        $res = $rawdata[test_model::ANALYSISINTERVAL];

        $resheader = array_slice($res, 0, 1, true)[0];
        $resdata = array_slice($res, 1, null, true);

        $expectedheadersize = count(test_model::get_indicator_instances()) + 1; // Header should contain indicator and target names.
        $this->assertEquals($expectedheadersize, count($resheader));

        $this->assertEquals(count($resdata), $nstudents * floor($createddaysago / 3));

        // Test serialize().
        $this->evidence->serialize();

        $serializedstring = $this->evidence->get_serialized_data();

        $expectedheadersize = count(test_model::get_indicator_instances()) + 1;
        $this->assertTrue(strlen($serializedstring) >= $expectedheadersize); // The string should contain at least a header.
        $this->assertTrue(str_contains($serializedstring, ',')); // The string should have commas.
    }

    /**
     * Check that collect throws an error if trying to call it twice for the same evidence.
     *
     * @covers ::tool_laaudit_dataset_collect
     */
    public function test_evidence_collect_error_again(): void {
        $this->create_test_data();
        parent::test_evidence_collect_error_again();
    }

    /**
     * Check that collect throws an error if data is missing.
     *
     * @covers ::tool_laaudit_dataset_collect
     */
    public function test_evidence_collect_error_nodata(): void {
        $options = $this->get_options();

        $this->expectException(Exception::class); // Expect exception if trying to collect but no data exists.
        $this->evidence->collect($options);
    }

    /**
     * Check that collect works even if the original model has been deleted.
     *
     * @covers ::tool_laaudit_dataset_collect
     */
    public function test_dataset_collect_deletedmodel(): void {
        $nstudents = 3;
        $createddaysago = 3;
        $this->create_test_data($nstudents, $createddaysago);
        test_model::delete($this->modelid);

        $options = $this->get_options();

        $this->evidence->collect($options);

        $rawdata = $this->evidence->get_raw_data();
        $this->assertTrue(isset($rawdata));
        $this->assertEquals(1, count($rawdata));

        $res = $rawdata[test_model::ANALYSISINTERVAL];
        $this->assertTrue(count($res) == $nstudents + 1);
        $resdata = array_slice($res, 1, null, true);
        $this->assertEquals(count($resdata), $nstudents * floor($createddaysago / 3));
    }

    /**
     * Check that get_shuffled returns a shuffled array.
     *
     * @covers ::tool_laaudit_dataset_helper_get_shuffled
     */
    public function test_dataset_helper_get_shuffled() : void {
        $data = test_dataset_evidence::create(10);

        $res = dataset_helper::get_shuffled($data);

        $this->assertFalse(json_encode($data) == json_encode($res));
        $this->assertEquals(count($data), count($res));
        $this->assertTrue(str_contains(json_encode($res), json_encode(test_dataset_evidence::get_header())));
    }

    /**
     * Create test data.
     *
     * @param int $nstudents amount of students
     * @param int $createddaysago how many days ago a sample course should have been started
     */
    protected function create_test_data(int $nstudents = 3, int $createddaysago = 3): void {
        test_course_with_students::create($this->getDataGenerator(), $nstudents, $createddaysago);
    }

    /**
     * Get the options object needed for collecting this evidence.
     *
     * @return array
     * @throws Exception
     * @throws Exception
     */
    public function get_options(): array {
        return [
                'contexts' => [],
                'analyser' => test_analyser::create($this->modelid),
                'modelid' => $this->modelid,
        ];
    }
}
