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
        $data['configid'] = $this->modelconfig->id;
        $data['configname'] = $this->get_name();

        // Add description.
        $description = [];

        $targetnameparts = explode('\\', $this->modelconfig->target);
        $description['target'] = end($targetnameparts);

        $modelanalysabletypenameparts = explode('\\', $this->modelconfig->modelanalysabletype);
        $description['modelanalysabletype'] = end($modelanalysabletypenameparts);

        $description['predictionsprocessor'] = explode('\\', $this->modelconfig->predictionsprocessor)[1];

        $analysisintervalnameparts = explode('\\', $this->modelconfig->analysisinterval);
        $description['analysisinterval'] = end($analysisintervalnameparts);

        $description['defaultcontextids'] = $this->get_defaultcontextids();

        $description['firstindicator'] = '';
        $indicators = json_decode($this->modelconfig->indicators);
        if (gettype($indicators) == 'array') {
            $description['firstindicator'] = $indicators[0];
            if (count($indicators) > 1) {
                $description['firstindicator'] = $description['firstindicator'] . ', ';
                $remainingindicators = array_slice($indicators, 1);
                $description['indicators'] = implode(', ', $remainingindicators);
            }
        } else if (gettype($indicators) == 'string') {
            $description['firstindicator'] = $indicators;
        }

        $data['description'] = $description;

        // Add session key for create model version button.
        $data['sesskey'] = sesskey();

        // Add started evidence sets.
        $data['versions'] = $this->get_versions($output);

        return $data;
    }

    /**
     * Get the model versions to show.
     *
     * @param renderer_base $output
     * @return array
     */
    protected function get_versions(renderer_base $output): array {
        $versions = []; // Todo: Differentiate started and finished evidence sets? Sort?
        foreach ($this->modelconfig->versions as $version) {
            $versionrenderer = new model_version($version);
            $versions[] = $versionrenderer->export_for_template($output);
        }
        return $versions;
    }

    /**
     * Get the name for this config.
     *
     * @return string
     */
    protected function get_name(): string {
        return $this->modelconfig->name;
    }

    /**
     * Get the default contextids for this config.
     *
     * @return string|null
     */
    protected function get_defaultcontextids(): ?string {
        $defaultcontextids = get_string('allcontexts', 'tool_lala');
        $contextids = json_decode($this->modelconfig->defaultcontextids);
        if (gettype($contextids) == 'array') {
            $defaultcontextids = implode(', ', $contextids);
        } else if (gettype($contextids) == 'string') {
            $defaultcontextids = $contextids;
        }
        return $defaultcontextids;
    }
}
