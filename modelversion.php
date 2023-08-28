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
require_once($CFG->libdir . '/filelib.php');
//require_once($CFG->libdir . '/curllib.php');

use tool_lala\model_version;

$configid = optional_param('configid', 0, PARAM_INT);
$auto = optional_param('auto', true, PARAM_BOOL); // Should version be created automatically with default settings?

// Routes
// POST /admin/tool/lala/modelversion.php?configid=<configid>&auto=<auto>

// Set some page parameters.
$pageurl = new moodle_url('/admin/tool/lala/modelversion.php', ['configid' => $configid, 'auto' => $auto]);
$context = context_system::instance();

$PAGE->set_url($pageurl);
$PAGE->set_context($context);

require_login();
require_capability('tool/lala:createmodelversion', $context);
require_sesskey();

if (!empty($configid) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $versionid = model_version::create_scaffold_and_get_for_config($configid);

    // If route contains auto param, do it automatically.
    if ($auto) {
        // Data you want to send
        $postData = array(
                'versionid' => $versionid,
        );

        // Set up POST data
        $postData = http_build_query($postData);

        // Set up cURL handle
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $CFG->wwwroot . '/admin/tool/lala/dataset.php'); // Replace with the correct path
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // Execute cURL request
        $res = curl_exec($ch);

        // Close cURL handle
        curl_close($ch);

        if (isset($res)) {
            $priorurl = new moodle_url('/admin/tool/lala/index.php#version'.$versionid);
            redirect($priorurl);
        }
    } else {
        // render a page for the user, from which the nexturl is called.
    }
} else {
    $priorurl = new moodle_url('/admin/tool/lala/index.php');
    redirect($priorurl);
}
