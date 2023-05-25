<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * The test dataset class, inheriting from the dataset class.
 * Collects and preserves evidence on test data used by the model
 *
 * @package     tool_laaudit
 * @copyright   2023 Linda Fernsel <fernsel@htw-berlin.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_laaudit;
class test_dataset extends dataset {

    public function collect($options) {
        if(!isset($options['data'])) {
            throw new \Exception('Missing split dataset');
        }
        if(!isset($options['testsize'])) {
            throw new \Exception('Missing test size');
        }

        $key = array_keys((array) ($options['data']))[0];
        $testdatawithheader = [];
        foreach($options['data'] as $arr) { // each analysisinterval has an object
            $totaldatapoints = sizeof($arr) - 1;
            $testdatapoints = round($options['testsize'] * $totaldatapoints);

            $upperlimit = $testdatapoints + 1; // + 1 for the heading, upper limit is exclusive

            $testdatawithheader[$key] = array_slice($arr, 0, $upperlimit, true);

            break;
        }

        $this->data = $testdatawithheader;
    }
}
