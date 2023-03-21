<?php

namespace tool_laaudit;

// file admin/tool/laaudit/classes/model_configuration.php
use core_analytics\model;

class model_configuration {
    private $ID;
    private $MODEL; // model object
    private $VERSIONS;


    /**
     * Constructor.
     *
     * @param \model $model
     * @return void
     */
    public function __construct($model) {
        global $DB;

        $this->MODEL = $model->get_model_obj();

        $this->MODEL->name_safe = isset($model->name) ? $model->name : "model" . $this->MODEL->id;

        if(!$DB->record_exists('tool_laaudit_model_configs', array('modelid' => $this->MODEL->id))) {
            model_configuration::insert_new_from_model_into_DB($this->MODEL);
        }

        $model_config = $DB->get_record('tool_laaudit_model_configs', array('modelid' => $this->MODEL->id), '*', MUST_EXIST);

        $this->ID = $model_config->id;
        $this->VERSIONS = json_decode($model_config->versions); //array of ids, todo see if this works
    }

    /**
     * Returns a plain \stdClass with the model config data (id, modelid, versions) plus modelname and modeltarget.
     *
     * @return \stdClass
     */
    public function get_model_config_obj() {
        $obj = new \stdClass();
        $obj->id = $this->ID;
        $obj->modelid = $this->MODEL->id;
        $obj->modelname = $this->MODEL->name_safe;
        $obj->modeltarget = $this->MODEL->target;
        $obj->versions = $this->VERSIONS;

        return $obj;
    }

    /**
     * Create a new model configuration from a model object
     *
     * @param \stdClass $model
     * @return void
     */
    public static function insert_new_from_model_into_DB($model) {
        global $DB;

        // create new db entry
        $obj = new \stdClass();
        $obj->modelid = $model->id;
        $obj->versions = json_encode([]);

        $DB->insert_record('tool_laaudit_model_configs', $obj);
    }
}