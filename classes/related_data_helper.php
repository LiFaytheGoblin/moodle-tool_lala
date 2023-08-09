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
 * The related_data helper class
 *
 * @package     tool_lala
 * @copyright   2023 Linda Fernsel <fernsel@htw-berlin.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_lala;

use Exception;

/**
 * Class to help the related data.
 */
class related_data_helper {

    /**
     * Extracts the tablename from a serializedfilelocation.
     *
     * @param array $relateddata an array of objects that each have an id.
     * @return array ids
     */
    public static function get_ids_used_in_related_data(array $relateddata): array {
        return array_column($relateddata, 'id');
    }

    /**
     * Adapter for getting the tablename from a serializedfilelocation, if one only has the evidenceid.
     *
     * @param int $evidenceid
     * @return string|bool
     */
    public static function get_tablename_from_evidenceid(int $evidenceid): string|bool {
        global $DB;
        $record = $DB->get_record('tool_lala_evidence', ['id' => $evidenceid], '*', MUST_EXIST);
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

    /** Create an idmap for a set of related data.
     *
     * @param array $relateddata
     * @return idmap
     * @throws Exception
     * @throws Exception
     */
    public static function create_idmap_from_related_data(array $relateddata): idmap {
        $originalids = self::get_ids_used_in_related_data($relateddata);
        return idmap::create_from_ids($originalids);
    }
}
