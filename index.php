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
$model_config_objs = tool_laaudit\model_configurations::get_all_model_config_objs();

/////// VIEW

// Set some page parameters.
$pageurl = new moodle_url('/admin/tool/laaudit/index.php');
$heading = get_string('pluginname', 'tool_laaudit');
$context = context_system::instance();

$PAGE->set_context($context);
$PAGE->set_url($pageurl);
$PAGE->set_pagelayout('standard');
$PAGE->set_title(format_string($heading));
$PAGE->set_heading($heading);

// Output the page.
$output = $PAGE->get_renderer('tool_laaudit');

echo $output->header();

$model_configs_renderable = new tool_laaudit\output\model_configurations($model_config_objs);
echo $output->render($model_configs_renderable);

/*
if (sizeof($data->modelconfigs) < 1) {
    echo get_string('nomodelconfigurations', 'tool_laaudit');
} else {
    echo $OUTPUT->render_from_template('tool_laaudit/model_configurations', $data);
}*/

echo $output->footer();
