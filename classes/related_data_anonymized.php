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
 * @package     tool_laaudit
 * @copyright   2023 Linda Fernsel <fernsel@htw-berlin.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_laaudit;

use LogicException;

/**
 * Class for the complete anonymized dataset evidence item.
 */
class related_data_anonymized extends related_data {
    const IGNORED_COLUMNS = ['timecreated', 'timemodified', 'modifierid', 'password', 'username', 'firstname', 'lastname',
    'firstnamephonetic', 'email', 'phone1', 'phone2', 'address', 'lastip', 'secret', 'description', 'middlename', 'imagealt',
    'alternatename', 'moodlenetprofile', 'picture', 'ip', 'other', 'realuserid'];

    /**
     * Pseudonomize the related dataset by replacing original keys with new keys.
     * Make sure that the used data is shuffled, so that the order of keys does not give away the identity.
     *
     * @param array $data original data
     * @param idmap[] $idmaps
     * @return array pseudonomized data
     */
    public function pseudonomize(array $data, array $idmaps, string $type): array {
        if (!isset($data)) throw new LogicException('No evidence has been collected that can be pseudonomized. Make sure to collect first.');
        if (!key_exists($type, $idmaps)) throw new LogicException('No idmap for type '.$type.' exists. ');

        $res = [];
        foreach ($data as $row) {
            $newrow = clone $row;

            // Pseudonomize the referenced ids.
            foreach ($row as $columnname => $columncontent) {
                if ($columnname == 'id') { // Pseudonomize the main id.
                    $newrow->id = $idmaps[$type]->get_pseudonym($row->id);
                    continue;
                }

                // Check if the column contains an id type that needs to be pseudonomized.
                $idpos = stripos($columnname, 'id');
                if ($idpos === false) continue; // This column is not about an id, so ignore it.

                $relatedtype = substr($columnname, 0, $idpos);
                if (!key_exists($relatedtype, $idmaps)) { // If relatedtype is not a valid table name in idmap already...
                    foreach ($idmaps as $idmaptablename => $idmap) { // Check if it hints at a table name, e.g. "relateduser" -> "user".
                        if (str_ends_with($relatedtype, $idmaptablename)) {
                            $relatedtype = $idmaptablename;
                            break;
                        }
                    }

                    if (!key_exists($relatedtype, $idmaps)) continue; // This is a type to which still no table can be found, so ignore it.
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
