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

/**
 * Test model.
 *
 * @package     tool_laaudit
 * @copyright   2023 Linda Fernsel <fernsel@htw-berlin.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_laaudit;

defined('MOODLE_INTERNAL') || die();
class test_model {
    const NAME = 'testmodel';
    const TARGET = '\core_course\analytics\target\no_recent_accesses';
    const INDICATORS = "[\"\\\\core\\\\analytics\\\\indicator\\\\any_course_access\"]";
    const ANALYSISINTERVAL = '\core\analytics\time_splitting\past_3_days';
    const PREDICTIONSPROCESSOR = '\mlbackend_php\processor';

    /**
     * Stores a model in the db and returns a modelid
     *
     * @return int
     */
    public static function create() : int {
        global $DB;
        $validmodelobject = [
                'name' => self::NAME,
                'target' => self::TARGET,
                'indicators' => self::INDICATORS,
                'timesplitting' => self::ANALYSISINTERVAL,
                'version' => time(),
                'timemodified' => time(),
                'usermodified' => 1,
        ];
        $modelid = $DB->insert_record('analytics_models', $validmodelobject);
        return $modelid;
    }

    /**
     * Deletes a model from the db
     */
    public static function delete($modelid) {
        global $DB;
        $DB->delete_records('analytics_models', ['id' => $modelid]);
    }

    public static function count_models() {
        global $DB;
        return $DB->count_records('analytics_models');
    }

    public static function get_highest_id() {
        global $DB;
        $existingmodelidsinconfigs = $DB->get_fieldset_select('tool_laaudit_model_configs', 'modelid', '1=1');
        $existingmodelidsinmodels = $DB->get_fieldset_select('analytics_models', 'id', '1=1');

        $allmodelids = array_merge($existingmodelidsinconfigs, $existingmodelidsinmodels);
        return max($allmodelids);
    }
}
