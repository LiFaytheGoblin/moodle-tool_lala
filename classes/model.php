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
 * The model class.
 *
 * @package     tool_laaudit
 * @copyright   2023 Linda Fernsel <fernsel@htw-berlin.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_laaudit;

use \Phpml\Classification\Linear\LogisticRegression;

/**
 * Class for the trained model evidence item.
 */
class model extends evidence {

    /**
     * Train a model using the data sent via $options and the $predictor.
     * Store the trained LogisticRegression model as the raw $data of this evidence item.
     *
     * @param array $options = [$data, $predictor]
     * @return void
     */
    public function collect($options) {
        if (!isset($options['data'])) {
            throw new \Exception('Missing training data');
        }
        if (!isset($options['predictor'])) {
            throw new \Exception('Missing predictor');
        }

        // Get only samples and targets.
        $datawithoutheader = [];
        foreach ($options['data'] as $arr) {
            $datawithoutheader = array_slice($arr, 1, null, true);
            break;
        }

        $trainx = [];
        $trainy = [];
        $ncolumns = count(end($datawithoutheader));
        foreach ($datawithoutheader as $row) {
            $xs = array_slice($row, 0, $ncolumns - 1);
            $y = end($row);

            $trainx[] = $xs;
            $trainy[] = $y;
        }

        // Currently always uses a logistic regression classifier.
        // (https://github.com/moodle/moodle/blob/MOODLE_402_STABLE/lib/mlbackend/php/classes/processor.php#L548).
        $iterations = $options['predictor']::TRAIN_ITERATIONS;
        $this->data = new LogisticRegression($iterations, true, LogisticRegression::CONJUGATE_GRAD_TRAINING, 'log');
        $this->data->train($trainx, $trainy);
    }

    /**
     * Serializes the model.
     * Store the serialization string in the filestring field.
     *
     * @return void
     */
    public function serialize() {
        $str = serialize($this->data);
        $this->filestring = $str;
    }

    /**
     * Returns the type of the stored file.
     * @return string
     */
    protected function get_file_type() {
        return 'ser';
    }
}
