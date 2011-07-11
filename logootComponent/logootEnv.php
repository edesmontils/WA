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

    const LOGOOTMODE_STD = 0;
    const LOGOOTMODE_PLS = 1;

    public static function getInstance() {
        return parent::getInstance(__CLASS__);
    }

    protected function __construct() {
        parent::__construct();
        $this->set();
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
        return $this->session_nim;
    }

    public function getSession_max() {
        return $this->session_max;
    }

    protected function setIdValues($digit) {
        $this->digit = $digit;
        $this->int_max = (integer) pow(10, $digit);
        $this->int_min = 0;
        $this->base = $this->int_max - $this->int_min;
    }

    public function set($digit = 2, $mode = logootEnv::LOGOOTMODE_STD) {
        $this->setIdValues($digit);

        $this->clock_min = "0";
        $this->clock_max = "100000000000000000000000";

        $this->session_min = "0";
        $this->session_max = "FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF"; //.CLOCK_MAX);
        //050F550EB44F6DE53333AE460EE85396

        $this->mode = $mode; //logootEnv::LOGOOTMODE_PLS ou logootEnv::LOGOOTMODE_STD
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

}

?>
