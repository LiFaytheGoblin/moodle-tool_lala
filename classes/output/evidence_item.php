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
 * Output for a single model version.
 *
 * @package     tool_lala
 * @copyright   2023 Linda Fernsel <fernsel@htw-berlin.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_lala\output;

use renderer_base;
use templatable;
use renderable;
use stdClass;
use tool_lala\related_data;
use tool_lala\related_data_helper;

/**
 * Class for the output for a single model version.
 */
class evidence_item implements templatable, renderable {
    /** @var stdClass $item of evidence of a model version */
    protected stdClass $item;
    /**
     * Constructor for this object.
     *
     * @param stdClass $item The evidence item object
     */
    public function __construct(stdClass $item) {
        $this->item = $item;
    }

    /**
     * Data for use with a template.
     *
     * @param renderer_base $output Renderer information.
     * @return array Said data.
     */
    public function export_for_template(renderer_base $output): array {
        $data = [];

        $data['id'] = $this->item->id;

        $nameparts = explode('\\', $this->item->name);
        $name = end($nameparts);
        $data['name'] = $name;

        $data['timecollectionstarted'] = userdate((int) $this->item->timecollectionstarted);
        $data['timecollectionfinished'] = userdate((int) $this->item->timecollectionfinished);
        $data['serializedfilelocation'] = $this->item->serializedfilelocation;

        $tablename = related_data_helper::get_tablename_from_serializedfilelocation($this->item->serializedfilelocation);
        if ($tablename !== false) {
            $data['tablename'] = $tablename;
        }

        return $data;
    }
}
