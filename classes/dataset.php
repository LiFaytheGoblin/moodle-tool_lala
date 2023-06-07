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
 * Collects and preserves evidence on data used by the model.
 * Can be inherited from for specific datasets.
 *
 * @package     tool_laaudit
 * @copyright   2023 Linda Fernsel <fernsel@htw-berlin.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_laaudit;

use core_analytics\local\analysis\result_array;
use core_analytics\analysis;

class dataset extends evidence {

    /**
     * Retrieve all available analysable samples, calculate features and label.
     * Store resulting data (sampleid, features, label) in the data field.
     *
     * @param $options = [$modelid, $analyser, $contexts]
     * @return void
     */
    public function collect($options) {
        if(!isset($options['contexts'])) {
            throw new \Exception('Missing contexts');
        }
        if(!isset($options['analyser'])) {
            throw new \Exception('Missing analyser');
        }
        if(!isset($options['modelid'])) {
            throw new \Exception('Missing model id');
        }

        $this->heavy_duty_mode();

        $analysables_iterator = $options['analyser']->get_analysables_iterator(null, $options['contexts']);

        $result_array = new result_array($options['modelid'], true, []);

        $analysis = new analysis($options['analyser'], true, $result_array);
        foreach($analysables_iterator as $analysable) {
            if (!$analysable) {
                continue;
            }
            $analysableresults = $analysis->process_analysable($analysable);
            $result_array->add_analysable_results($analysableresults);
        }

        $allresults = $result_array->get();

        if (sizeof($allresults) < 1) {
            throw new \moodle_exception('nodata', 'analytics');
        }

        $this->data = $allresults;
    }

    /**
     * Serialize the contents of the data field.
     * Store the serialization string in the filestring field.
     *
     * @return void
     */
    public function serialize() {
        $str = "";
        $columns = null;
        foreach($this->data as $results) {
            $ids = array_keys($results);
            foreach($ids as $id) {
                if ($id == "0") { // These are the indicator names (and target)
                    $columns = implode(",", $results[$id]);
                    continue;
                }
                $indicatorvaluesstr = implode(",", $results[$id]);
                $simpleid = explode("-", $id)[0];
                $str = $str.$simpleid.",".$indicatorvaluesstr."\n";
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
    public function get_file_type() {
        return 'csv';
    }

    /**
     * Shuffle a data set while preserving the key and the header.
     *
     * @param array $dataset
     * @return array
     */
    public function get_shuffled_data() {
        $key = array_keys((array) $this->data)[0];
        $datawithheader = [];
        foreach($this->data as $arr) { // each analysisinterval has an object
            $header = array_slice($arr, 0, 1, true);
            $remainingdata = array_slice($arr, 1, null, true);

            $sampleids = array_keys($remainingdata);
            shuffle($sampleids);
            $shuffled_data = [];
            foreach($sampleids as $id) {
                $shuffled_data[$id] = $remainingdata[$id]; // assign to each key in the random order the value from the original array
            }

            $datawithheader[$key] = $header + $shuffled_data;
            break;
        }

        return $datawithheader;
    }

    /**
     * Increases system memory and time limits.
     *
     * @return void
     */
    private function heavy_duty_mode() {
        if (ini_get('memory_limit') != -1) {
            raise_memory_limit(MEMORY_HUGE);
        }
        \core_php_time_limit::raise();
    }
}
