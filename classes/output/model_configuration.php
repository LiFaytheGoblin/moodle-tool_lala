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
 * Output for a single model configuration.
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
use single_button;
use stdClass;

/**
 * Class for the output for a single model configuration.
 */
class model_configuration implements templatable, renderable {
    /** @var stdClass $modelconfig of a model config */
    protected stdClass $modelconfig;

    /**
     * Constructor for this object.
     *
     * @param stdClass $modelconfig The model config object
     */
    public function __construct(stdClass $modelconfig) {
        $this->modelconfig = $modelconfig;
    }

    /**
     * Data for use with a template.
     *
     * @param renderer_base $output Renderer information.
     * @return array Said data.
     */
    public function export_for_template(renderer_base $output): array {
        $data = [];

        // Add info about the model configuration.
        $data['id'] = $this->modelconfig->id;
        $data['modelid'] = $this->modelconfig->modelid;
        $data['name'] = $this->modelconfig->name;

        $targetnameparts = explode('\\', $this->modelconfig->target);
        $data['target'] = end($targetnameparts);

        $modelanalysabletypenameparts = explode('\\', $this->modelconfig->modelanalysabletype);
        $data['modelanalysabletype'] = end($modelanalysabletypenameparts);

        $data['predictionsprocessor'] = explode('\\', $this->modelconfig->predictionsprocessor)[1];

        $analysisintervalnameparts = explode('\\', $this->modelconfig->analysisinterval);
        $data['analysisinterval'] = end($analysisintervalnameparts);

        $data['defaultcontextids'] = get_string('allcontexts', 'tool_laaudit');
        $contextids = json_decode($this->modelconfig->defaultcontextids);
        if (gettype($contextids) == 'array') {
            $data['defaultcontextids'] = implode(', ', $contextids);
        } else if (gettype($contextids) == 'string') {
            $data['defaultcontextids'] = $contextids;
        }

        $data['indicators'] = '';
        $indicators = json_decode($this->modelconfig->indicators);
        if (gettype($indicators) == 'array') {
            $data['indicators'] = implode(', ', $indicators);
        } else if (gettype($indicators) == 'string') {
            $data['indicators'] = $indicators;
        }

        // Add buttons.
        $buttons = [];
        $buttons[] = new single_button(new moodle_url('modelversion.php', ['configid' => $this->modelconfig->id, 'sesskey' => sesskey()]),
                get_string('automaticallycreateversion', 'tool_laaudit'), 'post');
        foreach ($buttons as $key => $button) {
            $buttons[$key] = $button->export_for_template($output);
        }
        $data['buttons'] = $buttons;

        // Add started evidence sets.
        $versions = []; // Todo: Differentiate started and finished evidence sets? Sort?
        foreach ($this->modelconfig->versions as $version) {
            $versionrenderer = new model_version($version);
            $versions[] = $versionrenderer->export_for_template($output);
        }
        $data['versions'] = $versions;

        return $data;
    }
}
