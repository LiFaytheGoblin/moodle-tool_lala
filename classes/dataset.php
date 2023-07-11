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
class dataset extends evidence {
    /**
     * Retrieve all available analysable samples, calculate features and label.
     * Store resulting data (sampleid, features, label) in the data field.
     *
     * @param array $options = [$modelid, $analyser, $contexts]
     * @return void
     */
    public function collect(array $options): void {
        if (!isset($options['contexts'])) {
            throw new InvalidArgumentException('Options is missing contexts.');
        }
        if (!isset($options['analyser'])) {
            throw new InvalidArgumentException('Options is missing analyser.');
        }
        if (!isset($options['modelid'])) {
            throw new InvalidArgumentException('Options is missing model id.');
        }
        if (isset($this->data) && sizeof($this->data) > 0) {
            throw new LogicException('Data has already been collected and can not be changed.');
        }

        $this->heavy_duty_mode();

        $analysablesiterator = $options['analyser']->get_analysables_iterator(null, $options['contexts']);

        $resultarray = new result_array($options['modelid'], true, []);

        $analysis = new analysis($options['analyser'], true, $resultarray);
        foreach ($analysablesiterator as $analysable) {
            if (!$analysable) {
                continue;
            }
            $analysableresults = $analysis->process_analysable($analysable);
            $resultarray->add_analysable_results($analysableresults);
        }

        $allresults = $resultarray->get();

        if (count($allresults) < 1) {
            throw new LengthException('No data was gathered from the site. Probably, no fitting data is available.');
        }

        $this->data = $allresults;
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

        foreach ($this->data as $results) {
            $ids = array_keys($results);
            foreach ($ids as $id) {
                if ($id == '0') {
                    $columns = implode(',', $results[$id]);
                    continue;
                }
                $indicatorvaluesstr = implode(',', $results[$id]);
                $str = $str.$id.','.$indicatorvaluesstr."\n";
            }
        }

        $heading = "sampleid,".$columns."\n";
        $this->filestring = $heading.$str;
    }

    /**
     * Returns the type of the stored file.
     *
     * @return string the file type of the serialized data.
     */
    public function get_file_type(): string {
        return 'csv';
    }

    /**
     * Helper: Shuffle a data set while preserving the key and the header.
     *
     * @param array $data
     * @return array shuffled data
     */
    public static function get_shuffled(array $data): array {
        if(sizeof($data) == 0) throw new DomainException('Data array to be shuffled can not be empty.');
        $keys = array_keys($data);
        $key = $keys[0];
        $datawithheader = [];
        foreach ($data as $arr) { // Each analysisinterval has an array.
            if(sizeof($arr) == 1) {
                throw new DomainException('Data array to be shuffled needs to be at least of size 2. 
        The first item is kept as item one, being treated as the header.');
            }
            $header = array_slice($arr, 0, 1, true);
            $remainingdata = array_slice($arr, 1, null, true);

            $sampleids = array_keys($remainingdata);
            if(sizeof($sampleids) < 2) return $data;
            shuffle($sampleids);
            $shuffleddata = [];
            foreach ($sampleids as $id) {
                // Assign to each key in the random order the value from the original array.
                $shuffleddata[$id] = $remainingdata[$id];
            }

            $datawithheader[$key] = $header + $shuffleddata;
            break;
        }

        return $datawithheader;
    }

    public static function get_sampleids_used_in_dataset(array $data) : array {
        $ids = [];

        $dataset = array_values($data)[0]; // First gathered dataset, first analysisinterval type
        $sampleids = array_keys($dataset);
        unset($sampleids['0']); // remove the header
        foreach ($sampleids as $sampleid) {
            $id = explode('-', $sampleid)[0];
            $ids[$id] = $id; // Preserve the order, avoid duplicates
        }

        return array_keys($ids);
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
