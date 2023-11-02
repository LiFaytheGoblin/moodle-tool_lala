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
 * @package     tool_lala
 * @copyright   2023 Linda Fernsel <fernsel@htw-berlin.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_lala\output;

use renderer_base;
use templatable;
use renderable;
use stdClass;

/**
 * Class for the output for a single model version description.
 */
class model_version_description implements templatable, renderable {
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

        // Add info about the model version.
        $data['versionid'] = $this->version->id;

        $data['versionname'] = $this->version->name;

        $data['timecreationstarted'] = userdate((int) $this->version->timecreationstarted);

        $finished = isset($this->version->timecreationfinished) && (int) $this->version->timecreationfinished > 0;
        $data['timecreationfinishedicon'] = $finished ? 'end' : 'half';
        $data['timecreationfinished'] = $finished ?
                userdate((int) $this->version->timecreationfinished) : get_string('unfinished', 'tool_lala');

        $params = new stdClass();
        $params->testsize = $this->version->relativetestsetsize * 100;
        $params->trainsize = 100 - $params->testsize;
        $data['traintestsplit'] = get_string('traintest', 'tool_lala', $params);

        $data['contextids'] = get_string('allcontexts', 'tool_lala');
        $contextids = json_decode($this->version->contextids);
        if (gettype($contextids) == 'array') {
            $data['contextids'] = implode(', ', $contextids);
        } else if (gettype($contextids) == 'string') {
            $data['contextids'] = $contextids;
        }

        $data['errormessage'] = $this->version->error;

        return $data;
    }
}
