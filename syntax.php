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


require_once(DOKU_PLUGIN."noticeboard/classes/Notice.php");
require_once(DOKU_PLUGIN."noticeboard/classes/NoticeList.php");
require_once(DOKU_PLUGIN."noticeboard/classes/ArrayList.php");

/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 */
class syntax_plugin_noticeboard extends DokuWiki_Syntax_Plugin {

    private $noticeList;

    /**
     * return some info
     */
    function getInfo() {
        return array(
                'author' => 'Gina HĂ¤uĂźge, Michael Klier, Esther Brunner',
                'email'  => 'dokuwiki@chimeric.de',
                'date'   => @file_get_contents(DOKU_PLUGIN.'discussion/VERSION'),
                'name'   => 'Discussion Plugin (comments component)',
                'desc'   => 'Enables discussion features',
                'url'    => 'http://wiki.splitbrain.org/plugin:discussion',
                );
    }

    function getType() { return 'substition'; }
    function getPType() { return 'block'; }
    function getSort() { return 230; }

    /**
     * Connect pattern to lexer
     */
    function connectTo($mode) {
        if ($mode == 'base') {
            $this->Lexer->addSpecialPattern('~~NOTICEBOARD~~', $mode, 'plugin_noticeboard');
        }
    }

    /**
     * Handle the match
     */
    function handle($match, $state, $pos, &$handler) {

    }

    function render($mode, &$renderer, $status) {
        Global $ID;
        Global $INFO;
        $renderer->info['cache'] = false;
        $renderer->doc .= "<div class='noticeboard-show'>";
        $renderer->doc .= "<div class='noticeboard-modeButtons'>";
        $renderer->doc .= $this->_showModeListButton();
        $renderer->doc .= $this->_showModeCalendarButton();
        $renderer->doc .= $this->_showRSSButton();
        $renderer->doc .= $this->_showIcalButton();
        if($INFO['perm'] >= 2){
            $renderer->doc .= '<a href="' . wl($ID.":NewNotice", array('do' => 'edit','noticeboard_newnotice'=> 'true')) . '" class="button2" title="">'.$this->getLang('addEvent').'</a>';
        }
        $renderer->doc .= "</div>";
        $renderer->doc .= $this->_showFilter();
        
       
             
        if($_SESSION['noticeboard_mode']== 'list'){ //mode list            
            $renderer->doc .= $this->_showList();
        }else{
            $renderer->doc .= $this->_showCalendar();

        }
        
        
        $renderer->doc .= "</div>";
       
        return true; // do nothing -> everything is handled in action component
    }


    private function _showModeListButton(){
        Global $ID;
        $script = script();

        $out;
        $out .= "<form action='".$script."' method='post'>
        <input type='hidden' name='id' value='".$ID."' />
        <input type='hidden' name='noticeboard_mode' value='list' />
        <input type='submit' class='button' name='submit' value='".$this->getLang('list')."' /></form>";
        return $out;
    }

    private function _showModeCalendarButton(){
        Global $ID;
        $script = script();

        $out;
        $out .= "<form action='".$script."' method='post'>
        <input type='hidden' name='id' value='".$ID."' />
        <input type='hidden' name='noticeboard_mode' value='calendar' />
        <input type='submit' class='button' name='submit' value='".$this->getLang('calendar')."' /></form>";
        return $out;
    }
    private function _showRSSButton(){
        Global $ID;
        $script = script();

        $out;
        $out .= "<form action='".$script."' method='get'>
        <input type='hidden' name='id' value='".$ID."' />
        <input type='hidden' name='noticeboard_rss_category' value='";
        if($_SESSION['noticeboard_list_category']){
           $out .= $_SESSION['noticeboard_list_category'];
        }else{
           $out .= 7;
        }
        $out .= "' />
                <input type='hidden' name='parent' value='".$ID."' />
                <input type='submit' class='button' name='submit' value='".$this->getLang('getRSS')."' /></form>";
        return $out;
    }

    private function _showIcalButton(){
        Global $ID;
        $script = script();

        $out;
        $out .= "<form action='".$script."' method='get'>
        <input type='hidden' name='id' value='".$ID."' />
        <input type='hidden' name='noticeboard_get_ical' value='1' />
        <input type='submit' class='button' name='submit' value='".$this->getLang('iCal')."' /></form>";
        return $out;
    }


    private function _showFilter(){
        Global $ID;
        Global $INFO;
        $script = script();
        $out;
        $category = $_SESSION['noticeboard_list_category'];
        $timeFilter = $_SESSION['noticeboard_show_time'];
        $sortFilter = $_SESSION['noticeboard_sort'];
        $sortOrder = $_SESSION['noticeboard_sort_order'];

        $out .="<form method='post'  action='".$script."' class='noticeboard-listForm'><p><strong>".$this->getLang('category').":</strong>";
        $out .= "<label for='noticeboard_show_category_all'>".$this->getLang('all')." </label> <input type='checkbox' value='8' id='noticeboard_show_category_all' name='noticeboard_show_category_all'";
        if($category > 7 || !$category){
            $category -=8;
            $out .="checked";
        }
        $out .= "/> ";
        $out .= "<label for='noticeboard_show_category_meeting'>".$this->getLang('meeting')." </label> <input type='checkbox' value='4' id='noticeboard_show_category_meeting' name='noticeboard_show_category_meeting'";
        if($category > 3){
            $category -=4;
            $out .="checked";
        }
        $out .= "/> ";
        $out .= "<label for='noticeboard_show_category_event'>".$this->getLang('event')." </label> <input type='checkbox' value='2' id='noticeboard_show_category_event' name='noticeboard_show_category_event'";
        if($category > 1){
            $category -=2;
            $out .="checked";
        }
        $out .= "/> ";
        $out .= "<label for='noticeboard_show_category_event'>".$this->getLang('conference')." </label> <input type='checkbox' value='1' id='noticeboard_show_category_conference' name='noticeboard_show_category_conference'";
        if($category == 1){
            $out .="checked";
        }
        $out .= "/> ";
        $out .="</p><p><strong>".$this->getLang('time').":</strong>";
        $out .= "<label for='noticeboard_show_time_future'>".$this->getLang('future')." </label> <input type='radio' value='1' id='noticeboard_show_time_future' name='noticeboard_show_time'";

        if($timeFilter == 1 || !$timeFilter){
            $out .="checked";
        }
        $out .= "/> ";
        $out .= "<label for='noticeboard_show_time_past'>".$this->getLang('past')." </label> <input type='radio' value='2' id='noticeboard_show_time_past' name='noticeboard_show_time'";
        if($timeFilter == 2){
            $out .="checked";
        }
        $out .= "/> ";
        $out .= "<label for='noticeboard_show_time_all'>".$this->getLang('all')." </label> <input type='radio' value='3' id='noticeboard_show_time_all' name='noticeboard_show_time'";
        if($timeFilter == 3){
            $out .="checked";
        }
        $out .= "/> ";
        $out .="</p><input type='submit' class='button submit' value='".$this->getLang('filter')."' /><p><strong>".$this->getLang('sort').": </strong> ";
        $out .= "<select name='noticeboard_sort'>";
        $out .= "<option value='1' ";
        if(!$sortFilter || $sortFilter == 1){
            $out .="selected";
        }
        $out .= ">".$this->getLang('startDate')."</option>";
        $out .= "<option value='3' ";
        if( $sortFilter == 3){
            $out .="selected";
        }
        $out .= ">".$this->getLang('name')."</option>";
        $out .= "<option value='4' ";
        if( $sortFilter == '4'){

            $out .="selected";
        }
        $out .= ">".$this->getLang('place')." </option>";
        $out .= "<option value='2' ";
        if( $sortFilter == 2){
            $out .="selected";
        }
        $out .= ">".$this->getLang('deadline')."</option>";
        $out .= "</select> ";

        $out .= " <select name='noticeboard_sort_order'>";
        $out .= "<option value='0' ";
        if(!$sortOrder || $sortOrder == 0){
            $out .="selected";
        }
        $out .= ">".$this->getLang('desc')."</option>";
        $out .= "<option value='1' ";
        if( $sortOrder == 1){
            $out .="selected";
        }
        $out .= ">".$this->getLang('asc')."</option>";
        $out .= "</select>";
        $out .="</p>";        
        $out .="<input type='hidden' name='noticeboard_list_filter' value='1'/>
                <input type='hidden' name='id' value='".$ID."' />
                </form>";
        return $out;
    }

    private function _showList(){
        Global $ID;
        Global $INFO;
        $script = script();
        $out;
        $category = $_SESSION['noticeboard_list_category'];
        $timeFilter = $_SESSION['noticeboard_show_time'];
        $sortFilter = $_SESSION['noticeboard_sort'];
        $sortOrder = $_SESSION['noticeboard_sort_order'];
       

        $noticeList = new helper_plugin_noticeboard_NoticeList($ID);

        //set list position
        $start = ($_SESSION['noticeboard_list_start'])?$_SESSION['noticeboard_list_start']:'0';
        

        //set category filter
        if($_SESSION['noticeboard_list_category']){
            $noticeList->setCategoryFilter($_SESSION['noticeboard_list_category']);
        }else{
            $noticeList->setCategoryFilter(8);
        }

        //set time filter
        if($_SESSION['noticeboard_show_time']){
            $noticeList->setTimeFilter($_SESSION['noticeboard_show_time']);
        }else{
            $noticeList->setTimeFilter(1);
        }
        
        //set sort filter
        if($_SESSION['noticeboard_sort']){
            $noticeList->setSortFilter($_SESSION['noticeboard_sort']);
        }else{
            $noticeList->setSortFilter(1);
        }

        //set sort order
        if($_SESSION['noticeboard_sort_order']){
            $noticeList->setSortOrder($_SESSION['noticeboard_sort_order']);
        }else{
            $noticeList->setSortOrder(0);
        }

        //get notice list + print
        $noticeList;
        $arrayList = $noticeList->getNoticeList($start,$start + $this->getConf('listSize'));        
        while(($arrayList && $arrayList->hasNext()) ){
            $notice = $arrayList->next();

            $out .= "<table class='noticeboard-event' cellspacing='0'><thead><tr><th colspan='2'>";
            $out .= '<a href="' . wl($notice->getId(), array('do' => 'show')) . '" class="" title="">'.$notice->getName().'</a>';

            if($INFO['perm'] >= 2){
             
                $out .= "<form action='".$script."' method='post'>
                <input type='hidden' name='id' value='".$ID."' />
                <input type='hidden' name='noticeboard_delete' value='".$notice->getId()."' />
                <input type='submit' class='button small' name='submit' value='".$this->getLang('delete')."' /></form>";
               // $out .= '<a href="' . wl($ID, array('do' => 'show','noticeboard-delete' => $notice->getId())) . '" class="button small" title="">'.$this->getLang('delete').'</a>';
                $out .= '<a href="' . wl($notice->getId(), array('do' => 'edit')) . '" class="button small" title="">'.$this->getLang('edit').'</a>';
            }

            $out .= "</th><th width='80' class='cat'>";
            $out .= $this->getLang($notice->getCategory());
            $out .= "</th></tr></thead><tbody><tr><td class='left'>";
            $out .= "<strong>".$this->getLang('startTime').":</strong></td><td colspan='2'> ";
            if($notice->hasStartTime()){
                $out .= date("d.m.Y H:i",$notice->getStartTime());
            }else{
                $out .= date("d.m.Y",$notice->getStartTime());
            }
            $out .= "</td></tr>";
           
            if($notice->getEndTime()){
                $out .= "<tr><td class='left'><strong>".$this->getLang('endTime').":</strong></td><td colspan='2'>";
                if($notice->hasEndTime()){
                    $out .= date("d.m.Y H:i",$notice->getEndTime());
                }else{
                    $out .= date("d.m.Y",$notice->getEndTime());
                }
                $out .= "</td></tr>";
            }
            
            if($notice->getDeadline()){
                 $out .= "<tr><td class='left'><strong>".$this->getLang('deadline').":</strong></td><td colspan='2'>";
                 $out .= date("d.m.Y",$notice->getDeadline());
                 $out .= "</td></tr>";
            }

            if($notice->getPlace()){
                 $out .= "<tr><td class='left'><strong>".$this->getLang('place').":</strong></td><td colspan='2'>";
                 $out .= $notice->getPlace();
                 $out .= "</td></tr>";
            }
            
			if($notice->getColor()){
                 $out .= "<tr><td class='left'><strong>".$this->getLang('color').":</strong></td><td colspan='2'>";
                 $out .= $notice->getColor();
                 $out .= "</td></tr>";
            }
			
            $out .= "</tbody></table>";
            

            //$out .= "Jméno: ".$notice->getName();
        }

        //show previous button
        if($noticeList->hasPrevious){
            $out .= "<form action='".$script."' method='post'>
            <input type='hidden' name='id' value='".$ID."' />
            <input type='hidden' name='noticeboard_list_start' value='".($noticeList->start - $this->getConf('listSize'))."' />
            <input type='submit' class='button' name='submit' value='".$this->getLang('previous')."' /></form>";
        }

        //show next button
        if($noticeList->hasNext){
            $out .= "<form action='".$script."' method='post'>
            <input type='hidden' name='id' value='".$ID."' />
            <input type='hidden' name='noticeboard_list_start' value='".$noticeList->end."' />
            <input type='submit' class='button' name='submit' value='".$this->getLang('next')."' /></form>";
        }        
        return $out;
    }


    public function _showCalendar(){
        global $ID;
        $script = script();
        $calendar_ns  = ($data[0]) ? $data[0] : $ID;
        $langDays     = $this->getLang('days');
        $langMonth    = $this->getLang('month');
        $curDate      = getdate(time());        
        $showMonth    = (is_numeric($_SESSION['noticeboard_month'])) ? $_SESSION['noticeboard_month'] : $curDate['mon'];
        $showYear     = (is_numeric($_SESSION['noticeboard_year']))  ? $_SESSION['noticeboard_year']  : $curDate['year'];
        $gTimestamp   = mktime(0,0,0,$showMonth,1,$showYear);
        $numDays      = date('t',$gTimestamp);
        $viewDate     = getdate($gTimestamp);
        $today        = ($viewDate['mon'] == $curDate['mon'] &&
                               $viewDate['year'] == $curDate['year']) ?
                               $curDate['mday'] : null;
        $monthStart   = ($viewDate['wday'] == 0) ? 7 : $viewDate['wday'];
        $monthStart = ($monthStart -1) %7;
        $prevMonth  = ($showMonth-1 > 0)  ? ($showMonth-1) : 12;
        $nextMonth  = ($showMonth+1 < 13) ? ($showMonth+1) : 1;
        $out;

        switch(true) {
            case($prevMonth == 12):
                $prevYear = ($showYear-1);
                $nextYear = $showYear;
                break;
            case($nextMonth == 1):
                $nextYear = ($showYear+1);
                $prevYear = $showYear;
                break;
            default:
                $prevYear = $showYear;
                $nextYear = $showYear;
                break;
        }
        $out .= "<table border='0' class='noticeboard_calendar_header'>
                  <tr>
                    <th width='33%' class='left'>
                      <form action='".$script."' method='post' class='prevnext'>
                        <input type='hidden' name='id' value='".$ID."' />
                        <input type='hidden' name='noticeboard_year' value='".$prevYear."' />
                        <input type='hidden' name='noticeboard_month' value='".$prevMonth."' />
                        <input type='submit' class='button button-left' name='submit' value='".$this->getLang('previous')."' />
                      </form>
                    </th>                   
                    <th width='33%'>".$langMonth[$viewDate['mon']]." ".$showYear."<br /></th>
                   
                    <th width='33%' class='right'>
                      <form action='".$script."' method='post' class='prevnext'>
                        <input type='hidden' name='id' value='".$ID."' />
                        <input type='hidden' name='noticeboard_year' value='".$nextYear."' />
                        <input type='hidden' name='noticeboard_month' value='".$nextMonth."' />
                        <input type='submit' class='button' name='submit' value='".$this->getLang('next')."' />
                      </form>
                    </th>
                  </tr></table>";

        // week days
        $out .= "<table class='noticeboard_calendar' cellspacing='0'><tr>";
       
        foreach($langDays as $day) {
            $out .= "<th>".$day."</th>";
        }
        $out .= "</tr>\n";
        
        // create calendar-body
        for($i=1;$i<=$numDays;$i++) {
            $day = $i;
            
            // close row at end of week
            if($wd == 7) $out .= "</tr>";
            // set weekday
            if(!isset($wd) or $wd == 7) { $wd = 0; }
            // start new row when new week starts
            if($wd == 0) $out .= "<tr>";

            // create blank fields up to the first day of the month            
            if(!$firstWeek) {
                while($wd < $monthStart) {
                    $out .= "<td class='blank'>&nbsp;</td>";
                    $wd++;
                }
                // ok - first week is printet
                $firstWeek = true;
            }

            
            $time = mktime(0,0,0,$viewDate['mon'],$day,$showYear);
            // check for today
            if($today == $day) {
                $out .= "<td class='today' valign='top'><span class='day-number'>".$day."</span>";
                $out .= $this->_getDayEvent($time);
                $out .= "</td>";
            } else {
                $out .= "<td width='14%' valign='top'><span class='day-number'>".$day."</span>";
                $out .= $this->_getDayEvent($time);
                $out .= "</td>";
            }

            // fill remaining days with blanks
            if($i == $numDays && $wd < 7) {
                while($wd<6) {
                    $out .= '<td class="blank">&nbsp;</td>';
                    $wd++;
                }
                $out .= '</tr>';
            }

            //weekdays
            $wd++;
        }
      
        $out .="</table>";

        $out .= '<form action="'.script().'" method="post">';       
        $out .= '<label>'.$this->getLang('year').':</label> ';
        $out .= ' <select id="year" name="noticeboard_year">';

        $year_start = ($showYear != $curDate['year']) ? $showYear - 10 : $curDate['year'] - 5;
        $year_end   = $showYear + 10;

        for($i=$year_start;$i<=$year_end;$i++) {
            if($i == $showYear || $i == $curDate['year']) {
                $out .= '<option value="'.$i.'" selected="selected">'.$i.'</option>';
            } else {
                $out .= '<option value="'.$i.'">'.$i.'</option>';
            }
        }

        $out .= '</select>';       
        $out .= ' <label for="noticeboard_month">'.$this->getLang('mon').': </label>';
        $out .= ' <select id="noticeboard_month" name="noticeboard_month">';

        for($i=1;$i<=12;$i++) {
            if($i == $showMonth) {
                $out .= '<option value="'.$i.'" selected="selected">'.$langMonth[$i].'</option>' . DOKU_LF;
            } else {
                $out .= '<option value="'.$i.'">'.$langMonth[$i].'</option>';
            }
        }

        $out .= '</select>';      
        $out .= '<input type="hidden" name="id" value="'.$ID.'" />';
        $out .= ' <input type="submit" class="button" value="'.$this->getLang('select').'" />' . DOKU_LF;
        $out .= '</form>';

        return $out;
    }

    private function _getDayEvent($time){
        Global $ID;
        $noticeList = new helper_plugin_noticeboard_NoticeList($ID);
        //set category filter
        if($_SESSION['noticeboard_list_category']){
            $noticeList->setCategoryFilter($_SESSION['noticeboard_list_category']);
        }else{
            $noticeList->setCategoryFilter(8);
        }

        //set time filter
        if($_SESSION['noticeboard_show_time']){
            $noticeList->setTimeFilter($_SESSION['noticeboard_show_time']);
        }else{
            $noticeList->setTimeFilter(1);
        }

        //set sort filter
        if($_SESSION['noticeboard_sort']){
            $noticeList->setSortFilter($_SESSION['noticeboard_sort']);
        }else{
            $noticeList->setSortFilter(1);
        }

        //set sort order
        if($_SESSION['noticeboard_sort_order']){
            $noticeList->setSortOrder($_SESSION['noticeboard_sort_order']);
        }else{
            $noticeList->setSortOrder(0);
        }

        $out .="<ul>";
        $arrayList = $noticeList->getNoticeAtDay($time);       
        while(($arrayList && $arrayList->hasNext()) ){
            $notice = $arrayList->next();

           
            $out .= '<li><a href="' . wl($notice->getId(), array('do' => 'show')) . '" class="" title="">'.$notice->getName().$notice->hasStartTime().'</a> ';
            /*if($notice->hasStartTime()){
                    $out .= date("H:i",$notice->getStartTime());
            }*/
            $out .= '</li>';


            
        }
        $out .="</ul>";
        
        return $out;
    }


}
// vim:ts=4:sw=4:et:enc=utf-8:
