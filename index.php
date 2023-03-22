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
use tool_laaudit\model_configuration_controller;

$model_configuration_controller = new model_configuration_controller();
$renderable = $model_configuration_controller->get_all_model_configs_renderable();

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

if (sizeof($renderable->modelconfigs) < 1) {
    echo get_string('nomodelconfigurations', 'tool_laaudit');
} else {
    echo $OUTPUT->render_from_template('tool_laaudit/model_configurations', $renderable);
}
// echo get_string('nomodelversions', 'tool_laaudit');

echo $OUTPUT->footer();
