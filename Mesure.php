<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Mesure
 *
 * @author ed
 */
class MValue {

    protected $key, $value;

    public function __construct($k, $v) {
        $this->key = $k;
        $this->value = $v;
    }

    public function getKey() {
        return $this->key;
    }

    public function getValue() {
        return $this->value;
    }

    public function __toString() {
        return "$this->key / $this->value";
    }

}

class Mesure {

    protected $titre;
    protected $mins, $maxs, $others;
    protected $vol, $nb, $sum, $nb_mins, $nb_maxs, $nb_others;

    public function __construct($title, $v = 100) {
        $this->mins = array();
        $this->maxs = array();
        $this->others = array();
        $this->nb = 0.0;
        $this->sum = 0.0;
        $this->vol = $v;
        $this->titre = $title;
        $this->nb_mins = 0;
        $this->nb_maxs = 0;
        $this->nb_others = 0;
    }

    protected function view($tab) {
        echo "@";
        foreach ($tab as $obj)
            echo $obj;
        echo "@\n";
    }

    public function add($key, $value) {
        $ok = false; // Permet d'indiquer si cette mesure est gardée (pour l'instant...)
        $this->nb += 1.0;
        $this->sum += $key;
        $obj = new MValue($key, $value);
        unset($old);
        //echo "Insert $key\n Mins -----";$this->view($this->mins);//var_dump($this->mins);
        //Est-ce un minimum ?
        $trouvee = false;
        $on = true;
        $i = $this->nb_mins - 1;
        while ($on and ($i >= 0)) {
            $on = $key < $this->mins[$i]->getKey();
            if ($on) {
                $tmp = $this->mins[$i];
                $this->mins[$i] = $obj;
                $this->mins[$i + 1] = $tmp;
                $trouvee = true;
                $i -= 1;
            }
        }
        if ($trouvee) {
            $ok = true;
            if ($this->nb_mins == $this->vol) {
                $old = $this->maxs[$this->nb_mins];
                unset($this->mins[$this->nb_mins]);
            } else
                $this->nb_mins += 1;
        } else if ($this->nb_mins < $this->vol) {
            $ok = true;
            $this->mins[$this->nb_mins] = $obj;
            $this->nb_mins += 1;
        }
        //$this->view($this->mins);echo "Maxs -----";$this->view($this->maxs);//var_dump($this->maxs);
        //Est-ce un maximum ?
        $trouvee = false;
        $on = true;
        $i = $this->nb_maxs - 1;
        while ($on and ($i >= 0)) {
            $on = $key > $this->maxs[$i]->getKey();
            if ($on) {
                $tmp = $this->maxs[$i];
                $this->maxs[$i] = $obj;
                $this->maxs[$i + 1] = $tmp;
                $trouvee = true;
                $i -= 1;
            }
        }
        if ($trouvee) {
            $ok = true;
            if ($this->nb_maxs == $this->vol) {
                $old = $this->maxs[$this->nb_maxs];
                unset($this->maxs[$this->nb_maxs]);
            } else
                $this->nb_maxs += 1;
        } else if ($this->nb_maxs < $this->vol) {
            $ok = true;
            $this->maxs[$this->nb_maxs] = $obj;
            $this->nb_maxs += 1;
        }
        //$this->view($this->maxs);echo "Others -----";$this->view($this->others);

        if (!$ok)
            $old = $obj;
        //On le garde en échantillon neutre ?
        if (isset($old) && (!in_array($old, $this->maxs)) && (!in_array($old, $this->mins))
                && (mt_rand(0, 99) < 10)) {
            //echo 'rand !';
            $pos = mt_rand(0, ($this->vol - 1));
            if ((!isset($this->others[$pos])) && ($this->nb_others < $this->vol))
                $this->nb_others += 1;
            $this->others[$pos] = $old;
            $ok = true;
        }
        //$this->view($this->others);//var_dump($this->others);
        return $ok;
    }

    public function min() {
        if ($this->nb_mins > 0)
            return array($this->mins[0]->getKey(), $this->mins[0]->getValue());
        else
            return null;
    }

    public function max() {
        if ($this->nb_maxs > 0)
            return array($this->maxs[0]->getKey(), $this->maxs[0]->getValue());
        else
            return null;
    }

    public function min_key() {
        if ($this->nb > 0)
            return $this->mins[0]->getKey();
        else
            return null;
    }

    public function max_key() {
        if ($this->nb > 0)
            return $this->maxs[0]->getKey();
        else
            return null;
    }

    public function avg_key() {
        if ($this->nb > 0)
            return $this->sum / $this->nb;
        else
            return null;
    }

    public function getXMLAbstract() {
        $cdc = ' <'.$this->titre.' min="'.$this->min_key().'" avg="'.round($this->avg_key(), 2).'" max="'.$this->max_key().'"/>';
        return $cdc;
    }

    public function getMins() {
        return $this->mins;
    }

    public function getMaxs() {
        return $this->maxs;
    }

    public function getOthers() {
        return $this->others;
    }

    public function getNb() {
        return $this->nb;
    }

    public function getSum() {
        return $this->sum;
    }

    public static function main() {
        $m = new Mesure('test', 2);
        $t = array(3, 2, 9, 8, 6, 2, 1, 5);
        foreach ($t as $v) {
            $m->add($v, '');
        }
    }

}

//Mesure::main();
?>
