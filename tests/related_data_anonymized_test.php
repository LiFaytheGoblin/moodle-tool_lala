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

namespace tool_lala;

use Exception;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/fixtures/test_course_with_students.php');
require_once(__DIR__ . '/related_data_test.php');

/**
 * Dataset test.
 *
 * @package     tool_lala
 * @copyright   2023 Linda Fernsel <fernsel@htw-berlin.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class related_data_anonymized_test extends related_data_test {
    /**
     * Get an evidence instance for the version id.
     * @return evidence
     * @throws Exception
     */
    protected function get_evidence_instance() : evidence {
        return related_data_anonymized::create_scaffold_and_get_for_version($this->versionid);
    }

    /**
     * Data provider for {@see test_related_data_collect()}.
     *
     * @return array List of source data information
     */
    public function tool_lala_all_parameters_provider() : array {
        return [
                'Min user, min days, table user' => [
                        'nstudents' => 3,
                        'createddaysago' => 3,
                        'tablename' => 'user',
                        'nrowsexpected' => 5, // Moodle has two default users.
                ],
                'Min user, some days, table user' => [
                        'nstudents' => 3,
                        'createddaysago' => 10,
                        'tablename' => 'user',
                        'nrowsexpected' => 5,
                ],
                'Some users, min days, table user' => [
                        'nstudents' => 10,
                        'createddaysago' => 3,
                        'tablename' => 'user',
                        'nrowsexpected' => 12,
                ],
                'Min user, min days, table user_enrolments' => [
                        'nstudents' => 3,
                        'createddaysago' => 3,
                        'tablename' => 'user_enrolments',
                        'nrowsexpected' => 3,
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
                        'nrowsexpected' => 9
                ],
        ];
    }

    /**
     * Check that collect gathers all necessary data
     *
     * @covers ::tool_lala_dataset_pseudonomize
     */
    public function test_evidence_pseudonomize(): void {
        $typemain = 'test';
        $typesecondary = 'other';
        $secondaryfieldname = $typesecondary.'id';
        $ternaryfieldname = 'alsomentioned'.$secondaryfieldname.'id';
        $data = [
              0 => (object) [
                    'id' => 1,
                    $secondaryfieldname => 4,
                    'someprop' => 'test1',
                    $ternaryfieldname => 5
              ],
              1 => (object) [
                   'id' => 2,
                   $secondaryfieldname => 5,
                   'someprop' => 'test2',
                   $ternaryfieldname => 5
              ],
              2 => (object) [
                   'id' => 3,
                   $secondaryfieldname => 6,
                   'someprop' => 'test3',
                   $ternaryfieldname => 4
              ]
        ];

        $pseudonymsmain = [7, 8 , 9];
        $idmapmain = new idmap(array_column($data, 'id'), $pseudonymsmain);

        $pseudonymssecondary = [10, 11, 12];
        $secondaryids = array_column($data, $secondaryfieldname);
        $idmapsecondary = new idmap($secondaryids, $pseudonymssecondary);

        $pseudonomizeddata = $this->evidence->pseudonomize($data, [$typemain => $idmapmain, $typesecondary => $idmapsecondary],
                $typemain);
        $this->assertTrue(isset($pseudonomizeddata));
        $this->assertEquals(3, count($pseudonomizeddata));

        // All needed new ids made it to the pseudonomized dataset & structure is ok.
        $missingpseudonyms = array_diff($idmapmain->get_pseudonyms(), related_data::get_ids_used($pseudonomizeddata));
        $this->assertEquals(0, count($missingpseudonyms));

        $secondarypseudonyms = $idmapsecondary->get_pseudonyms();
        $missingsecondarypseudonyms = array_diff($secondarypseudonyms, array_column($pseudonomizeddata, $secondaryfieldname));
        $this->assertEquals(0, count($missingsecondarypseudonyms));

        $missingternarypseudonyms = array_diff(array_column($pseudonomizeddata, $ternaryfieldname), $secondarypseudonyms);
        $this->assertEquals(0, count($missingternarypseudonyms));

        // The value for each new id is the value we have in dataset for the fitting old id, but the secondary id changed.
        $originalids = array_column($data, 'id');
        foreach ($pseudonomizeddata as $actualvalues) {
            $pseudonymmain = $actualvalues->id;
            $originalid = $idmapmain->get_originalid($pseudonymmain);
            $originalindex = array_search($originalid, $originalids);
            $expectedtestvalue = $data[$originalindex]->someprop;
            $actualtestvalue = $actualvalues->someprop;
            $this->assertEquals($expectedtestvalue, $actualtestvalue);

            $originalsecondaryid = $data[$originalindex]->$secondaryfieldname;
            $newsecondaryid = $actualvalues->$secondaryfieldname;
            $this->assertNotEquals($originalsecondaryid, $newsecondaryid);

            $originalternaryid = $data[$originalindex]->$ternaryfieldname;
            $newternaryid = $actualvalues->$ternaryfieldname;
            $this->assertNotEquals($originalternaryid, $newternaryid);
        }
    }

    /**
     * Data provider for {@see test_evidence_collect_error_notenoughdata()}.
     *
     * @return array List of source data information
     */
    public function tool_lala_get_source_data_error_notenoughdata_parameters_provider() : array {
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
     * @covers ::tool_lala_dataset_anonymized_collect
     *
     * @dataProvider tool_lala_get_source_data_error_notenoughdata_parameters_provider
     * @param int $nstudents amount of students
     * @param int $createddaysago how many days ago a sample course should have been started
     * @throws Exception
     * @throws Exception
     */
    public function test_evidence_collect_error_notenoughdata(int $nstudents, int $createddaysago): void {
        $this->create_test_data($nstudents, $createddaysago);
        $options = $this->get_options('user_enrolments');

        $this->expectException(Exception::class); // Expect exception if trying to collect but too little data exists.
        $this->evidence->collect($options);
    }

    /**
     * Get the options object needed for collecting this evidence.
     *
     * @param string $tablename
     * @return array
     * @throws Exception
     * @throws Exception
     */
    public function get_options(string $tablename = 'user'): array {
        $options = parent::get_options($tablename);
        $ids = test_course_with_students::get_ids($tablename);
        $pseudonyms = range(1, count($ids));
        $options['idmap'] = new idmap($ids, $pseudonyms);
        return $options;
    }
}
