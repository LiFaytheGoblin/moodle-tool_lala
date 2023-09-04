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
 * @package     tool_lala
 * @copyright   2023 Linda Fernsel <fernsel@htw-berlin.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_lala;

use Exception;
use LogicException;
use stdClass;
use context_system;
use moodle_url;

/**
 * Class for the evidence item.
 */
abstract class evidence {
    /** @var int $id id assigned to the evidence by the db. */
    protected int $id;
    /** @var int $versionid id of the belonging model version. */
    protected int $versionid;
    /** @var string $name of the evidence. */
    protected string $name;
    /** @var array|mixed|null $data raw data of the evidence. */
    protected mixed $data;
    /** @var string|null $filestring serialized data of the evidence. */
    protected ?string $filestring;
    /** @var int|null $timecollectionfinished of the evidence. */
    private ?int $timecollectionfinished;
    /** @var string|null $serializedfilelocation path of the evidence. */
    private ?string $serializedfilelocation;

    /**
     * Constructor. Deserialize DB object.
     *
     * @param int $id of the evidence
     * @return void
     */
    public function __construct(int $id) {
        global $DB;

        $evidence = $DB->get_record('tool_lala_evidence', ['id' => $id], '*', MUST_EXIST);

        // Fill properties from DB.
        $this->id = $evidence->id;
        $this->versionid = $evidence->versionid;
        $this->name = $evidence->name;
        $this->timecollectionfinished = $evidence->timecollectionfinished;
        $this->serializedfilelocation = $evidence->serializedfilelocation;
    }

    /**
     * Returns a stdClass with the evidence data.
     *
     * @param int $versionid of the version
     * @return evidence of the created evidence
     * @throws Exception
     * @throws Exception
     */
    public static function create_scaffold_and_get_for_version(int $versionid): evidence {
        global $DB;

        $obj = new stdClass();

        if (!$DB->record_exists('tool_lala_model_versions', ['id' => $versionid])) {
            throw new Exception('No evidence can be created for version with id '.$versionid.'because this version does not
            exist.');
        }

        $obj->versionid = $versionid;
        $classname = get_called_class();
        $classnameparts = explode('\\', $classname);
        $obj->name = end($classnameparts);
        $obj->timecollectionstarted = time();

        $id = $DB->insert_record('tool_lala_evidence', $obj);

        return new static($id);
    }

    /**
     * Collects the raw data.
     *
     * @param array $options depending on the implementation
     * @return void
     */
    abstract public function collect(array $options): void;

    /**
     * Validates the $options array.
     *
     * @param array $options depending on the implementation
     * @return void
     */
    abstract public function validate(array $options): void;

    /**
     * Stores a serialized data string in a file. Sets the serializedfilelocation property of the class.
     * @return void
     */
    public function store(): void {
        if (!isset($this->data)) {
            throw new LogicException('No evidence has been collected yet that could be serialized. Make sure to collect the
            evidence first.');
        }

        if (!isset($this->filestring)) {
            $this->serialize();
        }

        $fileinfo = $this->get_file_info();

        $fs = get_file_storage();

        $fs->create_file_from_string($fileinfo, $this->filestring);

        $this->set_serializedfilelocation();

        $this->finish();
    }

    /**
     * Serializes the raw data.
     * Store the serialization string in the filestring field.
     *
     * @return void
     */
    abstract public function serialize(): void;

    /**
     * Returns info on the serialized data file on the server.
     * @return array
     */
    public function get_file_info(): array {
        return [
                'contextid' => context_system::instance()->id,
                'component' => 'tool_lala',
                'filearea'  => 'tool_lala',
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
    abstract protected function get_file_type(): string;

    /**
     * Sets the path where the serialized data is located as a file on the server, for later download.
     *
     * @return void
     */
    protected function set_serializedfilelocation(): void {
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
        $DB->set_field('tool_lala_evidence', 'serializedfilelocation', $this->serializedfilelocation, ['id' => $this->id]);
    }

    /**
     * Mark this evidence collection as finished in the database.
     * @return void
     */
    public function finish(): void {
        global $DB;

        $this->timecollectionfinished = time();
        $DB->set_field('tool_lala_evidence', 'timecollectionfinished', $this->timecollectionfinished, ['id' => $this->id]);
    }

    /**
     * Returns the id of the evidence item. Used by the model version.
     * @return mixed id
     */
    public function get_id(): mixed {
        return $this->id;
    }

    /**
     * Returns the name of the evidence item. Used by the evidence items.
     * @return string name
     */
    public function get_name(): string {
        return $this->name;
    }

    /**
     * Returns the id of the version to which the evidence item belongs. Used by the evidence items.
     * @return int versionid
     */
    public function get_versionid(): int {
        return $this->versionid;
    }

    /**
     * Returns the raw data of the evidence. Used by the model version.
     * @return array|mixed|null raw data
     */
    public function get_raw_data(): mixed {
        return $this->data;
    }

    /**
     * Set the raw data for bypassing the collection process of the evidence item.
     *
     * @param $data
     * @return void
     */
    public function set_raw_data(mixed $data): void {
        $this->data = $data;
    }

    /**
     * Returns the serialized data of the evidence.
     * Useful for testing.
     *
     * @return string|null serialized data
     */
    public function get_serialized_data(): ?string {
        return $this->filestring;
    }

    /**
     * Returns the time when the collection was finished, if it has been finished already.
     * @return int|null
     */
    public function get_timecollectionfinished(): ?int {
        return $this->timecollectionfinished;
    }

    /**
     * Abort this evidence collection, e.g. if an error occurs, by deleting the evidence from the database.
     * @return void
     */
    public function abort(): void {
        global $DB;

        $DB->delete_records('tool_lala_evidence', ['id' => $this->id]);

        if (isset($this->serializedfilelocation)) {
            $fs = get_file_storage();
            $fileinfo = $this->get_file_info();
            $file = $fs->get_file($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'], $fileinfo['itemid'],
                    $fileinfo['filepath'], $fileinfo['filename']);
            $file->delete();
        }

        unset($this->data);
        unset($this->filestring);
        unset($this->serializedfilelocation);
    }
}
