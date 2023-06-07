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
 * The training dataset class, inheriting from sthe dataset class.
 *
 * @package     tool_laaudit
 * @copyright   2023 Linda Fernsel <fernsel@htw-berlin.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_laaudit;

/**
 * Class for the training dataset evidence item.
 */
class training_dataset extends dataset {
    /**
     * Retrieve the training portion of a data set, that is the first p% of data points.
     * Store resulting data (sampleid, features, label) in the data field.
     *
     * @param array $options = [$data, $testsize]
     * @return void
     */
    public function collect($options) {
        if(!isset($options['data'])) {
            throw new \Exception('Missing split dataset');
        }
        if(!isset($options['testsize'])) {
            throw new \Exception('Missing test size');
        }

        $key = array_keys((array) ($options['data']))[0];
        $trainingdatawithheader = [];
        foreach($options['data'] as $arr) { // each analysisinterval has an object
            $totaldatapoints = sizeof($arr) - 1;
            $testdatapoints = round($options['testsize'] * $totaldatapoints);

            $lowerlimit = $testdatapoints + 1;

            $header = array_slice($arr, 0, 1, true);
            $trainingdata = array_slice($arr, $lowerlimit, null, true);

            $trainingdatawithheader[$key] = $header + $trainingdata;
            break;
        }

        $this->data = $trainingdatawithheader;
    }
}
