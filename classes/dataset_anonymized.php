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

/**
 * Class for the complete dataset evidence item.
 */
class dataset_anonymized extends dataset {
    /** @var array|null $idmap used for anonymization */
    private ?array $idmap;

    /**
     * Retrieve all available analysable samples, calculate features and label.
     * Store resulting data (sampleid, features, label) in the data field.
     *
     * @param array $options = [$modelid, $analyser, $contexts]
     * @return void
     */
    public function collect(array $options): void {
        parent::collect($options);
        $this->idmap = self::create_new_idmap_from_ids_in_data($this->data);
        $n = sizeof($this->idmap);
        if ($n < 3) throw new \Exception('Too few samples available. Found only '.$n.' sample(s) to gather. To preserve anonymity, at least 3 samples are needed.');
        $this->data = $this->pseudonomize($this->data, $this->idmap);
    }

    /**
     * Pseudonomize the gathered dataset by applying new keys.
     * Make sure that the used data is shuffled, so that the order of keys does not give away the identity.
     *
     * @param array $data the data to anonymize ['analysisintervaltype' => ['0' => headerrow, 'someformerid' => datarow, ...]
     * @param array $idmap [oldkey => newkey]
     * @return array pseudonomized data ['analysisintervaltype' => ['0' => headerrow, 'somenewid' => datarow, ...]
     */
    public function pseudonomize(array $data, array $idmap): array {
        $res = [];
        foreach ($data as $resultskey => $results) {
            $replacements = [];
            $header = [];
            foreach ($results as $oldkey => $result) {
                if ($oldkey == '0') { // Skip header row.
                    $header = ['0' =>$results['0']];
                    continue;
                }
                $oldkeyparts = explode('-', $oldkey);
                $oldid = $oldkeyparts[0];
                $analysisintervalpart = array_key_exists(1, $oldkeyparts) ? '-'.$oldkeyparts[1] : '';

                $newkey = $idmap[$oldid];
                if (!isset($newkey)) throw new \Exception('Idmap is incomplete. No pseudonym found for id '.$oldkey);

                $replacements[$newkey.$analysisintervalpart] = $result;
            }
            ksort($replacements); // Re-sort so that order of keys does not give away identity.

            $res[$resultskey] = array_merge($header, $replacements);
        }
        return $res;
    }

    /**
     * Create id map.
     *
     * @param array data
     * @return array idmap [oldid => newid]
     */
    public static function create_new_idmap_from_ids_in_data(array $data): array {
        $oldids = dataset::get_sampleids_used_in_dataset($data);

        $offset = 2; // Moodle has two standard users, 1 and 2.
        $newids = range(1 + $offset, sizeof($oldids) + $offset);

        $idmap = array_combine($oldids, $newids);

        return $idmap;
    }

    /**
     * Get id map.
     *
     * @return array idmap [oldid => newid]
     */
    public function get_idmap() : array {
        return $this->idmap;
    }
}
