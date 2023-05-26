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
 * Collects and preserves evidence on the model itself, e.g. the learned weights
 *
 * @package     tool_laaudit
 * @copyright   2023 Linda Fernsel <fernsel@htw-berlin.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_laaudit;

class model extends evidence {
    public function store() {
        // TODO: Implement store() method.
    }

    protected function get_file_type() {
        return 'zip';
    }

    public function collect($options) {
        if(!isset($options['data'])) {
            throw new \Exception('Missing training data');
        }
        if(!isset($options['predictor'])) {
            throw new \Exception('Missing predictor');
        }
        // todo: get from training data

        // get only samples and targets
        $datawithoutheader = [];
        foreach($options['data'] as $arr) {
            $datawithoutheader = array_slice($arr, 1, null, true);

            break;
        }

        $trainx = [];
        $trainy = [];
        $n_columns = 0;
        foreach($datawithoutheader as $row) {
            $xs = array_slice($row, 0, $n_columns - 1);
            $y = end($row);

            $trainx[] = $xs;
            $trainy[] = $y;
        }
        
        //next: check whether there is enough data - at least two samples per target?

        // currently always returns a logistic regression classifier
        // (https://github.com/moodle/moodle/blob/MOODLE_402_STABLE/lib/mlbackend/php/classes/processor.php#L548)
        $this->data = $options['predictor']->instantiate_algorithm();
        $this->data->train($trainx, $trainy);
    }

    public function serialize() {
        // TODO: Implement serialize() method.
    }
}
