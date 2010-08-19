<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

if(!defined('DOKU_INC')) die();

if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');

require_once(DOKU_PLUGIN."noticeboard/classes/ArrayList.php");

/**
 * Description of NoticeList
 *
 * @author Tomas Zaruba
 */
class helper_plugin_noticeboard_NoticeList extends DokuWiki_Syntax_Plugin {

    private $list;
    private $NoticeboardId;
    public $hasPrevious;    //previous items in list
    public $hasNext;        //next items in list
    public $start;
    public $end;
    private $categoryFilter;
    private $timeFilter;
    private $sortFilter;
    private $sortOrder; // 0= desc 1=asc



    public function hasPrevious(){
        return $this->hasPrevious();
    }

     public function hasNext(){
        return $this->hasNext();
    }

    public function setCategoryFilter($value){
        $this->categoryFilter = $value;
    }

    public function setTimeFilter($value){
        $this->timeFilter = $value;
    }

    public function setSortFilter($value){
        $this->sortFilter = $value;
    }

    public function setSortOrder($value){
        $this->sortOrder = $value;
    }

    function helper_plugin_noticeboard_NoticeList($id){
        $this->list = new helper_plugin_noticeboard_ArrayList();
        $this->noticeboardId = $id;
        
    }

    public static function noticeIdExist($id){
        $fileNoticeboards = metaFN('','.noticeboard');


        if (!@file_exists($fileNoticeboards)){           
            return false;
        }else{
            $array = unserialize(io_readFile($fileNoticeboards, false));           
            if(array_key_exists($id,$array)){
                return true;
            }else{
            
                return false;
            }
        }
    }


    public function addNotice($notice){
        $file = metaFN($this->noticeboardId, '.noticeboard');
        $fileNoticeboards = metaFN('','.noticeboard');


        if (!@file_exists($fileNoticeboards)){//insert noticeboard page to list of known noticeboards
            $array[strtolower($notice->getId())] = $this->noticeboardId;
            io_saveFile($fileNoticeboards, serialize($array));
        }else{
            $array = unserialize(io_readFile($fileNoticeboards, false));
            if(!array_key_exists($notice->getId(),$array)){
                $array[strtolower($notice->getId())] = $this->noticeboardId;
                io_saveFile($fileNoticeboards, serialize($array));
            }
        }

        if (!@file_exists($file)){//make new file, insert one notice
            $this->list->add($notice);
            io_saveFile($file, serialize($this->list));
        }else{
            $this->list = unserialize(io_readFile($file, false));
            while($this->list->hasNext()){
                $next = $this->list->next();               
                if($notice->getId() == $next->getId()){                    
                    $array = $this->list->getList();
                    $array[$this->list->getPointer()-1] = $notice;
                    $this->list->setArrayList($array);                    
                    $i = 1;
                    break;
                }
            }
            $this->list->setPointer(0);
            if($i != 1){
                $this->list->add($notice);
            }
            io_saveFile($file, serialize($this->list));
        }        
    }

    public function deleteNotice($noticeId){
        $file = metaFN($this->noticeboardId, '.noticeboard');
        $fileNoticeboards = metaFN('','.noticeboard');

 
        if (@file_exists($fileNoticeboards)){//delete from notice list
            $array  = unserialize(io_readFile($fileNoticeboards, false));         
            unset($array[$noticeId]);
            //array_unshift($array, array_shift ($array));
            io_saveFile($fileNoticeboards, serialize($array));
        }

        if (@file_exists($file)){//delete notice
            $this->list = unserialize(io_readFile($file, false));
            //$array = $array();
            while($this->list->hasNext()){
                $next = $this->list->next();
                if($noticeId == $next->getId()){
                    $array = $this->list->getList();                   
                    if(count($array) == 1){
                        $array = array();                        
                    }else{
                        unset($array[$this->list->getPointer()-1]);
                        array_unshift ($array, array_shift ($array));
                    }                    
                    $this->list->setArrayList($array);                
                    break;
                }
            }
            $this->list->setPointer(0);  
            io_saveFile($file, serialize($this->list));
			//io_saveFile($file, '');
			//$fn = wikiFN($this->noticeboardId);
			//io_saveFile($fn,'');
        }
    }

    public function getNoticeAtDay($time){
        
        $file = metaFN($this->noticeboardId, '.noticeboard');
        if (!@file_exists($file)){
            return false;
        }
        // load data
        if (@file_exists($file)) {
            $returnList = unserialize(io_readFile($file, false));
        }
        $arrayGet = $returnList->getList();
        $array = array();
        $startTime = $time;
        
        $endTime = $time + 86400;//all day
        for($i = 0; $i < count($arrayGet); $i++){            
            $notice = $arrayGet[$i];            
                if($notice->getDeadline() >= $startTime && $notice->getDeadline() < $endTime){
                    $notice->setHasStartTime(false);
                    echo $notice->hasStartTime();
                    array_push($array, $notice);
                    echo $array[0]->hasStartTime();
                }else if($notice->getStartTime() >= $startTime && $notice->getStartTime() < $endTime){

                    array_push($array, $notice);
                }else if($notice->getEndTime() >= $startTime && $notice->getEndTime() < $endTime){

                    $notice->setHasStartTime(0);
                    array_push($array, $notice);
                }else if(($notice->getHasEnd() && $notice->getEndTime() > $startTime)&& $notice->getStartTime() < $startTime  ){

                    $notice->setHasStartTime(0);
                    array_push($array, $notice);
                }
            
        }
        $array = $this->selectCategory($array); //select categories
        $array = $this->selectTime($array); // select time (future - past - all)
        $array = $this->sortList($array);
        $returnList->setArrayList($array);

        return $returnList;
    }

    public function getNoticeList($start,$end){
        $this->start = $start;       
        $this->end = $end;
        $file = metaFN($this->noticeboardId, '.noticeboard');
        if (!@file_exists($file)){
            return false;
        }
        // load data
        if (@file_exists($file)) {
            $returnList = unserialize(io_readFile($file, false));
        }

        $array = $returnList->getList();

        $array = $this->selectCategory($array); //select categories
        $array = $this->selectTime($array); // select time (future - past - all)

        $array = $this->sortList($array);

        for($i = $start;$i<$end && $i<count($array);$i++){
            $arrayNew[$i-$start] = $array[$i];
        }
        $returnList->setArrayList($arrayNew);
        
        if($start > 0){
            $this->hasPrevious = true;
        }else{
            $this->hasPrevious = false;
        }
        
        if($end < count($array)){
            $this->hasNext = true;
        }else{
            $this->hasNext = false;
        }

        return $returnList;

    }


    private function selectTime($array){
        if($this->timeFilter ==3){
            return $array;      //show all time - nothing to filter
        }
        $currentTime = mktime(0,0,0,date("n"),date("j"),date("Y"));
        //if past show all past + today
        $returnArray = array();
        if($this->timeFilter == 2){
            
            for($i = 0;$i<count($array);$i++){
                $notice = $array[$i];
                if($notice->getStartTime() < $currentTime + 86400){ // all day
                    array_push($returnArray, $notice); 
                }
            }
        //if future show all future + today
        }else{            
            for($i = 0;$i<count($array);$i++){
                $notice = $array[$i];                
                if($notice->getStartTime() >= $currentTime){ // all day
                    array_push($returnArray, $notice);
                }
            }
        }
        return $returnArray;
        
    }

    private function selectCategory($array){       
        if($this->categoryFilter > 7){
            return $array; //nothing to filter ->show all
        }
        $returnArray = array();
        for($i = 0;$i<count($array);$i++){
            $cat = $this->categoryFilter;
            $notice = $array[$i];

            if($cat > 3){
                if($notice->getCategory() == 'meeting'){
                    array_push($returnArray, $notice);                   
                }
                $cat -= 4;
            }
            if($cat > 1){
                if($notice->getCategory() == 'event'){
                    array_push($returnArray, $notice);                    
                }
                $cat -= 2;
            }
            if($cat == 1){
                if($notice->getCategory() == 'conference'){
                    array_push($returnArray, $notice);                    
                }
            }            
        }
        return $returnArray;
    }

    private function sortList($array){
        Global $ID;
        
        $sortArray = array();      
        $returnArray = array();
        if(!$array){
            return $array;
        }
        if(!$this->sortFilter || $this->sortFilter == 1){           
            for($i = 0; $i<count($array);$i++){                
                $sortArray[$array[$i]->getId()] = $array[$i]->getStartTime();
            }
            if($this->sortOrder){
                arsort($sortArray);
            }else{
                asort($sortArray);
            }
            reset($sortArray);
            for($j = 0; $j<count($sortArray);$j++){
                $notice = $this->getNoticeById(key($sortArray));
                next($sortArray);
                $returnArray[$j] = $notice;
            }
        }else if($this->sortFilter == 2){
            for($i = 0; $i<count($array);$i++){
                if($array[$i]->getDeadline()){
                    $sortArray[$array[$i]->getId()] = $array[$i]->getDeadline();
                }
            }
            if($this->sortOrder){
                arsort($sortArray);
            }else{
                asort($sortArray);
            }
            reset($sortArray);
            for($j = 0; $j<count($sortArray);$j++){               
                $notice = $this->getNoticeById(key($sortArray));
                next($sortArray);
                $returnArray[$j] = $notice;
            }
        }else if($this->sortFilter == 3){
            for($i = 0; $i<count($array);$i++){
                $sortArray[$array[$i]->getId()] = $array[$i]->getName();
            }
            if($this->sortOrder){
                arsort($sortArray);
            }else{
                asort($sortArray);
            }
            reset($sortArray);
            for($j = 0; $j<count($sortArray);$j++){
                $notice = $this->getNoticeById(key($sortArray));
                next($sortArray);
                $returnArray[$j] = $notice;
            }
        }else if($this->sortFilter == 4){
            for($i = 0; $i<count($array);$i++){
                $sortArray[$array[$i]->getId()] = $array[$i]->getPlace();
            }
            if($this->sortOrder){
                arsort($sortArray);
            }else{
                asort($sortArray);
            }
            reset($sortArray);
            for($j = 0; $j<count($sortArray);$j++){              
                $notice = $this->getNoticeById(key($sortArray));
                next($sortArray);
                $returnArray[$j] = $notice;
            }
        }
        return $returnArray;
    }


    public function getNoticeById($id){
        $f = metaFN('', '.noticeboard');
        $array = unserialize(io_readFile($f, false));
      
        $parentId = $array[$id];
       
        if(!$parentId){
            return false;
        }
        $file = metaFN($parentId, '.noticeboard');
        if (!@file_exists($file)){
            return false;
        }
        // load data
        if (@file_exists($file)) {
            $this->list = unserialize(io_readFile($file, false));
            while($this->list->hasNext()){               
                $item = $this->list->next();               
                if($item->getId() == $id){                  
                    $notice = $item;                    
                }
            }

            return $notice;
        }
    }



}
?>
