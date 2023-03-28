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
 * Renderer.
 *
 * @package     tool_laaudit
 * @copyright   2023 Linda Fernsel <fernsel@htw-berlin.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_laaudit\output;

use plugin_renderer_base;

/**
 * Renderer
 */
class renderer extends plugin_renderer_base {
    /**
     * Defer to template.
     *
     * @param index_page $page
     * @return string html for the page
     */
    public function render_evidence_item($page) {
        $data = $page->export_for_template($this);
        return parent::render_from_template('tool_laaudit/evidence_item', $data);
    }
    /**
     * Defer to template.
     *
     * @param index_page $page
     * @return string html for the page
     */
    public function render_evidence_set($page) {
        $data = $page->export_for_template($this);
        return parent::render_from_template('tool_laaudit/evidence_set', $data);
    }

    /**
     * Defer to template.
     *
     * @param index_page $page
     * @return string html for the page
     */
    public function render_model_configuration($page) {
        $data = $page->export_for_template($this);
        return parent::render_from_template('tool_laaudit/model_configuration', $data);
    }

    /**
     * Defer to template.
     *
     * @param index_page $page
     * @return string html for the page
     */
    public function render_model_configurations($page) {
        $data = $page->export_for_template($this);
        return parent::render_from_template('tool_laaudit/model_configurations', $data);
    }
}
