<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace tool_lala\task;

use core\task\adhoc_task;
use tool_lala\model_version;

/**
* Adhoc task for creating a model version.
*/
class version_create extends adhoc_task {
    /**
     * Creator.
     *
     * @param int $versionid
     * @param array|null $contexts
     * @param string|null $dataset
     * @return version_create
     */
    public static function instance(int $versionid, ?array $contexts = null, ?string $dataset = null): version_create {
        $task = new self();
        $task->set_custom_data((object) [
                'versionid' => $versionid,
                'contexts' => $contexts,
                'dataset' => $dataset
        ]);
        return $task;
    }
    /**
    * Execute the task.
    */
    public function execute() {
        $data = $this->get_custom_data();
        mtrace('Creating version ' . $data->versionid . '...');
        model_version::create($data->versionid, $data->contexts, $data->dataset);
        mtrace('Finished version ' . $data->versionid . '.');
    }
}