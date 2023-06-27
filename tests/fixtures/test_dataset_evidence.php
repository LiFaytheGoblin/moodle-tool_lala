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
 * Test dataset (evidence).
 *
 * @package     tool_laaudit
 * @copyright   2023 Linda Fernsel <fernsel@htw-berlin.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_laaudit;

defined('MOODLE_INTERNAL') || die();
class test_dataset_evidence {
    /**
     * Stores a model in the db and returns a modelid
     *
     * @return array
     */
    public static function create($size = 3) {
        $header = self::get_header();
        $content = [
                '0' => $header
        ];
        for($i = 1; $i <= $size; $i++) {
            foreach($header as $ignored) {
                $content[$i][] = random_int(0, 1);
            }
        }
        return [
                test_model::ANALYSISINTERVAL => $content
        ];
    }

    public static function get_header() {
        $header = json_decode(test_model::INDICATORS);
        $header[] = test_model::TARGET;
        return $header;
    }

    public static function create_x($size = 3) {
        $indicators = json_decode(test_model::INDICATORS);
        $xs = [];
        for($i = 0; $i < $size; $i++) {
            foreach($indicators as $ignored) {
                $xs[$i][] = random_int(0, 1);
            }
        }
        return $xs;
    }
}
