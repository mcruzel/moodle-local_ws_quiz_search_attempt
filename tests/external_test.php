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

global $CFG;
require_once($CFG->dirroot . '/webservice/tests/helpers.php');

class external_test extends \advanced_testcase {
    /**
     * @var \stdClass
     */
    private $course;

    /**
     * @var \stdClass
     */
    private $quiz;

    /**
     * @var \stdClass
     */
    private $user1;

    /**
     * @var \stdClass
     */
    private $user2;

    /**
     * @var \stdClass
     */
    private $user3;

    /**
     * Configuration initiale pour les tests
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);

        // Créer un cours
        $this->course = $this->getDataGenerator()->create_course();

        // Créer un quiz
        $this->quiz = $this->getDataGenerator()->create_module('quiz', array(
            'course' => $this->course->id,
            'name' => 'Test Quiz'
        ));

        // Créer trois utilisateurs
        $this->user1 = $this->getDataGenerator()->create_user();
        $this->user2 = $this->getDataGenerator()->create_user();
        $this->user3 = $this->getDataGenerator()->create_user();

        // Inscrire les utilisateurs au cours
        $this->getDataGenerator()->enrol_user($this->user1->id, $this->course->id);
        $this->getDataGenerator()->enrol_user($this->user2->id, $this->course->id);
        // user3 n'est pas inscrit au cours

        // Donner les permissions de rapport au user1
        $this->getDataGenerator()->role_assign('teacher', $this->user1->id, \context_course::instance($this->course->id)->id);
    }

    /**
     * Test de la recherche de tentatives sans filtres
     */
    public function test_search_attempts_no_filters() {
        global $DB;

        // Créer des tentatives pour les deux utilisateurs
        $attempt1 = $this->create_quiz_attempt($this->user1->id, time() - 3600);
        $attempt2 = $this->create_quiz_attempt($this->user2->id, time() - 7200);

        // Exécuter le test en tant que user1 (avec permissions)
        $this->setUser($this->user1);

        $result = external::search_attempts($this->quiz->id);

        // Vérifier que nous avons bien 2 tentatives
        $this->assertCount(2, $result);

        // Vérifier la structure de la première tentative
        $this->assertArrayHasKey('attemptid', $result[0]);
        $this->assertArrayHasKey('userid', $result[0]);
        $this->assertArrayHasKey('user', $result[0]);
        $this->assertArrayHasKey('attempt', $result[0]);
        $this->assertArrayHasKey('timing', $result[0]);

        // Vérifier les données utilisateur
        $this->assertEquals($this->user1->firstname, $result[0]['user']['firstname']);
        $this->assertEquals($this->user1->lastname, $result[0]['user']['lastname']);
        $this->assertEquals($this->user1->email, $result[0]['user']['email']);

        // Vérifier les données de tentative
        $this->assertEquals($attempt1->attempt, $result[0]['attempt']['number']);
        $this->assertEquals($attempt1->uniqueid, $result[0]['attempt']['uniqueid']);
        $this->assertEquals($attempt1->state, $result[0]['attempt']['state']);
        $this->assertEquals($attempt1->sumgrades, $result[0]['attempt']['sumgrades']);
        $this->assertEquals($attempt1->layout, $result[0]['attempt']['layout']);
        $this->assertEquals($attempt1->currentpage, $result[0]['attempt']['currentpage']);

        // Vérifier les données temporelles
        $this->assertEquals($attempt1->timestart, $result[0]['timing']['timestart']);
        $this->assertEquals($attempt1->timefinish, $result[0]['timing']['timefinish']);
    }

    /**
     * Test de la recherche de tentatives avec filtre userid
     */
    public function test_search_attempts_with_userid() {
        global $DB;

        // Créer des tentatives pour les deux utilisateurs
        $attempt1 = $this->create_quiz_attempt($this->user1->id, time() - 3600);
        $this->create_quiz_attempt($this->user2->id, time() - 7200);

        // Exécuter le test en tant que user1
        $this->setUser($this->user1);

        $result = external::search_attempts($this->quiz->id, $this->user1->id);

        // Vérifier que nous avons bien 1 tentative pour user1
        $this->assertCount(1, $result);
        $this->assertEquals($this->user1->id, $result[0]['userid']);
        $this->assertEquals($this->user1->firstname, $result[0]['user']['firstname']);
        $this->assertEquals($this->user1->lastname, $result[0]['user']['lastname']);
        $this->assertEquals($this->user1->email, $result[0]['user']['email']);
    }

    /**
     * Test de la recherche de tentatives avec filtre timestamp
     */
    public function test_search_attempts_with_timestamps() {
        global $DB;

        $now = time();
        $attempt1 = $this->create_quiz_attempt($this->user1->id, $now - 3600);
        $attempt2 = $this->create_quiz_attempt($this->user2->id, $now - 7200);

        // Exécuter le test en tant que user1
        $this->setUser($this->user1);

        // Test avec before_timestamp
        $result = external::search_attempts($this->quiz->id, null, $now - 1800);
        $this->assertCount(1, $result);
        $this->assertEquals($this->user1->id, $result[0]['userid']);
        $this->assertEquals($attempt1->timefinish, $result[0]['timing']['timefinish']);

        // Test avec after_timestamp
        $result = external::search_attempts($this->quiz->id, null, null, $now - 1800);
        $this->assertCount(1, $result);
        $this->assertEquals($this->user2->id, $result[0]['userid']);
        $this->assertEquals($attempt2->timefinish, $result[0]['timing']['timefinish']);
    }

    /**
     * Test de la recherche avec un quiz inexistant
     */
    public function test_search_attempts_invalid_quiz() {
        $this->setUser($this->user1);

        $this->expectException(\moodle_exception::class);
        external::search_attempts(99999);
    }

    /**
     * Test de l'accès sans permission
     */
    public function test_search_attempts_no_permission() {
        // Créer une tentative
        $this->create_quiz_attempt($this->user2->id, time() - 3600);

        // Exécuter le test en tant que user2 (sans permission)
        $this->setUser($this->user2);

        $this->expectException(\moodle_exception::class);
        $this->expectExceptionMessage('You do not have permission to view quiz reports');
        external::search_attempts($this->quiz->id);
    }

    /**
     * Test de l'accès par un utilisateur non inscrit
     */
    public function test_search_attempts_not_enrolled() {
        // Créer une tentative
        $this->create_quiz_attempt($this->user2->id, time() - 3600);

        // Exécuter le test en tant que user3 (non inscrit)
        $this->setUser($this->user3);

        $this->expectException(\moodle_exception::class);
        $this->expectExceptionMessage('You do not have permission to view quiz reports');
        external::search_attempts($this->quiz->id);
    }

    /**
     * Test de la structure de retour
     */
    public function test_search_attempts_return_structure() {
        // Créer une tentative
        $this->create_quiz_attempt($this->user1->id, time() - 3600);

        // Exécuter le test en tant que user1
        $this->setUser($this->user1);

        $result = external::search_attempts($this->quiz->id);
        $attempt = $result[0];

        // Vérifier que seuls les champs prévus sont présents
        $expectedKeys = ['attemptid', 'userid', 'user', 'attempt', 'timing'];
        $this->assertEquals($expectedKeys, array_keys($attempt));

        // Vérifier la structure des sous-sections
        $this->assertEquals(['firstname', 'lastname', 'email'], array_keys($attempt['user']));
        $this->assertEquals(['number', 'uniqueid', 'state', 'sumgrades', 'layout', 'currentpage'], array_keys($attempt['attempt']));
        $this->assertEquals(['timestart', 'timefinish'], array_keys($attempt['timing']));
    }

    /**
     * Crée une tentative de quiz pour un utilisateur
     *
     * @param int $userid
     * @param int $timefinish
     * @return \stdClass
     */
    private function create_quiz_attempt($userid, $timefinish) {
        global $DB;

        $attempt = new \stdClass();
        $attempt->quiz = $this->quiz->id;
        $attempt->userid = $userid;
        $attempt->timestart = $timefinish - 1800; // 30 minutes avant timefinish
        $attempt->timefinish = $timefinish;
        $attempt->timemodified = $timefinish;
        $attempt->state = 'finished';
        $attempt->sumgrades = 0;
        $attempt->attempt = 1;
        $attempt->uniqueid = rand(1000, 9999);
        $attempt->layout = '1,2,3';
        $attempt->currentpage = 0;

        $attempt->id = $DB->insert_record('quiz_attempts', $attempt);
        return $attempt;
    }

    /**
     * Test la fonction search_attempts avec des paramètres valides
     */
    public function test_search_attempts_with_valid_params() {
        $this->resetAfterTest(true);

        // Créer un cours
        $course = $this->getDataGenerator()->create_course();

        // Créer un quiz
        $quiz = $this->getDataGenerator()->create_module('quiz', array('course' => $course->id));

        // Créer un utilisateur
        $user = $this->getDataGenerator()->create_user();

        // Créer une tentative de quiz
        $attempt = $this->getDataGenerator()->get_plugin_generator('mod_quiz')->create_attempt($quiz->id, $user->id);

        // Créer un utilisateur avec les permissions nécessaires
        $teacher = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($teacher->id, $course->id, 'editingteacher');

        // Se connecter en tant que professeur
        $this->setUser($teacher);

        // Appeler la fonction avec la nouvelle structure de paramètres
        $result = external::search_attempts(
            ['quizid'], // key
            [$quiz->id] // value
        );

        // Vérifier le résultat
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals($attempt->id, $result[0]['attemptid']);
        $this->assertEquals($user->id, $result[0]['userid']);
    }

    /**
     * Test la fonction search_attempts avec des timestamps
     */
    public function test_search_attempts_with_timestamps() {
        $this->resetAfterTest(true);

        // Créer un cours
        $course = $this->getDataGenerator()->create_course();

        // Créer un quiz
        $quiz = $this->getDataGenerator()->create_module('quiz', array('course' => $course->id));

        // Créer un utilisateur
        $user = $this->getDataGenerator()->create_user();

        // Créer une tentative de quiz avec des timestamps spécifiques
        $timestart = time() - 3600;
        $timefinish = time();
        $attempt = $this->getDataGenerator()->get_plugin_generator('mod_quiz')->create_attempt(
            $quiz->id,
            $user->id,
            array('timestart' => $timestart, 'timefinish' => $timefinish)
        );

        // Créer un utilisateur avec les permissions nécessaires
        $teacher = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($teacher->id, $course->id, 'editingteacher');

        // Se connecter en tant que professeur
        $this->setUser($teacher);

        // Appeler la fonction avec la nouvelle structure de paramètres
        $result = external::search_attempts(
            ['quizid', 'before_timestamp', 'after_timestamp'], // key
            [$quiz->id, $timefinish + 3600, $timestart - 3600] // value
        );

        // Vérifier le résultat
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals($attempt->id, $result[0]['attemptid']);
        $this->assertEquals($timestart, $result[0]['timing']['timestart']);
        $this->assertEquals($timefinish, $result[0]['timing']['timefinish']);
    }

    /**
     * Test la fonction search_attempts avec des tableaux de tailles différentes
     */
    public function test_search_attempts_with_mismatched_arrays() {
        $this->resetAfterTest(true);

        // Créer un cours et un quiz
        $course = $this->getDataGenerator()->create_course();
        $quiz = $this->getDataGenerator()->create_module('quiz', array('course' => $course->id));

        // Créer un utilisateur avec les permissions nécessaires
        $teacher = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($teacher->id, $course->id, 'editingteacher');
        $this->setUser($teacher);

        // Vérifier que l'exception est levée quand les tableaux ont des tailles différentes
        $this->expectException(\moodle_exception::class);
        external::search_attempts(
            ['quizid', 'before_timestamp'], // key
            [$quiz->id] // value (taille différente)
        );
    }

    /**
     * Test la fonction search_attempts sans quizid
     */
    public function test_search_attempts_without_quizid() {
        $this->resetAfterTest(true);

        // Créer un utilisateur avec les permissions nécessaires
        $teacher = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($teacher->id, 1, 'editingteacher');
        $this->setUser($teacher);

        // Vérifier que l'exception est levée quand quizid n'est pas fourni
        $this->expectException(\moodle_exception::class);
        external::search_attempts(
            ['before_timestamp'], // key (pas de quizid)
            [1234567890] // value
        );
    }

    /**
     * Test la fonction search_attempts sans permission
     */
    public function test_search_attempts_without_permission() {
        $this->resetAfterTest(true);

        // Créer un cours
        $course = $this->getDataGenerator()->create_course();

        // Créer un quiz
        $quiz = $this->getDataGenerator()->create_module('quiz', array('course' => $course->id));

        // Créer un utilisateur sans permission
        $user = $this->getDataGenerator()->create_user();

        // Se connecter en tant qu'utilisateur sans permission
        $this->setUser($user);

        // Vérifier que l'exception est levée
        $this->expectException(\moodle_exception::class);
        external::search_attempts(
            ['quizid'], // key
            [$quiz->id] // value
        );
    }

    /**
     * Test la fonction search_attempts avec un quiz inexistant
     */
    public function test_search_attempts_with_nonexistent_quiz() {
        $this->resetAfterTest(true);

        // Créer un utilisateur avec les permissions nécessaires
        $teacher = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($teacher->id, 1, 'editingteacher');

        // Se connecter en tant que professeur
        $this->setUser($teacher);

        // Vérifier que l'exception est levée
        $this->expectException(\moodle_exception::class);
        external::search_attempts(
            ['quizid'], // key
            [999999] // value (quiz inexistant)
        );
    }
} 