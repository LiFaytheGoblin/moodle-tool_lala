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

use tool_lala\model_version;

$configid = optional_param('configid', 0, PARAM_INT); // For which config id to create a version?
$auto = optional_param('auto', true, PARAM_BOOL); // Should version be created automatically with default settings?

// Routes
// POST /admin/tool/lala/modelversion.php?configid=<configid>

// Set some page parameters.
$pageurl = new moodle_url('/admin/tool/lala/modelversion.php', ['configid' => $configid, 'auto' => $auto]);
$context = context_system::instance();

$PAGE->set_url($pageurl);
$PAGE->set_context($context);

require_login();
require_capability('tool/lala:createmodelversion', $context);
require_sesskey();

$versionid = null;

if (!empty($configid) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $versionid = model_version::create_scaffold_and_get_for_config($configid);
    $version = new model_version($versionid);

    if ($auto) {
        try {
            $version->gather_dataset();
            $version->split_training_test_data();
            $version->train();
            $version->predict();
            $version->gather_related_data();
        } finally {
            $version->finish();
        }
    } else {
        $output = $PAGE->get_renderer('tool_lala');

        echo $output->header();

        $modelversionrenderable = new tool_lala\output\model_version($version->get_model_version_obj());
        echo $output->render($modelversionrenderable);

        echo $output->footer();
    }
}

$versionaddendum = isset($versionid) ? '#version'.$versionid : '';
$priorurl = new moodle_url('/admin/tool/lala/index.php'.$versionaddendum);
redirect($priorurl);

