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

/**
 * Adds a link to the tool page to the admin settings.
 *
 * @package     tool_laaudit
 * @copyright   2023 Linda Fernsel <fernsel@htw-berlin.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$capabilities = [
        'tool/laaudit:viewpagecontent' => [
            'riskbitmask' => RISK_SPAM,
            'captype' => 'read',
            'contextlevel' => CONTEXT_SYSTEM,
            'archetypes' => [
                    'auditor' => CAP_ALLOW,
            ],
        ],
        'tool/laaudit:downloadevidence' => [
            'riskbitmask' => RISK_PERSONAL,
            'captype' => 'read',
            'contextlevel' => CONTEXT_SYSTEM,
            'archetypes' => [
                    'auditor' => CAP_ALLOW,
            ],
        ],
        'tool/laaudit:createmodelversion' => [
            'riskbitmask' => RISK_SPAM,
            'captype' => 'write',
            'contextlevel' => CONTEXT_SYSTEM,
            'archetypes' => [
                    'auditor' => CAP_ALLOW,
            ],
        ],
];