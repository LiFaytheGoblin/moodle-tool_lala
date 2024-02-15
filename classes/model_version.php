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
 * @package     tool_lala
 * @copyright   2023 Linda Fernsel <fernsel@htw-berlin.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


namespace tool_lala;

use Exception;
use InvalidArgumentException;
use LogicException;
use moodle_exception;
use stdClass;
use core_analytics\manager;
use context;
use context_user;
use core_analytics\predictor;
use core_analytics\local\analyser\base;
use Phpml\Classification\Linear\LogisticRegression;
use stored_file;
use tool_lala\event\model_version_created;

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
     * @throws Exception
     */
    public function __construct(int $id) {
        global $DB;

        $version = $DB->get_record('tool_lala_model_versions', ['id' => $id], '*', MUST_EXIST);

        // Fill properties from DB.
        $this->id = $version->id;
        $this->configid = $version->configid;
        $this->name = $version->name;
        $this->timecreationstarted = $version->timecreationstarted;
        $this->timecreationfinished = $version->timecreationfinished;

        $this->relativetestsetsize = $version->relativetestsetsize;
        $this->contextids = $version->contextids;
        $this->error = $version->error;
        $this->evidenceobjects = $DB->get_records('tool_lala_evidence', ['versionid' => $this->id]);

        $config = $DB->get_record('tool_lala_model_configs', ['id' => $this->configid], '*', MUST_EXIST);
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
     * $predictor, $contexts, $analyser
     * Other loaded objects ($targetinstance, $indicatorinstances, $analysisintervalinstances) are only needed temporarily.
     *
     * @throws Exception
     */
    private function load_objects(): void {
        $this->predictor = manager::get_predictions_processor($this->predictionsprocessor, true);

        $this->load_context_objects();

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

        // Convert analysisintervals from string[] to instances.
        $analysisintervalinstanceinstance = manager::get_time_splitting($this->analysisinterval);
        $analysisintervalinstances = [$analysisintervalinstanceinstance];

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
     * Update the contextids to be used, if no data has been collected yet.
     *
     * @param int[] $contextids
     */
    public function set_contextids(array $contextids): void {
        if (isset($this->evidence['dataset']) || isset($this->evidence['dataset_anonymized'])) {
            throw new LogicException('Dataset has already been gathered. Contexts can only be set before gathering the dataset.');
        }

        $analyserclass = get_class($this->analyser);
        $potentialcontexts = $analyserclass::potential_context_restrictions();
        if (!$potentialcontexts) {
            throw new LogicException(get_string('errornocontextrestrictions', 'analytics'));
        } else {
            $selectedcontexts = array_flip($contextids);
            $invalidcontexts = array_diff_key($selectedcontexts, $potentialcontexts);
            if (!empty($invalidcontexts)) {
                throw new InvalidArgumentException(get_string('errorinvalidcontexts', 'analytics'));
            }
        }

        global $DB;
        $this->contextids = json_encode($contextids);

        $DB->set_field('tool_lala_model_versions', 'contextids', $this->contextids,
                ['id' => $this->id]);
        $this->load_context_objects();
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
        $modelconfig = $DB->get_record('tool_lala_model_configs', ['id' => $configid], 'defaultcontextids', MUST_EXIST);
        $obj->contextids = $modelconfig->defaultcontextids;

        return $DB->insert_record('tool_lala_model_versions', $obj);
    }

    /**
     * Execute the model version creation process with parameters. A new model
     * version object is created, and the cache from a prior model version is lost.
     *
     * @param int $versionid
     * @param array|null $contexts
     * @param string|null $dataset
     * @param boolean|null $anonymous
     * @return model_version
     * @throws Exception
     */
    public static function create(int $versionid, ?array $contexts = null, ?string $dataset = null,
            ?bool $anonymous = true): model_version {
        $version = new model_version($versionid);

        try {
            // If contexts are provided, set those to limit the data gathering scope.
            // This can be done before calling create asynchronously to make sure the UI
            // already displays the correct contexts.
            if (!empty($contexts)) {
                $version->set_contextids($contexts);
            }

            if (!empty($dataset)) { // If a dataset is provided, use that one.
                $version->set_dataset($dataset);
            } else { // Otherwise, gather data.
                $version->gather_dataset($anonymous);
            }

            $version->split_training_test_data();

            $version->train();

            $version->predict();

            if (empty($dataset)) { // Only if gathering data on site can we find related data.
                $version->gather_related_data();
            }
        } catch (Exception $e) {
            $version->register_error($e);
        } finally {
            $version->finish();
            return $version;
        }
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
        $obj->configid = $this->configid;
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
     * Call next step: Gather the data that can be used for training and testing this model version.
     *
     * @param bool $anonymous whether to gather the dataset and anonymize it.
     * @throws Exception
     */
    public function gather_dataset(bool $anonymous = true): void {
        $options = ['modelid' => $this->modelid, 'analyser' => $this->analyser, 'contexts' => $this->contexts];

        $evidencetype = $anonymous ? 'dataset_anonymized' : 'dataset';

        // If this evidence already exists, skip it.
        // Retrieve data in later step if necessary.
        if ($this->has_evidence($evidencetype)) {
            return;
        }

        $evidence = $this->add($evidencetype, $options);

        if ($anonymous) {
            // Create idmap and safe for later.
            if (!isset($this->idmaps)) {
                $this->idmaps = [];
            }
            $rawdata = $evidence->get_raw_data();

            $origintablename = $this->analyser->get_samples_origin();
            $this->idmaps[$origintablename] = dataset_helper::create_idmap_from_dataset($rawdata);

            // Pseudonomize the gathered data.
            $pseudonomizeddata = $evidence->pseudonomize($rawdata, $this->idmaps[$origintablename]);
            $this->evidence[$evidencetype][$evidence->get_id()] = $pseudonomizeddata;
        }

        $evidence->store();
    }

    /**
     * Bypass the data gathering step by directly setting the dataset evidence.
     * Fetches a submitted CSV file from the draft area and registers it as dataset for the model version creation.
     *
     * @param string $csvfileid
     */
    public function set_dataset(string $csvfileid): void {
        global $USER;

        $fs = get_file_storage();
        $context = context_user::instance($USER->id);
        $files = $fs->get_area_files($context->id, 'user', 'draft', $csvfileid, 'id DESC', false);

        $file = reset($files);

        $evidencetype = 'dataset';
        if ((isset($this->evidence[$evidencetype]) && count($this->evidence[$evidencetype]) > 0)
                || $this->has_evidence($evidencetype)) {
            throw new LogicException('Can not set a dataset. Dataset evidence has already been collected for this version.');
        }

        // Store as evidence.
        try {
            $evidence = dataset::create_scaffold_and_get_for_version($this->id);

            // Turn CSV file into valid dataset evidence data, and store into the evidence.
            try {
                $filehandle = $file->get_content_file_handle();
                $datasetrawdata = dataset_helper::build_from_csv($filehandle, $this->analysisinterval);
                dataset_helper::validate($datasetrawdata);
            } catch (Exception $e) {
                $evidence->abort();
                throw $e;
            }
            $evidence->set_raw_data($datasetrawdata);

            // Add id and raw data to cached field variables.
            if (!isset($this->evidence[$evidencetype])) {
                $this->evidence[$evidencetype] = [];
            }
            $evidenceid = $evidence->get_id();

            $this->evidence[$evidencetype][$evidenceid] = $evidence->get_raw_data();
        } catch (moodle_exception | Exception $e) {
            $this->register_error($e);
            throw $e;
        } finally {
            $this->set_contextids(['Using own dataset ' . $file->get_filename() . '.']);
        }

        $evidence->store();
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
        $class = '\tool_lala\\'.$evidencetype;

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
            $this->register_error($e); // Todo: Do this for any exceptions in the model version - find better place than here.
            throw $e;
        }
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
     * Register a thrown error in the error column of the model version table.
     *
     * @param moodle_exception|Exception $e the thrown exception
     */
    private function register_error(moodle_exception|Exception $e): void {
        global $DB;

        $DB->set_field('tool_lala_model_versions', 'error', $e->getMessage(), ['id' => $this->id]);
    }

    /**
     * Call next step: Gather the related data.
     *
     * @throws Exception
     */
    public function gather_related_data(): void {
        $origintablename = $this->analyser->get_samples_origin(); // The main tables to which related data should be gathered.

        if ($this->evidence_in_cache('dataset_anonymized')) {
            $this->gather_related_data_anonymized($origintablename);
        } else if ($this->has_evidence('dataset')) {
            $this->gather_related_data_unanonymized($origintablename);
        }
    }

    /**
     * Gather the related data with anonymizing it.
     *
     * @param string $origintablename
     * @throws Exception
     */
    public function gather_related_data_anonymized(string $origintablename) : void {
        $evidencetype = 'related_data_anonymized';
        if ($this->has_evidence($evidencetype)) {
            return;
        }

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

            $evidence = $this->add($evidencetype, $options);

            $evidences[] = $evidence; // Store the evidence for anonymizing it later.

            // Create idmaps.
            $notanonymizeddata = $evidence->get_raw_data();
            if (!isset($this->idmaps[$relatedtablename])) {
                $this->idmaps[$relatedtablename] = related_data_helper::create_idmap_from_related_data($notanonymizeddata);
            }
        }

        // Anonymize the related data. We need ALL idmaps for that.
        foreach ($evidences as $evidence) {
            $pseudonomizeddata = $evidence->pseudonomize($evidence->get_raw_data(), $this->idmaps, $evidence->get_tablename());
            $this->evidence[$evidencetype][$evidence->get_id()] = $pseudonomizeddata;
            $evidence->store(); // Only now can we store the data.
        }
    }

    /**
     * Gather the related data without anonymizing it.
     *
     * @param string $origintablename
     */
    public function gather_related_data_unanonymized(string $origintablename) : void {
        $evidencetype = 'related_data';
        if ($this->has_evidence($evidencetype)) {
            return;
        }

        $data = $this->get_single_evidence('dataset');
        $originids = dataset_helper::get_ids_used_in_dataset($data);

        $relatedtables = database_helper::get_related_tables($origintablename, $originids, [$origintablename => $originids]);

        $options = [];
        foreach ($relatedtables as $relatedtablename => $relevantids) {
            $options['tablename'] = $relatedtablename;
            $options['ids'] = $relevantids;
            $evidence = $this->add($evidencetype, $options);
            $evidence->store();
        }
    }

    /**
     * Get the first evidence item of a type.
     *
     * @param string $evidencetype a valid name of an evidence inheriting class
     * @return array|Phpml\Classification\Linear\LogisticRegression|null the evidence raw data
     */
    public function get_single_evidence(string $evidencetype): mixed {
        // Try to retrieve the data from the cache.
        if ($this->evidence_in_cache($evidencetype)) {
            $allevidence = array_values($this->evidence[$evidencetype]);
            return reset($allevidence);
        }
        // Try to retrieve the data from the server.
        if ($this->evidence_in_db($evidencetype)) {
            // Find the id of the evidence.
            global $DB;
            $evidencerecords = $DB->get_records('tool_lala_evidence', ['versionid' => $this->id, 'name' => $evidencetype]);
            if (!isset($evidencerecords) || count($evidencerecords) < 1) {
                throw new LogicException('Evidence ' . $evidencetype . ' should be in DB but is not');
            }
            $firstevidence = reset($evidencerecords);
            $evidenceid = $firstevidence->id;

            // Restore the evidence with this id.
            $class = '\tool_lala\\'.$evidencetype;
            $evidence = new $class($evidenceid);
            $evidence->restore_raw_data(['analysisintervalkey' => $this->analysisinterval]);

            return $evidence->get_raw_data(); // Return the evidence data.
        }
        return null; // No data available!
    }

    /**
     * Call next step: Split the data into training and testing data.
     */
    public function split_training_test_data(): void {
        $existingtrainingdataset = $this->get_single_evidence('training_dataset');
        $existingtestdataset = $this->get_single_evidence('test_dataset');
        if (isset($existingtrainingdataset) && isset($existingtestdataset)) {
            return; // The evidence has already been gathered completely - return.
        }

        // Get shuffled data to use for splitting.
        $datashuffled = $this->get_shuffled_data($existingtrainingdataset, $existingtestdataset);
        if (count($datashuffled) == 0) {
            throw new LogicException('No data available to split into training and testing data. Have you gathered data?');
        }
        $options = ['data' => $datashuffled, 'testsize' => $this->relativetestsetsize];

        if (!$existingtrainingdataset) {
            $trainingevidence = $this->add('training_dataset', $options);
            $trainingevidence->store();
        }
        if (!$existingtestdataset) {
            $testevidence = $this->add('test_dataset', $options);
            $testevidence->store();
        }
    }

    /**
     * Get the dataset evidence but shuffled. Also restore the shuffled dataset if possible.
     *
     * @param array|false $existingtrainingdataset
     * @param array|false $existingtestdataset
     * @return array
     */
    public function get_shuffled_data(mixed $existingtrainingdataset, mixed $existingtestdataset): array {
        if ($this->has_evidence('dataset_anonymized')) { // The dataset exists and is already shuffled.
            return $this->get_single_evidence('dataset_anonymized');
        }

        if ($this->has_evidence('dataset')) { // The dataset exists but is not shuffled yet.
            $data = $this->get_single_evidence('dataset');

            // If a training dataset exists already, we can not simply shuffle the dataset again.
            // This would lead to some wrong samples ending up in the test dataset.
            // Therefore, we need to reconstruct the shuffled dataset.
            // We know that the first part of the shuffled dataset is the training dataset.
            // So the second part needs to be the remaining data, but shuffled.
            if ($existingtrainingdataset !== false && !$existingtestdataset) {
                $trainingdata = $this->get_single_evidence('training_dataset'); // First part of reconstructed data.
                if (!isset($trainingdata)) {
                    $trainingdata = [];
                }
                $remainingdata = dataset_helper::diff($data, $trainingdata); // Second part of reconstructed data.
                $remainingdatashuffled = dataset_helper::get_shuffled($remainingdata);
                return dataset_helper::merge($trainingdata, $remainingdatashuffled);
            } else {
                return dataset_helper::get_shuffled($data);
            }
        }

        return [];
    }

    /**
     * Call next step: Train a model.
     */
    public function train(): void {
        // Check if the evidence has already been gathered.
        $evidencetype = 'model';
        if ($this->has_evidence($evidencetype)) {
            return;
        }

        $trainingdataset = $this->get_single_evidence('training_dataset');
        if (!isset($trainingdataset)) {
            throw new LogicException('No training data is available for training. Have you gathered data and split it into
            training and testing data?');
        }
        $options = ['data' => $trainingdataset, 'predictor' => $this->predictor];

        $evidence = $this->add('model', $options);
        $evidence->store();
    }

    /**
     * Call next step: Request predictions from a trained model.
     */
    public function predict(): void {
        $evidencetype = 'predictions_dataset';
        if ($this->has_evidence($evidencetype)) {
            return;
        }

        $model = $this->get_single_evidence('model');
        if (!isset($model)) {
            throw new LogicException('No model is available for getting predictions. Have
            you trained a model?');
        }
        $testdataset = $this->get_single_evidence('test_dataset');
        if (!isset($testdataset)) {
            throw new LogicException('No dataset is available for testing. Have you created a training/test split?');
        }
        $options = ['model' => $model, 'data' => $testdataset];

        $evidence = $this->add($evidencetype, $options);
        $evidence->store();
    }

    /**
     * Mark the model version as finished.
     */
    public function finish(): void {
        global $DB;

        $this->timecreationfinished = time();
        $DB->set_field('tool_lala_model_versions', 'timecreationfinished', $this->timecreationfinished,
                ['id' => $this->id]);

        // Register an event.
        $props = ['objectid' => $this->id, 'other' => ['configid' => $this->configid, 'modelid' => $this->modelid]];
        $event = model_version_created::create($props);
        $event->trigger();
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
     * Check if evidence of said type already exists in db.
     *
     * @param string $evidencetype
     * @return bool
     */
    public function evidence_in_db(string $evidencetype): bool {
        global $DB;
        $evidence = $DB->get_records('tool_lala_evidence', ['versionid' => $this->id, 'name' => $evidencetype], '', 'id');
        if (count($evidence) < 1) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Check if evidence of said type already exists in cache.
     *
     * @param string $evidencetype
     * @return bool
     */
    public function evidence_in_cache(string $evidencetype): bool {
        return isset($this->evidence[$evidencetype]) && count($this->evidence[$evidencetype]) > 0;
    }

    /**
     * Check if evidence of said type already exists in cache or db.
     *
     * @param string $evidencetype
     * @return bool
     */
    public function has_evidence(string $evidencetype): bool {
        return $this->evidence_in_cache($evidencetype) || $this->evidence_in_db($evidencetype);
    }

    /**
     * Get all evidence items of a type. Useful for testing.
     *
     * @param string $evidencetype a valid name of an evidence inheriting class
     * @return array[]|Phpml\Classification\Linear\LogisticRegression[] the evidence raw data
     */
    public function get_array_of_evidences(string $evidencetype): array {
        return $this->evidence[$evidencetype];
    }

    /**
     * Loads the contexts as objects from ids.
     */
    public function load_context_objects(): void {
        $this->contexts = [];
        if (!isset($this->contextids)) {
            return;
        }
        $contextidarr = json_decode($this->contextids);
        foreach ($contextidarr as $contextid) {
            $this->contexts[] = context::instance_by_id($contextid, IGNORE_MISSING);
        }
    }
}
