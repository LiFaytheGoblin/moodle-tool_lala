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

namespace tool_laaudit\event;

use core\event\base;
use context_system;

/**
 * Model version created event.
 *
 * @package     tool_laaudit
 * @copyright   2023 Linda Fernsel <fernsel@htw-berlin.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class model_version_created extends base {
    /**
     * Set basic properties for the event.
     */
    protected function init() {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = base::LEVEL_OTHER;
        $this->data['objecttable'] = 'tool_laaudit_model_versions';
        $this->context = context_system::instance();
    }

    /**
     * Returns localised general event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventmodelversioncreated', 'tool_laaudit');
    }

    /**
     * Returns non-localised event description with id's for admin use only.
     *
     * @return string
     */
    public function get_description() {
        return "The user with id '$this->userid' has created a new version '{$this->contextinstanceid}' of model configuration
         '{$this->other['modelid']} associated with model '{$this->other['modelid']}'.";
    }

    /**
     * Returns relevant URL.
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/admin/tool/laaudit/index.php#version'.$this->other['versionid']);
    }

    /**
     * Validates that the "other" event property has been set correctly.
     *
     * @return void
     */
    protected function validate_data() {
        parent::validate_data();

        if (!isset($this->other['configid'])) {
            throw new \coding_exception('The \'configid\' value must be set in other.');
        }

        if (!isset($this->other['modelid'])) {
            throw new \coding_exception('The \'modelid\' value must be set in other.');
        }
    }
}
