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
class related_data_old extends evidence {
    /**
     * Retrieve all available data related to the analysable samples.
     *
     * @param array $options = [$modelid, $analyser, $contexts]
     * @return void
     */
    public function collect(array $options): void {
        if (!isset($options['analyser'])) {
            throw new InvalidArgumentException('Options is missing analyser.');
        }
        if (!isset($options['dataset'])) {
            throw new InvalidArgumentException('Options is missing dataset.');
        }
        if (isset($this->data) && sizeof($this->data) > 0) {
            throw new LogicException('Data has already been collected and can not be changed.');
        }

        $this->heavy_duty_mode();

        $tablestoretrieve = [$options['analyser']->get_samples_origin()];
        // todo: add related tables.

        $this->data = [];
        global $DB;
        foreach ($tablestoretrieve as $tablename) {
            $records = $DB->get_records($tablename);
            // todo: filter out only those records that we need.
            $this->data[$tablename] = $records;
        }
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

        foreach ($this->data as $set) {
            // Todo: serialize.
        }

        // Create a csv per array entry, then bundle in zip.

        $heading = $columns;
        $this->filestring = $heading.$str;
    }

    /**
     * Stores a serialized data string in a file. Sets the serializedfilelocation property of the class.
     * @return void
     */
    public function store(): void {
        if (!isset($this->filestrings)) {
            throw new Exception('No data has been serialized for this evidence yet.');
        }
        $fileinfo = $this->get_file_info();

        $fs = get_file_storage();

        foreach ($this->filestrings as $filestring) {
            $fs->create_file_from_string($fileinfo, $filestring);
            // todo: create zip
        }

        $this->set_serializedfilelocation();
    }

    /**
     * Returns the type of the stored file.
     *
     * @return string the file type of the serialized data.
     */
    public function get_file_type(): string {
        return 'zip';
    }

    /**
     * Increases system memory and time limits.
     *
     * @return void
     */
    private function heavy_duty_mode(): void {
        if (ini_get('memory_limit') != -1) {
            raise_memory_limit(MEMORY_HUGE);
        }
        \core_php_time_limit::raise();
    }
}
