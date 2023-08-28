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

$versionid = optional_param('versionid', 0, PARAM_INT);
$contextids = optional_param_array('contextids', null, PARAM_INT);

// Routes
// POST /admin/tool/lala/dataset.php?versionid=<versionid>&contextids=<contextids>

// Set some page parameters.
$pageurl = new moodle_url('/admin/tool/lala/dataset.php', ['versionid' => $versionid, 'contextids' => $contextids]);
$context = context_system::instance();

$PAGE->set_url($pageurl);
$PAGE->set_context($context);

// Todo: Check if this can be enabled back again.
// require_login();
// require_capability('tool/lala:createmodelversion', $context);
// require_sesskey();

if (!empty($versionid) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $version = new model_version($versionid);

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
        echo($versionid);
    }
}