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
 * @package     tool_laaudit
 * @copyright   2023 Linda Fernsel <fernsel@htw-berlin.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../../config.php');

use \tool_laaudit\model_version;

require_admin();

$configid = optional_param('configid', 0, PARAM_INT);

// Routes
// POST /admin/tool/laaudit/modelversion.php?configid=<configid>

// Set some page parameters.
$priorurl = new moodle_url('/admin/tool/laaudit/index.php');
$pageurl = new moodle_url('/admin/tool/laaudit/modelversion.php', array("configid" => $configid));
$context = context_system::instance();

$PAGE->set_url($pageurl);
$PAGE->set_context($context);

if (!empty($configid) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $versionid = model_version::create_scaffold_and_get_for_config($configid);

    $version = new model_version($versionid);

    // If route contains auto param, do it automatically.
    $version->gather_dataset();
    //$version->calculate_features();
    //$version->train();
    //$version->predict();

    $version->finish();

    redirect($priorurl);
}

