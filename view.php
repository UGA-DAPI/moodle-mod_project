<?php
require_once('../../config.php');
require_once($CFG->libdir.'/tablelib.php');
require_once($CFG->dirroot.'/mod/project/lib.php');
require_once($CFG->dirroot.'/mod/project/locallib.php');
require_once($CFG->dirroot.'/mod/project/notifylib.php');

$PAGE->requires->js('/mod/project/js/js.js');
//ajout w3c2i include de css
$PAGE->requires->css( new moodle_url('/mod/project/styles.css'));
// fixes locale for all date printing.
setLocale(LC_TIME, substr(current_language(), 0, 2));
// action pour export xml
$exportxml = optional_param('expxml',0, PARAM_INT);
//cas de l'export XML des projets

$id = required_param('id', PARAM_INT);
// module id
$view = optional_param('view', @$_SESSION['currentpage'], PARAM_CLEAN);
    // viewed page id
$nohtmleditorneeded = true;
$editorfields = '';

$timenow = time();
    // get some useful stuff...
if (! $cm = get_coursemodule_from_id('project', $id)) {
    print_error('invalidcoursemodule');
}
if (! $course = $DB->get_record('course', array('id' => $cm->course))) {
    print_error('coursemisconf');
}
if (! $project = $DB->get_record('project', array('id' => $cm->instance))) {
    print_error('invalidprojectid', 'project');
}



$project->cmid = $cm->id;
if($exportxml==1){
    project_print_projects_xml($cm->course);
}

require_login($course->id, false, $cm);
/*
if (@$CFG->enableajax){
$PAGE->requires->yui2_lib('yui_yahoo');
$PAGE->requires->yui2_lib('yui_dom');
$PAGE->requires->yui2_lib('yui_event');
$PAGE->requires->yui2_lib('yui_dragdrop');
$PAGE->requires->yui2_lib('yui_connection');
}
*/
$context = context_module::instance($cm->id);
$systemcontext = context_system::instance(0);

$strprojects = get_string('modulenameplural', 'project');
$strproject= get_string('modulename', 'project');
$straction = (@$action) ? '-> '.get_string(@$action, 'project') : '';
// get some session toggles if possible
if (array_key_exists('editmode', $_GET) && !empty($_GET['editmode'])){
    $_SESSION['editmode'] = $_GET['editmode'];
} 
else {
    if (!array_key_exists('editmode', $_SESSION))
        $_SESSION['editmode'] = 'off';
}
$USER->editmode = $_SESSION['editmode'];
$groupmode=groups_get_activity_groupmode($cm, $course);
// check current group and change, for anyone who could
if ($groupmode == NOGROUPS){
    // groups are not being used
    $currentGroupId = 0;
} 
else {
    $currentGroupId = groups_get_activity_group($cm, true);
    $changegroup = optional_param('group',0, PARAM_INT);
        //Group change requested?
    if (isguestuser()){
        //for guests, use session
        if ($changegroup >= 0){
            $_SESSION['guestgroup'] = $changegroup;
        }
        $currentGroupId = 0 + @$_SESSION['guestgroup'];
    }
    else { 
        //for normal users, change current group
        $currentGroupId = 0 + groups_get_activity_group($cm, true);
        if (!groups_is_member($currentGroupId , $USER->id) && !is_siteadmin($USER->id)){
            $USER->editmode = "off";
        }
    }
}
//check des droits d'access
if(!has_capability('mod/project:view', $context)){
    notice("Accès interdit.", "$CFG->wwwroot/course/view.php?id=$course->id");
    exit;
}
// ...display header...
$url = $CFG->wwwroot."/mod/project/view.php?id=$id";
$PAGE->set_title(format_string($project->name));
$PAGE->set_url($url);
$PAGE->set_heading('');
$PAGE->set_focuscontrol('');
$PAGE->set_cacheable(true);
$PAGE->set_button(update_module_button($cm->id, $course->id, $strproject));
$pagebuffer = $OUTPUT->header();

$pagebuffer .= "<div align=\"right\">";
$pagebuffer .= project_edition_enable_button($cm, $course, $project, $USER->editmode);
$pagebuffer .= "</div>";
// ...and if necessary set default action

//Permet d'initialiser le mode admin et afficher les boutons d'action
if (has_capability('mod/project:addinstance', $context)) {
    if (empty($action)) { 
        // no action specified, either go straight to elements page else the admin page
        $action = 'teachersview';
    }
}
elseif (!isguestuser()) {
     // it's a not a teacher nor a guest
    if (!$cm->visible) {
        notice(get_string('activityiscurrentlyhidden'));
    }
    if ($groupmode != NOGROUPS && !$currentGroupId && !$project->ungroupedsees){
        $action = 'notingroup';
    }
    if ($timenow < $project->projectstart) {
        $action = 'notavailable';
    } 
    elseif (!@$action) {
        $action = 'studentsview';
    }
} 
else { 
    // it's a guest, just watch if possible! ==> no guest should be allowed to watch, i'll comment this
 /*if ($project->guestsallowed){
    $action = 'guestview';
} else {*/
    $action = 'notavailable';
}

// ...log activity...
//add_to_log($course->id, 'project', 'view', "view.php?id=$cm->id", $project->id, $cm->id);

// pass useful values to javasctript: 

$moodlevars = new StdClass;
$moodlevars->view = $view;
$moodlevars->userid = $USER->id;
$moodlevars->cmid = $cm->id;
$moodlevarsjson = addslashes(json_encode($moodlevars));
$pagebuffer .= "<script type=\"text/javascript\">";
$pagebuffer .= "var moodlevars = eval('({$moodlevarsjson})');";
$pagebuffer .= "</script>";


/****************** display final grade (for students) ************************************/
if ($action == 'displayfinalgrade' ) {
    echo $pagebuffer;
    echo get_string('endofproject', 'project');
/****************** assignment not available (for students)***********************/
} elseif ($action == 'notavailable') {
    echo $pagebuffer;
    echo $OUTPUT->heading(get_string('notavailable', 'project'));

/****************** student's view***********************/
} elseif ($action == 'studentsview') {

    if ($timenow > $project->projectend) { 
        // if project is over, just cannot change anything more
        $pagebuffer .= $OUTPUT->box('<span class="inconsistency">'.get_string('projectisover','project').'</span>', 'center', '70%');
        $USER->editmode = 'off';
    }
    // Print settings and things in a table across the top
    $pagebuffer .= '<table width="100%" border="0" cellpadding="3" cellspacing="0"><tr valign="top">';

    // Allow the student to change groups (for this session), seeing other's work
    if ($groupmode){ 
        // if group are used
        $groups = groups_get_all_groups($course->id);
        if ($groups){
            $grouptable = array();
            foreach($groups as $aGroup){
                // i can see only the groups i belong to
                if (($groupmode == SEPARATEGROUPS) && !groups_is_member($aGroup->id, $USER->id)) continue;
                // mark group as mine if i am member
                if (($groupmode == VISIBLEGROUPS) && groups_is_member($aGroup->id, $USER->id)) $aGroup->name .= ' (*)';
                $grouptable[$aGroup->id] = $aGroup->name;
            }
            $pagebuffer .= '<td>';
            $pagebuffer .= groups_print_activity_menu($cm, $url, true);
            $pagebuffer .= '</td>';
        }
    }
    $pagebuffer .= '</table>'; 
    // ungrouped students can view group 0's project (teacher's) but not change it if ungroupedsees is off.
    // in visible mode, student from other groups cannot edit our material.
    if ($groupmode != SEPARATEGROUPS && (!$currentGroupId || !groups_is_member($currentGroupId, $USER->id))) {
        if (!$project->ungroupedsees){
            $USER->editmode = 'off';
        }
        include('project.php');
    } else { 
        // just view unique project workspace
        include('project.php');
    }
}

/****************** guest's view - display projects without editing capabilities************/
///////////////////////////////TOTALLY DISABLED, GUESTS SHOULD NOT BE ABLE TO WATCH AS PROJECTS NEED TO BE PRIVATE //////////////////////
elseif ($action == 'guestview') {

 /*$demostr = '';
if (!$project->guestscanuse || $currentGroupId != 0){ 
// guest can sometimes edit group 0
    $USER->editmode = 'off';
} elseif ($project->guestscanuse && !$currentGroupId && $timenow < $project->projectend) { 
// guest could have edited but project is closed
    $demostr = '(' . get_string('demomodeclosedproject', 'project') . ') ' . $OUTPUT->help_icon('demomode', 'project', false);
    $USER->editmode = 'off';
} else {
    $demostr = '(' . get_string('demomode', 'project') . ') ' . $OUTPUT->help_icon('demomode', 'project', false);
}
/// Print settings and things in a table across the top
$pagebuffer .= '<table width="100%" border="0" cellpadding="3" cellspacing="0"><tr valign="top">';

/// Allow the guest to change groups (for this session) only for visible groups
if ($groupmode == VISIBLEGROUPS) {
    $groups = groups_get_all_groups($course->id);
    if ($groups){
        $grouptable = array();
        foreach($groups as $aGroup){
         $grouptable[$aGroup->id] = $aGroup->name;
     }
     $pagebuffer .= '<td>';
     $pagebuffer .= groups_print_activity_menu($cm, $url, true);
     $pagebuffer .= '</td>';
 }
}     
$pagebuffer .= '</table>'; */
include('project.php');     

/****************** teacher's view - display admin page************/
} elseif ($action == 'teachersview') {
    // Print settings and things in a table across the top
    $pagebuffer .= '<table width="100%" border="0" cellpadding="3" cellspacing="0"><tr valign="top">';

    // Allow the teacher to change groups (for this session)
    if ($groupmode != NOGROUPS) {
        $groups = groups_get_all_groups($course->id);
        if (!empty($groups)){
            $pagebuffer .= '<td>';
            $pagebuffer .= groups_print_activity_menu($cm, $url, true);
            $pagebuffer .= '</td>';
        }
    }     
    $pagebuffer .= '</tr></table>'; 
    if (empty($currentGroupId)){
        $currentGroupId = 0;
    }
    include('project.php');

    /****************** show description************/
} elseif ($action == 'showdescription') {
    echo $pagebuffer;
    project_print_assignement_info($project);
    echo $OUTPUT->box(format_text($project->description, $project->format), 'center', '70%', '', 5, 'generalbox', 'intro');
    echo $OUTPUT->continue_button($_SERVER["HTTP_REFERER"]);

    /*************** student is not in a group **************************************/
} elseif ($action == 'notingroup') {
    echo $pagebuffer;
    echo $OUTPUT->box(format_text(get_string('notingroup', 'project'), 'HTML'), 'center', '70%', '', 5, 'generalbox', 'intro');
    echo $OUTPUT->continue_button($_SERVER["HTTP_REFERER"]);

    /*************** no man's land **************************************/
} else {
    echo $pagebuffer;
    print_error('errorfatalaction', 'project', $action);
}
echo $OUTPUT->footer($course);

?>