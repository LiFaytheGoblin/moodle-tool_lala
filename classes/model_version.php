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
use core_analytics\local\analysis\result_array;
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
        $this->modelid =  $DB->get_fieldset_select('tool_laaudit_model_configs', 'modelid', 'id='.$this->configid)[0]; //get_fielset_select('tool_laaudit_model_configs', 'modelid', array('id' => $this->configid));
        if (!isset($this->modelid)) {
            $this->modelid = 0; // Todo: Model was maybe deleted. Catch this case!
        }
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

        return $obj;
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
     * Add some evidence to the version. This will continue developping the version.
     *
     * @param string $evidencekey what type of evidence should be collected next.
     * @param data $data possibly pre-existing data
     * @return void
     */
    public function add($evidencekey, $data = null) {
        if (array_key_exists($evidencekey, $this->evidence)) {
            echo("Evidence already exists.");
            return;
        }

        if (isset($this->$evidencekey) && !isset($data)) { // If we already have data from previous calculations, use it.
            $data = $this->$evidencekey;
        }

        // Create evidence for the data.
        $class = 'tool_laaudit\\'.$evidencekey;

        $evidence = call_user_func_array($class.'::create_and_get_for_version', array($this->id, $data, $this->modelid));

        // Add to evidence array.
        $this->evidence[$evidencekey] = $evidence->get_id();
        $this->$evidencekey = $evidence->get_raw_data();
    }

    /**
     * Retrieve the evidence for this version from the database
     *
     * @return stdClass[] of evidence records
     */
    private function get_evidence_from_db() {
        global $DB;

        $records = $DB->get_records('tool_laaudit_evidence', array('versionid' => $this->id));

        return $records;
    }

    /**
     * Interface method for add()
     *
     * @return void
     */
    public function gather_dataset() {
        // Prepare evidence object.
        $evidence = dataset::create_and_get_for_version($this->id);
        // Add to evidence array.
        $this->evidence['dataset'] = $evidence->get_id();

        $this->dataset = $this->get_dataset(); // TODO: needs to be field? Or can this be local?

        $evidence->store($this->dataset);

        $evidence->finish();
    }

    protected function get_dataset() {
        // Create a model object from the accompanying analytics model
        $model = new \core_analytics\model($this->modelid);

        // Init analyzer.
        $this->init_analyzer($model);

        $this->heavy_duty_mode();

        $predictor = $model->get_predictions_processor(); // Todo: Necessary?

        $contexts = $model->get_contexts();

        $analysables_iterator = $this->analyser->get_analysables_iterator(null, $contexts);
        // Todo: also store fitting headings
        $result_array = new result_array($this->modelid, true, []);
        $analysis = new analysis($this->analyser, true, $result_array);
        foreach($analysables_iterator as $analysable) {
            if (!$analysable) {
                continue;
            }

            $analysableresults = $analysis->process_analysable($analysable);
            $result_array->add_analysable_results($analysableresults);
        }

        $allresults = $result_array->get();

        return $allresults;
    }

    protected function init_analyzer($model) {
        $target = $model->get_target();
        if (empty($target)) {
            throw new \moodle_exception('errornotarget', 'analytics');
        }

        // Convert indicators from string[] to \core_analytics\local\indicator\base[]
        $fullclassnames = json_decode($this->indicators);
        if (!is_array($fullclassnames)) {
            throw new \coding_exception('Version ' . $this->id . ' indicators can not be read');
        }
        $this->indicatorinstances = array();
        foreach ($fullclassnames as $fullclassname) {
            $instance = \core_analytics\manager::get_indicator($fullclassname);
            if ($instance) {
                $this->indicatorinstances[$fullclassname] = $instance;
            } else {
                debugging('Can\'t load ' . $fullclassname . ' indicator', DEBUG_DEVELOPER);
            }
        }
        if (empty($this->indicatorinstances)) {
            throw new \moodle_exception('errornoindicators', 'analytics');
        }

        // Convert analysisintervals from string[] to instances
        $instance = \core_analytics\manager::get_time_splitting($this->analysisinterval);
        $analysisintervalinstances = [$instance];

        $options = array('evaluation'=>true, 'mode'=>'configuration'); // Todo: Correct?

        $analyzerclassname = $target->get_analyser_class();
        $this->analyser = new $analyzerclassname($this->modelid, $target, $this->indicatorinstances, $analysisintervalinstances, $options);
    }

    /**
     * Increases system memory and time limits.
     *
     * @return void
     */
    private function heavy_duty_mode() {
        if (ini_get('memory_limit') != -1) {
            raise_memory_limit(MEMORY_HUGE);
        }
        \core_php_time_limit::raise();
    }

    /**
     * Interface method for add()
     *
     * @return void
     */
    public function calculate_features() {
        $this->add('features_dataset'); // Create features dataset for training and testing data - unsure split or merge.
    }

    /**
     * Interface method for add()
     *
     * @return void
     */
    public function train() {
        $this->add('training_dataset'); // Needs split info.
        $this->add('model');
    }

    /**
     * Interface method for add()
     *
     * @return void
     */
    public function predict() {
        $this->add('test_dataset'); // Needs split info, related to training dataset.
        $this->add('predictions_dataset');
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
}
