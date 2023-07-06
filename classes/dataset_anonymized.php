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
 * The dataset class.
 *
 * @package     tool_laaudit
 * @copyright   2023 Linda Fernsel <fernsel@htw-berlin.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_laaudit;

use core_analytics\local\analysis\result_array;
use core_analytics\analysis;
use DomainException;
use InvalidArgumentException;
use LengthException;
use LogicException;

/**
 * Class for the complete dataset evidence item.
 */
class dataset_anonymized extends dataset {
    /**
     * Retrieve all available analysable samples, calculate features and label.
     * Store resulting data (sampleid, features, label) in the data field.
     *
     * @param array $options = [$modelid, $analyser, $contexts]
     * @return void
     */
    public function collect(array $options): void {
        parent::collect($options);
        $pseudonomized = $this->pseudonomize($this->data);
        $this->data = $pseudonomized;
    }

    /**
     * @return array [$data, $oldkeys, $newkeys]
     */
    public static function pseudonomize(array $data): array {
        $res = [];
        foreach ($data as $resultskey => $results) {
            $values = array_values($results);
            $keysold = array_keys($results);
            $header = $keysold['0'];

            $keysnew = range(1, sizeof($keysold) - 1); // minus header
            array_unshift($keysnew, $header);

            $res[$resultskey] = array_combine($keysnew, $values);
        }
        return $res;
    }
}
