<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace tool_laaudit;

use core_analytics\local\analyser\base;

defined('MOODLE_INTERNAL') || die();

/**
 * Test analyser.
 *
 * @package     tool_laaudit
 * @copyright   2023 Linda Fernsel <fernsel@htw-berlin.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class test_analyser {
    /**
     * Creates an analyser for a specific modelid.
     *
     * @param int $modelid
     * @return base analyser
     * @throws \Exception
     * @throws \Exception
     */
    public static function create(int $modelid): base {
        $options = ['evaluation' => true, 'mode' => 'configuration'];

        $target = test_model::get_target_instance();
        $indicatorinstances = test_model::get_indicator_instances();
        $analysisintervalinstanceinstances = test_model::get_analysisinterval_instances();

        $analyzerclassname = $target->get_analyser_class();
        return new $analyzerclassname($modelid, $target, $indicatorinstances, $analysisintervalinstanceinstances, $options);
    }
}
