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
use moodle_url;

/**
 * Class for the evidence item.
 */
abstract class evidence {
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
    /** @var data $data raw data of the evidence. */
    protected $data;
    /** @var string $filestring serialized data of the evidence. */
    protected $filestring;
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
     * @return evidence of the created evidence
     */
    public static function create_scaffold_and_get_for_version($versionid) {
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
     * Collects the raw data.
     * @param array $options depending on the implementation
     * @return void
     */
    abstract public function collect($options);

    /**
     * Serializes the raw data.
     * Store the serialization string in the filestring field.
     *
     * @return void
     */
    abstract public function serialize();

    /**
     * Stores a serialized data string in a file. Sets the serializedfilelocation property of the class.
     * @return void
     */
    public function store() {
        $fileinfo = $this->get_file_info();

        $fs = get_file_storage();

        $fs->create_file_from_string($fileinfo, $this->filestring);

        $this->set_serializedfilelocation();
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

    /**
     * Returns the raw data of the evidence. Used by the model version.
     * @return data raw data
     */
    public function get_raw_data() {
        return $this->data;
    }

    /**
     * Returns the serialized data of the evidence.
     * @return string serialized data
     */
    public function get_serialized_data() {
        return $this->filestring;
    }

    /**
     * Sets the path where the serialized data is located as a file on the server, for later download.
     *
     * @return void
     */
    protected function set_serializedfilelocation() {
        $fileinfo = $this->get_file_info();

        $serializedfileurl = moodle_url::make_pluginfile_url(
                $fileinfo['contextid'],
                $fileinfo['component'],
                $fileinfo['filearea'],
                $fileinfo['itemid'],
                $fileinfo['filepath'],
                $fileinfo['filename'],
                true
        );

        $this->serializedfilelocation = $serializedfileurl->out();

        global $DB;
        $DB->set_field('tool_laaudit_evidence', 'serializedfilelocation', $this->serializedfilelocation,  array('id' => $this->id));
    }

    /**
     * Returns the location of the serialized data as a file on the server, for later download.
     * @return string path location
     */
    public function get_serializedfilelocation() {
        return $this->serializedfilelocation;
    }

    /**
     * Returns info on the serialized data file on the server.
     * @return array
     */
    protected function get_file_info() {
        return [
                'contextid' => context_system::instance()->id,
                'component' => 'tool_laaudit',
                'filearea'  => 'tool_laaudit',
                'itemid'    => $this->id,
                'filepath'  => '/evidence/',
                'filename'  => 'modelversion' . $this->versionid . '-evidence' . $this->name . $this->id . '.' .
                        $this->get_file_type(),
        ];
    }

    /**
     * Returns the type of the stored file, e.g. "csv".
     * @return string
     */
    abstract protected function get_file_type();

    /**
     * Mark this evidence collection as finished in the data base.
     * @return void
     */
    public function finish() {
        global $DB;

        $this->timecollectionfinished = time();
        $DB->set_field('tool_laaudit_evidence', 'timecollectionfinished', $this->timecollectionfinished, array('id' => $this->id));
    }

    /**
     * Abort this evidence collection, e.g. if an error occurs, by deleting the evidence from the database.
     * @return void
     */
    public function abort() {
        global $DB;

        $DB->delete_records('tool_laaudit_evidence', array('id' => $this->id));
    }
}
