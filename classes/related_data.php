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
 * The related data evidence class.
 *
 * @package     tool_laaudit
 * @copyright   2023 Linda Fernsel <fernsel@htw-berlin.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_laaudit;

use InvalidArgumentException;
use LogicException;

/**
 * Class for the complete dataset evidence item.
 */
class related_data extends dataset {
    /**
     * Retrieve all available data related to the analysable samples.
     *
     * @param array $options = [$modelid, $analyser, $contexts]
     * @return void
     */
    public function collect(array $options): void {
        if (!isset($options['tablename'])) {
            throw new InvalidArgumentException('Options is missing analyser.');
        }
        if (!isset($options['ids'])) {
            throw new InvalidArgumentException('Options is missing ids.');
        }
        if (isset($this->data) && sizeof($this->data) > 0) {
            throw new LogicException('Data has already been collected and can not be changed.');
        }

        global $DB;
        $records = $DB->get_records($options['tablename']);
        // todo: filter out only those records that we need.
        $this->data = $records;
    }

    /**
     * Serialize the contents of the data field.
     * Store the serialization string in the filestring field.
     *
     * @return void
     */
    public function serialize(): void {
        if (!isset($this->data)) throw new LogicException('No evidence has been collected yet that could be serialized. Make sure to collect the evidence first.');
        if (isset($this->filestring)) {
            throw new LogicException('Data has already been serialized.');
        }

        $str = '';
        $columns = null;

        foreach ($this->data as $record) {
            $arr = (array) $record;
            if (!isset($columns)) {
                $columns = implode(',', array_keys($arr))."\n";;
            }
            $str = $str . implode(',', $arr) . "\n";;
        }

        $heading = $columns;
        $this->filestring = $heading.$str;
    }
}
