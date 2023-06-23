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
 * Dataset get_shuffled test.
 *
 * @package     tool_laaudit
 * @copyright   2023 Linda Fernsel <fernsel@htw-berlin.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class dataset_get_shuffled_test extends \advanced_testcase {
    public function test_dataset_serialize_error_again() {
        $header = [json_decode(test_model::INDICATORS)[0], test_model::TARGET];
        $data = [
                test_model::ANALYSISINTERVAL => [
                    '0' => $header,
                    'a' => [0, 0],
                    'b' => [0, 1],
                    'c' => [1, 0],
                    'd' => [1, 1]
                ]
        ];

        $res = dataset::get_shuffled($data);

        $this->assertFalse(json_encode($data) == json_encode($res));
        $this->assertEquals(sizeof($data), sizeof($res));
        $this->assertTrue(str_contains(json_encode($res), json_encode($header)));
    }
}
