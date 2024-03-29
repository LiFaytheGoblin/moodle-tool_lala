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
 * Output for creating a new version of a single model configuration.
 *
 * @package     tool_lala
 * @copyright   2023 Linda Fernsel <fernsel@htw-berlin.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_lala\output;

use renderer_base;
use templatable;
use renderable;
use moodle_url;
use single_button;
use stdClass;

/**
 * Class for the output for creating a new version of a single model configuration.
 */
class model_configuration_version_creation extends model_configuration {
    /** @var stdClass $modelversion of a model config */
    protected stdClass $modelversion;

    /** @var stdClass $forms to be used during manual model version creation */
    protected stdClass $forms;

    /**
     * Constructor for this object.
     *
     * @param stdClass $modelconfig The model config object
     * @param stdClass $modelversion The model version to be created
     * @param stdClass $forms The forms for the model version creation
     */
    public function __construct(stdClass $modelconfig, stdClass $modelversion, stdClass $forms) {
        parent::__construct($modelconfig);
        $this->modelversion = $modelversion;
        $this->forms = $forms;
    }

    /**
     * Data for use with a template.
     *
     * @param renderer_base $output Renderer information.
     * @return array Said data.
     */
    public function export_for_template(renderer_base $output): array {
        $data = parent::export_for_template($output);
        $data['versionid'] = $this->modelversion->id;
        $data['selectcontextform'] = $this->forms->selectcontext;
        $data['uploaddatasetform'] = $this->forms->uploaddataset;
        return $data;
    }

    /**
     * No versions should be shown when creating a version of a config.
     *
     * @param renderer_base $output
     * @return array
     */
    protected function get_versions(renderer_base $output): array {
        return [];
    }

    /**
     * Append a string to the config name as a page title.
     *
     * @return string
     */
    protected function get_name(): string {
        $params = new stdClass();
        $params->name = parent::get_name();
        return get_string('createmodelversiontitle', 'tool_lala', $params);
    }

    /**
     * The default context ids should always be null.
     * @return string|null
     */
    protected function get_defaultcontextids(): ?string {
        return null;
    }
}
