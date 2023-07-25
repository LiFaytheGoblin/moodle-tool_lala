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

use testing_data_generator;
use analytics\target\course_gradetopass;
use core_analytics\manager;
use core_analytics\course;

/**
 * Test course with students.
 *
 * @package     tool_laaudit
 * @copyright   2023 Linda Fernsel <fernsel@htw-berlin.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class test_course_with_students {
    /**
     * Generates a new course with students, but no activity.
     *
     * @param testing_data_generator $generator
     * @param int $nstudents
     * @param int $createddaysago
     */
    public static function create(testing_data_generator $generator, int $nstudents = 10, int $createddaysago = 7): void {
        $secondsperday = 60 * 60 * 24;
        $timestart = time() - ($secondsperday * $createddaysago);
        $timeend = time() - 1; // The course must have finished.

        $users = [];
        for ($i = 0; $i < $nstudents; $i++) {
            $users[] = $generator->create_user();
        }
        $course = $generator->create_course(['startdate' => $timestart, 'enddate' => $timeend]);
        $generator->create_grade_item(['itemtype' => 'course', 'courseid' => $course->id, 'gradepass' => 2.0]);

        foreach ($users as $user) {
            $generator->enrol_user($user->id, $course->id, null, 'manual', $timestart + 1, $timeend);
        }
    }

    /**
     * Get the ids used in a table.
     *
     * @param string $tablename
     * @return mixed
     */
    public static function get_ids(string $tablename) : array {
        global $DB;
        return $DB->get_fieldset_select($tablename, 'id', '1=1');
    }

    /**
     * Get the ids of a table $referee referenced by a table $referer
     * @param string $referee
     * @param string $referer
     * @return array
     */
    public static function get_ids_for_referenced_by(string $referee, string $referer) : array {
        global $DB;
        $fieldset = $DB->get_fieldset_select($referer, $referee.'id', '1=1');
        return array_unique($fieldset);
    }
}
