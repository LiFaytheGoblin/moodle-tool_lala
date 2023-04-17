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

use core_analytics;

class dataset extends evidence {
    /**
     * @var int|null
     */
    private $modelid;
    /**
     * @var core_analytics\local\analyser
     */
    private $analyser;

    protected function serialize() {
        // TODO: Implement serialize() method.
    }

    protected function collect($data = null, $modelid = null) {
        // TODO: Implement collect() method.
        // Create a model object from the accompanying analytics model
        $this->modelid = $modelid;
        $model = new \core_analytics\model($modelid);

        // Init analyzer.
        $this->init_analyzer($model);

        $this->heavy_duty_mode();

        $predictor = $model->get_predictions_processor();

        $datasets = $this->analyser->get_labelled_data($model->get_contexts()); // There should be only one in here.

    }

    protected function init_analyzer($model) {
        $target = $model->get_target();
        if (empty($target)) {
            throw new \moodle_exception('errornotarget', 'analytics');
        }
        $indicators = []; // Todo: insert from version
        if (empty($indicators)) {
            throw new \moodle_exception('errornoindicators', 'analytics');
        }
        $analysisintervals = [""]; // Todo: insert from version
        $options = array('evaluation'=>true, 'mode'=>'configuration'); // Todo: Correct?

        $analyzerclassname = $target->get_analyser_class();
        $this->analyser = new $analyzerclassname($this->modelid, $target, $indicators, $analysisintervals, $options);
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
