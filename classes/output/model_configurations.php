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
 * Output for a list of single model configurations.
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

class model_configurations implements templatable, renderable {
    protected $modelconfigs;
    /**
     * Constructor for this object.
     *
     * @param stdClass $modelconfigs An array of model config objects
     */
    public function __construct($modelconfigs) {
        $this->modelconfigs = $modelconfigs;

    }

    /**
     * Data for use with a template.
     *
     * @param renderer_base $output Renderer information.
     * @return stdClass Said data.
     */
    public function export_for_template(renderer_base $output) {
        $data = new stdClass();
        $data->modelconfigs = [];

        foreach ($this->modelconfigs as $modelconfig) {
            $modelconfig = new model_configuration($modelconfig);
            $data->modelconfigs[] = $modelconfig->export_for_template($output);
        }

        return $data;
    }
}