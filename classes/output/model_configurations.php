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

/**
 * Class for the output for a list of single model configurations.
 */
class model_configurations implements templatable, renderable {
    /** @var stdClass[] $modelconfigs of model configs */
    protected array $modelconfigs;
    /**
     * Constructor for this object.
     *
     * @param stdClass[] $modelconfigs An array of model config objects
     */
    public function __construct(array $modelconfigs) {
        $this->modelconfigs = $modelconfigs;
    }

    /**
     * Data for use with a template.
     *
     * @param renderer_base $output Renderer information.
     * @return array Said data.
     */
    public function export_for_template(renderer_base $output) : array {
        $items = [];
        foreach ($this->modelconfigs as $modelconfig) {
            $modelconfig = new model_configuration($modelconfig);
            $items[] = $modelconfig->export_for_template($output);
        }
        usort($items, "self::sort_nested_array_by_key_name");

        return ['modelconfigs' => $items];
    }

    /**
     * Sort by name.
     *
     * @param array $a
     * @param array $b
     * @return bool if a comes before b
     */
    private static function sort_nested_array_by_key_name(array $a, array $b) : bool {
        return $a['name'] > $b['name'];
    }
}
