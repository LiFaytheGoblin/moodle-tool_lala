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
    /** @var \core_analytics\indicator[] $indicatorinstances for this version */
    private $indicatorinstances;
    /** @var \core_analytics\analysis_interval[] $analysisintervalinstances for this version */
    private $analysisintervalinstances;
    /** @var evidence[] $evidence used by the model version */
    private $evidence;
    /** @var string $error that occurred first when creating this model version and gathering evidence */
    private $error;
    /** @var array $dataset */
    private $dataset;
    /** @var array $training_dataset */
    private $training_dataset;
    /** @var array $test_dataset */
    private $test_dataset;
    /** @var \core_analytics\model $modelconfig / moodle model this version belongs to */
    private $modelconfig;
    /** @var \Phpml\Classification\Linear\LogisticRegression $model trained for this version */
    private $model;
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

    /**
     * Loads php objects instead of just ids or names for some of the model properties,
     * so they can be reused later.
     * Objects that are being loaded:
     * $modelconfig, $target, $contexts, $predictor, $indicatorinstances, $analysisintervalinstances, $analyser
     *
     * @return void
     */
    private function load_objects() {
        $this->modelconfig = new model($this->modelid);
        $this->target = $this->modelconfig->get_target();
        $this->contexts = $this->modelconfig->get_contexts();
        $this->predictor = $this->modelconfig->get_predictions_processor();

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
        $moodlemodel = $DB->get_record('analytics_models', array('id' => $modelid),
                'timesplitting, predictionsprocessor, contextids, indicators', MUST_EXIST);
        if (self::valid_exists($moodlemodel->timesplitting)) {
            $obj->analysisinterval = $moodlemodel->timesplitting;
        } else {
            $analysisintervals = manager::get_time_splitting_methods_for_evaluation();
            $firstanalysisinterval = array_keys($analysisintervals)[0];
            $obj->analysisinterval = $firstanalysisinterval;
        }
        if (self::valid_exists($moodlemodel->predictionsprocessor)) {
            $obj->predictionsprocessor = $moodlemodel->predictionsprocessor;
        } else {
            $default = manager::default_mlbackend();
            $obj->predictionsprocessor = $default;
        }
        if (self::valid_exists($moodlemodel->contextids)) {
            $obj->contextids = $moodlemodel->contextids;
        }
        $obj->indicators = $moodlemodel->indicators;

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
     * Returns a plain stdClass with the model version data.
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
     * Add a next step and therefore next evidence in the model version creation process.
     * Steps ($evidencetype) can be:
     * 'dataset', 'test_dataset', 'training_dataset', 'model', 'predictions_dataset'.
     * Stores the retrieved evidence in the appropriate field of this model version.
     *
     * @param string $evidencetype created in the next step.
     * @param array $options those options that are relevant in the called classes, see for instance dataset->collect().
     * @return void
     */
    private function add($evidencetype, $options) {
        $class = 'tool_laaudit\\'.$evidencetype;

        $evidence = call_user_func_array($class.'::create_scaffold_and_get_for_version', array($this->id));  // Prepare evidence object.
        $this->evidence[$evidencetype] = $evidence->get_id(); // Add to evidence array.

        try {
            $evidence->collect($options);
        } catch (\moodle_exception|\Exception $e) {
            $evidence->abort();
            $this->register_error($e);
            throw $e;
        }

        $evidence->serialize();
        $evidence->store();
        $evidence->finish();

        $this->$evidencetype = $evidence->get_raw_data();
    }

    /**
     * Call next step: Gather the data that can be used for training and testing this model version.
     *
     * @return void
     */
    public function gather_dataset() {
        $options = array('modelid'=>$this->modelid, 'analyser'=>$this->analyser, 'contexts'=>$this->contexts);

        $this->add('dataset', $options);
    }

    /**
     * Call next step: Split the data into training and testing data.
     *
     * @param number $testsize the percentage of datapoints to be used as test data.
     * @return void
     */
    public function split_training_test_data($testsize = 0.2) {
        $data = dataset::get_shuffled($this->dataset);
        $options = array('data'=>$data, 'testsize'=>$testsize);

        $this->add('training_dataset', $options);
        $this->add('test_dataset', $options);
    }

    /**
     * Call next step: Train a model.
     *
     * @return void
     */
    public function train() {
        $options = array('data'=>$this->training_dataset, 'predictor'=>$this->predictor);

        $this->add('model', $options);
    }

    /**
     * Call next step: Request predictions from a trained model.
     *
     * @return void
     */
    public function predict() {
        $options = array('model'=>$this->model, 'data'=>$this->test_dataset);

        $this->add('predictions_dataset', $options);
    }

    /**
     * Mark the model version as finished.
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
     * @param \Exception $e the thrown exception
     * @return void
     */
    private function register_error(\moodle_exception|\Exception $e) {
        global $DB;

        $DB->set_field('tool_laaudit_model_versions', 'error', $e->getMessage(), array('id' => $this->id));
    }
}
