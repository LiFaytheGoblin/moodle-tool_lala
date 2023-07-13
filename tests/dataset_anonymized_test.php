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
require_once(__DIR__ . '/fixtures/test_course_with_students.php');
require_once(__DIR__ . '/fixtures/test_analyser.php');
require_once(__DIR__ . '/evidence_testcase.php');
require_once(__DIR__ . '/dataset_test.php');

/**
 * Dataset test.
 *
 * @package     tool_laaudit
 * @copyright   2023 Linda Fernsel <fernsel@htw-berlin.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class dataset_anonymized_test extends dataset_test {
    protected function get_evidence_instance() : evidence {
        return dataset_anonymized::create_scaffold_and_get_for_version($this->versionid);
    }

    /**
     * Data provider for {@see test_dataset_anonymized_collect()}.
     *
     * @return array List of source data information
     */
    public function tool_laaudit_get_source_data_parameters_provider() : array {
        return [
                'Min user, min days' => [
                        'nstudents' => 3,
                        'createddaysago' => 3
                ],
                'Min user, some days' => [
                        'nstudents' => 3,
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
     * @covers ::tool_laaudit_dataset_pseudonomize
     */
    public function test_evidence_pseudonomize() {
        $nsamples = 5;
        $data = test_dataset_evidence::create($nsamples);
        $idmap = idmap::create_from_dataset($data, 'test');

        $pseudonomized_data = $this->evidence->pseudonomize($data, $idmap);
        $this->assertTrue(isset($pseudonomized_data));
        $this->assertTrue(sizeof($pseudonomized_data) == 1);

        // All needed new ids made it to the pseudonomized dataset & structure is ok
        $res = $pseudonomized_data[test_model::ANALYSISINTERVAL];
        unset($res['0']); // Remove header
        $this->assertTrue(count($res) == $idmap->count());
        $actualnewids = array_keys($res);
        $expectednewids = $idmap->get_pseudonyms();
        $missingnewids = array_diff($actualnewids, $expectednewids);
        $this->assertTrue(sizeof($missingnewids) == 0);

        // the value for each new id is the value we have in dataset for the fitting old id
        $missingvalues = [];
        foreach ($res as $pseudonym => $actualvalues) {
            $originalid = $idmap->get_originalid($pseudonym);
            $expectedvalues = $data[test_model::ANALYSISINTERVAL][$originalid];
            $missingvaluesforthispseudonym = array_diff($expectedvalues, $actualvalues);
            if (sizeof($missingvaluesforthispseudonym) > 0) $missingvalues[$pseudonym] = $missingvaluesforthispseudonym;
        }
        $this->assertTrue(sizeof($missingvalues) == 0);
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
                'Still too few user, some days' => [
                        'nstudents' => 2,
                        'createddaysago' => 10
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
        $options = $this->get_options();

        $this->expectException(\Exception::class); // Expect exception if trying to collect but too little data exists.
        $this->evidence->collect($options);
    }

    /**
     * Check that the idmap is created correctly
     *
     * @covers ::tool_laaudit_dataset_create_new_idmap_from_ids_in_data
     */
    public function test_evidence_create_new_idmap_from_ids_in_data() {
        // todo
    }
}
