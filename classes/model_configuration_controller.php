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
 * The model configuration controller class.
 *
 * @package     tool_laaudit
 * @copyright   2023 Linda Fernsel <fernsel@htw-berlin.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_laaudit;

use core_analytics\manager;

class model_configuration_controller {
    private $models;
    private $modelconfigs;
    private $modelconfigsrenderable;
    public function __construct() {
        $this->models = manager::get_all_models();

        $this->modelconfigs = [];

        $this->modelconfigsrenderable = new \stdClass();
        $this->modelconfigsrenderable->modelconfigs = [];

        foreach ($this->models as $model) {
            // Todo: only check non-static models?
            $modelconfig = new model_configuration($model);
            // $this->modelconfigs[] = $modelconfig;

            $modelconfigobj = $modelconfig->get_template_context_json();
            $this->modelconfigsrenderable->modelconfigs[] = $modelconfigobj;
        }
    }
    /**
     * Set up model configurations
     *
     * @return json
     */
    public function get_all_model_configs_renderable() {
        return $this->modelconfigsrenderable;
    }
}
