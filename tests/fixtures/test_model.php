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

namespace tool_lala;

defined('MOODLE_INTERNAL') || die();

use core_analytics\local\time_splitting\base;
use core_analytics\manager;
use Exception;

/**
 * Test model.
 *
 * @package     tool_lala
 * @copyright   2023 Linda Fernsel <fernsel@htw-berlin.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class test_model {
    /** @var string NAME of the testmodel */
    const NAME = 'testmodel';
    /** @var string TARGET of the testmodel */
    const TARGET = '\core_course\analytics\target\course_gradetopass';
    /** @var string INDICATORS of the testmodel */
    const INDICATORS = "[\"\\\\core\\\\analytics\\\\indicator\\\\any_course_access\"]";
    /** @var string ANALYSISINTERVAL of the testmodel */
    const ANALYSISINTERVAL = '\core\analytics\time_splitting\past_3_days';
    /** @var string PREDICTIONSPROCESSOR of the testmodel */
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

    /**
     * Get the model data as an array.
     *
     * @return array
     */
    public static function get_modelobj() : array {
        return [
                'name' => self::NAME,
                'target' => self::TARGET,
                'indicators' => self::INDICATORS,
                'timesplitting' => self::ANALYSISINTERVAL,
                'predictionsprocessor' => self::PREDICTIONSPROCESSOR,
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
     * @param string|null $column name
     * @param mixed $value
     */
    public static function update(int $modelid, ?string $column = null, mixed $value = null)  : void {
        global $DB;
        $newobj = self::get_modelobj();
        $newobj['id'] = $modelid;
        if ($column) {
            $nevval = $value ?? 'changed' . time();
            $newobj[$column] = $nevval;
        }
        $newobj['timemodified'] = time();
        $DB->update_record('analytics_models', $newobj);
    }

    /**
     * Resets a model's values to default.
     *
     * @param int $modelid
     */
    public static function reset(int $modelid): void {
        global $DB;
        $newobj = self::get_modelobj();
        $newobj['id'] = $modelid;
        $newobj['timemodified'] = time();
        $DB->update_record('analytics_models', $newobj);
    }

    /**
     * Counts the existing machine learning models.
     *
     * @return int amount of machine learning models
     */
    public static function count_models(): int {
        $count = 0;
        global $DB;
        $targetnames = $DB->get_fieldset_select('analytics_models', 'target', '1=1');
        foreach ($targetnames as $targetname) {
            $count += (int) !manager::get_target($targetname)->based_on_assumptions();
        }
        return $count;
    }

    /**
     * Get the highest model id that is currently in use.
     *
     * @return int modelid
     */
    public static function get_highest_id(): int {
        global $DB;
        $existingmodelidsinconfigs = $DB->get_fieldset_select('tool_lala_model_configs', 'modelid', '1=1');
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
     * @return base[] analysisinterval(s)
     * @throws Exception
     * @throws Exception
     */
    public static function get_analysisinterval_instances(): array {
        $analysisintervalinstanceinstance = manager::get_time_splitting(test_model::ANALYSISINTERVAL);
        if(!$analysisintervalinstanceinstance) throw new Exception('Analysisinterval instance not found for analysis interval with name '.test_model::ANALYSISINTERVAL);
        return [$analysisintervalinstanceinstance];
    }
}
