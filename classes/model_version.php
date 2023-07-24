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

use Exception;
use LogicException;
use moodle_exception;
use stdClass;
use core_analytics\manager;
use core_analytics\context;
use core_analytics\predictor;
use core_analytics\local\analyser\base;
use Phpml\Classification\Linear\LogisticRegression;
use tool_laaudit\event\model_version_created;

require_once(__DIR__ . '/dataset_anonymized.php');
/**
 * Class for the model configuration.
 */
class model_version {
    /** @var float $DEFAULT_RELATIVE_TEST_SET_SIZE out of all available test data */
    const DEFAULT_RELATIVE_TEST_SET_SIZE = 0.2;
    /** @var int $id assigned to the version by the db. */
    private int $id;
    /** @var string $name assigned to the version. */
    private string $name;
    /** @var int $timecreationstarted when the version was first started to be created */
    private int $timecreationstarted;
    /** @var int|null $timecreationfinished when the version was finished, including all evidence collection steps */
    private ?int $timecreationfinished;
    /** @var int $configid of the model config this version belongs to */
    private int $configid;
    /** @var int $modelid of the model this version belongs to */
    private int $modelid;
    /** @var string $target for this version */
    private string $target;
    /** @var string $indicators used by the model version */
    private string $indicators;
    /** @var string $analysisinterval used for the model version */
    private string $analysisinterval;
    /** @var string $predictionsprocessor used by the model version */
    private string $predictionsprocessor;
    /** @var string|null $contextids used as data by the model version */
    private ?string $contextids;
    /** @var float $relativetestsetsize relative amount of available data to be used for testing */
    private float $relativetestsetsize;
    /** @var array[] $evidence used by the model version */
    private array $evidence;
    /** @var stdClass[] $evidenceobjects used by the model version */
    private array $evidenceobjects;
    /** @var string|null $error that occurred first when creating this model version and gathering evidence */
    private ?string $error;
    /** @var base $analyser for this version */
    private base $analyser;
    /** @var context[] $contexts for this version */
    private array $contexts;
    /** @var predictor $predictor for this version */
    private predictor $predictor;
    /** @var idmap[] $idmaps used for anonymization */
    private array $idmaps;

    /**
     * Constructor. Deserialize DB object.
     *
     * @param int $id of the version
     * @return void
     */
    public function __construct(int $id) {
        global $DB;

        $version = $DB->get_record('tool_laaudit_model_versions', ['id' => $id], '*', MUST_EXIST);

        // Fill properties from DB.
        $this->id = $version->id;
        $this->configid = $version->configid;
        $this->name = $version->name;
        $this->timecreationstarted = $version->timecreationstarted;
        $this->timecreationfinished = $version->timecreationfinished;

        $this->relativetestsetsize = $version->relativetestsetsize;
        $this->contextids = $version->contextids;
        $this->error = $version->error;
        $this->evidenceobjects = $DB->get_records('tool_laaudit_evidence', ['versionid' => $this->id]);

        $config = $DB->get_record('tool_laaudit_model_configs', ['id' => $this->configid], '*', MUST_EXIST);
        $this->modelid = $config->modelid;
        $this->target = $config->target;
        $this->predictionsprocessor = $config->predictionsprocessor;
        $this->analysisinterval = $config->analysisinterval;
        $this->indicators = $config->indicators;

        $this->load_objects();
    }

    /**
     * Loads php objects instead of just ids or names for some of the model properties,
     * so they can be reused later.
     * Objects that are being loaded for later use:
     * $contexts, $predictor, $analyser
     * Other loaded objects ($targetinstance, $indicatorinstances, $analysisintervalinstances) are only needed temporarily.
     *
     * @return void
     */
    private function load_objects(): void {
        $this->predictor = manager::get_predictions_processor($this->predictionsprocessor, true);

        $this->contexts = [];
        if (isset($this->contextids)) {
            foreach ($this->contextids as $contextid) {
                $this->contexts[] = context::instance_by_id($contextid, IGNORE_MISSING);
            }
        }

        // Convert analysisintervals from string[] to instances.
        $analysisintervalinstanceinstance = manager::get_time_splitting($this->analysisinterval);
        $analysisintervalinstances = [$analysisintervalinstanceinstance];

        // Convert indicators from string[] to instances.
        $fullclassnames = json_decode($this->indicators);
        $indicatorinstances = array();
        foreach ($fullclassnames as $fullclassname) {
            $instance = manager::get_indicator($fullclassname);
            if ($instance) {
                $indicatorinstances[$fullclassname] = $instance;
            } else {
                debugging('Can\'t load ' . $fullclassname . ' indicator', DEBUG_DEVELOPER);
            }
        }
        if (empty($indicatorinstances)) {
            throw new Exception('No indicator instances could be created from indicator string '.$this->indicators);
        }

        // Create an analyser.
        $options = ['evaluation' => true, 'mode' => 'configuration'];
        $targetinstance = manager::get_target($this->target);
        if (!$targetinstance) {
            throw new Exception('Target could not be retrieved from target name '.$this->target);
        }
        $analyzerclassname = $targetinstance->get_analyser_class();
        $this->analyser = new $analyzerclassname($this->modelid, $targetinstance, $indicatorinstances,
                $analysisintervalinstances, $options);
    }

    /**
     * Returns a stdClass with the model version data.
     *
     * @param int $configid
     * @return int id of the created config
     */
    public static function create_scaffold_and_get_for_config(int $configid): int {
        global $DB;

        $obj = new stdClass();

        $obj->configid = $configid;

        // Set defaults.
        $obj->timecreationstarted = time();
        $obj->relativetestsetsize = self::DEFAULT_RELATIVE_TEST_SET_SIZE;

        // Copy values from model.
        $modelconfig = $DB->get_record('tool_laaudit_model_configs', ['id' => $configid], 'defaultcontextids', MUST_EXIST);
        $obj->contextids = $modelconfig->defaultcontextids;

        return $DB->insert_record('tool_laaudit_model_versions', $obj);
    }

    /**
     * Returns a plain stdClass with the model version data.
     *
     * @return stdClass
     */
    public function get_model_version_obj(): stdClass {
        $obj = new stdClass();

        // Add info about the model version.
        $obj->id = $this->id;
        $obj->name = $this->name;
        $obj->timecreationstarted = $this->timecreationstarted;
        $obj->timecreationfinished = $this->timecreationfinished;
        $obj->relativetestsetsize = $this->relativetestsetsize;
        $obj->contextids = $this->contextids;
        $obj->evidenceobjects = $this->evidenceobjects;
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
     * @return evidence
     */
    private function add(string $evidencetype, array $options): evidence {
        $class = '\tool_laaudit\\'.$evidencetype;

        try {
            $evidence = $class::create_scaffold_and_get_for_version($this->id);

            try {
                $evidence->collect($options);
            } catch (Exception $e) {
                $evidence->abort();
                throw $e;
            }

            // Add id and raw data to cached field variables.
            if (!isset($this->evidence[$evidencetype])) {
                $this->evidence[$evidencetype] = [];
            }
            $evidenceid = $evidence->get_id();

            $this->evidence[$evidencetype][$evidenceid] = $evidence->get_raw_data();

            return $evidence;
        } catch (moodle_exception | Exception $e) {
            print("ERROR". $e->getMessage());
            $this->register_error($e); // Todo: Do this for any exceptions in the model version - find better place than here.
            throw $e;
        }
    }

    /**
     * Call next step: Gather the data that can be used for training and testing this model version.
     *
     * @return void
     */
    public function gather_dataset(bool $anonymous = true): void {
        $options = ['modelid' => $this->modelid, 'analyser' => $this->analyser, 'contexts' => $this->contexts];

        $evidencetype = $anonymous ? 'dataset_anonymized' : 'dataset';

        $evidence = $this->add($evidencetype, $options);

        if ($anonymous) {
            $this->idmaps = [];
            $origintablename = $this->analyser->get_samples_origin();
            $this->idmaps[$origintablename] = $evidence->get_idmap();
        }

        $evidence->store();
    }

    /**
     * Call next step: Gather the data that can be used for training and testing this model version.
     *
     * @param bool $anonymous whether to anonymize the related data
     * @return void
     */
    public function gather_related_data(bool $anonymous = true): void {
        $source = $anonymous ? 'dataset_anonymized' : 'dataset';
        if (!isset($this->evidence[$source])) {
            throw new LogicException('No data available for which to get related data. Have you gathered data?');
        }

        $origintablename = $this->analyser->get_samples_origin(); // The main tables to which related data should be gathered.

        if ($anonymous) {
            $this->gather_related_data_anonymized($origintablename);
        } else {
            $this->gather_related_data_unanonymized($origintablename);
        }
    }

    public function gather_related_data_unanonymized($origintablename) : void {
        $data = $this->get_single_evidence('dataset');
        $originids = dataset_helper::get_ids_used_in_dataset($data);

        $relatedtables = database_helper::get_related_tables($origintablename, $originids, [$origintablename => $originids]);

        $options = [];
        foreach ($relatedtables as $relatedtablename => $relevantids) {
            $options['tablename'] = $relatedtablename;
            $options['ids'] = $relevantids;
            $evidence = $this->add('related_data', $options);
            $evidence->store();
        }
    }

    public function gather_related_data_anonymized($origintablename) : void {
        if (!isset($this->idmaps)) {
            throw new LogicException('No idmaps available.');
        }
        if (!isset($this->idmaps[$origintablename])) {
            throw new LogicException('No idmap available for origin table '.$origintablename);
        }

        $originids = $this->idmaps[$origintablename]->get_originalids();
        $relatedtables = database_helper::get_related_tables($origintablename, $originids, [$origintablename => $originids]);
        $options = [];
        $evidences = [];

        foreach ($relatedtables as $relatedtablename => $relevantids) {
            // Add un-anonymized evidence first.
            $options['tablename'] = $relatedtablename;
            $options['ids'] = $relevantids;

            $evidence = $this->add('related_data_anonymized', $options);

            // Create idmaps.
            $notanonymizeddata = $evidence->get_raw_data();
            $this->idmaps[$relatedtablename] = idmap::create_from_related_data($notanonymizeddata, $relatedtablename);

            $evidences[] = $evidence; // Store the evidence for anonymizing it later.
        }

        // Anonymize the related data. We need ALL idmaps for that.
        foreach ($evidences as $evidence) {
            $pseudonomizeddata = $evidence->pseudonomize($evidence->get_raw_data(), $this->idmaps, $evidence->get_tablename());
            $this->evidence['related_data_anonymized'][$evidence->get_id()] = $pseudonomizeddata;
            $evidence->store(); // Only now can we store the data.
        }
    }

    /**
     * Call next step: Split the data into training and testing data.
     *
     * @return void
     */
    public function split_training_test_data(bool $anonymous = true): void {
        $sourcedataset = $anonymous ? 'dataset_anonymized' : 'dataset';

        if (!isset($this->evidence[$sourcedataset])) {
            throw new LogicException('No data available to split into training and testing data. Have you gathered data?');
        }
        $data = $this->get_single_evidence($sourcedataset);

        $datashuffled = dataset_helper::get_shuffled($data);

        $options = ['data' => $datashuffled, 'testsize' => $this->relativetestsetsize];

        $trainingevidence = $this->add('training_dataset', $options);
        $trainingevidence->store();

        $testevidence = $this->add('test_dataset', $options);
        $testevidence->store();
    }

    /**
     * Call next step: Train a model.
     *
     * @return void
     */
    public function train(): void {
        if (!isset($this->evidence['training_dataset'])) {
            throw new LogicException('No training data is available for training. Have you gathered data and split it into
            training and testing data?');
        }
        $options = ['data' => $this->get_single_evidence('training_dataset'), 'predictor' => $this->predictor];

        $evidence = $this->add('model', $options);
        $evidence->store();
    }

    /**
     * Call next step: Request predictions from a trained model.
     *
     * @return void
     */
    public function predict(): void {
        if (!isset($this->evidence['test_dataset'])) {
            throw new LogicException('No test data is available for getting predictions. Have you gathered data and split
            it into training and testing data?');
        }
        if (!isset($this->evidence['model'])) {
            throw new LogicException('No model is available for getting predictions. Have
            you trained a model?');
        }

        $options = ['model' => $this->get_single_evidence('model'), 'data' => $this->get_single_evidence('test_dataset')];

        $evidence = $this->add('predictions_dataset', $options);
        $evidence->store();
    }

    /**
     * Mark the model version as finished.
     *
     * @return void
     */
    public function finish(): void {
        global $DB;

        $this->timecreationfinished = time();
        $DB->set_field('tool_laaudit_model_versions', 'timecreationfinished', $this->timecreationfinished,
                ['id' => $this->id]);

        // Register an event.
        $props = ['objectid' => $this->id, 'other' => ['configid' => $this->configid, 'modelid' => $this->modelid]];
        $event = model_version_created::create($props);
        $event->trigger();
    }

    /**
     * Register a thrown error in the error column of the model version table.
     *
     * @param moodle_exception|Exception $e the thrown exception
     * @return void
     */
    private function register_error(moodle_exception|Exception $e): void {
        global $DB;

        $DB->set_field('tool_laaudit_model_versions', 'error', $e->getMessage(), ['id' => $this->id]);
    }

    /**
     * Getter for version id.
     *
     * @return int id
     */
    public function get_id(): int {
        return $this->id;
    }

    /**
     * Getter for idmaps.
     *
     * @return idmap[] idmaps
     */
    public function get_idmaps(): array {
        return $this->idmaps;
    }

    /**
     * Get the first evidence item of a type.
     *
     * @param string $evidencetype a valid name of an evidence inheriting class
     * @return array|Phpml\Classification\Linear\LogisticRegression|null the evidence raw data
     */
    public function get_single_evidence(string $evidencetype): mixed {
        if (!isset($this->evidence[$evidencetype])) {
            return null;
        }
        return array_values($this->evidence[$evidencetype])[0];
    }

    /**
     * Get all evidence items of a type.
     *
     * @param string $evidencetype a valid name of an evidence inheriting class
     * @return array[]|Phpml\Classification\Linear\LogisticRegression[]|null the evidence raw data
     */
    public function get_array_of_evidences(string $evidencetype): mixed {
        return $this->evidence[$evidencetype];
    }
}
