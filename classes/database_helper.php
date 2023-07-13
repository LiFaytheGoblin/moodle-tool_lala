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
 * The model version class, built on top of the analytics/model class.
 *
 * @package     tool_laaudit
 * @copyright   2023 Linda Fernsel <fernsel@htw-berlin.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_laaudit;

/**
 * Class for the model configuration.
 */
class database_helper {
    /**
     * Get graph of tables and relevant ids that are related to a specific table, recursively.
     *
     * @param string $tablenametohandle the table to which the searched tables should belong (e.g. tablenametohandle "user_enrolments")
     * @param int[]|string[] $relevantids ids for the tablenametohandle table, found in the prior tablenametohandle table
     * @param int[]|string[] $relatedtables collection of already found relatedtables names
     * @return void
     */
    public static function get_related_tables(string $tablenametohandle, array $relevantids, array $relatedtables): array {
        $res = $relatedtables;

        global $DB;
        $availabletables = $DB->get_tables();

        // get related tablenames from columns -> all columns that have a name that ends on "id" and has at leastlen = 4
        $columnnames = self::get_possible_column_names($tablenametohandle);
        foreach ($columnnames as $columnname) {
            if (count_chars($columnname) < 3) continue;
            $idpos = stripos($columnname, 'id');
            if ($idpos === false) continue;
            $tablename = substr($columnname, 0, $idpos);

            if (in_array($tablename, $availabletables)) { // If table exists...
                $relatedidrecords = $DB->get_records_list($tablenametohandle, 'id', $relevantids, null, 'id,' . $columnname);
                $relatedids = [];
                foreach ($relatedidrecords as $relatedidrecord) { // Unpack the retrieved records
                    $relatedids[] = $relatedidrecord->$columnname;
                }
                $res[$tablename] = $relatedids;
                $res = self::get_related_tables($tablename, $relatedids, $res);
            }
        }

        return $res;
    }

    public static function get_possible_column_names($tablename) : array {
        global $DB;
        $possiblecolumns = $DB->get_columns($tablename);
        $fieldnames = [];
        foreach ($possiblecolumns as $columninfo) {
            $fieldnames[] = $columninfo->name;
        }
        return $fieldnames;
    }
}
