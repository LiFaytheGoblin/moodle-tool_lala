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
 * Upgrades the database if a new plugin version is installed.
 *
 * @package     tool_lala
 * @copyright   2023 Linda Fernsel <fernsel@htw-berlin.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Upgrades the database if a new plugin version is installed.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_tool_lala_upgrade($oldversion): bool {
    global $CFG, $DB;

    $dbman = $DB->get_manager(); // Loads ddl manager and xmldb classes.

    if ($oldversion < 2023082800) {

        // Changing type of field error on table tool_lala_model_versions to text.
        $table = new xmldb_table('tool_lala_model_versions');
        $field = new xmldb_field('error', XMLDB_TYPE_TEXT, null, null, null, null, null, null);

        // Launch change of type for field error.
        $dbman->change_field_type($table, $field);

        // Lala savepoint reached.
        upgrade_plugin_savepoint(true, 2023082800, 'tool', 'lala');
    }

    if ($oldversion < 2023101109) {

        $table = new xmldb_table('tool_lala_model_configs');

        // Changing type of field target on table tool_lala_model_configs to char.
        $targetfield = new xmldb_field('target', XMLDB_TYPE_CHAR, 255, null, null, null, null, null);
        $dbman->change_field_type($table, $targetfield);

        // Change size limit of field name on table tool_lala_model_configs to 1333.
        $namefield = new xmldb_field('name', XMLDB_TYPE_CHAR, 1333, null, null, null, null, null);
        $dbman->change_field_precision($table, $namefield);

        // Lala savepoint reached.
        upgrade_plugin_savepoint(true, 2023101109, 'tool', 'lala');
    }

    // Everything has succeeded to here. Return true.
    return true;
}
