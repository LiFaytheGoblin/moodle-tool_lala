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
use core_analytics\analysis;
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
    /** @var int $modelid of the model this version belongs to */
    private $modelid;
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
    /** @var string $error that occurred first when creating this model version and gathering evidence */
    private $error;
    /** @var array dataset */
    private $dataset;
    /** @var array training dataset */
    private $trainingdataset;
    /** @var array test dataset */
    private $testdataset;
    /** @var \core_analytics\model $model this version belongs to */
    private $model;
    /** @var \Phpml\Classification\Linear\LogisticRegression $trainedmodel for this version */
    private $trainedmodel;
    /** @var stdClass $analyser for this version */
    private $analyser;
    /** @var stdClass $target for this version */
    private $target;
    /** @var context[] $contexts for this version */
    private $contexts;
    /** @var \core_analytics\predictor $predictor for this version */
    private $predictor;

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
        $this->error = $version->error;
        $this->evidence = $DB->get_records('tool_laaudit_evidence', array('versionid' => $this->id));
        $this->modelid =  $DB->get_fieldset_select('tool_laaudit_model_configs', 'modelid', 'id='.$this->configid)[0]; //get_fielset_select('tool_laaudit_model_configs', 'modelid', array('id' => $this->configid));
        if (!isset($this->modelid)) {
            $this->modelid = 0; // Todo: Model was maybe deleted. Catch this case!
        }
        $this->load_objects();

    }

    private function load_objects() {
        $this->model = new model($this->modelid);
        $this->target = $this->model->get_target();
        $this->contexts = $this->model->get_contexts();
        $this->predictor = $this->model->get_predictions_processor();

        // Convert indicators from string[] to instances
        $fullclassnames = json_decode($this->indicators);
        $indicatorinstances = array();
        foreach ($fullclassnames as $fullclassname) {
            $instance = \core_analytics\manager::get_indicator($fullclassname);
            if ($instance) {
                $indicatorinstances[$fullclassname] = $instance;
            } else {
                debugging('Can\'t load ' . $fullclassname . ' indicator', DEBUG_DEVELOPER);
            }
        }
        if (empty($indicatorinstances)) {
            throw new \moodle_exception('errornoindicators', 'analytics');
        }
        $this->indicatorinstances = $indicatorinstances;

        // Convert analysisintervals from string[] to instances
        $analysisintervalinstanceinstance = \core_analytics\manager::get_time_splitting($this->analysisinterval);
        $this->analysisintervalinstances = [$analysisintervalinstanceinstance];

        // Create an analyzer
        $options = array('evaluation'=>true, 'mode'=>'configuration');
        $analyzerclassname = $this->target->get_analyser_class();
        $this->analyser =  new $analyzerclassname($this->modelid, $this->target, $this->indicatorinstances, $this->analysisintervalinstances, $options);
    }

    /**
     * Returns a stdClass with the model version data.
     *
     * @param int $configid
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

        // Copy values from model.
        $modelconfig = $DB->get_record('tool_laaudit_model_configs', array('id' => $configid), 'modelid', MUST_EXIST);
        $modelid = $modelconfig->modelid;
        $model = $DB->get_record('analytics_models', array('id' => $modelid),
                'timesplitting, predictionsprocessor, contextids, indicators', MUST_EXIST);
        if (self::valid_exists($model->timesplitting)) {
            $obj->analysisinterval = $model->timesplitting;
        } else {
            $analysisintervals = manager::get_time_splitting_methods_for_evaluation();
            $firstanalysisinterval = array_keys($analysisintervals)[0];
            $obj->analysisinterval = $firstanalysisinterval;
        }
        if (self::valid_exists($model->predictionsprocessor)) {
            $obj->predictionsprocessor = $model->predictionsprocessor;
        } else {
            $default = manager::default_mlbackend();
            $obj->predictionsprocessor = $default;
        }
        if (self::valid_exists($model->contextids)) {
            $obj->contextids = $model->contextids;
        }
        $obj->indicators = $model->indicators;

        return $DB->insert_record('tool_laaudit_model_versions', $obj);
    }

    /**
     * Helper method: Short check to verify whether the provided value is valid, and thus a valid list exists.
     *
     * @param string $value to check
     * @return boolean
     */
    private static function valid_exists($value) {
        return isset($value) && $value != "" && $value != "[]";
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
        $obj->evidence = $this->evidence;
        $obj->error = $this->error;

        return $obj;
    }

    /**
     * Gather the data that can be used for training and testing this model version and store as evidence.
     *
     * @return void
     */
    public function gather_dataset() {
        $evidence = dataset::create_scaffold_and_get_for_version($this->id); // Prepare evidence object.

        $this->evidence['dataset'] = $evidence->get_id(); // Add to evidence array.

        $options = array('modelid'=>$this->modelid, 'analyser'=>$this->analyser, 'contexts'=>$this->contexts);
        try {
            $evidence->collect($options);
        } catch (\moodle_exception $e) {
            $evidence->abort();
            $this->register_error($e);
            throw $e;
        }

        $this->dataset = $evidence->get_raw_data();

        $evidence->serialize();

        $evidence->store();

        $evidence->finish();
    }

    public function split_training_test_data($testsize = 0.2) {
        $data = $this->shuffle_dataset($this->dataset);

        $options = array('data'=>$data, 'testsize'=>$testsize);

        // Gather training set
        $evidence_training = training_dataset::create_scaffold_and_get_for_version($this->id);
        $this->evidence['training_dataset'] = $evidence_training->get_id();

        try {
            $evidence_training->collect($options);
        } catch (\moodle_exception $e) {
            $evidence_training->abort();
            $this->register_error($e);
            throw $e;
        }

        $this->trainingdataset = $evidence_training->get_raw_data();
        $evidence_training->serialize();
        $evidence_training->store();
        $evidence_training->finish();

        // Gather test set
        $evidence_test = test_dataset::create_scaffold_and_get_for_version($this->id);
        $this->evidence['test_dataset'] = $evidence_test->get_id();

        try {
            $evidence_test->collect($options);
        } catch (\moodle_exception $e) {
            $evidence_test->abort();
            $this->register_error($e);
            throw $e;
        }

        $this->testdataset = $evidence_test->get_raw_data();
        $evidence_test->serialize();
        $evidence_test->store();
        $evidence_test->finish();
    }

    /**
     * Interface method for add()
     *
     * @return void
     */
    public function train() {
        $evidence = \tool_laaudit\model::create_scaffold_and_get_for_version($this->id);
        $this->evidence['model'] = $evidence->get_id();

        $options = array('data'=>$this->trainingdataset, 'predictor'=>$this->predictor);
        try {
            $evidence->collect($options);
        } catch (\moodle_exception $e) {
            $evidence->abort();
            $this->register_error($e);
            throw $e;
        }

        $this->trainedmodel = $evidence->get_raw_data();
        $evidence->serialize();
        $evidence->store();
        $evidence->finish();
    }

    /**
     * Interface method for add()
     *
     * @return void
     */
    public function predict() {
        $evidence = predictions_dataset::create_scaffold_and_get_for_version($this->id);
        $this->evidence['predictions_dataset'] = $evidence->get_id();

        $options = array('model'=>$this->trainedmodel);
        try {
            $evidence->collect($options);
        } catch (\moodle_exception $e) {
            $evidence->abort();
            $this->register_error($e);
            throw $e;
        }

        $this->trainedmodel = $evidence->get_raw_data();
        $evidence->serialize();
        $evidence->store();
        $evidence->finish();
    }

    /**
     * Mark the version as finished.
     *
     * @return void
     */
    public function finish() {
        global $DB;

        $this->timecreationfinished = time();
        $DB->set_field('tool_laaudit_model_versions', 'timecreationfinished', $this->timecreationfinished,
                array('id' => $this->id));
    }

    /**
     * Register a thrown error in the error column of the model version table.
     *
     * @param \Exception the thrown exception
     * @return void
     */
    private function register_error(\moodle_exception|\Exception $e) {
        global $DB;

        $DB->set_field('tool_laaudit_model_versions', 'error', $e->getMessage(), array('id' => $this->id));
    }

    /**
     * Shuffle a data set while preserving the key and the header.
     *
     * @param array $dataset
     * @return array
     */
    private function shuffle_dataset($dataset) {
        $key = array_keys((array) $dataset)[0];
        $datawithheader = [];
        foreach($dataset as $arr) { // each analysisinterval has an object
            $header = array_slice($arr, 0, 1, true);
            $remainingdata = array_slice($arr, 1, null, true);

            $sampleids = array_keys($remainingdata);
            shuffle($sampleids);
            $shuffled_data = [];
            foreach($sampleids as $id) {
                $shuffled_data[$id] = $remainingdata[$id]; // assign to each key in the random order the value from the original array
            }

            $datawithheader[$key] = $header + $shuffled_data;
            break;
        }

        return $datawithheader;
    }
}
