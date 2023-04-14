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
 * The accompanying db table is needed to preserve a record of the model configuration
 * even if the model has been deleted.
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
    /** @var int $modelid of the belonging analytics model. */
    private $modelid;
    /** @var string $modelname of the belonging analytics model. */
    private $modelname;
    /** @var string $modeltarget of the belonging analytics model. */
    private $modeltarget;
    /** @var int[] $versions created of the model config. */
    private $versions;
    /**
     * Constructor. Deserialize DB object.
     *
     * @param model $model
     * @return void
     */
    public function __construct($id) {
        global $DB;
        // Fill properties from DB.
        $modelconfig = $DB->get_record('tool_laaudit_model_configs', array('id' => $id), '*', MUST_EXIST);

        $this->id = $modelconfig->id;
        $this->modelid = $modelconfig->modelid;

        $model = $DB->get_record('analytics_models', array('id' => $this->modelid), '*', MUST_EXIST);

        $this->modelname = isset($model->name) ? $model->name : "model" . $this->modelid;
        $this->modeltarget = $model->target;

        $this->versions = $this->get_versions_from_db();
    }

    /**
     * Create a new model configuration for a model id, or if it already exists, retrieve that config.
     *
     * @param int $modelid of an analytics model
     * @return int id of created or retrieved object
     */
    public static function get_or_create_and_get_for_model($modelid) {
        global $DB;

        if ($DB->record_exists('tool_laaudit_model_configs', array('modelid' => $modelid))) {
            $record = $DB->get_record('tool_laaudit_model_configs', array('modelid' => $modelid), 'id', MUST_EXIST);
            return $record->id;
        }

        $obj = new stdClass();
        $obj->modelid = $modelid;

        return $DB->insert_record('tool_laaudit_model_configs', $obj);
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
        $obj->versions = $this->versions;

        return $obj;
    }

    /**
     * Retrieve versions from the DB and store in object properties
     *
     * @return stdClass[] versions
     */
    public function get_versions_from_db() {
        global $DB;

        $versionids = $DB->get_fieldset_select('tool_laaudit_model_versions', 'id', '1=1');

        $versions = [];
        foreach ($versionids as $versionid) {
            $version = new model_version($versionid);
            $versions[] = $version->get_model_version_obj();
        }
        return $versions;
    }
}
