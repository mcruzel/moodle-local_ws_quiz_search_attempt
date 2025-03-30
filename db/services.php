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

$functions = array(
    'local_ws_quiz_search_attempt' => array(
        'classname' => 'local_ws_quiz_search_attempt\external',
        'methodname' => 'search_attempts',
        'type' => 'read',
        'capabilities' => 'mod/quiz:viewreports',
        'ajax' => true,
        'loginrequired' => true,
        'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
        'description' => 'Recherche des tentatives de quiz selon différents critères. Permet de filtrer par utilisateur et/ou période.',
        'help' => 'Cette fonction permet de rechercher des tentatives de quiz en fonction de différents critères :
                  - L\'ID du quiz (obligatoire)
                  - L\'ID de l\'utilisateur (optionnel)
                  - Une période (optionnelle) avec before_timestamp et/ou after_timestamp
                  La fonction retourne un tableau contenant les tentatives trouvées avec leurs détails.',
    ),
); 