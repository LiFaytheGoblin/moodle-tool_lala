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
 * The model configuration list class.
 *
 * @package     tool_laaudit
 * @copyright   2023 Linda Fernsel <fernsel@htw-berlin.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_laaudit;

use core_analytics\manager;
use single_button;

/**
 * Class for the list of model configurations.
 */
class model_configurations {

    /**
     * Collect all model configuration objects.
     *
     * @return object[] of model config objects
     */
    public static function init_and_get_all_model_config_objs(): array {
        global $DB;

        // Get all existing model configs.
        $modelconfigs = $DB->get_records('tool_laaudit_model_configs');

        // Add configs for new models/ models that have not received a config entry yet.
        // Can we use something else than modelid? The version is stored somewhere I think?
        $modelidsinconfigtable = $DB->get_fieldset_select('tool_laaudit_model_configs', 'modelid', '1=1');
        $modelidsinanalyticsmodelstabel = $DB->get_fieldset_select('analytics_models', 'id', '1=1');
        $missingmodelids = array_diff($modelidsinanalyticsmodelstabel, $modelidsinconfigtable);
        foreach ($missingmodelids as $missingmodelid) {
            $configid = model_configuration::create_and_get_for_model($missingmodelid);
            $modelconfig = new model_configuration($configid);
            $modelconfigs[] = $modelconfig->get_model_config_obj();
        }

        // If a model has changed indicators, create a new config for it.

        return $modelconfigs;
    }
}
