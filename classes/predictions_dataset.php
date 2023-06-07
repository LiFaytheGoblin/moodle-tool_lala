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
 * The predictions dataset class, inheriting from the dataset class.
 * Collects and preserves evidence on predictions made by the model
 *
 * @package     tool_laaudit
 * @copyright   2023 Linda Fernsel <fernsel@htw-berlin.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_laaudit;

class predictions_dataset extends dataset {
    function collect($options) {
        if(!isset($options['model'])) {
            throw new \Exception('Missing trained model');
        }

        if(!isset($options['data'])) {
            throw new \Exception('Missing test dataset');
        }

        // Get the test data without analysisinterval container and header
        $header = [];
        $testdata = [];
        $analysisintervalkey = array_keys((array) ($options['data']))[0];
        foreach ($options['data'] as $arr) {
            $header = $arr['0'];
            $testdata = array_slice($arr, 1, null, true);
            break;
        }

        // Extract the sample ids, x and y values from the test set
        $sampleids = array_keys($testdata);
        $testx = [];
        $testy = [];
        foreach($testdata as $row) {
            $len = sizeof($row);
            $testx[] = array_slice($row, 0, $len - 1, true);
            $testy[] = $row[$len - 1];
        }

        // Get predictions
        $predictedlabels = $options['model']->predict($testx);

        // Build dataset back together and get the structure Moodle usually works with
        $header[] = 'prediction';
        $mergeddata = [];
        $mergeddata['0'] = $header;
        foreach($sampleids as $key => $sampleid) {
            $mergeddata[$sampleid] = array_merge($testx[$key], [$testy[$key], $predictedlabels[$key]]);
        }

        $res = [];
        $res[$analysisintervalkey] = $mergeddata;

        $this->data = $res;
    }
}
