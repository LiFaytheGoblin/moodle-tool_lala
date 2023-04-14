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

use core_analytics\manager;
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
    /** @var evidence[] $evidence used by the model version */
    private $evidence;

    /**
     * Constructor. Deserialize DB object.
     *
     * @param int $id of the version
     * @return void
     */
    public function __construct($id) {
        global $DB;

        $version = $DB->get_record('tool_laaudit_model_versions', array('id' => $id), '*', MUST_EXIST);

        // Fill properties from DB.
        $this->id = $version->id;
        $this->configid = $version->configid;
        $this->name = $version->name;
        $this->timecreationstarted = $version->timecreationstarted;
        $this->timecreationfinished = $version->timecreationfinished;
        $this->analysisinterval = $version->analysisinterval;
        $this->predictionsprocessor = $version->predictionsprocessor;
        $this->contextids = $version->contextids;
        $this->indicators = $version->indicators;
        $this->evidence = $this->get_evidence_from_db();
    }

    /**
     * Returns a stdClass with the model version data.
     *
     * @return stdClass
     */
    public static function create_scaffold_and_get_for_config($configid) {
        global $DB;

        $obj = new stdClass();

        $obj->configid = $configid;

        // Set defaults.
        $obj->name = "default";
        $obj->timecreationstarted = time();
        $obj->timecreationfinished = 0;

        // Copy values from model
        $modelconfig = $DB->get_record('tool_laaudit_model_configs', array('id' => $configid), 'modelid', MUST_EXIST);
        $modelid = $modelconfig->modelid;
        $model = $DB->get_record('analytics_models', array('id' => $modelid), 'timesplitting, predictionsprocessor, contextids, indicators', MUST_EXIST);
        if (self::valid_exists($model->timesplitting)) {
            $obj->analysisinterval = $model->timesplitting;
        } else {
            $analysis_intervals = manager::get_time_splitting_methods_for_evaluation();
            $first_analysis_interval = array_keys($analysis_intervals)[0];
            $obj->analysisinterval = $first_analysis_interval;
        }
        if (self::valid_exists($model->predictionsprocessor)) {
            $obj->predictionsprocessor = $model->predictionsprocessor;
        } else {
            $default = manager::default_mlbackend();
            $obj->predictionsprocessor = $default;
        }
        if (self::valid_exists($model->contextids)) {
            $obj->contextids =  $model->contextids;
        }
        $obj->indicators =  $model->indicators;

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
        $obj->evidence = $this->evidence; // Todo: Return more than the ids

        return $obj;
    }

    private static function valid_exists($value) {
        return isset($value) && $value != "" && $value != "[]";
    }

    public function add($evidencekey, $data = null) {
        // $version->add('testing_dataset');
        // $version->add(new dataset($data));

        // check whether this is already in the evidence
        if (array_key_exists($evidencekey, $this->evidence)) {
            echo("Evidence already exists.");
            return;
        }

        if (isset($this->$evidencekey) && !isset($data)) { //if we already have data from previous calculations, use it
            $data = $this->$evidencekey;
        }

        // create evidence for the data
        $class = 'tool_laaudit\\'.$evidencekey;

        $evidence = call_user_func_array($class.'::create_and_get_for_version', array($this->id, $data));

        // add to evidence array
        $this->evidence[$evidencekey] = $evidence->get_id();
        $this->$evidencekey = $evidence->get_raw_data();
    }

    private function get_evidence_from_db() {
        global $DB;

        $records = $DB->get_records('tool_laaudit_evidence', array('versionid' => $this->id));
        return $records;
    }

    public function set_data() {
        $this->add('dataset'); // could also pass the class directly
    }

    public function calculate_features() {
        $this->add('features_dataset'); // create features dataset for training and testing data - unsure split or merge
    }

    public function train() {
        $this->add('training_dataset'); // needs split info
        $this->add('model'); // weights
    }

    public function predict() {
        $this->add('test_dataset'); // needs split info, related to training dataset
        $this->add('predictions_dataset');
    }
}
