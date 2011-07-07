<?php

header('Content-type: text/plain');

function __autoload($classe) {
    require_once './logootComponent/' . $classe . '.php';
}

$debug = true;

require_once './logootModel/manager.php';
require_once './logootModel/boModel.php';
require_once './logootModel/boModelPlus.php';
require_once './logootComponent/Math/BigInteger.php';
require_once './utils.php';
require_once './WikipediaReader.php';

if (!defined('DIGIT')) {
    define('DIGIT', 2);
}

if (!defined('INT_MAX')) {
    define('INT_MAX', (integer) pow(10, DIGIT));
}

if (!defined('INT_MIN')) {
    define('INT_MIN', 0);
}

if (!defined('BASE')) {
    define('BASE', (integer) (INT_MAX - INT_MIN));
}

if (!defined('CLOCK_MAX')) {
    define('CLOCK_MAX', "100000000000000000000000");
}

if (!defined('CLOCK_MIN')) {
    define('CLOCK_MIN', "0");
}

if (!defined('SESSION_MAX')) {
    define('SESSION_MAX', "FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF"); //.CLOCK_MAX);
    //050F550EB44F6DE53333AE460EE85396
}

if (!defined('SESSION_MIN')) {
    define('SESSION_MIN', "0");
}

if (!defined('BOUNDARY')) {
    define('BOUNDARY', (integer) pow(10, DIGIT / 2));
}

if (!defined('LOGOOTMODE')) {
    //define('LOGOOTMODE', 'STD');
    define('LOGOOTMODE', 'PLS');
}

function wfDebugLog($type, $message) {
    global $debug;
    if ($debug)
        echo '-- Debug Log --> Type : ' . $type . "\n\t" . $message . "\n";
}

class PageReader extends WikipediaReader {

    public function __construct($page) {
        parent::__construct($page);
    }

    public function __destruct() {
        parent::__destruct();
    }

}

/**
 * Description of LogootAnalyser
 *
 * @author emmanueldesmontils
 */
class LogootAnalyser {

    protected $logoot;
    protected $liste_pages;
    protected $rep;

    public function __construct($rep, $liste) {
        $this->rep = $rep;
        $logoot = new logootPlusEngine(NULL, 3);
        $logoot = manager::getNewEngine(manager::loadModel(0), 3);

        $this->liste_pages = simplexml_load_file($liste);
    }

    public function run() {
        $file = $this->rep.'/Algèbre_und_générale'.'.xml';
        $pr = new PageReader($file);
        
    }

    public static function main($param) {
        if (isset($param['d']) && isset($param['l'])) {
            $la = new LogootAnalyser($param['d'], $param['l']);
            $la->run();
        }
        else
            echo "php LogootAnalyser.php -d wiki_corpus_repository -l WA_list.xml\n";
    }

}

LogootAnalyser::main(getopt("d:l:"));
?>
