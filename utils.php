<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Utils
 *
 * @author emmanueldesmontils
 */
class utils {

    private $utils_clock = 0;

    public static function toFileName($title) {
        return str_replace(
                array(" ", "(", ")", "'", "/", ":"), array("_und_", "_ope_", "_clo_", "_cot_", "_slh_", "_ns_"), $title);
    }

    public static function isIP($ip) {
        return preg_match("/([0-9]{1,3}\.){3}[0-9]{1,3}/i", $ip);
    }

    static function getNextClock() {
        $this->utils_clock +=1;
        return $this->utils_clock;
    }

    static function getClock() {
        return $this->utils_clock;
    }

}

?>
