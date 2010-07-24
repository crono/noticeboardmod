<?php
/**
 * Noticeboard Plugin
 *
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author  Zaruba Tomas <zatomik@gmail.com>
 */

if (!defined('DOKU_INC')) die();
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN', DOKU_INC . 'lib/plugins/');
require_once (DOKU_PLUGIN . 'action.php');

require_once(DOKU_PLUGIN."noticeboard/classes/EditForm.php");
require_once(DOKU_PLUGIN."noticeboard/classes/Notice.php");
require_once(DOKU_PLUGIN."noticeboard/classes/NoticeList.php");
require_once(DOKU_PLUGIN."noticeboard/classes/rss.php");
require_once(DOKU_PLUGIN."noticeboard/classes/ICal.php");

//DK

require_once (DOKU_INC.'inc/DifferenceEngine.php');



class action_plugin_noticeboard extends DokuWiki_Action_Plugin {


    private $form; // edit Form
    private $start; // start time of event
    private $end; // end time of event
    private $deadline; // deadline of event
    private $noticeboardId; //id of root page with plugin
    private $isValid;   //edit for is valid -> notice can be saved
    private $showDetail; // page is detail of some notice
    private $notice; // notice to show
    /**
     * Return some info
     */
    function getInfo() {
        return array (
            'author' => 'Some name',
            'email' => 'foo@bar.org',
            'date' => '2007-04-05',
            'name' => 'Toolbar Action Plugin',
            'desc' => 'Inserts a button into the toolbar',
            'url' => 'http://www.example.com/plugin/toolbar',
        );
    }

    /**
     * Register the eventhandlers
     */
    function register(&$controller) {       
        $controller->register_hook('HTML_EDITFORM_OUTPUT',
                                   'BEFORE',
                                   $this,
                                   'editForm');
        $controller->register_hook('ACTION_ACT_PREPROCESS',
                                   'BEFORE',
                                   $this,
                                   'handle_act_preprocess',
                                   array());
        $controller->register_hook('TPL_ACT_RENDER',
                                   'BEFORE',
                                   $this,
                                   'handle_act_render',
                                   array());
        $controller->register_hook('ACTION_HEADERS_SEND',
                                   'BEFORE',
                                   $this,
                                   'handle_header',
                                   array());

     
    }

    /**
     * CHECK if user want RSS or iCAL
     */
    function handle_header(& $event, $param) {
        global $ID;
        //RSS
        if($_GET['noticeboard_rss_category']){
            $category = htmlspecialchars($_GET['noticeboard_rss_category'],ENT_QUOTES);
            $parentId = htmlspecialchars($_GET['noticeboard_rss_parent'],ENT_QUOTES);
            $rss = new helper_plugin_noticeboard_Rss($category,$ID);
            $rss->generateOutput();
            die();
        }else if($_GET['noticeboard_get_ical']){
            $cal = new helper_plugin_noticeboard_ICal($ID);
            $cal->generateOutput();
            die();
        }
        

    }

    public function editForm(& $event, $param) {
        global $ID;
        global $TEXT;
        global $INFO;
        
        

        if($INFO['perm'] < 2){
            return;             //edit form only for authorized users
        }
        $this->noticeboardId = substr($ID,0,strlen($ID)-10);
                                //check if page is notice
        
        $isNotice = helper_plugin_noticeboard_NoticeList::noticeIdExist($ID);      
        if(!($_GET['noticeboard_newnotice'] == true || $isNotice)){
            return;             // not a noticeboard page, nothing to do
        }

        $headerPosition = $event->data->findElementByAttribute("name","wikitext");
        $noticePosition = $event->data->findElementByAttribute("class","editButtons");
        
        if($TEXT != ''){
            $a =  form_makeWikiText($TEXT);
            $event->data->replaceElement($headerPosition,$a);
        }
        if(!$this->form){
            $this->form = new helper_plugin_noticeboard_EditForm();
        }
        $out = $this->form->getForm();        
        $event->data->insertElement($noticePosition,$out);
    }

    public function handle_act_preprocess(& $event, $param) {
        Global $ACT;
        Global $ID;
        Global $TEXT;
        Global $INFO;
        $act = $this->_act_clean($event->data);
       

        if($act=='show'){
            if($_POST['noticeboard_mode']){
                session_register('noticeboard_mode');
                $_SESSION['noticeboard_mode'] = $_POST['noticeboard_mode'];
            }

            if($_POST['noticeboard_list_start'] !== null){               
                session_register('noticeboard_list_start');
                $_SESSION['noticeboard_list_start'] = $_POST['noticeboard_list_start'];
            }

            if($_POST['noticeboard_sort'] !== null){
                session_register('noticeboard_sort');
                $_SESSION['noticeboard_sort'] = $_POST['noticeboard_sort'];
            }

            //detail of page
            $noticeList = new helper_plugin_noticeboard_NoticeList($ID);
            $notice = $noticeList->getNoticeById($ID);
            if($notice){
                $this->showDetail = true;
                $this->notice = $notice;
            }

            //list filter
            if($_POST['noticeboard_list_filter']){
                if($_SESSION['noticeboard_list_start']){
                    $_SESSION['noticeboard_list_start'] = 0; // set list to first page
                }
                session_register('noticeboard_list_category');
                $_SESSION['noticeboard_list_category'] = 0;
                if($_POST['noticeboard_show_category_all'] == 8){
                    $_SESSION['noticeboard_list_category'] = 8;
                }
                if($_POST['noticeboard_show_category_meeting'] == 4){
                    $_SESSION['noticeboard_list_category'] += 4;
                }
                if($_POST['noticeboard_show_category_event'] == 2){
                    $_SESSION['noticeboard_list_category'] += 2;
                }
                if($_POST['noticeboard_show_category_conference'] == 1){
                    $_SESSION['noticeboard_list_category'] += 1;
                }

                session_register('noticeboard_sort_order');
                $_SESSION['noticeboard_sort_order'] = $_POST['noticeboard_sort_order'];

                session_register('noticeboard_show_time');
                $_SESSION['noticeboard_show_time'] = $_POST['noticeboard_show_time'];
            }


            //calendar month
            if($_POST['noticeboard_year']){
                session_register('noticeboard_year');
                $_SESSION['noticeboard_year'] = $_POST['noticeboard_year'];
                session_register('noticeboard_month');
                $_SESSION['noticeboard_month'] = $_POST['noticeboard_month'];
            }
            
        }

        //delete post - only for auth
        if($_POST['noticeboard_delete'] && $INFO['perm'] >= 2){
           $noticeList->deleteNotice($_POST['noticeboard_delete']);
        }




        //check save - > save notice
        $act = $this->_act_clean($event->data);
        if(($act=='save' && $_REQUEST['noticeboard_category']) && $INFO['perm'] > 1){
            $this->form = new helper_plugin_noticeboard_EditForm();
            $this->isValid = $this->_validateForm();
            if($this->isValid){ //form is corectly filled in
                if(!$_REQUEST['noticeboard_parrentId']){                    
                    $this->noticeboardId = substr($ID,0,strlen($ID)-10);
                    $ID = cleanID(substr($ID,0,strlen($ID)-10).":"
                        .htmlspecialchars($_REQUEST['noticeboard_category'],ENT_QUOTES).":"                        
                        .date("Y:m:d",$this->start).":"
                        .str_replace(" ","-",htmlspecialchars($_REQUEST['noticeboard_name'],ENT_QUOTES)));
                }else{
                   $this->noticeboardId = $_REQUEST['noticeboard_parrentId'];
                }
                $this->_saveNotice();
            }else{ // form is filled bad, return back to edit page to correct it
                 $ACT = "edit";
            }
        }
    }

    public function handle_act_render(& $event, $param) {
        if($this->showDetail == true){
            $this->_showDetail();            
        }
    }
   

    private function _validateForm(){
        $valid = true;

        //check if name is empty
        if($_REQUEST['noticeboard_name'] == ''){
            $this->form->setEMName($this->getLang('errorName'));
            $valid = false;
        }else{
            $this->form->setEMName('');
        }


        if( !$_REQUEST['noticeboard_start_time'] ||(
            preg_match("/^[0-9][0-9]:[0-9][0-9]$/",$_REQUEST['noticeboard_start_time']) &&
            (substr($_REQUEST['noticeboard_start_time'],0,2) < 24 &&
            substr($_REQUEST['noticeboard_start_time'],3,2) < 60))){

            $this->form->setEMStartTime('');
        }else{
           $this->form->setEMStartTime($this->getLang('errorStartTime'));
           $valid = false;
        }

        if( !$_REQUEST['noticeboard_end_time'] ||(
            preg_match("/^[0-9][0-9]:[0-9][0-9]$/",$_REQUEST['noticeboard_end_time']) &&
            (substr($_REQUEST['noticeboard_end_time'],0,2) < 24 &&
            substr($_REQUEST['noticeboard_end_time'],3,2) < 60))){

            $this->form->setEMEndTime('');
        }else{
           $this->form->setEMEndTime($this->getLang('errorEndTime'));
           $valid = false;
        }

       
        //check correct form of Start date
        if(preg_match("/^[0-9][0-9].[0-9][0-9].[0-9][0-9][0-9][0-9]$/",$_REQUEST['noticeboard_start_date']) &&
            checkdate(  substr($_REQUEST['noticeboard_start_date'],3,2),
                        substr($_REQUEST['noticeboard_start_date'],0,2),
                        substr($_REQUEST['noticeboard_start_date'],6,4))){            
            $this->start = mktime(substr($_REQUEST['noticeboard_start_time'],0,2),
                        substr($_REQUEST['noticeboard_start_time'],3,2),
                        0,
                        substr($_REQUEST['noticeboard_start_date'],3,2),
                        substr($_REQUEST['noticeboard_start_date'],0,2),
                        substr($_REQUEST['noticeboard_start_date'],6,4));
            $this->form->setEMStartDate('');

        }else{
            $this->form->setEMStartDate($this->getLang('errorStartDate'));
            $valid = false;
        }

        //check correct form of End date - if filled in
        if(!$_REQUEST['noticeboard_end_date'] || (preg_match("/^[0-9][0-9].[0-9][0-9].[0-9][0-9][0-9][0-9]$/",$_REQUEST['noticeboard_end_date']) &&
            checkdate(  substr($_REQUEST['noticeboard_end_date'],3,2),
                        substr($_REQUEST['noticeboard_end_date'],0,2),
                        substr($_REQUEST['noticeboard_end_date'],6,4)))){
           
            $this->end = mktime(substr($_REQUEST['noticeboard_end_time'],0,2),
                        substr($_REQUEST['noticeboard_end_time'],3,2),
                        0,
                        substr($_REQUEST['noticeboard_end_date'],3,2),
                        substr($_REQUEST['noticeboard_end_date'],0,2),
                        substr($_REQUEST['noticeboard_end_date'],6,4));
            if(!$_REQUEST['noticeboard_end_date'] || $this->end > $this->start){
                $this->form->setEMEndDate('');
            }else{                
                $this->form->setEMEndDate($this->getLang('errorEndDateBigger'));
            }
            
        }else{          
            $this->form->setEMEndDate($this->getLang('errorEndDate'));
            $valid = false;
        }

        if(!$_REQUEST['noticeboard_deadline'] || (preg_match("/^[0-9][0-9].[0-9][0-9].[0-9][0-9][0-9][0-9]$/",$_REQUEST['noticeboard_deadline']) &&
            checkdate(  substr($_REQUEST['noticeboard_deadline'],3,2),
                        substr($_REQUEST['noticeboard_deadline'],0,2),
                        substr($_REQUEST['noticeboard_deadline'],6,4)))){

            $this->deadline = mktime(0,0,0,
                        substr($_REQUEST['noticeboard_deadline'],3,2),
                        substr($_REQUEST['noticeboard_deadline'],0,2),
                        substr($_REQUEST['noticeboard_deadline'],6,4));
             $this->form->setEMDeadline('');

        }else{
            $this->form->setEMDeadline($this->getLang('errorDeadline'));
            $valid = false;
        }
        return $valid;
    }
	
	/***********************************
	 * HTML Mail functions
	 *
	 * Sends HTML-formatted mail
	 * By Lin Junjie (mail [dot] junjie [at] gmail [dot] com)
	 *
	 ***********************************/
	public function mail_send_html($to, $subject, $body, $bodyhtml, $from='', $cc='', $bcc='', $headers=null, $params=null){
	  if(defined('MAILHEADER_ASCIIONLY')){
		$subject = utf8_deaccent($subject);
		$subject = utf8_strip($subject);
	  }
	 
	  if(!utf8_isASCII($subject)) {
		$subject = '=?UTF-8?Q?'.mail_quotedprintable_encode($subject,0).'?=';
		// Spaces must be encoded according to rfc2047. Use the "_" shorthand
		$subject = preg_replace('/ /', '_', $subject);
	  }
	 
	  $header  = '';
	 
	  $usenames = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? false : true;
	 
	  $random_hash = md5(date('r', time())); // added
	 
	  $to = mail_encode_address($to,'',$usenames);
	  $header .= mail_encode_address($from,'From');
	  $header .= mail_encode_address($cc,'Cc');
	  $header .= mail_encode_address($bcc,'Bcc');
	  $header .= 'MIME-Version: 1.0'.MAILHEADER_EOL;
	  $header .= "Content-Type: multipart/alternative; boundary=PHP-alt-".$random_hash.MAILHEADER_EOL;
	  $header .= $headers;
	  $header  = trim($header);
	 
	  $body = mail_quotedprintable_encode($body);
	  $bodyhtml = mail_quotedprintable_encode($bodyhtml);
	 
	  $message =	"--PHP-alt-".$random_hash."\r\n".
					"Content-Type: text/plain; charset=UTF-8"."\r\n".
					"Content-Transfer-Encoding: quoted-printable"."\r\n\r\n".
					$body."\r\n\r\n".
					"--PHP-alt-".$random_hash."\r\n".
					"Content-Type: text/html; charset=UTF-8"."\r\n".
					"Content-Transfer-Encoding: quoted-printable"."\r\n\r\n".
					$bodyhtml."\r\n".
					"--PHP-alt-".$random_hash."--";
	 
	  if($params == null){
		return @mail($to,$subject,$message,$header);
	  }else{
		return @mail($to,$subject,$message,$header,$params);
	  }
	}
	
	private function _buildFooter($html = true) {
		$footer = '';
		if($html) {
			//html version
			$dokulink = '<a href="' . DOKU_URL . '">' . DOKU_URL . '</a>';
			
			$footer .= "<br />\n" .
						'<p>---<br />' . "\n" .
						
						$this->getLang('sentBy') . $dokulink .
						
						"</p>\n";
			
		} else {
			//plain version
			$footer .= DOKU_LF . 
						'---' . DOKU_LF .
						$this->getLang('sentBy') . DOKU_URL .
						DOKU_LF;
			
		}
		return $footer;
	}
	
	private function _buildHtmlDiff($id, $txt) {
	
		$df  		=	new Diff(explode("\n",htmlspecialchars(rawWiki($id,''))), /// tady porovnat $TEXT a id bez rev a mame vyhrano!
						explode("\n",htmlspecialchars($txt)));
						
		$left_top	=	$this->getLang('originalVersion') . 
						' (' . strftime($this->getLang('medium_date'),@filemtime(wikiFN($id))) . ')';
						
		$right_top	=	$this->getLang('newVersion') . 
						' (' . $this->getLang('current') . ')';
	 
		$tdf 		=	 new TableDiffFormatter();
	 
	 
		$record_link = '<a href="'.wl($id,'',true).'">' . $id. '</a>';
		$diffHTML 	=	"<html>\n<body>\n" . 
						
						"<h1>" . $this->getLang('record_edited') . "</h1>" .
						
						
						"<p>" . sprintf($this->getLang('record_edited2'), $record_link, $_SERVER['REMOTE_USER'], strftime($this->getLang('medium_date')) ) . "</p>" .
						
						
						// strftime($conf['dformat']) 
						
						
						'<table>' . "\n" .
						'<tr>' . "\n" .
						'<th width="50%" colspan="2">'  . $left_top  . '</th>' . "\n" .
						'<th width="50%" colspan="2">' . $right_top . '</th>' . "\n" .
						'</tr>' . "\n" .
						
						$tdf->format($df) . // format diff 
						
						'</table>' . "\n" .
						
						$this->_buildFooter() .
						
						"</body>\n</html>";
	 
		$diffHTML	=	str_replace('class="diff-blockheader"','style="font-weight: bold; font-family: courier new;"',$diffHTML);
		$diffHTML 	=	str_replace('class="diff-addedline"','style="background-color: #ddffdd; font-family: courier new;"',$diffHTML);
		$diffHTML 	=	str_replace('class="diff-deletedline"','style="background-color: #ffffbb; font-family: courier new;"',$diffHTML);
		$diffHTML 	=	str_replace('class="diff-context"','style="background-color: #f5f5f5; font-family: courier new;"',$diffHTML);	
		$diffHTML 	=	str_replace('class="diffchange"','style="color: red;"',$diffHTML);
		$diffHTML 	=	str_replace('<strong>','<strong><font color="#FF0000">',$diffHTML);
		$diffHTML 	=	str_replace('</strong>','</font></strong>',$diffHTML);
		$diffHTML 	=	str_replace('<td>','<td style="font-family: courier new;">',$diffHTML);

	
		return $diffHTML;
	}
	
	private function _buildNew($id, $txt, $html = true) {
		$content = '';
		if($html) {
			//html version
			
			$record_link = '<a href="'.wl($id,'',true).'">' . $id. '</a>';
			$content .= "<html>\n<body>\n" . 
						
						"<h1>" . $this->getLang('record_created') . "</h1>" .
						
						"<p>" . sprintf($this->getLang('record_created2'), $record_link, $_SERVER['REMOTE_USER'], strftime($this->getLang('medium_date')) ) . "</p>" .
						
						$this->render($txt) .
						
						$this->_buildFooter() .
						
						"</body>\n</html>";
			
			
		} else {
			//plain version
			$content .= $this->getLang('record_created') . DOKU_LF .
						
						sprintf($this->getLang('record_created2'), $id, $_SERVER['REMOTE_USER'], strftime($this->getLang('medium_date')) ) . DOKU_LF .
						
						
						$this->render($txt) . //???
						
						$this->_buildFooter(false); //non html
						
		}
		return $content;
	}
	
	private function _getLastRev($id) {
		$list = getRevisions($id,0,1);
		return $list[0];
	}
	
	private function _notifyGroup() {
		Global $ACT;
        Global $ID;
        Global $TEXT;
        Global $INFO;
		
		$adds = $this->_getAddresses();
		
		
		//$body = $ID . ' se zmenilo!' . DOKU_LF . 'novy text je: ' . $this->render($TEXT) . DOKU_LF . '|' . $adds[0] . '|' .  $adds[1];
		
		//$body = $this->render($TEXT) ;
		
		if(page_exists($ID)) {
			$subject = sprintf($this->getLang('record_edited_subject'), DOKU_URL);
			$bodyhtml = $this->_buildHtmlDiff($ID, $TEXT);
			$bodyplain = 'plain zmena';
		
		} else {
			$subject = sprintf($this->getLang('record_created_subject'), DOKU_URL);
			$bodyhtml = $this->_buildNew($ID, $TEXT) ; 
			$bodyplain = $this->_buildNew($ID, $TEXT, false);
		}
		
		
		
		foreach ($adds as $to) {
			//mail_send($mail_ad, $subject, $body,'','','',$headers);
			//mail($mail_ad, $subject, $body, $headers);
			
			$this->mail_send_html($to,$subject,$bodyplain,$bodyhtml);
			
		}
		
		
		
	}

	/* returns array of addresses, or an empty array */
	private function _getAddresses() {
		global $auth;
		
		$newlist = array();
		$filter['grps']=$this->getConf('watchGroup');
		$userlist = $auth->retrieveUsers(0,0,$filter);
	
		foreach ( $userlist as $user) {
			$newlist[] = $user[mail]; //creating array of email adresses;
		}
		
		//mail_isvalid  ??
		
		return $newlist;
	}
	

    private function _saveNotice(){
         Global $ID;
        		 
         $notice = new helper_plugin_noticeboard_Notice();
         $notice->setCategory(htmlspecialchars($_REQUEST['noticeboard_category'], ENT_QUOTES));
         $notice->setName(htmlspecialchars($_REQUEST['noticeboard_name'], ENT_QUOTES));
         $notice->setPlace(htmlspecialchars($_REQUEST['noticeboard_place'], ENT_QUOTES));
         $notice->setStartTime($this->start);
         if($_REQUEST['noticeboard_start_time']){
             $notice->setHasStartTime(true);
         }
         if($_REQUEST['noticeboard_end_time']){
             $notice->setHasEndTime(true);
         }
         if($_REQUEST['noticeboard_end_date']){
            $notice->setEndTime($this->end);
         }
         if($_REQUEST['noticeboard_deadline']){
            $notice->setDeadline($this->deadline);
         }
         $notice->setId(strtolower($ID));      
         $notice->setParentId($this->noticeboardId);
         $noticeList = new helper_plugin_noticeboard_NoticeList($this->noticeboardId);
         $noticeList->addNotice($notice);
        
		//send mail here
			$this->_notifyGroup();
		 //end
		 
    }

    private function _showDetail(){
        Global $INFO;
        Global $ID;
            $out .= "<table class='noticeboard-show-detail' cellspacing='0'><thead><tr><th colspan='2'>";
            $out .= $this->notice->getName();         
            $out .= "</th><th width='80' class='cat'>";
            $out .= $this->notice->getCategory();
            $out .= "</th></tr></thead><tbody><tr><td class='left'>";
            $out .= "<strong>".$this->getLang('startTime').":</strong></td><td colspan='2'> ";
            if($this->notice->hasStartTime()){
                $out .= date("d.m.Y H:i",$this->notice->getStartTime());
            }else{
                $out .= date("d.m.Y",$this->notice->getStartTime());
            }
            $out .= "</td></tr>";

            if($this->notice->getEndTime()){
                $out .= "<tr><td class='left'><strong>".$this->getLang('endTime').":</strong></td><td colspan='2'>";
                if($this->notice->hasEndTime()){
                    $out .= date("d.m.Y H:i",$this->notice->getEndTime());
                }else{
                    $out .= date("d.m.Y",$this->notice->getEndTime());
                }
                $out .= "</td></tr>";
            }

            if($this->notice->getDeadline()){
                 $out .= "<tr><td class='left'><strong>".$this->getLang('deadline').":</strong></td><td colspan='2'>";
                 $out .= date("d.m.Y",$this->notice->getDeadline());
                 $out .= "</td></tr>";
            }

            if($this->notice->getPlace()){
                 $out .= "<tr><td class='left'><strong>".$this->getLang('place').":</strong></td><td colspan='2'>";
                 $out .= $this->notice->getPlace();
                 $out .= "</td></tr>";
            }

            $out .= "</tbody></table>";
        echo $out;
    }


    private function _act_clean($act){
         // check if the action was given as array key
         if(is_array($act)){
           list($act) = array_keys($act);
         }

         //remove all bad chars
         $act = strtolower($act);
         $act = preg_replace('/[^a-z_]+/','',$act);

         return $act;
     }


}