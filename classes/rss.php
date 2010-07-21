<?php

/**
 * Description of rss
 *
 * @author Tomas Zaruba
 */

/**
 * Noticeboard Plugin - RSS GENERATE
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
class helper_plugin_noticeboard_Rss extends DokuWiki_Plugin{
    private $category;
    private $parentId;
    private $noticeList;


    public function helper_plugin_noticeboard_Rss($category,$parentId){
        $this->category = $category;
        $this->parentId = $parentId;

        $this->noticeList = new helper_plugin_noticeboard_NoticeList($this->parentId);
        $this->noticeList->setCategoryFilter($category);
        $this->noticeList->setTimeFilter(3);
        $this->noticeList->setSortFilter(1);

    }

    public function generateOutput(){
        Global $conf;
        header("Content-Type: application/xml; charset=UTF-8");
        $out;
        $out .= $this->getHeader();
        $out .= $this->getBody();
        $out .= $this->getFooter();
        echo $out;
    }

    private function getHeader(){
        Global $conf;
        $link = "http://".$_SERVER['SERVER_NAME'];

        $out  = '';
        $out .= "<?xml version='1.0' encoding='UTF-8' ?>";
        $out .= "<rss version='2.0'>";
        $out .= "<channel>";
        $out .= "<title>".$conf['title']." - ".$this->parentId."</title>";
        $out .= "<language>".$conf['lang']."</language>";
        $out .= "<description>".$this->getLang('rssDesc')."</description>";
        $out .= "<link>".$link .wl($this->parentId, array('do' => 'show'))."</link>";
        return $out;

    }

    private function getBody(){
        $link = "http://".$_SERVER['SERVER_NAME'];
        $out;
        $arrayList = $this->noticeList->getNoticeList(0,50);   //last 50 msges
        while(($arrayList && $arrayList->hasNext())){
            $notice = $arrayList->next();
            $out .= "<item>";
            $out .= "<title>".$notice->getName()."</title>";
            $out .= "<link>".$link .wl($notice->getId(), array('do' => 'show'))."</link>";
            $out .= "<description>";
            $out .= $this->getLang('startTime').": ";
            if($notice->hasStartTime()){
                $out .= date("d.m.Y H:i",$notice->getStartTime());
            }else{
                $out .= date("d.m.Y",$notice->getStartTime());
            }
            $out .= " <br />";
            $out .= $this->getLang('category').": ".$this->getLang($notice->getCategory());
            $out .= " <br />";
            if($notice->getEndTime()){
                $out .= $this->getLang('endTime').": ";
                if($notice->hasEndTime()){
                    $out .= date("d.m.Y H:i",$notice->getEndTime());
                }else{
                    $out .= date("d.m.Y",$notice->getEndTime());
                }
                $out .= " <br />";
            }
             if($notice->getDeadline()){
                 $out .= $this->getLang('deadline').": ";
                 $out .= date("d.m.Y",$notice->getDeadline());
                 $out .= " <br />";
            }

            if($notice->getPlace()){
                 $out .= $this->getLang('place').": ";
                 $out .= $notice->getPlace();
                 $out .= " <br />";
            }
            $out .= $this->getLang('moreInfo').": <a href='".$link .wl($notice->getId(), array('do' => 'show'))."'>".$notice->getName()."</a>";

            $out .= "</description>";
            $out .= "<pubDate>".date(DATE_RFC822,$notice->getStartTime())."</pubDate>";
            $out .= "</item>";

            if($notice->getDeadline()){
                $out .= "<item>";
                $out .= "<title>".$notice->getName()." Deadline</title>";
                $out .= "<link>".$link .wl($notice->getId(), array('do' => 'show'))."</link>";
                $out .= "<description>";
                $out .= $this->getLang('startTime').": ";
                if($notice->hasStartTime()){
                    $out .= date("d.m.Y H:i",$notice->getStartTime());
                }else{
                    $out .= date("d.m.Y",$notice->getStartTime());
                }
                $out .= " <br />";
                 if($notice->getDeadline()){
                     $out .= $this->getLang('deadline').": ";
                     $out .= date("d.m.Y",$notice->getDeadline());
                     $out .= "<br />";
                }
                $out .= "<a href='".$link .wl($notice->getId(), array('do' => 'show'))."'>".$this->getLang('moreInfo')."</a>";

                $out .= "</description>";
                $out .= "<pubDate>".date(DATE_RFC822,$notice->getDeadline())."</pubDate>";
                $out .= "</item>";
            }
        }
        return $out;
    }




    private function getFooter(){
        $out;
        $out .="</channel></rss>";
        return $out;
    }

}


?>
