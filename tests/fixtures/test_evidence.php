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
 * Test evidence.
 *
 * @package     tool_lala
 * @copyright   2023 Linda Fernsel <fernsel@htw-berlin.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class test_evidence extends evidence {
    /** @var string[] RAWDATA */
    const RAWDATA =  ['a', 'b', 'c'];
    /** @var string[] DATASTRING */
    const DATASTRING = "['a', 'b', 'c']";
    /** @var string FILETYPE */
    const FILETYPE = 'txt';

    /**
     * Creates a general piece of evidence for a version.
     *
     * @param int $versionid
     * @return int evidenceid
     */
    public static function create(int $versionid): int {
        global $DB;

        $validmodelobject = [
                'name' => 'test_evidence',
                'versionid' => $versionid,
                'timecollectionstarted' => time(),
        ];

        return $DB->insert_record('tool_lala_evidence', $validmodelobject);
    }

    /**
     * Collects the raw data. Example implementation.
     *
     * @param array $options = []
     */
    public function collect(array $options): void {
        $this->validate_collect_options($options);
        $this->data = self::RAWDATA;
    }

    /**
     * Serializes the raw data. Example implementation.
     * Store the serialization string in the filestring field.
     */
    public function serialize(): void {
        $this->filestring = self::DATASTRING;
    }

    /**
     * Returns the type of the stored file: "txt". Example implementation.
     * @return string
     */
    protected function get_file_type(): string {
        return self::FILETYPE;
    }

    /** Validate the $options -
     * @param array $options
     */
    public function validate_collect_options(array $options): void {
        if (isset($this->data)) throw new Exception('Already collected.');
    }

    public function restore_raw_data(): void {
        // TODO: Implement restore_raw_data() method.
    }
}