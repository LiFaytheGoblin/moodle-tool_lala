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
 * Output for a single model version description.
 *
 * @package     tool_laaudit
 * @copyright   2023 Linda Fernsel <fernsel@htw-berlin.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_laaudit\output;

use renderer_base;
use templatable;
use renderable;
use moodle_url;
use help_icon;
use single_button;
use stdClass;

/**
 * Class for the output for a single model version description.
 */
class model_version_description implements templatable, renderable {
    /** @var stdClass $version of a model config */
    protected $version;
    /**
     * Constructor for this object.
     *
     * @param stdClass $version The model version object
     */
    public function __construct($version) {
        $this->version = $version;
    }

    /**
     * Data for use with a template.
     *
     * @param renderer_base $output Renderer information.
     * @return stdClass Said data.
     */
    public function export_for_template(renderer_base $output) {
        $data = new stdClass();

        // Add info about the model version.
        $data->id = $this->version->id;
        $data->name = $this->version->name;
        $data->timecreationstarted = userdate((int) $this->version->timecreationstarted);
        $finished = (int) $this->version->timecreationfinished > 0;
        $data->timecreationfinishedicon = $finished ? 'end' : 'half';
        $data->timecreationfinished = $finished ? userdate((int) $this->version->timecreationfinished) : get_string('unfinished', 'tool_laaudit');
        $data->analysisinterval = $this->version->analysisinterval;
        $data->predictionsprocessor = $this->version->predictionsprocessor;
        $data->contextids = $this->version->contextids;
        $data->indicators = $this->version->indicators;

        return $data;
    }
}
