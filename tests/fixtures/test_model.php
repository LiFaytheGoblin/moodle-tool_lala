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

use core_analytics\manager;
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
        $validmodelobject = self::get_modelobj();
        return $DB->insert_record('analytics_models', $validmodelobject);
    }

    public static function get_modelobj() {
        return [
                'name' => self::NAME,
                'target' => self::TARGET,
                'indicators' => self::INDICATORS,
                'timesplitting' => self::ANALYSISINTERVAL,
                'version' => time(),
                'timemodified' => time(),
                'usermodified' => 1,
        ];
    }

    /**
     * Deletes a model from the db.
     *
     * @param int $modelid
     */
    public static function delete(int $modelid): void {
        global $DB;
        $DB->delete_records('analytics_models', ['id' => $modelid]);
    }

    /**
     * Updates a model column.
     *
     * @param int $modelid
     */
    public static function update(int $modelid, string $column)  : void {
        global $DB;
        $newobj = self::get_modelobj();
        $newobj['id'] = $modelid;
        $newobj[$column] = 'changed'.time();
        $newobj['timemodified'] = time();
        $DB->update_record('analytics_models', $newobj);
    }

    /**
     * Resets a model's values to default.
     *
     * @param int $modelid
     */
    public static function reset(int $modelid) {
        global $DB;
        $newobj = self::get_modelobj();
        $newobj['id'] = $modelid;
        $newobj['timemodified'] = time();
        $DB->update_record('analytics_models', $newobj);
    }

    /**
     * Counts the existing models.
     *
     * @return int amount of models
     */
    public static function count_models(): int {
        global $DB;
        return $DB->count_records('analytics_models');
    }

    /**
     * Get the highest model id that is currently in use.
     *
     * @return int modelid
     */
    public static function get_highest_id(): int {
        global $DB;
        $existingmodelidsinconfigs = $DB->get_fieldset_select('tool_laaudit_model_configs', 'modelid', '1=1');
        $existingmodelidsinmodels = $DB->get_fieldset_select('analytics_models', 'id', '1=1');

        $allmodelids = array_merge($existingmodelidsinconfigs, $existingmodelidsinmodels);
        return max($allmodelids);
    }

    /**
     * Get an instance of the test target.
     *
     * @return \core_analytics\local\target\base target
     */
    public static function get_target_instance(): \core_analytics\local\target\base {
        return manager::get_target(self::TARGET);
    }

    /**
     * Get instances for the test indicators
     *
     * @return \core_analytics\local\indicator\base[] indicators
     */
    public static function get_indicator_instances(): array {
        $fullclassnames = json_decode(self::INDICATORS);
        $indicatorinstances = array();
        foreach ($fullclassnames as $fullclassname) {
            $instance = manager::get_indicator($fullclassname);
            $indicatorinstances[$fullclassname] = $instance;
        }
        return $indicatorinstances;
    }

    /**
     * Get instances for the test analysisinterval(s)
     *
     * @return \core_analytics\local\time_splitting\base[] analysisinterval(s)
     */
    public static function get_analysisinterval_instances(): array {
        $analysisintervalinstanceinstance = manager::get_time_splitting(test_model::ANALYSISINTERVAL);
        if(!$analysisintervalinstanceinstance) throw new \Exception('Analysisinterval instance not found for analysis interval with name '.test_model::ANALYSISINTERVAL);
        return [$analysisintervalinstanceinstance];
    }



}
