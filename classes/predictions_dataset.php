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
 *
 * @package     tool_lala
 * @copyright   2023 Linda Fernsel <fernsel@htw-berlin.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_lala;

use InvalidArgumentException;
use LogicException;

/**
 * Class for the predictions dataset evidence item.
 */
class predictions_dataset extends dataset {
    /**
     * Retrieve predictions for certain $data from a $model.
     * Store resulting data (sampleid, target, prediction) in the data field.
     *
     * @param array $options = [$model, $data]
     * @return void
     */
    public function collect(array $options): void {
        $this->validate($options);

        // Get the test data without analysisinterval container and header.
        $datawithoutheader = dataset_helper::get_rows($options['data']);

        // Extract the sample ids, x and y values from the test set.
        $testxys = dataset_helper::get_separate_x_y_from_rows($datawithoutheader);

        // Get predictions.
        $predictedlabels = $options['model']->predict($testxys['x']);

        // Build dataset back together and get the structure Moodle usually works with.
        $analysisintervalkey = dataset_helper::get_analysisintervalkey($options['data']);
        $header = ['target', 'prediction'];
        $sampleids = array_keys($datawithoutheader);
        $this->data = dataset_helper::build($analysisintervalkey, $header, $sampleids, $testxys['y'], $predictedlabels);
    }

    /** Validate the evidence's options.
     * @param array $options
     * @return void
     */
    public function validate(array $options): void {
        if (!isset($options['model'])) {
            throw new InvalidArgumentException('Missing trained model');
        }
        if (!isset($options['data'])) {
            throw new InvalidArgumentException('Missing test dataset');
        }
        if (isset($this->data) && count($this->data) > 0) {
            throw new LogicException('Data has already been collected and can not be changed.');
        }
    }
}
