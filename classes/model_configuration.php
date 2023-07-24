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

use core_analytics\manager;
use stdClass;

/**
 * Class for the model configuration.
 */
class model_configuration {
    /** @var int $id id assigned to the configuration by the db. */
    private int $id;
    /** @var int $modelid of the belonging analytics model. */
    private int $modelid;
    /** @var string $name of the belonging analytics model. */
    private string $name;
    /** @var string $target of the belonging analytics model. */
    private string $target;
    /** @var string $modelanalysabletype that will be used for calculating features for the model. */
    private string $modelanalysabletype;
    /** @var string $analysisinterval used for the model version */
    private string $analysisinterval;
    /** @var string $predictionsprocessor used by the model version */
    private string $predictionsprocessor;
    /** @var string|null defaultcontextids used as data by the model version */
    private ?string $defaultcontextids;
    /** @var string $indicators used by the model version */
    private string $indicators;
    /** @var int[] $versions created of the model config. */
    private array $versions;


    /**
     * Constructor. Import from DB.
     *
     * @param number $id
     * @return void
     */
    public function __construct($id) {
        global $DB;

        $modelconfig = $DB->get_record('tool_laaudit_model_configs', ['id' => $id], '*', MUST_EXIST);

        $this->id = $modelconfig->id;
        $this->modelid = $modelconfig->modelid;
        $this->target = $modelconfig->target;
        $this->name = $modelconfig->name ?? 'model' . $this->modelid;
        $this->predictionsprocessor = $modelconfig->predictionsprocessor;
        $this->analysisinterval = $modelconfig->analysisinterval;
        $this->defaultcontextids = $modelconfig->defaultcontextids;
        $this->indicators = $modelconfig->indicators;

        $targetinstance = manager::get_target($this->target);
        if (!$targetinstance) {
            throw new \Exception('Target could not be retrieved from target name '.$this->target);
        }
        $this->modelanalysabletype = $targetinstance->get_analyser_class();

        $this->versions = $this->get_versions_from_db();
    }

    /**
     * Retrieve versions from the DB and store in object properties
     *
     * @return stdClass[] versions
     */
    public function get_versions_from_db(): array {
        global $DB;

        $versionids = $DB->get_fieldset_select('tool_laaudit_model_versions', 'id', 'configid='.$this->id);

        $versions = [];
        foreach ($versionids as $versionid) {
            $version = new model_version($versionid);
            $versions[] = $version->get_model_version_obj();
        }
        return $versions;
    }

    /**
     * Create a new model configuration for a model id.
     * Failing gracefully: If config for this model already exists, just return it.
     * The accompanying db table is needed to preserve a record of the model configuration
     * even if the model has been deleted.
     *
     * @param int $modelid of an analytics model
     * @return int id of created or retrieved object
     */
    public static function create_and_get_for_model(int $modelid) : int {
        global $DB;

        $modelobj = $DB->get_record('analytics_models', ['id' => $modelid], '*', MUST_EXIST);

        $obj = new stdClass();
        $obj->modelid = $modelid;
        $obj->name = $modelobj->name;
        if (!isset($obj->name)) {
            $modelidcount = $DB->count_records('tool_laaudit_model_configs', ['modelid' => $modelid]);
            $obj->name = 'config' . $modelid . '/' . $modelidcount;
        }
        $obj->target = $modelobj->target;

        if (self::valid_exists($modelobj->predictionsprocessor)) {
            $obj->predictionsprocessor = $modelobj->predictionsprocessor;
        } else {
            $default = manager::default_mlbackend();
            $obj->predictionsprocessor = $default;
        }

        if (self::valid_exists($modelobj->timesplitting)) {
            $obj->analysisinterval = $modelobj->timesplitting;
        } else {
            $analysisintervals = manager::get_time_splitting_methods_for_evaluation();
            $firstanalysisinterval = array_keys($analysisintervals)[0];
            $obj->analysisinterval = $firstanalysisinterval;
        }

        if (self::valid_exists($modelobj->contextids)) {
            $obj->defaultcontextids = $modelobj->contextids;
        }

        $obj->indicators = $modelobj->indicators;

        $obj->timecreated = time();

        return $DB->insert_record('tool_laaudit_model_configs', $obj);
    }

    /**
     * Helper method: Short check to verify whether the provided value is valid, and thus a valid list exists.
     *
     * @param string|null $value to check
     * @return boolean
     */
    private static function valid_exists(?string $value): bool {
        return isset($value) && $value != "" && $value != "[]";
    }

    /**
     * Returns a plain stdClass with the model config data.
     *
     * @return stdClass
     */
    public function get_model_config_obj(): stdClass {
        $obj = new stdClass();

        // Add info about the model configuration.
        $obj->id = $this->id;
        $obj->modelid = $this->modelid;
        $obj->name = $this->name;
        $obj->target = $this->target;
        $obj->modelanalysabletype = $this->modelanalysabletype;
        $obj->analysisinterval = $this->analysisinterval;
        $obj->predictionsprocessor = $this->predictionsprocessor;
        $obj->defaultcontextids = $this->defaultcontextids;
        $obj->indicators = $this->indicators;
        $obj->versions = $this->versions;

        return $obj;
    }

    public function get_id(): int {
        return $this->id;
    }

    public function get_modelid(): int {
        return $this->modelid;
    }

    public function get_name(): string {
        return $this->name;
    }

    public function get_target(): string {
        return $this->target;
    }
}
