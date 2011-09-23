<?php

header('Content-type: text/plain');
ini_set('memory_limit', '2048M');
require_once './logoot_lib/utils.php';

//require_once './DiffEngine.php';
function __autoload($classe) {
    require_once './' . $classe . '.php';
}

class WAStat extends WikipediaReader {

    private $annee = array("2000", "2001", "2002", "2003", "2004", "2005", "2006", "2007"
        , "2008", "2009", "2010", "2011");
    private $mois = array("01", "02", "03", "04", "05", "06", "07", "08", "09", "10", "11", "12");
    private $calendar;

    public function __construct($wiki) {
        parent::__construct($wiki);
        date_default_timezone_set('Europe/Paris');

        for ($i = 0; $i < 10; $i++) {
            for ($j = 0; $j < 12; $j++) {
                $date = $this->annee[$i] . '-' . $this->mois[$j];//echo $date;
                $this->calendar[$date] = 0;
            }
        }
        //var_dump($this->calendar);
    }

    public function __destruct() {
        parent::__destruct();
    }

    protected function openElement($element) {
        switch ($element) {
            case 'res' :
                $ok = $this->read();
                break;
            case 'page' :
                $created = $this->getAttribute('created');
                echo substr($created, 0, 7)."\n";
                $this->calendar[substr($created, 0, 7)] +=1;
                $ok = $this->read();
                break;
            default : $ok = $this->next();
        }
        return $ok;
    }

    public function run() {
        parent::run();
        //var_dump($this->calendar);
        foreach($this->calendar as $annee => $cpt) {
            echo "$annee $cpt\n";
        }
    }

    public static function main($param) {
        if (isset($param['l'])) {
            $wa = new WAStat($param['l']);
            $wa->run();
        } else {
            echo "Programme outil pour faire des stats sur les pages Wikipedia\n";
            echo "php WAStat.php -l echoWA.xml\n";
        }
    }

}

WAStat::main(getopt("l:"));
?>
