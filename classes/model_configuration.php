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
 * @package     tool_lala
 * @copyright   2023 Linda Fernsel <fernsel@htw-berlin.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_lala;

use core_analytics\manager;
use Exception;
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
    /** @var stdClass[] $versions created of the model config. */
    private array $versions;

    /**
     * Constructor. Import from DB.
     *
     * @param number $id
     * @return void
     * @throws Exception
     * @throws Exception
     */
    public function __construct($id) {
        global $DB;

        $modelconfig = $DB->get_record('tool_lala_model_configs', ['id' => $id], '*', MUST_EXIST);

        $this->id = $modelconfig->id;
        $this->modelid = $modelconfig->modelid;
        $this->target = $modelconfig->target;
        $this->name = $modelconfig->name;
        $this->predictionsprocessor = $modelconfig->predictionsprocessor;
        $this->analysisinterval = $modelconfig->analysisinterval;
        $this->defaultcontextids = $modelconfig->defaultcontextids;
        $this->indicators = $modelconfig->indicators;

        $targetinstance = manager::get_target($this->target);
        if (!$targetinstance) {
            throw new Exception('Target could not be retrieved from target name '.$this->target);
        }
        $this->modelanalysabletype = $targetinstance->get_analyser_class();

        $this->versions = $this->get_versions_from_db();
    }

    /**
     * Retrieve versions from the DB and store in object properties
     *
     * @return stdClass[] versions
     * @throws Exception
     * @throws Exception
     */
    public function get_versions_from_db(): array {
        global $DB;

        $versionids = $DB->get_fieldset_select('tool_lala_model_versions', 'id', 'configid='.$this->id);

        $versions = [];
        foreach ($versionids as $versionid) {
            $version = new model_version($versionid);
            $versions[] = $version->get_model_version_obj();
        }
        return $versions;
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

    /** Getter for the config id.
     * @return int
     */
    public function get_id(): int {
        return $this->id;
    }

    /** Getter for the config's model id.
     * @return int
     */
    public function get_modelid(): int {
        return $this->modelid;
    }

    /** Getter for the config's name.
     * @return string
     */
    public function get_name(): string {
        return $this->name;
    }

    /** Getter for the config's target.
     * @return string
     */
    public function get_target(): string {
        return $this->target;
    }
}
