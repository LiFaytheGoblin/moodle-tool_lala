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

namespace tool_lala;

defined('MOODLE_INTERNAL') || die();

/**
 * Test config.
 *
 * @package     tool_lala
 * @copyright   2023 Linda Fernsel <fernsel@htw-berlin.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class test_config {
    /**
     * Stores a config in the db and returns the id.
     *
     * @param int $modelid
     * @return int configid
     */
    public static function create(int $modelid) : int {
        global $DB;
        $valididconfigobject = [
                'modelid' => $modelid,
                'target' => test_model::TARGET,
                'timecreated' => time(),
                'name' => test_model::NAME,
                'analysisinterval' => test_model::ANALYSISINTERVAL,
                'indicators' => test_model::INDICATORS
        ];
        return $DB->insert_record('tool_lala_model_configs', $valididconfigobject);
    }

    /**
     * Gets the highest config id that is currently in use.
     *
     * @return int highest configid
     */
    public static function get_highest_id(): int {
        global $DB;
        $existingconfigids = $DB->get_fieldset_select('tool_lala_model_configs', 'id', '1=1');
        return (sizeof($existingconfigids) > 0) ? max($existingconfigids) : 0;
    }
}
