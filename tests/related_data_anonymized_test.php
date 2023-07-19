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

require_once(__DIR__ . '/fixtures/test_course_with_students.php');
require_once(__DIR__ . '/related_data_test.php');

/**
 * Dataset test.
 *
 * @package     tool_laaudit
 * @copyright   2023 Linda Fernsel <fernsel@htw-berlin.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class related_data_anonymized_test extends related_data_test {
    protected function get_evidence_instance() : evidence {
        return related_data_anonymized::create_scaffold_and_get_for_version($this->versionid);
    }

    /**
     * Data provider for {@see test_related_data_collect()}.
     *
     * @return array List of source data information
     */
    public function tool_laaudit_all_parameters_provider() : array {
        $expecteduserids = test_course_with_students::get_ids('user');
        $expecteduserenrolmentsids = test_course_with_students::get_ids('user_enrolments');
        $expectedenrolids = test_course_with_students::get_ids('enrol');
        $expectedcourseids = test_course_with_students::get_ids('course');
        $expectedroleids = test_course_with_students::get_ids('role');

        return [
                'Min user, min days, table user' => [
                        'nstudents' => 3,
                        'createddaysago' => 3,
                        'tablename' => 'user',
                        'nrowsexpected' => 5, // Moodle has two default users.
                        'expectedids' => range(1, 5)
                ],
                'Min user, some days, table user' => [
                        'nstudents' => 3,
                        'createddaysago' => 10,
                        'tablename' => 'user',
                        'nrowsexpected' => 5,
                        'expectedids' => range(1, 5)
                ],
                'Some users, min days, table user' => [
                        'nstudents' => 10,
                        'createddaysago' => 3,
                        'tablename' => 'user',
                        'nrowsexpected' => 12,
                        'expectedids' => range(1, 12)
                ],
                'Min user, min days, table user_enrolments' => [
                        'nstudents' => 3,
                        'createddaysago' => 3,
                        'tablename' => 'user_enrolments',
                        'nrowsexpected' => 3,
                        'expectedids' => range(1, 3)
                ],
                'Some users, some days, table user_enrolments' => [
                        'nstudents' => 10,
                        'createddaysago' => 10,
                        'tablename' => 'user_enrolments',
                        'nrowsexpected' => 10,
                        'expectedids' => range(1, 10)
                ],
                'Some users, min days, table enrol' => [
                        'nstudents' => 10,
                        'createddaysago' => 3,
                        'tablename' => 'enrol',
                        'nrowsexpected' => 3,
                        'expectedids' => range(1, 3)
                ],
                'Some users, min days, table course' => [
                        'nstudents' => 10,
                        'createddaysago' => 3,
                        'tablename' => 'course',
                        'nrowsexpected' => 2, // There's also a site entry in the course table
                        'expectedids' => range(1, 2)
                ],
                'Some users, min days, table role' => [
                        'nstudents' => 10,
                        'createddaysago' => 3,
                        'tablename' => 'role',
                        'nrowsexpected' => 9,
                        'expectedids' => range(1, 9)
                ],
        ];
    }

    /**
     * Check that collect gathers all necessary data
     *
     * @covers ::tool_laaudit_dataset_pseudonomize
     */
    public function test_evidence_pseudonomize() {
        $nsamples = 5;

        $data = [
              0 => (object) [
                 'id' => 1,
                  'otherid' => 'a',
                  'someprop' => 'test1'
              ],
              1 => (object) [
                   'id' => 2,
                   'otherid' => 'b',
                   'someprop' => 'test2'
              ],
              2 => (object) [
                   'id' => 3,
                   'otherid' => 'c',
                   'someprop' => 'test3'
              ]
        ];

        $pseudonyms = [4, 5, 6];
        $idmap = new idmap(array_keys($data), $pseudonyms, 'test');

        $pseudonomized_data = $this->evidence->pseudonomize($data, $idmap);
        $this->assertTrue(isset($pseudonomized_data));
        // has correct size
        $this->assertEquals(3, count($pseudonomized_data));

        // All needed new ids made it to the pseudonomized dataset & structure is ok
        $missingpseudonyms = array_diff($idmap->get_pseudonyms(), related_data::get_ids_used($pseudonomized_data));
        $this->assertEquals(0, count($missingpseudonyms));

        // the value for each new id is the value we have in dataset for the fitting old id
        $missingvalues = [];
        foreach ($pseudonomized_data as $actualvalues) {
            $pseudonym = $actualvalues->id;
            $originalid = $idmap->get_originalid($pseudonym);
            $expectedvalues = $this->get_by_id($data, $originalid);
            $missingvaluesforthispseudonym = array_diff($expectedvalues, (array) $actualvalues);
            if (sizeof($missingvaluesforthispseudonym) > 0) $missingvalues[$pseudonym] = $missingvaluesforthispseudonym;
        }
        $this->assertTrue(sizeof($missingvalues) == 0);
    }

    function get_by_id(array $data, mixed $id) : array {
        foreach ($data as $entry) {
            if ($entry->id === $id) {
                return $entry;
            }
        }
        return [];
    }

    /**
     * Data provider for {@see test_evidence_collect_error_notenoughdata()}.
     *
     * @return array List of source data information
     */
    public function tool_laaudit_get_source_data_error_notenoughdata_parameters_provider() : array {
        return [
                'Too few user, min days' => [
                        'nstudents' => 1,
                        'createddaysago' => 3
                ],
                'Still too few user, min days' => [
                        'nstudents' => 2,
                        'createddaysago' => 3
                ]
        ];
    }
    /**
     * Check that collect throws an error if too few users exist.
     *
     * @covers ::tool_laaudit_dataset_anonymized_collect
     *
     * @dataProvider tool_laaudit_get_source_data_error_notenoughdata_parameters_provider
     * @param int $nstudents amount of students
     * @param int $createddaysago how many days ago a sample course should have been started
     */
    public function test_evidence_collect_error_notenoughdata(int $nstudents, int $createddaysago): void {
        $this->create_test_data($nstudents, $createddaysago);
        $options = $this->get_options('user_enrolments');

        $this->expectException(\Exception::class); // Expect exception if trying to collect but too little data exists.
        $this->evidence->collect($options);
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
                        'expectedids' => range(1, 5)
                ],
        ];
    }

    /**
     * Get the options object needed for collecting this evidence.
     * @return array
     */
    public function get_options(string $tablename = 'user'): array {
        $options = parent::get_options($tablename);
        $ids = test_course_with_students::get_ids($tablename);
        $pseudonyms = range(1, count($ids));
        $options['idmap'] = new idmap($ids, $pseudonyms, $tablename);
        return $options;
    }
}
