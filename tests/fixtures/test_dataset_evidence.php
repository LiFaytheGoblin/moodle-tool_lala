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
     * Creates a random dataset evidence.
     *
     * @param int $size of the dataset
     * @return array the created dataset
     */
    public static function create(int $size = 3): array {
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

    /**
     * Gets a test header fitting the test model.
     *
     * @return array the header
     */
    public static function get_header(): array {
        $header = json_decode(test_model::INDICATORS);
        $header[] = test_model::TARGET;
        return $header;
    }

    /**
     * Create a row of random indicator values.
     *
     * @param int $size amount of indicator values
     * @return array the indicator values
     */
    public static function create_x(int $size = 3): array {
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
