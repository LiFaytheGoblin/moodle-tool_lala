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
 * @package     tool_lala
 * @copyright   2023 Linda Fernsel <fernsel@htw-berlin.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_lala\output;

use plugin_renderer_base;

/**
 * Renderer
 */
class renderer extends plugin_renderer_base {
    /**
     * Defer to template.
     *
     * @param evidence_item $page
     * @return string html for the page
     */
    public function render_evidence_item(evidence_item $page): string {
        $data = $page->export_for_template($this);
        return parent::render_from_template('tool_lala/evidence_item', $data);
    }
    /**
     * Defer to template.
     *
     * @param model_version_description $page
     * @return string html for the page
     */
    public function render_model_version_description(model_version_description $page): string {
        $data = $page->export_for_template($this);
        return parent::render_from_template('tool_lala/model_version_description', $data);
    }
    /**
     * Defer to template.
     *
     * @param model_version $page
     * @return string html for the page
     */
    public function render_model_version(model_version $page): string {
        $data = $page->export_for_template($this);
        return parent::render_from_template('tool_lala/model_version', $data);
    }

    /**
     * Defer to template.
     *
     * @param model_configuration $page
     * @return string html for the page
     */
    public function render_model_configuration(model_configuration $page): string {
        $data = $page->export_for_template($this);
        return parent::render_from_template('tool_lala/model_configuration', $data);
    }

    /**
     * Defer to template.
     *
     * @param model_configurations $page
     * @return string html for the page
     */
    public function render_model_configurations(model_configurations $page): string {
        $data = $page->export_for_template($this);
        return parent::render_from_template('tool_lala/model_configurations', $data);
    }
}
