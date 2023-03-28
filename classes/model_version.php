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
 * The model version class, built on top of the analytics/model class.
 *
 * @package     tool_laaudit
 * @copyright   2023 Linda Fernsel <fernsel@htw-berlin.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_laaudit;

use core_analytics\model;
use core_date;
use DateTime;
use stdClass;

/**
 * Class for the model configuration.
 */
class model_version {
    /** @var int $id assigned to the version by the db. */
    private $id;
    /** @var string $name assigned to the version. */
    private $name;
    /** @var DateTime $timecreationstarted when the version was first started to be created */
    private $timecreationstarted;
    /** @var DateTime $timecreationfinished when the version was finished, including all evidence collection steps */
    private $timecreationfinished;
    /** @var int $configid of the model config this version belongs to */
    private $configid;
    /** @var string $analysisinterval used for the model version */
    private $analysisinterval;
    /** @var string $predictionsprocessor used by the model version */
    private $predictionsprocessor;
    /** @var string $contextids used as data by the model version */
    private $contextids;
    /** @var string $indicators used by the model version */
    private $indicators;

    /**
     * Constructor.
     *
     * @param int $configid
     * @return void
     */
    public function __construct($configid) {
        global $DB;

        // Create in DB.

        $config = $DB->get_record('tool_laaudit_model_configs', ['id' => $configid]);
        $model = $DB->get_record('analytics_models', ['id' => $config->modelid]);
        $this->id = self::insert_new_from_model_into_db($model, $configid);

        // Fill properties from DB.

        $version = $DB->get_record('tool_laaudit_model_versions', array('id' => $this->id), '*', MUST_EXIST);

        $this->configid = $configid;
        $this->name = $version->name;
        $this->timecreationstarted = $version->timecreationstarted;
        $this->timecreationfinished = $version->timecreationfinished;
        $this->analysisinterval = $version->analysisinterval;
        $this->predictionsprocessor = $version->predictionsprocessor;
        $this->contextids = $version->contextids;
        $this->indicators = $version->indicators;
    }

    private static function insert_new_from_model_into_db($model, $configid) {
        global $DB;

        $obj = new stdClass();

        $obj->configid = $configid;

        // Set defaults.
        $obj->name = "default";
        $obj->timecreationstarted = time();
        $obj->timecreationfinished = 0;

        // Copy values from model
        $obj->analysisinterval = isset($model->timesplitting)? $model->timesplitting : "None";
        $obj->predictionsprocessor = isset($model->predictionsprocessor) ? $model->predictionsprocessor : "None";
        $obj->contextids = isset($model->contextids) ? $model->contextids : "[]";
        $obj->indicators = isset($model->indicators) ? $model->indicators : "[]";

        return $DB->insert_record('tool_laaudit_model_versions', $obj);
    }

    /**
     * Returns a plain stdClass with the model version data (...).
     *
     * @return stdClass
     */
    public function get_model_version_obj() {
        $obj = new stdClass();

        // Add info about the model version.
        $obj->id = $this->id;
        $obj->name = $this->name;
        $obj->timecreationstarted = $this->timecreationstarted;
        $obj->timecreationfinished = $this->timecreationfinished;
        $obj->analysisinterval = $this->analysisinterval;
        $obj->predictionsprocessor = $this->predictionsprocessor;
        $obj->contextids = $this->contextids;
        $obj->indicators = $this->indicators;

        return $obj;
    }
}
