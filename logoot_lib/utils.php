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

    private static $utils_clock = 0;

    public static function toFileName($title) {
        return str_replace(
                array(" ", "(", ")", "'", "/", ":", "&"), 
                array("_und_", "_ope_", "_clo_", "_cot_", "_slh_", "_ns_", "_amp_"), 
                $title);
    }

    public static function fromFileName($title) {
        return str_replace(
                array("_und_", "_ope_", "_clo_", "_cot_", "_slh_", "_ns_", "_amp_"), 
                array(" ", "(", ")", "'", "/", ":", "&"), 
                $title);
    }
    
    public static function toAttribute($att) {
        return str_replace(
                array("'", '"',"&"), 
                array("&apos;","&quot;", "&amp;"), 
                $att);
    }

     public static function fromAttribute($att) {
        return str_replace(
                array("&apos;","&quot;", "&amp;"),
                array("'", '"',"&"),  
                $att);
    }
    
    public static function isIP($ip) {
        return preg_match("/([0-9]{1,3}\.){3}[0-9]{1,3}/i", $ip);
    }

    static function getNextClock() {

        utils::$utils_clock +=1;
        return utils::$utils_clock;
    }

    static function getClock() {
        return utils::$utils_clock;
    }

}

function wfDebugLog($type, $message) {
    global $debug;
    if ($debug)
        echo '-- Debug Log --> Type : ' . $type . "\n\t" . $message . "\n";
}

?>
