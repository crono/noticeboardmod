<?php
/**
 * Noticeboard Plugin
 *
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author  Zaruba Tomas <zatomik@gmail.com>
 */

// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');

/**
 * Notice class
 *
 * @author Tomas Zaruba
 */
class helper_plugin_noticeboard_Notice extends DokuWiki_Plugin{

    private $noticeId;
    private $name;
    private $place;
    private $startTime;
    private $hasStartTime;
    private $endTime;
    private $hasEnd;
    private $hasEndTime;
    private $deadline;
    private $category;
    private $parentId;


    


    function helper_plugin_noticeboard_Notice(){
        
    }

    public function setId($id){
        $this->noticeId = $id;
    }

    public function getId(){
        return $this->noticeId;
    }

    public function setParentId($id){
        $this->parentId = $id;
    }

    public function getParentId(){
        return $this->parentId;
    }

    public function setName($name){
        $this->name = $name;
    }

    public function getName(){
        return $this->name;
    }

    public function setPlace($place){
        $this->place = $place;
    }

    public function getPlace(){
        return $this->place;
    }

    public function setStartTime($time){
        $this->startTime = $time;
    }

    public function getStartTime(){
        return $this->startTime;
    }

    public function setEndTime($time){
        $this->endTime = $time;
        $this->setHasEnd(true);
    }

    public function getEndTime(){
        return $this->endTime;
    }
    
    public function setHasEnd($boolean){
        $this->hasEnd = $boolean;
    }

    public function getHasEnd(){
        return $this->hasEnd;
    }

    public function setDeadline($date){
        $this->deadline = $date;
    }

    public function getDeadline(){
        return $this->deadline;
    }

    public function setCategory($category){
        $this->category = $category;
    }

    public function getCategory(){
        return $this->category;
    }

    public function setHasEndTime($bool){
        $this->hasEndTime = $bool;
    }

    public function hasEndTime(){
        return $this->hasEndTime;
    }

    public function setHasStartTime($bool){
        $this->hasStartTime = $bool;
    }

    public function hasStartTime(){
        return $this->hasStartTime;
    }

   
    
    

    public function load($id){
        $file = metaFN($id, '.noticeboard');

        if (!@file_exists($file)){
            return false;
        }
        // load data
        if (@file_exists($file)) {
            $object= unserialize(io_readFile($file, false));
            $this->name = $object->getName();
            return $this;
        }
    }

   
    
}
?>
