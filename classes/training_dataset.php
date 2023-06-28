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
        if (!isset($options['data'])) {
            throw new \Exception('Missing dataset that can be split.');
        }
        if (!isset($options['testsize'])) {
            throw new \Exception('Missing test size.');
        }
        if (sizeof($options['data']) == 0) {
            throw new \Exception('Dataset is empty. No training data can be extracted from it.');
        }

        if (isset($this->data) && sizeof($this->data) > 0) {
            throw new \Exception('Data has already been collected and can not be changed.');
        }

        $key = array_keys((array) ($options['data']))[0];
        $trainingdatawithheader = [];
        foreach ($options['data'] as $arr) { // Each analysisinterval has an object.
            $totaldatapoints = count($arr) - 1;
            $testdatapoints = round($options['testsize'] * $totaldatapoints);
            if($testdatapoints < 1) {
                throw new \Exception('Not enough data available for creating a training and testing split. Need at least 1 datapoint for testing, and 2 for training.');
            }

            $lowerlimit = $testdatapoints + 1;

            $header = array_slice($arr, 0, 1, true);
            $trainingdata = array_slice($arr, $lowerlimit, null, true);

            if (count($trainingdata) < 2) {
                throw new \Exception('Not enough data available for creating a training split. Need at least 2 datapoints.');
            }

            $trainingdatawithheader[$key] = $header + $trainingdata;
            break;
        }

        $this->data = $trainingdatawithheader;
    }
}
