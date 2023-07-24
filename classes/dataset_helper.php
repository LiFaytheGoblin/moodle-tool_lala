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
 * The dataset class.
 *
 * @package     tool_laaudit
 * @copyright   2023 Linda Fernsel <fernsel@htw-berlin.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_laaudit;

use core_analytics\local\analysis\result_array;
use core_analytics\analysis;
use core_php_time_limit;
use DomainException;

/**
 * Class for the complete dataset evidence item.
 */
class dataset_helper {
    /**
     * Helper: Shuffle a data set while preserving the key and the header.
     *
     * @param array $dataset
     * @return array shuffled data
     */
    public static function get_shuffled(array $dataset): array {
        if (count($dataset) == 0) {
            throw new DomainException('Data array to be shuffled can not be empty.');
        }

        $analysisintervalkey = self::get_analysisintervalkey($dataset);

        $arr = $dataset[$analysisintervalkey];

        if (count($arr) == 1) {
            throw new DomainException('Data array to be shuffled needs to be at least of size 2.
            The first item is kept as item one, being treated as the header.');
        }

        $header = self::get_first_row($dataset);
        $remainingdata = self::get_rows($dataset);

        $shuffleddata = self::shuffle_array_preserving_keys($remainingdata);

        $datawithheader = [];
        $datawithheader[$analysisintervalkey] = $header + $shuffleddata;

        return $datawithheader;
    }

    public static function shuffle_array_preserving_keys($arr) : array {
        $keys = array_keys($arr);
        if (count($keys) < 2) {
            return $arr;
        }
        shuffle($keys);
        $shuffleddata = [];
        foreach ($keys as $key) {
            // Assign to each key in the random order the value from the original array.
            $shuffleddata[$key] = $arr[$key];
        }
        return $shuffleddata;
    }

    public static function get_rows($dataset) : array {
        $analysisintervalkey = self::get_analysisintervalkey($dataset);
        return array_slice($dataset[$analysisintervalkey], 1, null, true);
    }

    public static function get_separate_x_y_from_rows($rows) {
        $testx = [];
        $testy = [];
        foreach ($rows as $row) {
            $len = count($row);
            $testx[] = array_slice($row, 0, $len - 1, true);
            $testy[] = $row[$len - 1];
        }
        return [
                'x' => $testx,
                'y' => $testy
        ];
    }

    public static function build(int|string $analysisintervalkey, $header, $sampleids, $xs, $ys) {
        $mergeddata = [];
        $mergeddata['0'] = $header;
        foreach ($sampleids as $key => $sampleid) {
            $mergeddata[$sampleid] = [$xs[$key], $ys[$key]];
        }

        $res = [];
        $res[$analysisintervalkey] = $mergeddata;
        return $res;
    }

    public static function replace_rows_in_dataset($dataset, array $newrows) : array {
        $res = [];
        $analysisintervalkey = self::get_analysisintervalkey($dataset);
        $header = self::get_first_row($dataset);
        $res[$analysisintervalkey] = $header + $newrows;
        return $res;
    }

    public static function get_first_row(array $dataset) {
        $analysisintervalkey = self::get_analysisintervalkey($dataset);
        return array_slice($dataset[$analysisintervalkey], 0, 1, true);
    }

    public static function get_analysisintervalkey(array $dataset) {
        return array_keys($dataset)[0];
    }

    public static function get_ids_used_in_dataset(array $dataset) : array {
        $ids = [];
        $analysisintervalkey = self::get_analysisintervalkey($dataset);
        $sampleids = array_keys($dataset[$analysisintervalkey]);
        unset($sampleids['0']); // Remove the header.
        foreach ($sampleids as $sampleid) {
            $id = self::get_id_part($sampleid);
            $ids[$id] = $id; // Preserve the order, avoid duplicates.
        }

        return array_keys($ids);
    }

    /**
     * @param int|string $sampleid
     * @return string
     */
    public static function get_id_part(int|string $sampleid): string {
        return explode('-', $sampleid)[0];
    }

    public static function get_analysisinterval_part(int|string $sampleid): ?string {
        $sampleidparts = explode('-', $sampleid);
        if (array_key_exists(1, $sampleidparts)) {
            return $sampleidparts[1];
        } else {
            return null;
        }
    }
}
