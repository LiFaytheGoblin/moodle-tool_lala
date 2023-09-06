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
use tool_lala\output\form\upload_dataset;

$configid = optional_param('configid', null, PARAM_INT);
$auto = optional_param('auto', true, PARAM_BOOL); // Should version be created automatically with default settings?
$versionid = optional_param('versionid', null, PARAM_INT); // Should version be created automatically with default settings?
$contexts = optional_param_array('contexts', null, PARAM_INT);
$dataset= optional_param('dataset', null, PARAM_FILE);

// Routes
// POST /admin/tool/lala/modelversion.php?configid=<configid>&auto=<auto>&versionid=<versionid>&contextids=<contextids>

// Set some page parameters.
$priorpath = '/admin/tool/lala/index.php';
$priorurl = new moodle_url($priorpath);
$pagepath = '/admin/tool/lala/modelversion.php';
$pageurl = new moodle_url($pagepath, ['configid' => $configid, 'auto' => $auto, 'versionid' => $versionid]);
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

function render_page(model_version $version, int $configid) {
    // Create form to select contexts.
    $customdata = ['versionid' => $version->get_id(), 'configid' => $configid];
    $selectcontextform = new select_context(null, $customdata);
    $selectcontextformhtml = $selectcontextform->render();

    // Create form to upload dataset.
    $uploaddatasetform = new upload_dataset(null, $customdata);
    $uploaddatasetformhtml = $uploaddatasetform->render();

    // Add created forms html to a forms object for passing to the renderer and to the mustache templates.
    $forms = new stdClass();
    $forms->selectcontext = $selectcontextformhtml;
    $forms->uploaddataset = $uploaddatasetformhtml;

    // Render the page
    global $PAGE;
    $output = $PAGE->get_renderer('tool_lala');

    echo $output->header();

    $modelversionobj = $version->get_model_version_obj();
    $modelconfig = new model_configuration($configid);
    $modelconfigobj = $modelconfig->get_model_config_obj();
    $modelconfigrenderable = new tool_lala\output\model_configuration_version_creation($modelconfigobj, $modelversionobj, $forms);
    echo $output->render($modelconfigrenderable);

    echo $output->footer();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($configid)) {
    if (empty($versionid)) {
        $versionid = model_version::create_scaffold_and_get_for_config($configid);
        $version = new model_version($versionid);

        // If route contains auto param, do it automatically.
        if ($auto) {
            try { // possibly replace with a call to the moodle url with the version id?
                $version->gather_dataset();
                $version->split_training_test_data();
                $version->train();
                $version->predict();
                $version->gather_related_data();
            } finally {
                $version->finish();
                redirect(new moodle_url($priorpath.'#version'.$versionid));
            }
        } else {
            render_page($version, $configid);
        }
    } else {
        $version = new model_version($versionid);

        try {
            if (!empty($contexts)) {
                $version->set_contextids($contexts);
                $version->gather_dataset();
            } else if (!empty($dataset)) {
                global $USER;

                $fs = get_file_storage();
                $context = context_user::instance($USER->id);
                $draftid = $dataset;
                $files = $fs->get_area_files($context->id, 'user', 'draft', $draftid, 'id DESC', false);

                $storedfile = reset($files);
                $version->set_dataset($storedfile);
            } else {
                $version->gather_dataset();
            }

            $version->split_training_test_data();
            $version->train();
            $version->predict();

            if (empty($dataset)) {
                $version->gather_related_data(); // We can only gather this data if related data can be found on the site.
            }
        } finally {
            $version->finish();
            redirect(new moodle_url($priorpath.'#version'.$versionid));
        }
    }
} else {
    redirect($priorurl);
}
