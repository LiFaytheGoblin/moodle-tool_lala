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
use tool_lala\task\version_create;

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
     * Get the description for the model version.
     *
     * @param renderer_base $output
     * @return array
     */
    protected function get_description(renderer_base $output): array {
        $descriptionrenderer = new model_version_description($this->version);
        return [$descriptionrenderer->export_for_template($output)];
    }

    /**
     * Get evidence items of this model.
     *
     * @param renderer_base $output
     * @return array
     */
    protected function get_evidence_items(renderer_base $output): array {
        $evidenceitems = [];
        foreach ($this->version->evidenceobjects as $evidenceobject) {
            $evidencerenderer = new evidence_item($evidenceobject);
            $evidenceitems[] = $evidencerenderer->export_for_template($output);
        }
        return $evidenceitems;
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
        $data['description'] = $this->get_description($output);
        $data['evidence'] = $this->get_evidence_items($output);

        $interrupted = true;
        // If the version creation is finished, scheduled to be running or running, it's not interrupted.
        $finished = isset($this->version->timecreationfinished) && (int) $this->version->timecreationfinished > 0;
        if ($finished || version_create::is_active($this->version->id)) {
            $interrupted = false;
        }

        $data['model_version_interrupted'] = $interrupted;
        $data['hasevidence'] = count($data['evidence']) > 0;

        return $data;
    }
}
