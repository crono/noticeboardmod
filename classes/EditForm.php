<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
if(!defined('DOKU_INC')) die();

if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');

/* imports */
require_once(DOKU_PLUGIN."noticeboard/classes/Notice.php");
require_once(DOKU_PLUGIN."noticeboard/classes/NoticeList.php");

/**
 * Description of EditForm
 *
 * @author zatomik
 */
class helper_plugin_noticeboard_EditForm extends DokuWiki_Plugin{

    private $Name;
    private $category;
    private $Place;
	private $color; //DK
    private $startDate;
    private $startTime;
    private $endDate;
    private $endTime;
    private $deadline;
    private $parentId;


    private $EMName;
    private $EMStartDate;
    private $EMEndDate;
    private $EMStartTime;
    private $EMEndTime;
    private $EMDeadline;
    

    function EditForm(){
       
    }



    function setEMName($text){
        $this->EMName = $text;
    }

    function setEMStartDate($text){
        $this->EMStartDate = $text;
    }

    function setEMEndDate($text){
        $this->EMEndDate = $text;
    }

    function setEMStartTime($text){
        $this->EMStartTime = $text;
    }

    function setEMEndTime($text){
        $this->EMEndTime = $text;
    }

    function setEMDeadline($text){
        $this->EMDeadline = $text;
    }


    function getForm(){
        Global $ID;
        $data;

        $noticeList = new helper_plugin_noticeboard_NoticeList($ID);
        $notice = $noticeList->getNoticeById($ID);        
        if($notice){
            $this->Name = $notice->getName();
            $this->category = $notice->getCategory();
            $this->Place = $notice->getPlace();
			$this->color = $notice->getColor(); //DK
            if($notice->getDeadline()){
                $this->deadline = date("d.m.Y",$notice->getDeadline());
            }            
            $this->startDate = date("d.m.Y",$notice->getStartTime());
            if($notice->hasStartTime()){
                $this->startTime = date("H:i",$notice->getStartTime());
            }
            if($notice->getHasEnd()){
                $this->endDate = date("d.m.Y",$notice->getEndTime());
            }
            if($notice->hasEndTime()){
                $this->endTime = date("H:i",$notice->getEndTime());
            }
            $this->parentId = $notice->getParentId();

        }
        
        if($_REQUEST['noticeboard_category']){
            $this->category = $_REQUEST['noticeboard_category'];
        }
        if($_REQUEST['noticeboard_name']){
            $this->Name = $_REQUEST['noticeboard_name'];
        }
        if($_REQUEST['noticeboard_place']){
            $this->Place = $_REQUEST['noticeboard_place'];
        }
        if($_REQUEST['noticeboard_color']){ //DK
            $this->color = $_REQUEST['noticeboard_color'];
        }
        if($_REQUEST['noticeboard_start_date']){
            $this->startDate = $_REQUEST['noticeboard_start_date'];
        }
        if($_REQUEST['noticeboard_start_time']){
            $this->startTime = $_REQUEST['noticeboard_start_time'];
        }
        if($_REQUEST['noticeboard_end_date']){
            $this->endDate = $_REQUEST['noticeboard_end_date'];
        }
        if($_REQUEST['noticeboard_end_time']){
            $this->endTime = $_REQUEST['noticeboard_end_time'];
        }
        if($_REQUEST['noticeboard_deadline']){
            $this->deadline = $_REQUEST['noticeboard_deadline'];
        }

        
        $data .= "<input type='hidden' name='noticeboard_parrentId' value='".$this->parentId."' />";
        $data .= "<div class='noticeboard-editForm'>";
        $data .= "<p class='noticeboard-editMessage'>".$this->getLang('noticeForm')."</p>";
        $data .= "<p><small>".$this->getLang('noticeForm2')."</small></p>";
        $data .= "<p class='category'>".$this->getLang('category').":</p>
                  <input type='radio' onclick='noticeboard_checkCategory()' id='noticeboard_category1' name='noticeboard_category' value='meeting' ";
        $data .= (!$this->category || $this->category == "meeting") ? ("checked") : ("");
        $data .= " /><label for='noticeboard_category1' class='line'>".$this->getLang('meeting')."</label><br />
                 <input type='radio' onclick='noticeboard_checkCategory()' id='noticeboard_category2' name='noticeboard_category' value='event'";
        $data .= ($this->category == "event") ? ("checked") : ("");
        $data .= " /><label for='noticeboard_category2' class='line'>".$this->getLang('event')."</label><br />
                 <input type='radio' onclick='noticeboard_checkCategory()' id='noticeboard_category3' name='noticeboard_category' value='conference' ";
        $data .= ($this->category == "conference") ? ("checked") : ("");
        $data .= " /><label for='noticeboard_category3' class='line'>".$this->getLang('conference')."</label>";
        $data .= "<label for='noticeboard_name'>".$this->getLang('noticeName').":*</label>";
       
        $data .= "<input type='text' name='noticeboard_name' id='noticeboard_name' value='".$this->Name."' />";
        if($this->EMName){
            $data .= " <span class='noticeboard-red'> ".$this->EMName."</span><br />";
        }

        $data .= "<br>";
        $data .= "<label for='noticeboard_place'>".$this->getLang('place').":</label>";
        $data .= "<input type='text' id='noticeboard_place' name='noticeboard_place' value='".$this->Place."' />";

		//DK color
		$colors = $this->getConf('availableColors');
		$color_names = $this->getLang('availableColorNames');
		
		$data .= "<label for='noticeboard_color'>".$this->getLang('color').":</label>"; 
		
		//this is color selection in formEdit while editing or creating a new notice.
		$data .= '<select id="noticeboard_color" name="noticeboard_color" size="1">' ;
		$data .= '<option value="">---</option>'; //??
		foreach ($colors as $current_color) {
			$bgclr = ' style="background-color:' . $current_color . ';" ';
			$data .= '<option value="' . $current_color . '" ' . $bgclr . (($current_color == $this->color)?' selected ':'') . '>' . $color_names[$current_color] . '</option>'; //plus selected!
		}
		$data .= '</select>';
       
		/*
		$data .= "<input type='text' id='noticeboard_color' name='noticeboard_color' value='".$this->color."' />";
		*/
		
        $data .= "<br><table border='0'>";
        $data .= "<tr><td><label for='noticeboard_start_date'>".$this->getLang('startDate').":*<br /><small>".$this->getLang('dateFormat')."</small></label>";
        $data .= "<input type='text' id='noticeboard_start_date' name='noticeboard_start_date' value='".$this->startDate."' /></td>";

        $data .= "<td>
                <a href='javascript:noticeboard_addStartTime();'
                id='noticeboard_addStartTimeButton'><img src='lib/plugins/noticeboard/images/add.png' /> ".$this->getLang('addTime')."</a>";
        $data .= "<div id='noticeboard_addStartTime'>
                <label for='noticeboard_StartTime'>".$this->getLang('startTime').": <a href='javascript:noticeboard_deleteStartTime();'
                id='noticeboard_deleteStartTimeButton'><img src='lib/plugins/noticeboard/images/delete.png' /> ".$this->getLang('delete')." </a><br />
                <small>".$this->getLang('timeFormat')."</small></label>
                <input type='text' id='noticeboard_StartTime' name='noticeboard_start_time' value='".$this->startTime."' />
                </div></td></tr><tr></table>";
        if($this->EMStartDate){
            $data .= "<span class='noticeboard-red'>".$this->EMStartDate."</span>";
        }else if($this->EMStartTime){
            $data .= "<span class='noticeboard-red'>".$this->EMStartTime."</span>";
        }

        $data .= "<table><tr><td><label for='noticeboard_end_date'>".$this->getLang('endDate').":<br /><small>".$this->getLang('dateFormat')."</small></label>";
        $data .= "<input type='text' id='noticeboard_end_date' name='noticeboard_end_date' value='".$this->endDate."' /></td>";

        $data .= "<td>
                <a href='javascript:noticeboard_addEndTime();'
                id='noticeboard_addEndTimeButton'><img src='lib/plugins/noticeboard/images/add.png' /> ".$this->getLang('addTime')."</a>";
        $data .= "<div id='noticeboard_addEndTime'>
                <label for='noticeboard_EndTime'>".$this->getLang('endTime').": <a href='javascript:noticeboard_deleteEndTime();'
                id='noticeboard_deleteEndTimeButton'><img src='lib/plugins/noticeboard/images/delete.png' /> ".$this->getLang('delete')."</a><br />
                <small>".$this->getLang('timeFormat')."</small></label>
                <input type='text' id='noticeboard_EndTime' name='noticeboard_end_time' value='".$this->endTime."' />
                </div></td></tr></table>";

        if($this->EMEndDate){
            $data .= "<span class='noticeboard-red'>".$this->EMEndDate."</span>";
        }else if($this->EMEndTime){
            $data .= "<span class='noticeboard-red'>".$this->EMEndTime."</span>";
        }


    
        $data .= "<div id='noticeboard_deadlineDiv'><label for='noticeboard_deadline'>".$this->getLang('deadline').":</label>";
        $data .= "<input type='text' id='noticeboard_deadline' name='noticeboard_deadline' value='".$this->deadline."' /><br />";
         if($this->EMDeadline){
            $data .= " <span class='noticeboard-red'> ".$this->EMDeadline."</span><br />";
        }
        $data .= "</div></div>";
        return $data;
    }

}
?>
