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
 * Adds a link to the tool page to the admin settings.
 *
 * @package     tool_lala
 * @copyright   2023 Linda Fernsel <fernsel@htw-berlin.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_analytics\manager;

defined('MOODLE_INTERNAL') || die();

$context = context_system::instance();
if (manager::is_analytics_enabled()) {
    $ADMIN->add('analytics', new admin_externalpage('tool_lala_index',
            get_string('pluginname', 'tool_lala'),
            $CFG->wwwroot . '/' . $CFG->admin . '/tool/lala/index.php', 'tool/lala:viewpagecontent'));
}
