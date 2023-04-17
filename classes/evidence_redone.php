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
 * The abstract evidence class that can be extended.
 *
 * @package     tool_laaudit
 * @copyright   2023 Linda Fernsel <fernsel@htw-berlin.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_laaudit;

use stdClass;
use context_system;

/**
 * Class for the evidence element.
 */
abstract class evidence_redone {
    /** @var int $id id assigned to the evidence by the db. */
    protected $id;
    /** @var int $versionid id of the belonging model version. */
    protected $versionid;
    /** @var string $name of the evidence. */
    private $name;
    /** @var string $timecollectionstarted of the evidence. */
    private $timecollectionstarted;
    /** @var string $timecollectionfinished of the evidence. */
    private $timecollectionfinished;
    /** @var string $serializedfilelocation path of the evidence. */
    private $serializedfilelocation;
    /** @var string $data raw data of the evidence. */
    private $data;
    /**
     * Constructor. Deserialize DB object.
     *
     * @param int $id of the evidence
     * @return void
     */
    public function __construct($id) {
        global $DB;

        $evidence = $DB->get_record('tool_laaudit_evidence', array('id' => $id), '*', MUST_EXIST);

        // Fill properties from DB.
        $this->id = $evidence->id;
        $this->versionid = $evidence->versionid;
        $this->name = $evidence->name;
        $this->timecollectionstarted = $evidence->timecollectionstarted;
        $this->timecollectionfinished = $evidence->timecollectionfinished;
        $this->serializedfilelocation = $evidence->serializedfilelocation;
    }

    /**
     * Returns a stdClass with the evidence data.
     * @param int $versionid of the version
     * @param data $data possibly pre-existing data
     * @return stdClass of the created evidence
     */
    public static function create_and_get_for_version($versionid) {
        global $DB;

        $obj = new stdClass();

        $obj->versionid = $versionid;
        $classname = get_called_class();
        $classnameparts = explode('\\', $classname);
        $obj->name = end($classnameparts);
        $obj->timecollectionstarted = time();

        $id = $DB->insert_record('tool_laaudit_evidence', $obj);

        $evidence = new static($id);

        return $evidence;
    }

    /**
     * Serializes the raw data and stores it in a file. Sets the serializedfilelocation property of the class.
     * @return void
     */
    abstract public function store($data);

    abstract protected function get_file_type();

    /**
     * Returns the path where the serialized data is located as a file on the server, for later download.
     * @return string path location
     */
    public function get_serializedfilelocation() {
        return $this->serializedfilelocation;
    }

    /**
     * Sets the path where the serialized data is located as a file on the server, for later download.
     * @param string path location
     * @return void
     */
    protected function set_serializedfilelocation($url) {
        $this->serializedfilelocation = $url;

        global $DB;
        $DB->set_field('tool_laaudit_evidence', 'serializedfilelocation', $this->serializedfilelocation,  array('id' => $this->id));
    }

    /**
     * Returns the raw data of the evidence. Used by the model version.
     * @return data serialized data
     */
    public function get_raw_data() {
        return $this->data;
    }

    /**
     * Returns the id of the evidence item. Used by the model version.
     * @return int id
     */
    public function get_id() {
        return $this->id;
    }

    /**
     * Returns the name of the evidence item. Used by the evidence items.
     * @return string name
     */
    public function get_name() {
        return $this->name;
    }

    /**
     * Returns the id of the version to which the evidence item belongs. Used by the evidence items.
     * @return int versionid
     */
    public function get_versionid() {
        return $this->versionid;
    }

    protected function get_file_path() {
        return '/evidence/';
    }

    protected function get_file_name() {
        return 'modelversion' . $this->versionid . '-evidence' . $this->name . $this->id;
    }

    protected function get_file_info() {
        return [
                'contextid' => context_system::instance()->id,
                'component' => 'tool_laaudit',
                'filearea'  => 'tool_laaudit',
                'itemid'    => $this->id,
                'filepath'  => $this->get_file_path(),
                'filename'  => $this->get_file_name() . '.' . $this->get_file_type(),
        ];
    }



    public function finish() {
        global $DB;

        $this->timecollectionfinished = time();
        $DB->set_field('tool_laaudit_evidence', 'timecollectionfinished', $this->timecollectionfinished, array('id' => $this->id));
    }
}
