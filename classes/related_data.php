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
    /** @var string[] IGNORED_COLUMNS columns to ignore when retrieving the data */
    const IGNORED_COLUMNS = [];
    /** @var string|null $tablename to which the related data belongs */
    protected ?string $tablename;

    /**
     * Extracts the tablename from a serializedfilelocation.
     *
     * @param array $relateddata an array of objects that each have an id.
     * @return array ids
     */
    public static function get_ids_used(array $relateddata): array {
        return array_column($relateddata, 'id');
    }

    public static function get_tablename_from_evidenceid($evidenceid): string|bool {
        global $DB;
        $record = $DB->get_record('tool_laaudit_evidence', ['id' => $evidenceid], '*', MUST_EXIST);
        return self::get_tablename_from_serializedfilelocation($record->serializedfilelocation);
    }

    /**
     * Extracts the tablename from a serializedfilelocation.
     *
     * @param string $serializedfilelocation a path with file name and type
     * @return string|bool tablename
     */
    public static function get_tablename_from_serializedfilelocation(string $serializedfilelocation): string|bool {
        $pattern = "/(?<=\d-)([a-zA-Z_]+)(?=\.)/";
        $hastablename = preg_match($pattern, $serializedfilelocation, $regexresults);
        if ($hastablename) {
            return $regexresults[0];
        }
        return false;
    }

    /**
     * Retrieve all relevant data related to the analysable samples.
     *
     * @param array $options = [string $tablename, array $ids]
     * @return void
     */
    public function collect(array $options): void {
        $this->validate($options);

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
     * @param $options
     * @return void
     */
    public function validate($options) : void {
        if (!isset($options['tablename'])) {
            throw new InvalidArgumentException('Options is missing the name of the related table.');
        }
        if (!isset($options['ids'])) {
            throw new InvalidArgumentException('Options is missing the ids the data should be related to.');
        }
        if (isset($this->data) && count($this->data) > 0) {
            throw new LogicException('Data has already been collected and can not be changed.');
        }
    }

    /**
     * Serialize the contents of the data field.
     * Store the serialization string in the filestring field.
     *
     * @return void
     */
    public function serialize(): void {
        $str = '';
        $columns = null;

        foreach ($this->data as $record) {
            $arr = (array) $record;
            if (!isset($columns)) {
                $columns = implode(',', array_keys($arr))."\n";
            }
            $str = $str . implode(',', $arr) . "\n";
        }

        $heading = $columns;
        $this->filestring = $heading.$str;
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
