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

/////// CONTROLLER

// Get all model configurations.
$modelconfigobjs = tool_laaudit\model_configurations::init_and_get_all_model_config_objs();

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

require_login();
require_capability('tool/laaudit:viewpagecontent', $context);

// Output the page.
$output = $PAGE->get_renderer('tool_laaudit');

echo $output->header();

$modelconfigsrenderable = new tool_laaudit\output\model_configurations($modelconfigobjs);
echo $output->render($modelconfigsrenderable);

echo $output->footer();
