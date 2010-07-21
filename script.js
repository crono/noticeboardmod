/**
 * Noticeboard Plugin - Script
 *
 * @author  Zaruba Tomas <zatomik@gmail.com>
 */
window.onload = init;
function init(){
    if(document.getElementById("noticeboard_addStartTimeButton")){
        if(document.getElementById("noticeboard_StartTime").value == ''){
            noticeboard_deleteStartTime();
        }
        if(document.getElementById("noticeboard_EndTime").value == ''){
            noticeboard_deleteEndTime();
        }
        noticeboard_checkCategory();
    }
}


function noticeboard_addStartTime(){
    document.getElementById("noticeboard_addStartTimeButton").style.display = 'none';
    document.getElementById("noticeboard_addStartTime").style.display = 'block';
    

}

function noticeboard_deleteStartTime(){
    document.getElementById("noticeboard_addStartTimeButton").style.display = 'block';
    document.getElementById("noticeboard_addStartTime").style.display = 'none';
    document.getElementById("noticeboard_StartTime").value = '';
}

function noticeboard_addEndTime(){
    document.getElementById("noticeboard_addEndTimeButton").style.display = 'none';
    document.getElementById("noticeboard_addEndTime").style.display = 'block';
   

}

function noticeboard_deleteEndTime(){
    document.getElementById("noticeboard_addEndTimeButton").style.display = 'block';
    document.getElementById("noticeboard_addEndTime").style.display = 'none';
    document.getElementById("noticeboard_EndTime").value = '';
}

function noticeboard_checkCategory(){
    if(document.getElementById("noticeboard_category3").checked){
        document.getElementById("noticeboard_deadlineDiv").style.display = 'block';
    }else{
        document.getElementById("noticeboard_deadlineDiv").style.display = 'none';
        document.getElementById("noticeboard_deadline").value = '';
    }
}

