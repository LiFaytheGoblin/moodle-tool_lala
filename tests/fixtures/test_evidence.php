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
 * Test model.
 *
 * @package     tool_laaudit
 * @copyright   2023 Linda Fernsel <fernsel@htw-berlin.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_laaudit;

defined('MOODLE_INTERNAL') || die();
class test_evidence extends evidence {
    const rawdata =  ['a', 'b', 'c'];
    const datastring = "['a', 'b', 'c']";

    public static function create(int $versionid) {
        global $DB;

        $validmodelobject = [
                'name' => 'test_evidence',
                'versionid' => $versionid,
                'timecollectionstarted' => time(),
        ];

        return $DB->insert_record('tool_laaudit_evidence', $validmodelobject);
    }

    /**
     * Collects the raw data. Example implementation.
     * @param array $options = []
     * @return void
     */
    public function collect($options) {
        $this->data = self::rawdata;
    }

    /**
     * Serializes the raw data. Example implementation.
     * Store the serialization string in the filestring field.
     *
     * @return void
     */
    public function serialize() {
        $this->filestring = self::datastring;
    }

    /**
     * Returns the type of the stored file: "txt". Example implementation.
     * @return string
     */
    protected function get_file_type() {
        return '.txt';
    }
}