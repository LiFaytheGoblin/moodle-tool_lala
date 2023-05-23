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
 * The dataset class.
 * Collects and preserves evidence on data used by the model.
 * Can be inherited from for specific datasets.
 *
 * @package     tool_laaudit
 * @copyright   2023 Linda Fernsel <fernsel@htw-berlin.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_laaudit;


class dataset extends evidence {

    public function store($data) {
        $this->data = $data;

        $fileinfo = $this->get_file_info();

        $fs = get_file_storage();
        $filestring = $this->serialize($data);
        $fs->create_file_from_string($fileinfo, $filestring);

        $this->set_serializedfilelocation();
    }

    private function serialize($data) {
        $str = "";
        $indicatornamesstring = null;
        foreach($data as $results) {
            $ids = array_keys($results);
            foreach($ids as $id) {
                if ($id == "0") { // These are the indicator names
                    $indicatornamesstring = implode(",", $results[$id]);
                    continue;
                }
                $indicatorvaluesstr = implode(",", $results[$id]);
                $simpleid = explode("-", $id)[0];
                $str = $str.$simpleid.",".$indicatorvaluesstr."\n";
            }
        }

        $heading = "sampleid,".$indicatornamesstring."\n";
        $str = $heading.$str;

        return $str;
    }

    protected function get_file_type() {
        return 'csv';
    }
}
