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
use stdClass;

/**
 * Class for the model configuration.
 */
class model_configuration {
    /** @var int $id id assigned to the configuration by the db. */
    private $id;
    /** @var stdClass $model of the serialized model object. */
    private $model;


    /**
     * Constructor.
     *
     * @param model $model
     * @return void
     */
    public function __construct($model) {
        global $DB;

        // Create in DB.
        $model_obj = $model->get_model_obj();
        if (!$DB->record_exists('tool_laaudit_model_configs', array('modelid' => $model_obj->id))) {
            self::insert_new_from_model_into_db($model_obj);
        }

        // Fill properties from DB.
        $modelconfig = $DB->get_record('tool_laaudit_model_configs', array('modelid' => $model_obj->id), '*', MUST_EXIST);

        $this->id = $modelconfig->id;
        $this->modelid = $model_obj->id;
        $this->modelname = isset($model_obj->name) ? $model_obj->name : "model" . $this->modelid;
        $this->modeltarget = $model_obj->target;
    }

    /**
     * Returns a plain stdClass with the model config data (id, modelid, versions) plus modelname and modeltarget.
     *
     * @return stdClass
     */
    public function get_model_config_obj() {
        $obj = new stdClass();

        // Add info about the model configuration.
        $obj->id = $this->id;
        $obj->modelid = $this->modelid;
        $obj->modelname = $this->modelname;
        $obj->modeltarget = $this->modeltarget;

        return $obj;
    }

    /**
     * Create a new model configuration from a model object
     *
     * @param stdClass $model model object
     * @return void
     */
    public static function insert_new_from_model_into_db($model) {
        global $DB;

        $obj = new stdClass();
        $obj->modelid = $model->id;

        $DB->insert_record('tool_laaudit_model_configs', $obj);
    }
}
