<?php

function xmldb_tool_lala_upgrade($oldversion): bool {
    global $CFG, $DB;

    $dbman = $DB->get_manager(); // Loads ddl manager and xmldb classes.

    if ($oldversion < 2023082800) {

        // Changing type of field error on table tool_lala_model_versions to text.
        $table = new xmldb_table('tool_lala_model_versions');
        $field = new xmldb_field('error', XMLDB_TYPE_TEXT, null, null, null, null, null, 'configid');

        // Launch change of type for field error.
        $dbman->change_field_type($table, $field);

        // Lala savepoint reached.
        upgrade_plugin_savepoint(true, 2023082800, 'tool', 'lala');
    }

    // Everything has succeeded to here. Return true.
    return true;
}