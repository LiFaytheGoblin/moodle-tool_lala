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

/**
 * Test model.
 *
 * @package     tool_laaudit
 * @copyright   2023 Linda Fernsel <fernsel@htw-berlin.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_laaudit;

defined('MOODLE_INTERNAL') || die();
class test_course_with_students {
    /**
     * Generates a new course with students, but no activity.
     */
    public static function create($generator, $nstudents=10, $createddaysago=7) {
        $timestart = time() - (60 * 60 * 24 * $createddaysago);

        $users = [];
        for ($i = 0; $i < $nstudents; $i++) {
            $users[] = $generator->create_user();
        }
        $course = $generator->create_course(['startdate' => $timestart]);

        foreach ($users as $user) {
            $generator->enrol_user($user->id, $course->id, null, 'manual', $timestart + 1);
        }
    }
}
