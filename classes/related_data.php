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
 * @package     tool_lala
 * @copyright   2023 Linda Fernsel <fernsel@htw-berlin.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_lala;

use InvalidArgumentException;
use LogicException;

/**
 * Class for the complete dataset evidence item.
 */
class related_data extends dataset {
    /** @var string[] IGNORED_COLUMNS columns to ignore when retrieving the data */
    const IGNORED_COLUMNS = [];
    /** @var string|null $tablename to which the related data belongs */
    protected ?string $tablename;

    /**
     * Retrieve all relevant data related to the analysable samples.
     *
     * @param array $options = [string $tablename, array $ids]
     */
    public function collect(array $options): void {
        $this->validate_collect_options($options);
        $this->fail_if_attempting_to_overwrite();

        $this->tablename = $options['tablename'];

        global $DB;
        $possiblecolumns = database_helper::get_possible_column_names($this->tablename);
        $keptcolumns = array_diff($possiblecolumns, $this::IGNORED_COLUMNS);
        $fieldsstring = implode(',', $keptcolumns);
        $records = $DB->get_records_list($this->tablename, 'id', $options['ids'], null, $fieldsstring);

        $this->data = $records;
    }

    /**
     * Validate the options of this evidence.
     *
     * @param array $options
     */
    public function validate_collect_options(array $options) : void {
        if (!isset($options['tablename'])) {
            throw new InvalidArgumentException('Options is missing the name of the related table.');
        }
        if (!isset($options['ids'])) {
            throw new InvalidArgumentException('Options is missing the ids the data should be related to.');
        }
    }

    /**
     * Serialize the contents of the data field.
     * Store the serialization string in the filestring field.
     */
    public function serialize(): void {
        $this->filestring = related_data_helper::serialize($this->data);
    }

    /**
     * Getter for the tablename.
     *
     * @return string tablename
     */
    public function get_tablename(): string {
        return $this->tablename;
    }

    /**
     * Returns info on the serialized data file on the server.
     * @return array
     */
    public function get_file_info(): array {
        $info = parent::get_file_info();
        $info['filename'] = 'modelversion' . $this->versionid . '-evidence' . $this->name . $this->id . '-' . $this->tablename .
                '.' . $this->get_file_type();
        return $info;
    }
}
