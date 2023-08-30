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
 * The model version router
 *
 * @package     tool_lala
 * @copyright   2023 Linda Fernsel <fernsel@htw-berlin.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../../config.php');

use tool_lala\model_configuration;
use tool_lala\model_version;
use tool_lala\output\form\select_context;

$configid = optional_param('configid', null, PARAM_INT);
$auto = optional_param('auto', true, PARAM_BOOL); // Should version be created automatically with default settings?
$versionid = optional_param('versionid', null, PARAM_INT); // Should version be created automatically with default settings?
$contextids = optional_param_array('contextids', null, PARAM_INT);

// Routes
// POST /admin/tool/lala/modelversion.php?configid=<configid>&auto=<auto>&versionid=<versionid>&contextids=<contextids>

// Set some page parameters.
$pagepath = '/admin/tool/lala/modelversion.php';
$pageurl = new moodle_url($pagepath, ['configid' => $configid, 'auto' => $auto, 'versionid' => $versionid, 'contextids' => $contextids]);
$heading = get_string('pluginname', 'tool_lala');
$context = context_system::instance();

$PAGE->set_context($context);
$PAGE->set_url($pageurl);
$PAGE->set_pagelayout('standard');
$PAGE->set_title(format_string($heading));
$PAGE->set_heading($heading);

require_login();
require_capability('tool/lala:createmodelversion', $context);
require_sesskey();


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($configid) && empty($versionid)) {
        $versionid = model_version::create_scaffold_and_get_for_config($configid);
        $version = new model_version($versionid);

        // If route contains auto param, do it automatically.
        if ($auto) {
            try {
                if (isset($contextids)) {
                    $version->set_contextids($contextids);
                }
                $version->gather_dataset();
                $version->split_training_test_data();
                $version->train();
                $version->predict();
                $version->gather_related_data();
            } finally {
                $version->finish();
            }

            if (!$version->has_error()) {
                $priorurl = new moodle_url('/admin/tool/lala/index.php#version'.$versionid);
                redirect($priorurl);
            }
        } else {
            // Create form to select contexts.
            $customdata = ['versionid' => $versionid];
            $selectcontextform = new select_context(null, $customdata);
            if ($data = $selectcontextform->get_data()) {
                $version->set_contextids($data->contexts); // May not be ids!
            }
            $selectcontextformhtml = $selectcontextform->render();

            // Add created forms html to a forms object for passing to the renderer and to the mustache templates.
            $forms = new stdClass();
            $forms->selectcontext = $selectcontextformhtml;

            // Render the page
            $output = $PAGE->get_renderer('tool_lala');

            echo $output->header();

            $modelversionobj = $version->get_model_version_obj();
            $modelconfig = new model_configuration($configid);
            $modelconfigobj = $modelconfig->get_model_config_obj();
            $modelconfigrenderable = new tool_lala\output\model_configuration_version_creation($modelconfigobj, $modelversionobj, $forms);
            echo $output->render($modelconfigrenderable);

            echo $output->footer();
        }
    }

    if (!empty($versionid)) {
        $version = new model_version($versionid);

        if (!empty($contextids)) {
            $version->set_contextids($contextids);
        }

        // Todo: contextids is NULL! Why?
        var_dump($contextids);
        //print_r($_REQUEST);
        //print($versionid);
    }
} else {
    print('No post req');
    print_r($_REQUEST);
    $priorurl = new moodle_url('/admin/tool/lala/index.php');
    redirect($priorurl);
}
