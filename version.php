<?php
/**
 * External functions for the local_ws_mod_quiz_update_key plugin
 *
 * @package    local_ws_quiz_search_attempt
 * @category   external
 * @copyright  2025 Maxime Cruzel
 * @license    https://opensource.org/licenses/MIT MIT
 */

defined('MOODLE_INTERNAL') || die();

$plugin->version = 2024032036;  // Version du plugin (YYYYMMDDXX)
$plugin->requires = 2022112800; // Version minimale de Moodle requise
$plugin->component = 'local_ws_quiz_search_attempt'; // Nom du plugin
$plugin->dependencies = array(
    'mod_quiz' => ANY_VERSION,
); 