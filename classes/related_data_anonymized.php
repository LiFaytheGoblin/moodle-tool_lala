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

use InvalidArgumentException;
use LogicException;

/**
 * Class for the complete anonymized dataset evidence item.
 */
class related_data_anonymized extends related_data {
    const IGNORED_COLUMNS = ['timecreated', 'timemodified', 'modifierid', 'password', 'username', 'firstname', 'lastname',
    'firstnamephonetic', 'email', 'phone1', 'phone2', 'address', 'lastip', 'secret', 'description', 'middlename', 'imagealt',
    'alternatename', 'moodlenetprofile', 'picture', 'ip', 'other'];

    /**
     * Retrieve all relevant data related to the analysable samples.
     *
     * @param array $options = [string $tablename, array $ids]
     * @return void
     */
    public function collect(array $options): void {
        if (!isset($options['idmap'])) {
            throw new InvalidArgumentException('Options is missing the look up table of original user ids and pseudonyms.');
        }
        $idmapentitytype = ($options['idmap'])->get_entitytype();
        if ($idmapentitytype != $options['tablename']) {
            throw new LogicException('Passed idmap does not belong to '.$options['tablename'].' but to '.$idmapentitytype);
        }
        parent::collect($options);
        $this->data = $this->pseudonomize($this->data, $options['idmap']);
    }

    /**
     * Pseudonomize the related dataset by replacing original keys with new keys.
     * Make sure that the used data is shuffled, so that the order of keys does not give away the identity.
     *
     * @param array $data
     * @param idmap $idmap
     */
    public function pseudonomize(array $data, idmap $idmap): array {
        $res = [];
        foreach ($data as $record) {
            $newrec = $record;
            $newrec->id = $idmap->get_pseudonym($record->id);
            $res[] = $newrec;
        }
        return $res;
    }
}
