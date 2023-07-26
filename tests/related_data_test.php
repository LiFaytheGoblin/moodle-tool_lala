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
                        'nrowsexpected' => 3, // Moodle has two default users.
                ],
                'Min user, some days, table user' => [
                        'nstudents' => 1,
                        'createddaysago' => 10,
                        'tablename' => 'user',
                        'nrowsexpected' => 3,
                ],
                'Some users, min days, table user' => [
                        'nstudents' => 10,
                        'createddaysago' => 3,
                        'tablename' => 'user',
                        'nrowsexpected' => 12,
                ],
                'Min user, min days, table user_enrolments' => [
                        'nstudents' => 1,
                        'createddaysago' => 3,
                        'tablename' => 'user_enrolments',
                        'nrowsexpected' => 1,
                ],
                'Some users, some days, table user_enrolments' => [
                        'nstudents' => 10,
                        'createddaysago' => 10,
                        'tablename' => 'user_enrolments',
                        'nrowsexpected' => 10,
                ],
                'Some users, min days, table enrol' => [
                        'nstudents' => 10,
                        'createddaysago' => 3,
                        'tablename' => 'enrol',
                        'nrowsexpected' => 3,
                ],
                'Some users, min days, table course' => [
                        'nstudents' => 10,
                        'createddaysago' => 3,
                        'tablename' => 'course',
                        'nrowsexpected' => 2, // There's also a site entry in the course table.
                ],
                'Some users, min days, table role' => [
                        'nstudents' => 10,
                        'createddaysago' => 3,
                        'tablename' => 'role',
                        'nrowsexpected' => 9,
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
     * @param string $tablename name of the table to collect data from
     * @param int $nrowsexpected amount of rows to be returned
     */
    public function test_related_data_collect(int $nstudents, int $createddaysago, string $tablename, int $nrowsexpected): void {
        $this->create_test_data($nstudents, $createddaysago);

        $options = $this->get_options($tablename);
        $this->evidence->collect($options);

        $rawdata = $this->evidence->get_raw_data();

        // Check that all datapoints are there.
        $this->assertEquals($nrowsexpected, count($rawdata));

        // Check that all ids are there.
        $ids = related_data::get_ids_used($rawdata);
        $expectedids = test_course_with_students::get_ids($tablename);
        $missingids = array_diff($expectedids, $ids);
        $this->assertEquals(0, count($missingids));

        $unnecessaryids = array_diff($ids, $expectedids);
        $this->assertEquals(0, count($unnecessaryids));

        // Check that header has correct size.
        $anyid = array_values($expectedids)[0];

        $resheader = array_keys((array) $rawdata[$anyid]);
        $expectedheadersize = count(database_helper::get_possible_column_names($tablename));
        $this->assertTrue($expectedheadersize >= count($resheader));

        // Check that header does not contain forbidden columns.
        $notinheader = array_diff(related_data::IGNORED_COLUMNS, $resheader);
        $this->assertEquals(related_data::IGNORED_COLUMNS, $notinheader);

        // Test serialize().
        $this->evidence->serialize();

        $serializedstring = $this->evidence->get_serialized_data();

        $this->assertTrue(strlen($serializedstring) >= $expectedheadersize); // The string should contain at least a header.
        $this->assertTrue(str_contains($serializedstring, ',')); // The string should have commas.
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
     * Data provider for {@see test_dataset_collect_deletedmodel()}.
     *
     * @return array List of source data information
     */
    public function tool_laaudit_deleted_model_parameters_provider() : array {
        return [
                'Regular test case' => [
                        'nstudents' => 3,
                        'createddaysago' => 3,
                ],
        ];
    }

    /**
     * Check that collect works even if the original model has been deleted.
     *
     * @covers ::tool_laaudit_related_data_collect
     *
     * @dataProvider tool_laaudit_deleted_model_parameters_provider
     * @param int $nstudents amount of students
     * @param int $createddaysago how many days ago a sample course should have been started
     */
    public function test_dataset_collect_deletedmodel(int $nstudents, int $createddaysago): void {
        $this->create_test_data($nstudents, $createddaysago);
        test_model::delete($this->modelid);

        $options = $this->get_options();

        $this->evidence->collect($options);

        $rawdata = $this->evidence->get_raw_data();

        // Quickly verify rawdata.
        $expectedids = test_course_with_students::get_ids('user');
        $ids = related_data::get_ids_used($rawdata);
        $missingids = array_diff($expectedids, $ids);
        $this->assertEquals(0, count($missingids));

        $unnecessaryids = array_diff($ids, $expectedids);
        $this->assertEquals(0, count($unnecessaryids));
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
     * @param string $tablename
     * @return array
     */
    public function get_options(string $tablename = 'user'): array {
        return [
                'tablename' => $tablename,
                'ids' => test_course_with_students::get_ids($tablename)
        ];
    }
}
