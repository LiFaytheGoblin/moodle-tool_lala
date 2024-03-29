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
 * @package     tool_lala
 * @copyright   2023 Linda Fernsel <fernsel@htw-berlin.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_lala;

use core_analytics\local\analysis\result_array;
use core_analytics\analysis;
use core_php_time_limit;
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
     */
    public function collect(array $options): void {
        $this->validate_collect_options($options);
        $this->fail_if_attempting_to_overwrite();

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
            $logstext = '';
            $logs = implode(". ", $options['analyser']->get_logs());
            if (strlen($logs) > 0) {
                $logstext = ' Here are the details: ' . $logs;
            }
            throw new LengthException('No data was gathered from the site.
             Probably, no fitting data was available in the selected contexts.' . $logstext);
        }

        $this->data = $allresults;
    }

    /** Validate the evidence's options.
     * @param array $options
     */
    public function validate_collect_options(array $options) : void {
        if (!isset($options['contexts'])) {
            throw new InvalidArgumentException('Options is missing contexts.');
        }
        if (!isset($options['analyser'])) {
            throw new InvalidArgumentException('Options is missing analyser.');
        }
        if (!isset($options['modelid'])) {
            throw new InvalidArgumentException('Options is missing model id.');
        }
    }

    /**
     * Increases system memory and time limits.
     */
    private function heavy_duty_mode(): void {
        if (ini_get('memory_limit') != -1) {
            raise_memory_limit(MEMORY_HUGE);
        }
        core_php_time_limit::raise();
    }

    /**
     * Serialize the contents of the data field.
     * Store the serialization string in the filestring field.
     */
    public function serialize(): void {
        $this->filestring = dataset_helper::serialize($this->data);;
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
     * Restore the data for a model version to the cache from the stored CSV files.
     *
     * @param array $options
     * @return void
     */
    public function restore_raw_data(array $options): void {
        $this->validate_restore_options($options);
        $file = $this->get_file();
        $filehandle = $file->get_content_file_handle();
        $this->data = dataset_helper::build_from_csv($filehandle, $options['analysisintervalkey']);
    }

    /**
     * Validate the evidence's options.
     *
     * @param array $options
     */
    public function validate_restore_options(array $options) : void {
        if (!isset($options['analysisintervalkey'])) {
            throw new InvalidArgumentException('Options is missing analysisintervalkey.');
        }
    }
}
