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

        // we need to keep the sample ids
        $testx = []; //todo
        $predictedlabels = $options['model']->predict($testx);

        $testy = [];
        // build dataset
        $this->data = [];
    }
}
