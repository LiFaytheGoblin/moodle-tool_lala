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
 *
 * @package     tool_lala
 * @copyright   2023 Linda Fernsel <fernsel@htw-berlin.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_lala;

use InvalidArgumentException;
use LengthException;
use LogicException;

/**
 * Class for the test dataset evidence item.
 */
class test_dataset extends dataset {
    /**
     * Retrieve the test portion of a data set, that is the last p% of data points.
     * Store resulting data (sampleid, features, label) in the data field.
     *
     * @param array $options = [$data, $testsize]
     */
    public function collect(array $options): void {
        $this->validate($options);

        $datawithoutheader = dataset_helper::get_rows($options['data']);

        $ntotaldatapoints = count($datawithoutheader);
        $ntestdatapoints = round($options['testsize'] * $ntotaldatapoints);

        if ($ntestdatapoints < 1) {
            throw new LengthException('Not enough data available for creating a training and testing split. Need at least
            1 datapoint for testing, and 2 for training.');
        }

        $newrows = array_slice($datawithoutheader, 0, $ntestdatapoints, true);

        $this->data = dataset_helper::replace_rows_in_dataset($options['data'], $newrows);
    }

    /**
     * Validates this evidence's options.
     *
     * @param array $options
     */
    public function validate(array $options): void {
        if (!isset($options['data'])) {
            throw new InvalidArgumentException('Missing dataset that can be split.');
        }
        if (!isset($options['testsize'])) {
            throw new InvalidArgumentException('Missing test size.');
        }
        if (count($options['data']) == 0) {
            throw new InvalidArgumentException('Dataset can not be empty. No test data can be extracted from it.');
        }
        if ($options['testsize'] <= 0 || $options['testsize'] >= 1) {
            throw new InvalidArgumentException('Testsize must be a float between 0 and 1.');
        }
        if (isset($this->data) && count($this->data) > 0) {
            throw new LogicException('Data has already been collected and can not be changed.');
        }
    }
}
