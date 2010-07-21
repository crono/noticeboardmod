<?php
/**
 * Noticeboard Plugin
 *
 * @author  Zaruba Tomas <zatomik@gmail.com>
 */

// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');

/**
 * ArrayList class
 *
 * @author Mike Lang - icurtain.co.uk
 */
class helper_plugin_noticeboard_ArrayList{
  //Copyright Mike Lang - icurtain.co.uk - please retain this header and give credit if used.
 //This is a little class I've written to make my life easier and a little more like Java
 //for those occasions when i just can't afford Java hosting
 //It doesnt work the same as a Java arrayList.. it's just a high level aproximation of it
 //if you have any comments or suggestions for improving or changing this class feel free to mail me
 //mike [at] bluemedia dot co dot uk

    //ARRAY LIST CLASS STARTS COUNTING AT 0!!!!!
    private $arrayList = array();
    private $pointer = 0;


    public function getPointer(){
       return $this->pointer;
    }

    public function setPointer($p){
       $this->pointer = $p;
    }

    public function setArrayList($array){
        $this->arrayList = $array;
    }

    public function add($item){
       //$this->arrayList[sizeof($this->arrayList)] = $item;
       array_push($this->arrayList, $item);
    }

    public function addAtPos($position, $item){
       if($position < count($this->arrayList) && $position >= 0)
       {
       $this->add($item);
       $this->shift(count($this->arrayList)-1, $position);
       }
       else
       {
       throw new Exception('List out of bounds');
       }
    }

    public function getList(){
       return $this->arrayList;
    }

    public function hasValue(){
       if(isset($this->arrayList[$this->pointer]))
          {
             return true;
          }
       else
          {
             return false;
          }
     }

     public function hasNext(){
       if($this->pointer <= count($this->arrayList)-1)
          {
             return true;
          }
       else
          {
             return false;
          }
     }


    public function next(){
       if(isset($this->arrayList[$this->pointer]))
       {
          //return $this->arrayList[($this->pointer++)-1] = $value;
       $this->pointer++;
          return($this->arrayList[$this->pointer-1]);
       }
       else
       {
          return null;
       }
       }

   

  

    public function remove($item){
       if(array_key_exists($item, $this->arrayList)){
          unset($this->arrayList[$item]);
       }
       else
       {
       throw new Exception('key not found');
       }
    }

    public function addArray($array){
     foreach ($array as $item) {
          $this->add($item);
             }
    }

       public function size(){
       return count($this->arrayList);
    }

   

    public function end(){
       $this->pointer = count($this->arrayList) -1;
    }

 }

?>
