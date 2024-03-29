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

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/fixtures/test_dataset_evidence.php');

/**
 * Dataset helper test.
 *
 * @package     tool_lala
 * @copyright   2023 Linda Fernsel <fernsel@htw-berlin.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class dataset_helper_test extends advanced_testcase {
    /**
     * Check that get_ids_used_in_dataset returns the correct amount of ids.
     *
     * @covers ::tool_lala_test_dataset_helper_get_ids_used_in_dataset
     */
    public function test_dataset_helper_get_ids_used_in_dataset(): void {
        $nids = 2;
        $testdataset = test_dataset_evidence::create($nids);
        $ids = dataset_helper::get_ids_used_in_dataset($testdataset);
        $this->assertEquals($nids, count($ids));
    }
}
