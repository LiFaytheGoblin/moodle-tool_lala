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

use core\task\manager;
use tool_lala\model_configuration;
use tool_lala\model_version;
use tool_lala\output\form\select_context;
use tool_lala\output\form\upload_dataset;
use tool_lala\task\version_create;

$configid = required_param('configid', PARAM_INT);
$manual = optional_param('manual', true, PARAM_BOOL); // Should version be created automatically with default settings?
$versionid = optional_param('versionid', null, PARAM_INT); // Should version be created automatically with default settings?
$contexts = optional_param_array('contexts', null, PARAM_INT);
$dataset = optional_param('dataset', null, PARAM_FILE);
$sesskey = optional_param('sesskey', null, PARAM_ALPHANUMEXT);

// Set some page parameters.
$priorpath = '/admin/tool/lala/index.php';
$priorurl = new moodle_url($priorpath);
$pagepath = '/admin/tool/lala/modelversion.php';
$pageurl = new moodle_url($pagepath, ['configid' => $configid, 'manual' => $manual, 'versionid' => $versionid]);
$heading = get_string('pluginname', 'tool_lala');
$context = context_system::instance();

$PAGE->set_context($context);
$PAGE->set_url($pageurl);
$PAGE->set_pagelayout('standard');
$PAGE->set_title(format_string($heading));
$PAGE->set_heading($heading);

// Security verification.
require_login();
require_capability('tool/lala:createmodelversion', $context);
require_sesskey();

// Router.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($versionid)) {
        // We do not have a version scaffold yet - first, create one.
        $versionid = model_version::create_scaffold_and_get_for_config($configid);
    }
    if ($manual) {
        render_page($versionid, $configid);
    } else {
        // Now we have a version scaffold and possibly some creation parameters,
        // and need to create the version according to the set parameters.
        set_contexts($versionid, $contexts);
        trigger_adhoc_model_version_creation($versionid, $contexts, $dataset);
        redirect(new moodle_url($priorpath, null, 'version'.$versionid));
    }
} else {
    http_response_code(405);
}

/**
 * Updates the contexts for a model version.
 *
 * @param int $versionid
 * @param array|null $contexts
 * @return void
 */
function set_contexts(int $versionid, ?array $contexts = null): void {
    $version = new model_version($versionid);
    if (!empty($contexts)) { // If contexts are provided, set those to limit the data gathering scope.
        $version->set_contextids($contexts);
    }
}

/**
 * Creates and queues a new adhoc task for creating a model version.
 *
 * @param int $versionid
 * @param array|null $contexts
 * @param mixed $dataset
 * @return void
 */
function trigger_adhoc_model_version_creation(int $versionid, ?array $contexts = null, mixed $dataset = null): void {
    global $USER;
    $asynctask = version_create::instance($versionid, $contexts, $dataset);
    $asynctask->set_userid($USER->id);
    manager::queue_adhoc_task($asynctask, true);
}

/**
 * Renders the manual version creation settings page.
 *
 * @param int $versionid
 * @param int $configid
 * @throws Exception
 */
function render_page(int $versionid, int $configid): void {
    $version = new model_version($versionid);

    // Create form to select contexts.
    $customdata = ['versionid' => $versionid, 'configid' => $configid];
    $selectcontextform = new select_context(null, $customdata);
    $selectcontextformhtml = $selectcontextform->render();

    // Create form to upload dataset.
    $uploaddatasetform = new upload_dataset(null, $customdata);
    $uploaddatasetformhtml = $uploaddatasetform->render();

    // Add created forms html to a forms object for passing to the renderer and to the mustache templates.
    $forms = new stdClass();
    $forms->selectcontext = $selectcontextformhtml;
    $forms->uploaddataset = $uploaddatasetformhtml;

    // Render the page.
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
