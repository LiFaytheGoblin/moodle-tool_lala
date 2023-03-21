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
 * The main screen of the tool.
 *
 * @package     tool_laaudit
 * @copyright   2023 Linda Fernsel <fernsel@htw-berlin.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../../config.php');

require_admin();

/////// CONTROLLER

// Get all model configurations.
use tool_laaudit\model_configuration;
use core_analytics\manager;

$models = manager::get_all_models();
$modelconfigs = [];

foreach ($models as $model) {
    // Todo: only check non-static models?
    $modelconfig = new model_configuration($model);
    $modelconfigobj = $modelconfig->get_modelconfig_obj();

    array_push($modelconfigs, json_encode($modelconfigobj));
}


/////// VIEW

$pageurl = new moodle_url('/admin/tool/laaudit/index.php');
$heading = get_string('pluginname', 'tool_laaudit');
$context = context_system::instance();

$PAGE->set_context($context);
$PAGE->set_url($pageurl);
$PAGE->set_pagelayout('standard');
$PAGE->set_title(format_string($heading));
$PAGE->set_heading($heading);

echo $OUTPUT->header();

echo implode($modelconfigs);

echo get_string('pluginname', 'tool_laaudit');
echo get_string('nomodelconfigurations', 'tool_laaudit');
echo get_string('nomodelversions', 'tool_laaudit');

echo $OUTPUT->footer();
