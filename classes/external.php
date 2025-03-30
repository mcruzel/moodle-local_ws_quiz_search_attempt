<?php
/**
 * External functions for the local_ws_mod_quiz_update_key plugin
 *
 * @package    local_ws_quiz_search_attempt
 * @category   external
 * @copyright  2025 Maxime Cruzel
 * @license    https://opensource.org/licenses/MIT MIT
 */

namespace local_ws_quiz_search_attempt;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/mod/quiz/locallib.php');

class external extends \external_api {
    /**
     * Retourne la description des paramètres de la fonction search_attempts
     *
     * @return \external_function_parameters
     */
    public static function search_attempts_parameters() {
        return new \external_function_parameters([
            'key' => new \external_multiple_structure(
                new \external_value(PARAM_ALPHANUMEXT, 'Clé du paramètre'),
                'Liste des clés des paramètres'
            ),
            'value' => new \external_multiple_structure(
                new \external_value(PARAM_INT, 'Valeur du paramètre'),
                'Liste des valeurs des paramètres'
            )
        ]);
    }

    /**
     * Recherche les tentatives de quiz selon les critères fournis
     *
     * @param array $key Liste des clés des paramètres
     * @param array $value Liste des valeurs des paramètres
     * @return array
     * @throws \moodle_exception
     */
    public static function search_attempts($key, $value) {
        global $DB;
    
        // Valider les paramètres
        $validatedparams = self::validate_parameters(self::search_attempts_parameters(), [
            'key' => $key,
            'value' => $value,
        ]);
    
        // Vérifier que les tableaux ont la même taille
        if (count($validatedparams['key']) !== count($validatedparams['value'])) {
            throw new \moodle_exception('invalidparameter', 'webservice', '', 'Les tableaux key et value doivent avoir la même taille');
        }
    
        // Créer un tableau associatif des paramètres
        $params = array_combine($validatedparams['key'], $validatedparams['value']);
    
        // Vérifier que quizid est présent
        if (!isset($params['quizid'])) {
            throw new \moodle_exception('invalidparameter', 'webservice', '', 'Le paramètre quizid est obligatoire');
        }
    
        $quizid = (int)$params['quizid'];
    
        // Vérifier l'existence du quiz
        $quiz = $DB->get_record('quiz', ['id' => $quizid], '*', MUST_EXIST);
    
        // Vérifier les permissions
        $cm = get_coursemodule_from_instance('quiz', $quiz->id, $quiz->course, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('mod/quiz:viewreports', $context);
    
        // Construction de la requête SQL
        $where = "quiz = :quizid AND timefinish > 0";
        $sqlparams = ['quizid' => $quizid];
    
        // Parcourir chaque critère fourni et appliquer la condition SQL appropriée
        foreach ($params as $key => $value) {
            if ($key === 'userid') {
                $where .= " AND userid = :userid";
                $sqlparams['userid'] = (int)$value;
            } elseif ($key === 'before_timestamp') {
                $where .= " AND timefinish <= :before_timestamp";
                $sqlparams['before_timestamp'] = (int)$value;
            } elseif ($key === 'after_timestamp') {
                $where .= " AND timefinish >= :after_timestamp";
                $sqlparams['after_timestamp'] = (int)$value;
            }
        }
        
        // Récupérer les tentatives
        $attempts = $DB->get_records_select('quiz_attempts', $where, $sqlparams);
    
        // Enrichir les résultats avec les informations utilisateur
        $result = [];
        foreach ($attempts as $attempt) {
            $user = $DB->get_record('user', ['id' => $attempt->userid], 'firstname, lastname, email', MUST_EXIST);
            $result[] = [
                'attemptid' => (int)$attempt->id,
                'userid'    => (int)$attempt->userid,
                'user'      => [
                    'firstname' => $user->firstname,
                    'lastname'  => $user->lastname,
                    'email'     => $user->email,
                ],
                'attempt'   => [
                    'number'      => (int)$attempt->attempt,
                    'uniqueid'    => (int)$attempt->uniqueid,
                    'state'       => $attempt->state,
                    'sumgrades'   => $attempt->sumgrades === null ? null : (float)$attempt->sumgrades,
                    'layout'      => $attempt->layout,
                    'currentpage' => (int)$attempt->currentpage,
                ],
                'timing' => [
                    'timestart'  => (int)$attempt->timestart,
                    'timefinish' => (int)$attempt->timefinish,
                ],
            ];
        }
    
        return $result;
    }

    /**
     * Retourne la description de la valeur de retour de la fonction search_attempts
     *
     * @return \external_multiple_structure
     */
    public static function search_attempts_returns() {
        return new \external_multiple_structure(
            new \external_single_structure([
                'attemptid' => new \external_value(PARAM_INT, 'ID unique de la tentative'),
                'userid'    => new \external_value(PARAM_INT, 'ID de l\'utilisateur'),
                'user'      => new \external_single_structure([
                    'firstname' => new \external_value(PARAM_TEXT, 'Prénom de l\'utilisateur'),
                    'lastname'  => new \external_value(PARAM_TEXT, 'Nom de l\'utilisateur'),
                    'email'     => new \external_value(PARAM_EMAIL, 'Email de l\'utilisateur'),
                ], 'Informations sur l\'utilisateur'),
                'attempt'   => new \external_single_structure([
                    'number'      => new \external_value(PARAM_INT, 'Numéro de la tentative'),
                    'uniqueid'    => new \external_value(PARAM_INT, 'ID unique de la tentative'),
                    'state'       => new \external_value(PARAM_ALPHA, 'État de la tentative (finished, inprogress, etc.)'),
                    'sumgrades'   => new \external_value(PARAM_FLOAT, 'Note totale de la tentative'),
                    'layout'      => new \external_value(PARAM_RAW, 'Structure du quiz'),
                    'currentpage' => new \external_value(PARAM_INT, 'Page actuelle du quiz'),
                ], 'Informations sur la tentative'),
                'timing'    => new \external_single_structure([
                    'timestart'  => new \external_value(PARAM_INT, 'Timestamp de début de la tentative'),
                    'timefinish' => new \external_value(PARAM_INT, 'Timestamp de fin de la tentative'),
                ], 'Informations temporelles'),
            ], 'Structure d\'une tentative de quiz')
        );
    }
} 
