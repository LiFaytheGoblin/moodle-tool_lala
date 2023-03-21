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
 * The model configuration class, built on top of the analytics/model class.
 *
 * @package     tool_laaudit
 * @copyright   2023 Linda Fernsel <fernsel@htw-berlin.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_laaudit;

use core_analytics\model;

class model_configuration {
    private $id;
    private $model; // Serialized model object.
    private $versions;


    /**
     * Constructor.
     *
     * @param \model $model
     * @return void
     */
    public function __construct($model) {
        global $DB;

        $this->model = $model->get_model_obj();
        $this->model->name_safe = isset($model->name) ? $model->name : "model" . $this->model->id;

        if (!$DB->record_exists('tool_laaudit_model_configs', array('modelid' => $this->model->id))) {
            self::insert_new_from_model_into_db($this->model);
        }

        $modelconfig = $DB->get_record('tool_laaudit_model_configs', array('modelid' => $this->model->id), '*', MUST_EXIST);

        $this->id = $modelconfig->id;
        $this->versions = json_decode($modelconfig->versions); // Array of ids, todo see if this works!
    }

    /**
     * Returns a plain \stdClass with the model config data (id, modelid, versions) plus modelname and modeltarget.
     *
     * @return \stdClass
     */
    public function get_modelconfig_obj() {
        $obj = new \stdClass();
        $obj->id = $this->id;
        $obj->modelid = $this->model->id;
        $obj->modelname = $this->model->name_safe;
        $obj->modeltarget = $this->model->target;
        $obj->versions = $this->versions;

        return $obj;
    }

    /**
     * Create a new model configuration from a model object
     *
     * @param \stdClass $model
     * @return void
     */
    public static function insert_new_from_model_into_db($model) {
        global $DB;

        $obj = new \stdClass();
        $obj->modelid = $model->id;
        $obj->versions = json_encode([]);

        $DB->insert_record('tool_laaudit_model_configs', $obj);
    }
}
