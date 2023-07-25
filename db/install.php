<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Adds a new role to the system
 *
 * @package     tool_laaudit
 * @copyright   2023 Linda Fernsel <fernsel@htw-berlin.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Install the new roles for this plugin.
 * @return void
 */
function xmldb_tool_laaudit_install() {
    $shortname = 'laaudit_auditor';

    try {
        $auditorrole = create_role('Learning Analytics Auditor', $shortname, 'A Learning Analytics Auditor may only view,
        test and download data from the Learning Analytics models in the system, but by default has no permissions to interact with
        the system in other ways. This allows it to have Moodle users who are only auditors, but do not have admin or teacher
        power.');

        set_role_contextlevels($auditorrole, [CONTEXT_SYSTEM]);

    } catch (dml_write_exception) {
        debugging('Role has already been created previously.');
    }

    update_capabilities('tool_laaudit');

    $context = context_system::instance();

    global $DB;
    $rolerecord = $DB->get_record('role', ['shortname' => $shortname], '*', MUST_EXIST);

    assign_capability('tool/laaudit:viewpagecontent', CAP_ALLOW, $rolerecord->id, $context->id, true);
    assign_capability('tool/laaudit:downloadevidence', CAP_ALLOW, $rolerecord->id, $context->id, true);
    assign_capability('tool/laaudit:createmodelversion', CAP_ALLOW, $rolerecord->id, $context->id, true);

    $context->mark_dirty();
}
