<?php

/*
*
* @package mod-project
* @category mod
* @author Yohan Thomas - W3C2i (support@w3c2i.com)
* @date 30/09/2013
* @version 3.0
* @license http://www.gnu.org/copyleft/gpl.html GNU Public License
*
*/
if (!defined('MOODLE_INTERNAL')) die ("You cannot enter directly in this script");

///memorizes current page - typical session switch

if (!empty($view)) {
    $_SESSION['currentpage'] = $view;
} elseif (empty($_SESSION['currentpage'])) {
    $_SESSION['currentpage'] = 'description';
}
$currentpage = $_SESSION['currentpage'];

/// memorizes edit mode - typical session switch

$editmode = optional_param('editmode', '', PARAM_ALPHA);
if (!empty($editmode)) {
    $_SESSION['editmode'] = $editmode;
} elseif (empty($_SESSION['editmode'])) {
    $_SESSION['editmode'] = 'off';
}

/// get general command name
$work = optional_param('work', '', PARAM_ALPHA);


/*
//Print groupe name
    
    if ($currentGroupId) {
        $group = $DB->get_record("groups", array("id" => $currentGroupId));
        echo "<center><b>". get_string('groupname', 'project') . $group->name . "</b></center><br/>";
    }
    
    $typeProject=false;
    if((int)$project->typeprojet==0){
        $typeProject=true;
    }*/
/// Make menu

    $tabrequtitle = get_string('requirements', 'project');
    //$tabrequlabel = (!has_capability('mod/project:changerequs', $context)) ? $tabrequtitle . " <img src=\"{$CFG->wwwroot}/mod/project/pix/p/lock.gif\" />" : $tabrequtitle ;
    $tabrequlabel = $tabrequtitle ;
    $tabspectitle = get_string('specifications', 'project');
    //$tabspeclabel = (!has_capability('mod/project:changespecs', $context)) ? "<img src=\"".$OUPTUT->pix_url('p/spec', 'project').'" /> ' . $tabspectitle . " <img src=\"{$CFG->wwwroot}/mod/project/pix/p/lock.gif\" />" : $tabspectitle ;
    //$tabtasklabel = (!has_capability('mod/project:changetasks', $context)) ? "<img src=\"{$CFG->wwwroot}/mod/project/pix/p/task.gif\" /> " . $tabtasktitle . " <img src=\"{$CFG->wwwroot}/mod/project/pix/p/lock.gif\" />" : $tabtasktitle ;
    //$tabmilelabel = (!has_capability('mod/project:changemiles', $context)) ? $tabmiletitle . " <img src=\"{$CFG->wwwroot}/mod/project/pix/p/lock.gif\" />" : $tabmiletitle ;
    //$tabdelivlabel = (!has_capability('mod/project:changedelivs', $context)) ? $tabdelivtitle . " <img src=\"{$CFG->wwwroot}/mod/project/pix/p/lock.gif\" />" : $tabdelivtitle ;
    //$tabvalidlabel = (!has_capability('mod/project:validate', $context)) ? $tabvalidtitle . " <img src=\"{$CFG->wwwroot}/mod/project/pix/p/lock.gif\" />" : $tabvalidtitle ;
    $tabrequlabel = "<img src=\"{$CFG->wwwroot}/mod/project/pix/p/req.gif\" height=\"14\" /> " . $tabrequlabel;
    //$tabspeclabel = "<img src=\"{$CFG->wwwroot}/mod/project/pix/p/spec.gif\" height=\"14\" /> " . $tabspeclabel;
    //$tabtasklabel = "<img src=\"{$CFG->wwwroot}/mod/project/pix/p/task.gif\" height=\"14\" /> " . $tabtasklabel;
    //$tabdelivlabel = "<img src=\"{$CFG->wwwroot}/mod/project/pix/p/deliv.gif\" height=\"14\" /> " . $tabdelivlabel;
    $tabEquipePicto = "<img src=\"{$CFG->wwwroot}/mod/project/pix/equipe_16x16.png\" />";
    $tabEtapePicto = "<img src=\"{$CFG->wwwroot}/mod/project/pix/etape_16x16.png\" />";
    $tabLivrablePicto = "<img src=\"{$CFG->wwwroot}/mod/project/pix/livrable_16x16.png\" />";
    $tabRessourcePicto = "<img src=\"{$CFG->wwwroot}/mod/project/pix/ressource_16x16.png\" />";
    $tabMessagePicto = "<img src=\"{$CFG->wwwroot}/mod/project/pix/message_16x16.png\" />";
    $tabProjectsPicto = "<img src=\"{$CFG->wwwroot}/mod/project/pix/vue-projets_16x16.png\" />";
    $tabValidPicto = "<img src=\"{$CFG->wwwroot}/mod/project/pix/vue-projets_16x16.png\" />";
    $tabTaskPicto = "<img src=\"{$CFG->wwwroot}/mod/project/pix/vue-projets_16x16.png\" />";

    $tabs = array();

    
    /*
    if(has_capability('mod/project:viewpreproductionentities', $context, $USER->id)){
        if (@$project->projectusesrequs){
            $tabs[0][] = new tabobject('requirements', "view.php?id={$cm->id}&amp;view=requirements", $tabrequlabel, $tabrequtitle);
        }
        if (@$project->projectusesspecs){
            $tabs[0][] = new tabobject('specifications', "view.php?id={$cm->id}&amp;view=specifications", $tabspeclabel, $tabspectitle);
        }
    }
    */
    //

    $tabs[0][] = new tabobject('description', "view.php?id={$cm->id}&amp;view=description", get_string('description', 'project'));  
    $tabs[0][] = new tabobject('views', "view.php?id={$cm->id}&amp;view=summary", $tabEquipePicto. get_string('views', 'project'),get_string('views', 'project'));
    $tabs[0][] = new tabobject('milestones', "view.php?id={$cm->id}&amp;view=milestones", $tabEtapePicto.get_string('milestones', 'project'), get_string('milestones', 'project'));
    if (@$project->projectusesdelivs){
        $tabs[0][] = new tabobject('ressources', "view.php?id={$cm->id}&amp;view=ressources", $tabRessourcePicto.get_string('ressource', 'project'), get_string('ressource', 'project'));
    }
    if (@$project->projectusesdelivs){
        $tabs[0][] = new tabobject('deliverables', "view.php?id={$cm->id}&amp;view=deliverables", $tabLivrablePicto.get_string('deliverable', 'project'),get_string('deliverable', 'project'));
    }
    if (@$project->projectusesvalidations){
        $tabs[0][] = new tabobject('validations', "view.php?id={$cm->id}&amp;view=validations", $tabValidPicto.get_string('validations', 'project'), get_string('validations', 'project'));
    }
    if (@$project->projectusestasks) {
        $tabs[0][] = new tabobject('tasks', "view.php?id={$cm->id}&amp;view=tasks", $tabTaskPicto.get_string('tasks', 'project'), get_string('tasks', 'project'));
    }
    $tabs[0][] = new tabobject('messages', "view.php?id={$cm->id}&amp;view=messages", $tabMessagePicto.get_string('messages', 'project'),get_string('messages', 'project'));
    if(has_capability('mod/project:addinstance', $context, $USER->id)){
        $tabs[0][] = new tabobject('vue projets', "view.php?id={$cm->id}&amp;view=projects", $tabProjectsPicto.get_string('projects', 'project'),get_string('projects', 'project'));
    }
    /*if (preg_match("/view_/", $currentpage)){
        $tabs[1][] = new tabobject('view_summary', "view.php?id={$cm->id}&amp;view=view_summary", get_string('summary', 'project'));
        $tabs[1][] = new tabobject('view_byassignee', "view.php?id={$cm->id}&amp;view=view_byassignee", get_string('byassignee', 'project'));
        $tabs[1][] = new tabobject('view_bypriority', "view.php?id={$cm->id}&amp;view=view_bypriority", get_string('bypriority', 'project'));
        $tabs[1][] = new tabobject('view_byworktype', "view.php?id={$cm->id}&amp;view=view_byworktype", get_string('byworktype', 'project'));
        $tabs[1][] = new tabobject('view_detail', "view.php?id={$cm->id}&amp;view=view_detail", get_string('detail', 'project'));
        $tabs[1][] = new tabobject('view_todo', "view.php?id={$cm->id}&amp;view=view_todo", get_string('todo', 'project'));
        $tabs[1][] = new tabobject('view_gantt', "view.php?id={$cm->id}&amp;view=view_gantt", get_string('gantt', 'project'));
    }*/
    /*
    if (has_capability('mod/project:viewprojectcontrols', $context)){
        $tabs[0][] = new tabobject('teacher', "view.php?id={$cm->id}&amp;view=teacher_assess", get_string('teacherstools', 'project'));
        if (preg_match("/teacher_/", $currentpage)){
            if ($project->grade && has_capability('mod/project:gradeproject', $context)){
                 $tabs[1][] = new tabobject('teacher_assess', "view.php?id={$cm->id}&amp;view=teacher_assess", get_string('assessments', 'project'));
                 if ($project->teacherusescriteria && has_capability('mod/project:managecriteria', $context)){
                    $tabs[1][] = new tabobject('teacher_criteria', "view.php?id={$cm->id}&amp;view=teacher_criteria", get_string('criteria', 'project'));
                }
            }
            if (has_capability('mod/project:manage', $context)){
                $tabs[1][] = new tabobject('teacher_projectcopy', "view.php?id={$cm->id}&amp;view=teacher_projectcopy", get_string('projectcopy', 'project'));
            }
            if ($project->enablecvs && has_capability('mod/project:manageremoterepository', $context)) {
                $tabs[1][] = new tabobject('teacher_cvs', "view.php?id={$cm->id}&amp;view=teacher_cvs", get_string('cvscontrol', 'project'));
            }
            $tabs[1][] = new tabobject('teacher_load', "view.php?id={$cm->id}&amp;view=teacher_load", get_string('load', 'project'));
        }

        if (has_capability('mod/project:configure', $context)){
            $tabs[0][] = new tabobject('domains', $CFG->wwwroot."/mod/project/view.php?view=domains&id={$id}", get_string('domains', 'project'));
            if (preg_match("/domains_?/", $currentpage)){
                if (!preg_match("/domains_heavyness|domains_complexity|domains_severity|domains_priority|domains_worktype|domains_taskstatus|domains_strength|domains_deliv_status/", $view)) $view = 'domains_complexity';
                $tabs[1][] = new tabobject('domains_strength', "view.php?id={$id}&amp;view=domains_strength", get_string('strength', 'project'));
                $tabs[1][] = new tabobject('domains_heavyness', "view.php?id={$id}&amp;view=domains_heavyness", get_string('heavyness', 'project'));
                $tabs[1][] = new tabobject('domains_complexity', "view.php?id={$id}&amp;view=domains_complexity", get_string('complexity', 'project'));
                $tabs[1][] = new tabobject('domains_severity', "view.php?id={$id}&amp;view=domains_severity", get_string('severity', 'project'));
                $tabs[1][] = new tabobject('domains_priority', "view.php?id={$id}&amp;view=domains_priority", get_string('priority', 'project'));
                $tabs[1][] = new tabobject('domains_worktype', "view.php?id={$id}&amp;view=domains_worktype", get_string('worktype', 'project'));
                $tabs[1][] = new tabobject('domains_taskstatus', "view.php?id={$id}&amp;view=domains_taskstatus", get_string('taskstatus', 'project'));
                $tabs[1][] = new tabobject('domains_deliv_status', "view.php?id={$id}&amp;view=domains_deliv_status", get_string('deliv_status', 'project'));
                $currentpage = $view;
            }
        }
        
    }
    */
    if ($currentpage == 'summary') {
    //if (preg_match("/^view_/", $currentpage)) {
        $activated[] = 'views';
    } elseif (preg_match("/^teacher_/", $currentpage)) {
        $activated[] = 'teacher';
    } elseif (preg_match("/^domains_/", $currentpage)) {
        $activated[] = 'domains';
    } else {
        $activated = NULL;
    }
    $pagebuffer .= print_tabs($tabs, $_SESSION['currentpage'], NULL, $activated, true);
    $pagebuffer .= '<br/>';
/// Route to detailed screens

    if ($currentpage == 'description') {
        //$pagebuffer .= project_print_assignement_info($project, true);
        include 'description.php';
    } elseif ($currentpage == 'requirements') { //CAPA TODO. innacessible ?
        include("requirement.php");
    } elseif ($currentpage == 'specifications') { //seem deprecated at l 77
        include("specification.php");
    } elseif ($currentpage == 'tasks') { //TODO
        include("task.php");
    } elseif ($currentpage == 'milestones') { //RECHECK POUR LE CLEAR ALL ET LE SORT ????
        include("milestone.php");
    } elseif ($currentpage == 'deliverables') { //DONE, NORMALLY
        include("deliverables.php");
    } elseif ($currentpage == 'ressources') { //DONE, NORMALLY
        include("ressources.php");
    } elseif ($currentpage == 'validation') { // NOT USED
        include("validation.php");
    } elseif ($currentpage == 'validations') { //NOT USED
        include("validations.php");
    }elseif ($currentpage == 'summary') { // TODO WITH THE EDIT GROUP FUNCTION
        include("summary.php");
    }elseif ($currentpage == 'messages') { //DONE, NORMALLY. WHY EDITINGTEACHER CANT SEE THIS PAGE ? MAY CHECK LATER
        include("messages.php");
    }elseif ($currentpage == 'projects') {
        include("projects.php");
    }
    /*elseif (preg_match("/view_/", $currentpage)) {
        if ($currentpage == 'view_summary') {
            include("summary.php");
        } elseif ($currentpage == 'view_byassignee') {
            include("byassignee.php");
        } elseif ($currentpage == 'view_bypriority') {
            include("bypriority.php");
        } elseif ($currentpage == 'view_byworktype') {
            include("byworktype.php");
        } elseif ($currentpage == 'view_detail') {
            include("detail.php");
        } elseif ($currentpage == 'view_todo') {
            include("todo.php");
        } elseif ($currentpage == 'view_gantt') {
            include("gantt.php");
        }
    }*/ elseif (preg_match("/teacher_/", $currentpage)) {
        // falldown if no grading enabled.
        if (!$project->grade && ($currentpage == 'teacher_assess' || $currentpage == 'teacher_criteria')) $currentpage = 'teacher_projectcopy';
        if ($currentpage == 'teacher_assess') {
            include("assessments.php"); //DONE, NORMALLY
        }
        if ($currentpage == 'teacher_criteria') {
            include("criteria.php"); //OK
        }
        if ($currentpage == 'teacher_projectcopy') {
            include("copy.php"); //OK
        }
        if ($currentpage == 'teacher_cvs') {
            include("cvs.php"); //OK
        }
        if ($currentpage == 'teacher_load') {
            include("imports.php"); //OK
        }
    } elseif (preg_match("/domains_/", $currentpage)) {
        $action = optional_param('what', '', PARAM_RAW);
        $domain = str_replace('domains_', '', $currentpage);
        include "view_domain.php"; //INTERNAL. DONT NEED CAPABILITIES
    } else {
        print_error('errorfatalscreen', 'project', $currentpage);
    }
    ?>