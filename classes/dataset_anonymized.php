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
 * The dataset anonymized class.
 *
 * @package     tool_lala
 * @copyright   2023 Linda Fernsel <fernsel@htw-berlin.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_lala;

use Exception;

/**
 * Class for the anonymized complete dataset evidence item.
 */
class dataset_anonymized extends dataset {

    /**
     * Retrieve all available analysable samples, calculate features and label.
     * Store resulting data (sampleid, features, label) in the data field.
     *
     * @param array $options = [$modelid, $analyser, $contexts]
     * @throws Exception
     */
    public function collect(array $options): void {
        parent::collect($options);

        if ($options['analyser']->processes_user_data()) {
            $n = count(dataset_helper::get_ids_used_in_dataset($this->data));
            if ($n < 3) {
                $this->abort();
                throw new Exception('Too few samples available. Found only ' . $n . ' sample(s) to gather.
                To preserve anonymity with a model that processes user related data, at least 3 samples are needed.');
            }
        }
    }

    /**
     * Pseudonomize the gathered dataset by applying new keys.
     * Make sure that the used data is shuffled, so that the order of keys does not give away the identity.
     *
     * @param array $data the data to anonymize ['analysisintervaltype' => ['0' => headerrow, 'someformerid' => datarow, ...]
     * @param idmap $idmap
     * @return array pseudonomized data ['analysisintervaltype' => ['0' => headerrow, 'somenewid' => datarow, ...]
     * @throws Exception
     */
    public function pseudonomize(array $data, idmap $idmap): array {
        $rows = dataset_helper::get_rows($data);
        $newrows = [];
        foreach ($rows as $originalsampleid => $values) {
            $pseudonym = $idmap->get_pseudonym_sampleid($originalsampleid);
            $newrows[$pseudonym] = $values;
        }

        // Re-sort so that order of keys does not give away identity.
        $newrowsshuffled = dataset_helper::shuffle_array_preserving_keys($newrows);

        $pseudonymizeddata = dataset_helper::replace_rows_in_dataset($data, $newrowsshuffled);
        $this->data = $pseudonymizeddata;
        return $pseudonymizeddata;
    }
}
