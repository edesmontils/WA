<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of LogootAnalyser
 *
 * @author emmanueldesmontils
 */
class LogootAnalyser {
    
    
    public static function main($param) {
        //frwiki-head.xml frwiki-20110409-pages-meta-history.xml
        if (isset($param['w']) && isset($param['l'])) {
            $ex = new Extractor($param['w'], $param['l']);
            $ex->run();
        }
        else
            echo "php Extractor.php -w wiki_dump.xml -l WA_list.xml\n";
    }

}

LogootAnalyser::main(getopt("d:l:"));

?>
