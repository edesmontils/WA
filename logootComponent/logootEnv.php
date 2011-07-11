<?php

/**
 * Description of logootEnv
 *
 * @author emmanueldesmontils
 */
include_once('./logoot_lib/Singleton.php');

class logootEnv extends Singleton {

    private static $clock = 0;
    protected $digit;
    protected $int_min, $int_max;
    protected $base;
    protected $clock_max, $clock_min;
    protected $session_min, $session_max;
    protected $mode;

    const LOGOOTMODE_STD = 0;
    const LOGOOTMODE_PLS = 1;

    public static function getInstance() {
        return parent::getInstance(__CLASS__);
    }

    protected function __construct() {
        parent::__construct();
        $this->setIdValues(2);

        $this->clock_min = "0";
        $this->clock_max = "100000000000000000000000";

        $this->session_min = "0";
        $this->session_max = "FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF"; //.CLOCK_MAX);
        //050F550EB44F6DE53333AE460EE85396

        $this->setMode(logootEnv::LOGOOTMODE_STD); //logootEnv::LOGOOTMODE_PLS ou logootEnv::LOGOOTMODE_STD
    }

    public function getDigit() {
        return $this->digit;
    }

    public function setDigit($digit) {
        $this->setIdValues($digit);
    }

    public function getInt_min() {
        return $this->int_min;
    }

    public function getInt_max() {
        return $this->int_max;
    }

    public function getBase() {
        return $this->base;
    }

    public function getClock_max() {
        return $this->clock_max;
    }

    public function getClock_min() {
        return $this->clock_min;
    }

    public function getSession_min() {
        return $this->session_min;
    }

    public function getSession_max() {
        return $this->session_max;
    }

    public function getMode() {
        return $this->mode;
    }

    public function setMode($mode) {
        $this->mode = $mode;
    }

    protected function setIdValues($digit) {
        $this->digit = min($digit,strlen(PHP_INT_MAX)-3);
        $this->int_max = (integer) pow(10, $digit);
        $this->int_min = 0;
        $this->base = $this->int_max - $this->int_min;
    }

    public function getLPINTMINDIGIT() {
        return str_pad($this->getInt_min(), $this->getDigit(), '0', STR_PAD_LEFT);
    }

    static function getNextClock() {
        logootEnv::$clock +=1;
        return logootEnv::$clock;
    }

    static function getClock() {
        return logootEnv::$clock;
    }

    public function __call($name, $arguments) {
        echo('p2p' . ' - logootEnv function unknown ' . $name . " / " . $arguments);
        exit();
    }

    public function __get($name) {
        echo('p2p' . ' - logootEnv get field unknown ' . $name);
        exit();
    }

    public function __set($name, $value) {
        echo('p2p' . ' - logootEnv set field unknown ' . $name . " / " . $value);
        exit();
    }

}

?>
