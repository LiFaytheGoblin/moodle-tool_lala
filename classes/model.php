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

use DomainException;
use InvalidArgumentException;
use LengthException;
use LogicException;
use Phpml\Classification\Linear\LogisticRegression;

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
    public function collect(array $options): void {
        $this->validate($options);

        // Get only samples and targets.
        $datawithoutheader = dataset_helper::get_rows($options['data']);
        if (count($datawithoutheader) < 2) {
            throw new LengthException('Not enough training data. Need to provide at least 2 datapoints.');
        }

        // Separate rows into x and y values.
        $trainxys = dataset_helper::get_separate_x_y_from_rows($datawithoutheader);
        if (count($trainxys['x'][0]) < 1) {
            throw new LengthException('Need to provide at least one column of indicator values in the training data.');
        }

        // Currently always uses a logistic regression classifier.
        // (https://github.com/moodle/moodle/blob/MOODLE_402_STABLE/lib/mlbackend/php/classes/processor.php#L548).
        $iterations = $options['predictor']::TRAIN_ITERATIONS;
        $this->data = new LogisticRegression($iterations, true, LogisticRegression::CONJUGATE_GRAD_TRAINING, 'log');
        $this->data->train($trainxys['x'], $trainxys['y']);
    }

    /** Validate the options.
     * @param array $options
     * @return void
     */
    public function validate(array $options): void {
        if (!isset($options['predictor'])) {
            throw new InvalidArgumentException('Options array is missing predictor.');
        }
        if (!isset($options['data'])) {
            throw new InvalidArgumentException('Options array is missing training data.');
        }
        if (count($options['data']) == 0) {
            throw new DomainException('Training dataset can not be empty.');
        }
        if (isset($this->data)) {
            throw new LogicException('Model has already been trained and can not be changed.');
        }
    }

    /**
     * Serializes the model.
     * Store the serialization string in the filestring field.
     *
     * @return void
     */
    public function serialize(): void {
        $str = serialize($this->data);
        $this->filestring = $str;
    }

    /**
     * Returns the type of the stored file.
     * @return string
     */
    protected function get_file_type(): string {
        return 'ser';
    }


}
