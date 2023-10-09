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
 * The model configuration helper class.
 *
 * @package     tool_lala
 * @copyright   2023 Linda Fernsel <fernsel@htw-berlin.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_lala;

use Exception;
use stdClass;
use core_analytics\manager;

/**
 * Class to help the model configuration.
 */
class model_configuration_helper {

    const CANBENULL = ['predictionsprocessor', 'timesplitting'];

    const CORRESPONDINGSETTINGS = [
        'id' => 'modelid',
        'predictionsprocessor' => 'predictionsprocessor',
        'timesplitting' => 'analysisinterval',
        'indicators' => 'indicators'
    ];

    /**
     * Return all available model config objects.
     *
     * @return array
     * @throws Exception
     */
    public static function get_all_model_config_obs(): array {
        global $DB;

        $modelconfigids = $DB->get_fieldset_select('tool_lala_model_configs', 'id', '1=1');

        $modelconfigs = [];
        foreach ($modelconfigids as $configid) {
            $modelconfig = new model_configuration($configid);
            $modelconfigs[] = $modelconfig->get_model_config_obj();
        }

        return $modelconfigs;
    }

    /**
     * Add a new config for each model without an (up-to-date) config.
     * This means, if a model has changed indicators, predictionsprocessor, analysisinterval,
     * and no config for this combination of settings has been created yet,
     * create a new config for it.
     *
     * @return void
     */
    public static function init_all_model_config_obs(): void {
        global $DB;

        // Add configs for new models/ models that have not received a config entry yet.
        $modelids = $DB->get_fieldset_select('analytics_models', 'id', '1=1');

        foreach ($modelids as $modelid) { // For each modelid...
            // Skip if the model is static.
            if (self::is_model_static($modelid)) {
                continue;
            }

            // If no corresponding and up-to-date config exists...
            $relatedconfigs = self::get_related_configs($modelid);
            if (count($relatedconfigs) < 1) {
                self::create_and_get_for_model($modelid); // Create a new one.
            }
        }
    }

    /**
     * Collect all model configuration objects.
     *
     * @return stdClass[] of model config objects
     * @throws Exception
     */
    public static function init_and_get_all_model_config_objs(): array {
        self::init_all_model_config_obs();
        return self::get_all_model_config_obs();
    }

    /**
     * Helper method: Short check to verify whether the provided value is valid, and thus a valid list exists.
     *
     * @param string|null $value to check
     * @return boolean
     */
    public static function valid_exists(?string $value): bool {
        return isset($value) && $value != "" && $value != "[]";
    }

    /**
     * Create a new model configuration for a model id.
     * Failing gracefully: If config for this model already exists, just return it.
     * The accompanying db table is needed to preserve a record of the model configuration
     * even if the model has been deleted.
     *
     * @param int $modelid of an analytics model
     * @return int id of created or retrieved object
     */
    public static function create_and_get_for_model(int $modelid): int {
        global $DB;

        $modelobj = $DB->get_record('analytics_models', ['id' => $modelid], '*', MUST_EXIST);

        $obj = new stdClass();
        $obj->modelid = $modelid;
        $obj->name = $modelobj->name;
        if (!isset($obj->name)) {
            $modelidcount = $DB->count_records('tool_lala_model_configs', ['modelid' => $modelid]);
            $obj->name = 'config' . $modelid . '/' . $modelidcount;
        }
        $obj->target = $modelobj->target;

        if (self::valid_exists($modelobj->predictionsprocessor)) {
            $obj->predictionsprocessor = $modelobj->predictionsprocessor;
        } else {
            $default = manager::default_mlbackend();
            $obj->predictionsprocessor = $default;
        }

        if (self::valid_exists($modelobj->timesplitting)) {
            $obj->analysisinterval = $modelobj->timesplitting;
        } else {
            $analysisintervals = manager::get_time_splitting_methods_for_evaluation();
            $firstanalysisinterval = array_keys($analysisintervals)[0];
            $obj->analysisinterval = $firstanalysisinterval;
        }

        if (self::valid_exists($modelobj->contextids)) {
            $obj->defaultcontextids = $modelobj->contextids;
        }

        $obj->indicators = $modelobj->indicators;

        $obj->timecreated = time();

        return $DB->insert_record('tool_lala_model_configs', $obj);
    }

    /**
     * Get configs that correspond to the given model.
     *
     * @param int $modelid
     * @return array
     */
    public static function get_related_configs(mixed $modelid): array {
        global $DB;

        $analyticsmodelsettings = $DB->get_record('analytics_models', ['id' => $modelid],
                'id, predictionsprocessor, timesplitting, indicators', MUST_EXIST);
        $where = [];
        $params = [];
        foreach ($analyticsmodelsettings as $setting => $value) {
            if (($value == null || $value == '') && in_array($setting, self::CANBENULL)) {
                continue; // This value does not need to be compared - it is expected to be different in the config.
            }

            // Not all corresponding columns have an equal name.
            // Use the name of the column in the config table for constructing the query.
            $correspondingsetting = self::CORRESPONDINGSETTINGS[$setting];
            $where[] = $correspondingsetting . '= :' . $correspondingsetting;
            $params[$correspondingsetting] = $value;
        }
        $wherestring = implode(' AND ', $where);

        // Try to get a config with the same (relevant) settings.
        $query = "SELECT id 
                    FROM {tool_lala_model_configs}
                   WHERE " . $wherestring;
        return $DB->get_records_sql($query, $params);
    }

    /**
     * Check whether the model is static.
     *
     * @param int $modelid
     * @return bool
     */
    private static function is_model_static(int $modelid): bool {
        global $DB;
        $targetname = $DB->get_fieldset_select('analytics_models', 'target', 'id='.$modelid)[0];
        return manager::get_target($targetname)->based_on_assumptions();
    }
}
