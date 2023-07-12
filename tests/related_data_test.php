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

require_once(__DIR__ . '/evidence_testcase.php');

/**
 * Dataset test.
 *
 * @package     tool_laaudit
 * @copyright   2023 Linda Fernsel <fernsel@htw-berlin.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class related_data_test extends evidence_testcase {
    protected function setUp(): void {
        parent::setUp();

        $this->evidence = $this->get_evidence_instance();
    }

    protected function get_evidence_instance() : evidence {
        return related_data::create_scaffold_and_get_for_version($this->versionid);
    }
    /**
     * Data provider for {@see test_related_data_collect()}.
     *
     * @return array List of source data information
     */
    public function tool_laaudit_all_parameters_provider() : array {
        return [
                'Min user, min days, table user' => [
                        'nstudents' => 1,
                        'createddaysago' => 3,
                        'tablename' => 'user',
                ],
                'Min user, some days, table user' => [
                        'nstudents' => 1,
                        'createddaysago' => 10,
                        'tablename' => 'user',
                ],
                'Some users, min days, table user' => [
                        'nstudents' => 10,
                        'createddaysago' => 3,
                        'tablename' => 'user',
                ],
                'Min user, min days, table user_enrolments' => [
                        'nstudents' => 1,
                        'createddaysago' => 3,
                        'tablename' => 'user_enrolments',
                ],
                'Some users, some days, table user_enrolments' => [
                        'nstudents' => 10,
                        'createddaysago' => 10,
                        'tablename' => 'user_enrolments',
                ],
                'Some users, min days, table enrol' => [
                        'nstudents' => 10,
                        'createddaysago' => 3,
                        'tablename' => 'enrol',
                ],
                'Some users, min days, table course' => [
                        'nstudents' => 10,
                        'createddaysago' => 3,
                        'tablename' => 'course',
                ],
                'Some users, min days, table role' => [
                        'nstudents' => 10,
                        'createddaysago' => 3,
                        'tablename' => 'role',
                ],
        ];
    }
    /**
     * Check that collect gathers all necessary data
     *
     * @covers ::tool_laaudit_related_data_collect
     *
     * @dataProvider tool_laaudit_all_parameters_provider
     * @param int $nstudents amount of students
     * @param int $createddaysago how many days ago a sample course should have been started
     */
    public function test_related_data_collect(int $nstudents, int $createddaysago, string $tablename): void {
        $this->create_test_data($nstudents, $createddaysago);

        $options = $this->get_options($tablename);
        $this->evidence->collect($options);

        $rawdata = $this->evidence->get_raw_data();
        // todo: add checks for rawdata
        // rawdata contains all the expected ids

        // Test serialize()
        $this->evidence->serialize();

        $serializedstring = $this->evidence->get_serialized_data();

        $expectedheadersize = sizeof(test_model::get_indicator_instances()) + 1;
        $this->assertTrue(strlen($serializedstring) >= $expectedheadersize); // The string should contain at least a header.
        $this->assertTrue(str_contains($serializedstring, ',')); // the string should have commas.
    }

    /**
     * Check that collect throws an error if trying to call it twice for the same evidence.
     *
     * @covers ::tool_laaudit_related_data_collect
     */
    public function test_evidence_collect_error_again(): void {
        $this->create_test_data();
        parent::test_evidence_collect_error_again();
    }

    /**
     * Check that collect works even if the original model has been deleted.
     *
     * @covers ::tool_laaudit_related_data_collect
     */
    public function test_dataset_collect_deletedmodel(): void {
        $nstudents = 3;
        $createddaysago = 3;
        $this->create_test_data($nstudents, $createddaysago);
        test_model::delete($this->modelid);

        $options = $this->get_options();

        $this->evidence->collect($options);

        $rawdata = $this->evidence->get_raw_data();
        // todo: verify raw data
    }

    /**
     * Check that serialize throws an error if no data can be serialized.
     *
     * @covers ::tool_laaudit_related_data_serialize
     */
    public function test_dataset_serialize_error_nodata(): void {
        $this->expectException(Exception::class); // Expect exception if no data collected yet.
        $this->evidence->serialize();
    }

    /**
     * Check that serialize throws an error if being called again.
     *
     * @covers ::tool_laaudit_related_data_serialize
     */
    public function test_dataset_serialize_error_again(): void {
        $this->create_test_data();
        $options = $this->get_options();

        $this->evidence->collect($options);
        $this->evidence->serialize();

        $this->expectException(Exception::class); // Expect exception if no data collected yet.
        $this->evidence->serialize();
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
     * @return array
     */
    public function get_options(string $tablename = 'user'): array {
        return [
                'tablename' => $tablename,
                'ids' => test_course_with_students::get_ids($tablename)
        ];
    }
}
