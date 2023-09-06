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
 * The related data anonymized evidence class.
 *
 * @package     tool_lala
 * @copyright   2023 Linda Fernsel <fernsel@htw-berlin.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_lala;

use Exception;
use LogicException;

/**
 * Class for the complete anonymized dataset evidence item.
 */
class related_data_anonymized extends related_data {
    /** @var string[] IGNORED_COLUMNS columns to ignore when retrieving the data */
    const IGNORED_COLUMNS = ['timecreated', 'timemodified', 'modifierid', 'password', 'username', 'firstname', 'lastname',
    'firstnamephonetic', 'lastnamephonetic', 'alternatename', 'email', 'phone1', 'phone2', 'address', 'lastip', 'secret',
    'middlename', 'imagealt', 'moodlenetprofile', 'picture', 'ip'];

    /**
     * Retrieve all relevant data related to the analysable samples.
     * Make sure to only return allowed columns and only if enough data exists.
     *
     * @param array $options depending on the implementation
     * @throws Exception
     * @throws Exception
     */
    public function collect(array $options): void {
        $this->validate($options);

        $this->tablename = $options['tablename'];

        // Find out, which columns that can appear in this table, should be excluded.
        global $DB;
        $columns = $DB->get_columns($this->tablename);
        $ignoredcolumns = $this::IGNORED_COLUMNS;
        foreach ($columns as $columnname => $columnmetadata) {
            if (in_array($columnname, $this::IGNORED_COLUMNS)) {
                continue;
            }
            if (str_contains($columnname, 'id')) {
                continue; // We keep ids for now, as they are pseudonomized later on.
            }
            // We do not want other columns with unique values, such as username.
            // Columns that can contain text should also be treated as sensitive.
            if ($columnmetadata->unique) {
                $ignoredcolumns[] = $columnname;
            } else if ($columnmetadata->type == 'longtext') {
                $ignoredcolumns[] = $columnname;
            }
        }

        // Retrieve data from the database but only for those columns, that we do not ignore.
        $possiblecolumns = array_keys($columns);
        $keptcolumns = (count($ignoredcolumns) > 0) ? array_diff($possiblecolumns, $ignoredcolumns) : $possiblecolumns;
        $fieldsstring = implode(',', $keptcolumns);
        $records = $DB->get_records_list($this->tablename, 'id', $options['ids'], null, $fieldsstring);

        $this->data = $records;

        // If the tablename contains 'user' (tables 'user', 'user_enrolments'), only return if sufficient data is available.
        if (str_contains($this->tablename, 'user')) {
            $ids = array_column($this->data, 'id');
            $n = count($ids);
            if ($n < 3) {
                $this->abort();
                throw new Exception('Too few samples available. Found only ' . $n . ' sample(s) to gather for table ' .
                        $this->tablename . '. To preserve anonymity with a model that processes user related data, at least 3
                        samples are needed.');
            }
        }

        // Find all id columns that relate to a user table. For each such column, make sure there are at least 3 distinct ids.
        foreach ($keptcolumns as $columnname) {
            if (str_contains('user', $columnname) && str_contains('id', $columnname)) {
                $ids = array_unique(array_column($this->data, $columnname));
                $n = count($ids);
                if ($n < 3) {
                    $this->abort();
                    throw new Exception('Too few samples available. Found only ' . $n . ' distinct id(s) for column ' .
                            $columnname . ' in table ' . $this->tablename . '.  To preserve anonymity with a model that processes
                            user related data, at least 3 ids are needed.');
                }
            }
        }
    }

    /**
     * Pseudonomize the related dataset by replacing original keys with new keys.
     * Make sure that the used data is shuffled, so that the order of keys does not give away the identity.
     *
     * @param array $data original data
     * @param idmap[] $idmaps an array of idmaps that can be used for different id types
     * @param string $type - which table the $data belongs to, and thus which idmap to use to pseudonomize the id.
     * @return array pseudonomized data
     */
    public function pseudonomize(array $data, array $idmaps, string $type): array {
        if (!key_exists($type, $idmaps)) {
            throw new LogicException('No idmap for type ' . $type . ' exists. ');
        }

        $res = [];
        foreach ($data as $row) {
            $newrow = clone $row;

            // Pseudonomize the referenced ids.
            foreach ($row as $columnname => $columncontent) {
                if ($columncontent == null || strlen($columncontent) == 0) {
                    continue; // No content! No need to pseudonomize anything here.
                }

                if ($columnname == 'id') { // Pseudonomize the main id.
                    $newrow->id = $idmaps[$type]->get_pseudonym($row->id);
                    continue;
                }

                // Check if the column contains an id type that needs to be pseudonomized.
                $idpos = stripos($columnname, 'id');
                if ($idpos === false) {
                    continue; // This column is not about an id, so ignore it.
                }

                $relatedtype = substr($columnname, 0, $idpos);
                if (!key_exists($relatedtype, $idmaps)) { // If relatedtype is not a valid table name in idmap already...
                    foreach ($idmaps as $idmaptablename => $idmap) { // Check if it hints at a table name, e.g. "relateduser".
                        if (str_ends_with($relatedtype, $idmaptablename)) {
                            $relatedtype = $idmaptablename;
                            break;
                        }
                    }

                    if (!key_exists($relatedtype, $idmaps)) {
                        continue; // This is a type to which still no table can be found, so ignore it.
                    }
                }

                // Pseudonomize the referenced id.
                $newrow->$columnname = $idmaps[$relatedtype]->get_pseudonym($columncontent);
            }

            $res[] = $newrow;
        }
        shuffle($res); // So that we can't match identities based on the order.
        $this->data = $res;
        return $res;
    }
}
