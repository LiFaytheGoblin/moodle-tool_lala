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

use stdClass;
use core_analytics\manager;

/**
 * Class to help the model configuration.
 */
class model_configuration_helper {

    /**
     * Collect all model configuration objects.
     *
     * @return stdClass[] of model config objects
     * @throws \Exception
     * @throws \Exception
     */
    public static function init_and_get_all_model_config_objs(): array {
        global $DB;

        // Get all existing model configs ids.
        $modelconfigids = $DB->get_fieldset_select('tool_lala_model_configs', 'id', '1=1');

        // Add configs for new models/ models that have not received a config entry yet.
        // Can we use something else than modelid? The version is stored somewhere I think?
        $modelidsinconfigtable = $DB->get_fieldset_select('tool_lala_model_configs', 'modelid', '1=1');
        $modelidsinanalyticsmodelstable = $DB->get_fieldset_select('analytics_models', 'id', '1=1');

        // If a model has changed indicators, predictionsprocessor, analysisinterval,
        // and no config for this combination of settings has been created yet,
        // create a new config for it.
        $modelidsfromanalyticsmodeltableinconfigtable = array_intersect($modelidsinanalyticsmodelstable, $modelidsinconfigtable);
        if (count($modelidsfromanalyticsmodeltableinconfigtable) > 0) {
            foreach ($modelidsfromanalyticsmodeltableinconfigtable as $modelid) {
                $configtimecreated = $DB->get_fieldset_select('tool_lala_model_configs', 'timecreated', 'modelid='.$modelid);
                $modeltimemodified = $DB->get_fieldset_select('analytics_models', 'timemodified', 'id='.$modelid)[0];

                if ($modeltimemodified > max($configtimecreated)) {
                    $analyticsmodelsettings = $DB->get_records('analytics_models', ['id' => $modelid], null,
                            'id, predictionsprocessor, timesplitting, indicators');
                    $configsettings = $DB->get_records('tool_lala_model_configs', ['modelid' => $modelid], null,
                            'id, predictionsprocessor, analysisinterval, indicators');
                    $analyticsmodelsetting = $analyticsmodelsettings[$modelid];
                    $analyticsmodelsettingvalues = self::get_settings_values($analyticsmodelsetting);

                    $modelalreadyhasexactsameconfig = false;
                    foreach ($configsettings as $configsetting) {
                        $configsettingvalues = self::get_settings_values($configsetting);
                        $diff = array_diff($analyticsmodelsettingvalues, $configsettingvalues);

                        if (count($diff) == 0) {
                            $modelalreadyhasexactsameconfig = true;
                            break;
                        }
                    }

                    if (!$modelalreadyhasexactsameconfig) {
                        $modelconfigids[] = self::create_and_get_for_model($modelid);
                    }
                }
            }
        }

        $missingmodelids = array_diff($modelidsinanalyticsmodelstable, $modelidsinconfigtable);
        foreach ($missingmodelids as $missingmodelid) {
            // Check if the model is a static model.
            $targetname = $DB->get_fieldset_select('analytics_models', 'target', 'id='.$missingmodelid)[0];
            $target = manager::get_target($targetname);
            if (!$target->based_on_assumptions()) {
                // For now, ignore static models and only handle machine learning models.
                $modelconfigids[] = self::create_and_get_for_model($missingmodelid);
            }
        }

        $modelconfigs = [];
        foreach ($modelconfigids as $configid) {
            $modelconfig = new model_configuration($configid);
            $modelconfigs[] = $modelconfig->get_model_config_obj();
        }

        return $modelconfigs;
    }

    /**
     * Collect all model configuration objects.
     *
     * @param stdClass $settingdbentry
     * @return stdClass[] of model config objects
     */
    private static function get_settings_values(stdClass $settingdbentry): array {
        $vals = array_values((array) $settingdbentry);
        return array_slice($vals, 1, null);
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
}
