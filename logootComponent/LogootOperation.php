<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of LogootOperation
 *
 * @author emmanuel Desmontils
 */
class LogootOperation {
    protected $mLogootPosition;
    protected $mLineContent;
    protected $lineNb;
    
    const UNKNOWN=-1;
    const INSERT=0;
    const DELETE=1;

    public function __construct($position, $content, $line = -1) {
        $this->setLogootPosition($position);
        $this->setLineContent($content);
        $this->lineNb = $line;
    }

    public function  __call($name, $arguments) {
        wfDebugLog('p2p', $this->clock . ' - function unknown '.$name." / ".$arguments);
        exit();
    }

    public function  __get($name) {
         wfDebugLog('p2p', $this->clock . ' - field unknown '.$name);
         exit();
    }

    public function  __set($name, $value) {
        wfDebugLog('p2p', $this->clock . ' - field unknown '.$name." / ".$value);
        exit();
    }

    public function getLogootPosition(){
        return $this->mLogootPosition;
    }

    public function getLineContent(){
        return $this->mLineContent;
    }

    public function setLogootPosition(LogootPosition $position){
        $this->mLogootPosition = $position;
    }

    public function setLineContent($content) {
        $this->mLineContent = $content;
    }

    public function  __toString() {
        $res = "Logoot Operation ".get_class($this)."\n\t Pos :".$this->mLogootPosition."\n\t Content : '".$this->mLineContent."'\n";
        return $res;
    }
    
    public function toXMLWriter(XMLWriter $writer) {
        $writer->startElement(get_class($this));
        $writer->writeAttribute('line', $this->lineNb);
        $writer->writeAttribute('txt', $this->mLineContent);
        $writer->writeAttribute('id', $this->mLogootPosition);
        $writer->endElement();
    }

    public function type() {
        return LogootOperation::UNKNOWN;
    }

    public function __clone() {
        return new LogootOperation(clone $this->mLogootPosition, $this->mLineContent, $this->lineNb);
    }
}
?>
