<?php

/**
 * Description of iCal
 *
 * @author Tomas Zaruba
 */

/**
 * Noticeboard Plugin - iCal generate
 *
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author  Zaruba Tomas <zatomik@gmail.com>
 */

// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');

require_once(DOKU_PLUGIN."noticeboard/classes/NoticeList.php");
/**
 * Rss class
 *
 * @author Tomas Zaruba
 */
class helper_plugin_noticeboard_ICal extends DokuWiki_Plugin{
    private $category;
    private $parentId;
    private $noticeList;


    public function helper_plugin_noticeboard_ICal($id){
        $this->parentId = $id;
        $this->noticeList = new helper_plugin_noticeboard_NoticeList($this->parentId);
         //set category filter
        if($_SESSION['noticeboard_list_category']){
            $this->noticeList->setCategoryFilter($_SESSION['noticeboard_list_category']);
        }else{
            $this->noticeList->setCategoryFilter(8);
        }

        //set time filter
        if($_SESSION['noticeboard_show_time']){
            $this->noticeList->setTimeFilter($_SESSION['noticeboard_show_time']);
        }else{
            $this->noticeList->setTimeFilter(1);
        }

    }

    public function generateOutput(){
        Global $conf;
        header("Content-Type: text/calendar; charset=UTF-8");
        header('Content-Disposition: attachment; filename="calendar.ics"');
        $out;
        $out .= $this->getHeader();
        $out .= $this->getBody();
        $out .= $this->getFooter();
        echo $out;
    }

    private function getHeader(){
        Global $conf;        

        $out .= "BEGIN:VCALENDAR\r\n";
        $out .= "VERSION:2.0\r\n";
        $out .= "PRODID:-//Dokuwiki//Wiki//EN\r\n";
        $out .= "CALSCALE:GREGORIAN\r\n";
        $out .= "METHOD:PUBLISH\r\n";
        //$out .= "X-WR-TIMEZONE:Europe/Moscow\r\n";
       
        return $out;

    }

    private function getBody(){
        $link = "http://".$_SERVER['SERVER_NAME'];

        $arrayList = $this->noticeList->getNoticeList(0,50);   //last 50 msges
        while(($arrayList && $arrayList->hasNext())){
            $notice = $arrayList->next();
            $out .= "BEGIN:VEVENT\r\n";
            $out .= "TRANSP:TRANSPARENT\r\n";
            $out .= "LOCATION:".$notice->getPlace()."\r\n";
            $out .= "SUMMARY:".$notice->getName()."\r\n";
            $out .= "DTSTART:".date("Ymd\THis\Z",$notice->getStartTime())."\r\n";
            if($notice->getHasEnd()){
                $out .= "DTEND:".date("Ymd\THis\Z",$notice->getEndTime())."\r\n";
            }else{
                $out .= "DTEND:".date("Ymd\THis\Z",$notice->getStartTime())."\r\n";
            }            
            $out .= "END:VEVENT\r\n";
           
            if($notice->getDeadline()){
                $out .= "BEGIN:VEVENT\r\n";
                $out .= "TRANSP:TRANSPARENT\r\n";
                $out .= "LOCATION:".$notice->getPlace()."\r\n";
                $out .= "SUMMARY:".$notice->getName()." - Deadline\r\n";
                $out .= "DTSTART:".date("Ymd\THis\Z",$notice->getDeadline())."\r\n";
                $out .= "DTEND:".date("Ymd\THis\Z",$notice->getDeadline())."\r\n";
                $out .= "END:VEVENT\r\n";
            }
        }
        return $out;
    }




    private function getFooter(){
        $out;
        $out .="END:VCALENDAR\r\n";
        return $out;
    }

}


?>
