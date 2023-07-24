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
 * @package     tool_laaudit
 * @copyright   2023 Linda Fernsel <fernsel@htw-berlin.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_laaudit\output;

use renderer_base;
use templatable;
use renderable;
use stdClass;

/**
 * Class for the output for a single model version.
 */
class model_version implements templatable, renderable {
    /** @var stdClass $version of a model config */
    protected stdClass $version;
    /**
     * Constructor for this object.
     *
     * @param stdClass $version The model version object
     */
    public function __construct(stdClass $version) {
        $this->version = $version;
    }

    /**
     * Data for use with a template.
     *
     * @param renderer_base $output Renderer information.
     * @return array Said data.
     */
    public function export_for_template(renderer_base $output) : array {
        $data = [];

        $data['id'] = $this->version->id;
        $data['name'] = $this->version->name;

        // Add info about the model version.
        $descriptionrenderer = new model_version_description($this->version);
        $data['description'] = [$descriptionrenderer->export_for_template($output)];

        $evidenceitems = [];
        foreach ($this->version->evidenceobjects as $evidenceobject) {
            $evidencerenderer = new evidence_item($evidenceobject);
            $evidenceitems[] = $evidencerenderer->export_for_template($output);
        }
        $data['evidence'] = $evidenceitems;

        return $data;
    }
}
