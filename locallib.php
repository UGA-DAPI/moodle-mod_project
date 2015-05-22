<?php

/**
*
* Library of extra functions
*
*
* @package mod-project
* @category mod
* @author Yohan Thomas - W3C2i (support@w3c2i.com)
* @date 30/09/2013
* @version 3.0
* @license http://www.gnu.org/copyleft/gpl.html GNU Public License
*
*/

/**
 * Requires and includes
 */
include_once "treelib.php";
include_once "mailtemplatelib.php";

/**
* hours for timework division unit
*/
define('HOURS',1);

/**
* halfdays for timework division unit
*/
define('HALFDAY',2);

/**
* days for timework division unit
*/
define('DAY',3);


/**
* checks the availability of the edition button and returns button code
* @param object $cm the course module instance
* @param object $course the actual course
* @param object $project the project instance
* @param string $editmode the actual value of the editmode session variable
* @return the button code or an empty string
*/
function project_edition_enable_button($cm, $course, $project, $editmode){
  global $CFG, $USER;

        // protect agains some unwanted situations
  $groupmode = 0 + groups_get_activity_groupmode($cm, $course);
        //$currentGroupId = (isguestuser()) ? $_SESSION['guestgroup'] : get_current_group($cm->course);
  if (isguestuser()) {
    $currentGroupId=$_SESSION['guestgroup'];
}
else{

    $mygroupid=groups_get_activity_group($cm);
    if ($mygroupid===false) {
            $currentGroupId=0; //should not happen ?
        }
        else{
            $currentGroupId=$mygroupid;
        }
    /*
        //réécriture de get_current_group, une melleure solution serait envisageable
        global $SESSION;
        $mygroupid = groups_get_all_groups($cm->course);
        if (isset($SESSION->currentgroup[$cm->course])) {
            $currentGroupId = $SESSION->currentgroup[$cm->course]->id;
        }
        elseif (is_array($mygroupid)) {
            foreach ($mygroupid as $value) {
                if (groups_is_member($value->id)) {
                    $currentGroupId=$value->id;
                }
            }
        }
        else{
            $currentGroupId = 0;
        }
        */
    }
    $context = context_course::instance($course->id);
    if (!has_capability('moodle/grade:edit', $context)){
        if (isguestuser() && !$project->guestscanuse) return '';
        if (!isguestuser() && !groups_is_member($currentGroupId)) return '';
        if (isguestuser() && ($currentGroupId || !$project->guestscanuse)) return '';
    }

    if ($editmode == 'on') {
        $str = "<form method=\"get\" style=\"display : inline\" action=\"view.php\">";
        $str.= "<input type=\"hidden\" name=\"editmode\" value=\"off\" />";
        $str .= "<input type=\"hidden\" name=\"id\" value=\"{$cm->id}\" />";
        $str .= "<input type=\"submit\" value=\"" .    get_string('disableedit', 'project') . "\" />";
        $str .= "</form>";
    } else {
        $str = "<form method=\"get\"    style=\"display : inline\" action=\"view.php\">";
        $str.= "<input type=\"hidden\" name=\"editmode\" value=\"on\" />";
        $str .= "<input type=\"hidden\" name=\"id\" value=\"{$cm->id}\" />";
        $str .= "<input type=\"submit\" value=\"" .    get_string('enableedit', 'project') . "\" />";
        $str .= "</form>";
    }
    return $str;
}

/**
* prints assignement
* @param project the current project
*/
function project_print_assignement_info($project, $return = false) {
  global $CFG, $SESSION, $DB, $OUTPUT, $COURSE;

  $str = '';

  if (! $course = $DB->get_record('course', array('id' => $project->course))) {
    print_error('errorinvalidcourseid');
}
if (! $cm = get_coursemodule_from_instance('project', $project->id, $course->id)) {
    print_error('errorinvalidcoursemoduleid');
}
        // print standard assignment heading
        //$str .= $OUTPUT->heading(format_string($project->name));
        //$str .= $OUTPUT->box_start('center');
$str .= '<div id="summary-content">';

        // print phase and date info

	//desactivation car inutile ajouter plutot étape en cours
	//$string = '<b>'.get_string('currentphase', 'project').'</b>: '.project_phase($project, '', $course).'<br />';
$string = '';

	//calcul prochaine étape
	$currentgroupid = 0 + groups_get_course_group($COURSE); // ensures compatibility 1.8 
    $query = " SELECT * FROM {project_milestone} as m WHERE projectid = $project->id AND groupid = $currentgroupid ORDER BY ordering desc";
    $milestones = $DB->get_records_sql($query);
    if (count($milestones)>0){
        $nextMilestone;
        $milestoneFound = false;
        while(!$milestoneFound && count($milestones)>0){
            $milestone= array_pop($milestones);
            if($milestone->statut!=3){
                $nextMilestone = $milestone;
                $milestoneFound=true;
            }
        }
        if(isset($nextMilestone->abstract)){
			$string .= '<b>'.get_string('currentmilestone', 'project').'</b> : '.$nextMilestone->abstract." <br />";//étape en cours
			if($nextMilestone->deadlineenable==1){
				$string .= '<b>'.get_string('currentmilestoneendtime', 'project').'</b> : '.userdate($nextMilestone->deadline)." <br />";//date fin de l'étape
			}
		}
    }

	//calcule des dates du projet
    $dates = array('projectstart' => $project->projectstart,'projectend' => $project->projectend);
	// option enlevé du tableau 'assessmentstart' => $project->assessmentstart
    foreach ($dates as $type => $date) {
        if ($date) {
            $strdifference = format_time($date - time());
            /*if (($date - time()) < 0) {$strdifference = "<font color=\"red\">$strdifference</font>";}*/
            $string .= '<b>'.get_string($type, 'project').'</b>: '.userdate($date)." <br />";//($strdifference)
        }
    }
    $str .= $string;
    //$str .= $OUTPUT->box_end();
    $str .= '</div>';
    if ($return) return $str;
    echo $str;

}

/**
* phasing the project module in time. Phasing is a combination of
* module standard phasing strategy and project defined milestones
* @param project the current project
* @param style not used 
* @return a printable representation of the current project phase 
*/
function project_phase($project, $style='') {
  global $CFG, $SESSION, $DB, $COURSE;

  $time = time();
  $course = $DB->get_record('course', array('id' => $project->course));
        $currentgroupid = 0 + groups_get_course_group($COURSE); // ensures compatibility 1.8 
        // getting all timed info
        $query = "
        SELECT
        m.*,
        deadline as phasedate,
        'milestone' as type
        FROM
        {project_milestone} as m
        WHERE
        projectid = $project->id AND
        groupid = $currentgroupid AND
        deadlineenable = 1
        ";
        $dated = $DB->get_records_sql($query);
        $aDated = new StdClass;
        $aDated->id = 'projectstart';
        $aDated->phasedate = $project->projectstart;
        $aDated->ordering = 0;
        $dated[] = $aDated;
        $aDated = new StdClass;
        $aDated->id = 'projectend';
        $aDated->phasedate = $project->projectend;
        $aDated->ordering = count($dated) + 1;
        $dated[] = $aDated;
        function sortbydate($a, $b){
          if ($a->phasedate == $b->phasedate) return 0;
          return ($a->phasedate < $b->phasedate) ? -1 : 1;
      }
      usort($dated, "sortbydate");

      $i = 0;
      while($time > $dated[$i]->phasedate && $i < count($dated) - 1){
          $i++;
      }
      if ($dated[$i]->id == 'projectstart'){
          return get_string('phasestart', 'project');
      } elseif ($dated[$i]->id == 'projectend'){
          return get_string('phaseend', 'project');
      } else {
       return "M{$dated[$i]->ordering} : {$dated[$i]->abstract} (<font color=\"green\">".format_time($dated[$i]->phasedate - $time)."</font>)";
   }
}

/**
* prints a specification entry with its tree branch
* @param project the current project
* @param group the group of students
* @fatherid the father node
* @param numspec the propagated autonumbering prefix
* @param cmid the module id (for urls)
*/
function project_print_specifications($project, $group, $fatherid, $cmid){
	global $CFG, $USER, $DB, $OUTPUT;
	static $level = 0;
	static $startuplevelchecked = false;

	project_check_startup_level('specification', $fatherid, $level, $startuplevelchecked);

	$query = "
   SELECT 
   s.*,
   c.collapsed
   FROM 
   {project_specification} s
   LEFT JOIN
   {project_collapse} c
   ON
   s.id = c.entryid AND
   c.entity = 'specifications' AND
   c.userid = $USER->id
   WHERE 
   s.groupid = {$group} and 
   s.projectid = {$project->id} AND 
   s.fatherid = {$fatherid}
   GROUP BY
   s.id
   ORDER BY 
   s.ordering
   ";	

   if ($specifications = $DB->get_records_sql($query)) {
      $i = 1;
      foreach($specifications as $specification){
        echo "<div class=\"nodelevel{$level}\">";
        $level++;
        project_print_single_specification($specification, $project, $group, $cmid, count($specifications));
        $expansion = (!$specification->collapsed) ? '' : "style=\"visbility:hidden; display:none\" " ;
        $visibility = ($specification->collapsed) ? 'display: none' : 'display: block' ; 
        echo "<div id=\"sub{$specification->id}\" style=\"$visibility\" >";
        project_print_specifications($project, $group, $specification->id, $cmid);
        echo "</div>";
        $level--;
        echo "</div>";
    }
} else {
  if ($level == 0){
     echo $OUTPUT->box_start();
     print_string('nospecifications', 'project');
     echo $OUTPUT->box_end();
 }
}
}

/**
* prints a single specification object
* @param specification the current specification to print
* @param project the current project
* @param group the current group
* @param cmid the current coursemodule (useful for making urls)
* @param setSize the size of the set of objects we are printing an item of
* @param fullsingle true if prints a single isolated element
*/
function project_print_single_specification($specification, $project, $group, $cmid, $setSize, $fullsingle = false){
  global $CFG, $USER, $SESSION, $DB, $OUTPUT;

  $context = context_module::instance($cmid);
  $canedit = $USER->editmode == 'on' && has_capability('mod/project:changespecs', $context);
  $numspec = implode('.', project_tree_get_upper_branch('project_specification', $specification->id, true, true));
  if (!$fullsingle){
     if (project_count_subs('project_specification', $specification->id) > 0) {
       $ajax = $CFG->enableajax && $CFG->enablecourseajax;
       $hidesub = "<a href=\"javascript:toggle('{$specification->id}','sub{$specification->id}', $ajax, '$CFG->wwwroot');\"><img name=\"img{$specification->id}\" src=\"{$CFG->wwwroot}/mod/project/pix/p/switch_minus.gif\" alt=\"collapse\" /></a>";
   } else {
    $hidesub = "<img src=\"{$CFG->wwwroot}/mod/project/pix/p/empty.gif\" />";
}
} else {
   $hidesub = '';
}

$speclevel = count(explode('.', $numspec)) - 1;
$indent = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $speclevel);

	// assigned tasks by subspecs count
$specList = str_replace(",", "','", project_get_subtree_list('project_specification', $specification->id));
$taskcount = project_print_entitycount('project_specification', 'project_task_to_spec', $project->id, $group, 'spec', 'task', $specification->id, $specList);
$reqcount = project_print_entitycount('project_specification', 'project_spec_to_req', $project->id, $group, 'spec', 'req', $specification->id, $specList);

        // completion count
$query = "
SELECT 
SUM(t.done) as completion,
COUNT(*) as total
FROM 
{project_task_to_spec} AS tts,
{project_task} as t
WHERE 
tts.taskid = t.id AND
tts.specid = $specification->id
";
$res = $DB->get_record_sql($query);
$completion = ($res->total != 0) ? project_bar_graph_over($res->completion / $res->total, 0) : project_bar_graph_over(-1, 0);
$checkbox = ($canedit)? "<input type=\"checkbox\" id=\"sel{$specification->id}\" name=\"ids[]\" value=\"{$specification->id}\" /> " : '' ;
$priorityoption = project_get_option_by_key('priority', $project->id, $specification->priority);
$severityoption = project_get_option_by_key('severity', $project->id, $specification->severity);
$complexityoption = project_get_option_by_key('complexity', $project->id, $specification->complexity);
        /*
	$prioritysignal = "<span class=\"scale_{$priorityoption->truelabel}\" title=\"{$priorityoption->label}\">P</span>";
	$severitysignal = "<span class=\"scale_{$severityoption->truelabel}\" title=\"{$severityoption->label}\">S</span>";
	$complexitysignal = "<span class=\"scale_{$complexityoption->truelabel}\" title=\"{$complexityoption->label}\">C</span>";
	*/
	$prioritysignal = "<img src=\"".$OUTPUT->pix_url("/priority_{$priorityoption->truelabel}", 'project')."\" title=\"{$priorityoption->label}\" />";
	$severitysignal = "<img src=\"".$OUTPUT->pix_url("/severity_{$severityoption->truelabel}", 'project')."\" title=\"{$severityoption->label}\" />";
	$complexitysignal = "<img src=\"".$OUTPUT->pix_url("/complexity_{$complexityoption->truelabel}", 'project')."\" title=\"{$complexityoption->label}\" />";

	if (!$fullsingle){
     $hideicon = (!empty($specification->description)) ? 'hide' : 'hide_shadow' ;
     $hidedesc = "<a href=\"javascript:toggle_show('{$numspec}','{$numspec}', '$CFG->wwwroot');\"><img name=\"eye{$numspec}\" src=\"".$OUTPUT->pix_url("/p/{$hideicon}", 'project')."\" alt=\"collapse\" /></a>";
 } else {
     $hidedesc = '';
 }

 $head = "<table width=\"100%\" class=\"nodecaption\"><tr><td align='left' width='70%'><b>{$checkbox}{$indent}<span class=\"level{$speclevel}\">{$hidesub} <a name=\"node{$specification->id}\"></a>S{$numspec} - ".format_string($specification->abstract)."</span></b></td><td align='right' width='30%'>{$severitysignal} {$prioritysignal} {$complexitysignal} {$reqcount} {$taskcount} {$completion} {$hidedesc}</td></tr></table>";

 unset($innertable);
 $innertable = new html_table();
 $innertable->class = 'unclassed';
 $innertable->width = '100%';
 $innertable->style = array('parmname', 'parmvalue');
 $innertable->align = array ('left', 'left');
 $innertable->data[] = array(get_string('priority','project'), "<span class=\"scale{$priorityoption->id}\" title=\"{$priorityoption->label}\">{$priorityoption->label}</span>");
 $innertable->data[] = array(get_string('severity','project'), "<span class=\"scale{$severityoption->id}\" title=\"{$severityoption->label}\">{$severityoption->label}</span>");
 $parms = project_print_project_table($innertable, true);
 $description = file_rewrite_pluginfile_urls($specification->description, 'pluginfile.php', $context->id, 'mod_project', 'specificationdescription', $specification->id);

 if (!$fullsingle || $fullsingle === 'HEAD'){
  $initialDisplay = 'none';
  $description = close_unclosed_tags(shorten_text(format_text($description, $specification->descriptionformat), 800));
} else {
  $initialDisplay = 'block';
  $description = format_string(format_text($description, $specification->descriptionformat));
}
$desc = "<div id='{$numspec}' class='entitycontent' style='display: {$initialDisplay};'>{$parms}".$description;
if (!$fullsingle){
   $desc .= "<br/><a href=\"{$CFG->wwwroot}/mod/project/view.php?id={$cmid}&amp;view=view_detail&amp;objectId={$specification->id}&amp;objectClass=specification\" >".get_string('seedetail','project')."</a></p>"; 
}
$desc .= '</div>';

$table = new html_table();
$table->class = 'entity';    
$table->head    = array ($head);
$table->cellpadding = 1;
$table->cellspacing = 1;
$table->width = '100%';
$table->align = array ('left');
$table->data[] = array($desc);
$table->rowclass[] = 'description';

if ($canedit) {
   $link = array();
   $link[] = "<a href='view.php?id={$cmid}&amp;work=add&amp;fatherid={$specification->id}&amp;view=specifications'>
   <img src='".$OUTPUT->pix_url('/p/newnode', 'project')."' alt=\"".get_string('addsubspec', 'project')."\" /></a>";
   $link[] = "<a href='view.php?id={$cmid}&amp;work=update&amp;specid={$specification->id}&amp;view=specifications'>
   <img src='".$OUTPUT->pix_url('/t/edit')."' title=\"".get_string('update')."\" /></a>";
   $link[] = "<a href='view.php?id={$cmid}&amp;work=delete&amp;specid={$specification->id}&amp;view=specifications'>
   <img src='".$OUTPUT->pix_url('/t/delete')."' title=\"".get_string('delete')."\" /></a>";
   $templateicon = ($specification->id == @$SESSION->project->spectemplateid) ? "{$CFG->wwwroot}/mod/project/pix/p/activetemplate.gif" : "{$CFG->wwwroot}/mod/project/pix/p/marktemplate.gif" ;
   $link[] = "<a href='view.php?id={$cmid}&amp;work=domarkastemplate&amp;specid={$specification->id}&amp;view=specifications#node{$specification->id}'>
   <img src='$templateicon' title=\"".get_string('markastemplate', 'project')."\" /></a>";
   if ($specification->ordering > 1)
      $link[] = "<a href='view.php?id={$cmid}&amp;work=up&amp;specid={$specification->id}&amp;view=specifications#node{$specification->id}'>
  <img src='".$OUTPUT->pix_url('/t/up')."' title=\"".get_string('up', 'project')."\" /></a>";
  if ($specification->ordering < $setSize)
      $link[] = "<a href='view.php?id={$cmid}&amp;work=down&amp;specid={$specification->id}&amp;view=specifications#node{$specification->id}'>
  <img src='".$OUTPUT->pix_url('/t/down')."' title=\"".get_string('down', 'project')."\" /></a>";
  if ($specification->fatherid != 0)
      $link[] = "<a href='view.php?id={$cmid}&amp;work=left&amp;specid={$specification->id}&amp;view=specifications#node{$specification->id}'>
  <img src='".$OUTPUT->pix_url('/t/left')."' title=\"".get_string('left', 'project')."\" /></a>";
  if ($specification->ordering > 1)
      $link[] = "<a href='view.php?id={$cmid}&amp;work=right&amp;specid={$specification->id}&amp;view=specifications#node{$specification->id}'>
  <img src='".$OUTPUT->pix_url('/t/right')."' title=\"".get_string('right', 'project')."\" /></a>";
  $table->data[] = array($indent . implode (' ' , $link));
  $table->rowclass[] = 'controls';
}

$table->style = 'generaltable';

	// echo html_writer::table($table);
project_print_project_table($table);
unset($table);
}

/**
* prints a requirement entry with its tree branch
* @param project the current project
* @param group the group of students
* @fatherid the father node
* @param numrequ the propagated autonumbering prefix
* @param cmid the module id (for urls)
* @uses $CFG
* @uses $USER
*/
function project_print_requirements($project, $group, $fatherid, $cmid){
	global $CFG, $USER, $DB, $OUTPUT;
	static $level = 0;
	static $startuplevelchecked = false;

	project_check_startup_level('requirement', $fatherid, $level, $startuplevelchecked);

	$query = "
   SELECT 
   r.*,
   COUNT(str.specid) as specifs,
   c.collapsed
   FROM 
   {project_requirement} r
   LEFT JOIN
   {project_spec_to_req} str
   ON
   r.id = str.reqid
   LEFT JOIN
   {project_collapse} c
   ON
   r.id = c.entryid AND
   c.entity = 'requirements' AND
   c.userid = $USER->id
   WHERE 
   r.groupid = $group AND 
   r.projectid = {$project->id} AND 
   fatherid = $fatherid
   GROUP BY
   r.id
   ORDER BY 
   ordering
   ";
   if ($requirements = $DB->get_records_sql($query)) {
      $i = 1;
      foreach($requirements as $requirement){
        echo "<div class=\"nodelevel{$level}\">";
        $level++;
        project_print_single_requirement($requirement, $project, $group, $cmid, count($requirements));

        $visibility = ($requirement->collapsed) ? 'display: none' : 'display: block' ; 
        echo "<div id=\"sub{$requirement->id}\" class=\"treenode\" style=\"$visibility\" >";
        project_print_requirements($project, $group, $requirement->id, $cmid);
        echo "</div>";
        $level--;
        echo "</div>";
    }
} else {
  if ($level == 0){
     echo $OUTPUT->box_start();
     print_string('norequirements', 'project');
     echo $OUTPUT->box_end();
 }
}
}

/**
* prints a single requirement object
* @param requirement the current requirement to print
* @param project the current project
* @param group the current group
* @param cmid the current coursemodule (useful for making urls)
* @param setSize the size of the set of objects we are printing an item of
* @param fullsingle true if prints a single isolated element
*/
function project_print_single_requirement($requirement, $project, $group, $cmid, $setSize, $fullsingle = false){
  global $CFG, $USER, $DB, $OUTPUT;

  $context = context_module::instance($cmid);
  $canedit = $USER->editmode == 'on' && has_capability('mod/project:changerequs', $context);
  $numrequ = implode('.', project_tree_get_upper_branch('project_requirement', $requirement->id, true, true));
  if (!$fullsingle){
     if (project_count_subs('project_requirement', $requirement->id) > 0) {
        $ajax = $CFG->enableajax && $CFG->enablecourseajax;
        $hidesub = "<a href=\"javascript:toggle('{$requirement->id}','sub{$requirement->id}', '$ajax', '$CFG->wwwroot');\"><img name=\"img{$requirement->id}\" src=\"".$OUTPUT->pix_url('/p/switch_minus', 'project')."\" alt=\"collapse\" /></a>";
    } else {
        $hidesub = "<img src=\"".$OUTPUT->pix_url('/p/empty', 'project')."\" />";
    }
} else {
   $hidesub = '';
}
$query = "
SELECT
SUM(t.done) as completion,
count(*) as total
FROM
{project_requirement} as r,
{project_spec_to_req} as str,
{project_task_to_spec} as tts,
{project_task} as t
WHERE
r.id = $requirement->id AND
r.id = str.reqid AND
str.specid = tts.specid AND
tts.taskid = t.id AND
r.projectid = {$project->id} AND
r.groupid = {$group}
";
$res = $DB->get_record_sql($query);
$completion = ($res->total != 0) ? project_bar_graph_over($res->completion / $res->total, 0) : project_bar_graph_over(-1, 0);

$requlevel = count(explode('.', $numrequ)) - 1;
$indent = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $requlevel);

	// assigned by subrequs count
$reqList = str_replace(",", "','", project_get_subtree_list('project_requirement', $requirement->id));
$speccount = project_print_entitycount('project_requirement', 'project_spec_to_req', $project->id, $group, 'req', 'spec', $requirement->id, $reqList);
$checkbox = ($canedit) ? "<input type=\"checkbox\" id=\"sel{$requirement->id}\" name=\"ids[]\" value=\"{$requirement->id}\" /> " : '';

$strengthoption = project_get_option_by_key('strength', $project->id, $requirement->strength);
$strengthsignal = '';
if (!empty($requirement->strength)){
   $strengthsignal = "<span class=\"scale_{$strengthoption->truelabel}\" title=\"{$strengthoption->label}\">s</span>";
}

$heavynessoption = project_get_option_by_key('heaviness', $project->id, $requirement->heavyness);
$heavynessclass = '';
if (!empty($requirement->heavyness)){
   $heavynessclass = "scale_{$heavynessoption->truelabel}";
}

if (!$fullsingle){
   $hideicon = (!empty($requirement->description)) ? 'hide' : 'hide_shadow' ;
   $hidedesc = "<a href=\"javascript:toggle_show('{$numrequ}','{$numrequ}', '$CFG->wwwroot');\"><img name=\"eye{$numrequ}\" src=\"".$OUTPUT->pix_url("/p/{$hideicon}", 'project')."\" alt=\"collapse\" /></a>";
} else {
   $hidedesc = '';
}
$head = "<table width='100%' class=\"nodecaption $heavynessclass\"><tr><td align='left' width='70%'><span class=\"level{$requlevel}\">{$checkbox}{$indent}{$hidesub} <a name=\"node{$requirement->id}\"></a>R{$numrequ} - ".format_string($requirement->abstract)."</span></td><td align='right' width='30%'>{$strengthsignal} {$speccount} {$completion} {$hidedesc}</td></tr></table>";

unset($innertable);
$innertable = new html_table();
$innertable->class = 'unclassed';
$innertable->width = '100%';
$innertable->style = array('parmname', 'parmvalue');
$innertable->align = array ('left', 'left');
$innertable->data[] = array(get_string('strength', 'project'), "<span class=\"scale{$strengthoption->id}\" title=\"{$strengthoption->label}\">{$strengthoption->label}</span>");
$parms = project_print_project_table($innertable, true);
$description = file_rewrite_pluginfile_urls($requirement->description, 'pluginfile.php', $context->id, 'mod_project', 'requirementdescription', $requirement->id);

if (!$fullsingle || $fullsingle === 'HEAD'){
  $initialDisplay = 'none';
  $description = close_unclosed_tags(shorten_text(format_text($description, $requirement->descriptionformat), 800));
} else {
  $initialDisplay = 'block';
  $description = format_string(format_text($description, $requirement->descriptionformat));
}
$desc = "<div id='{$numrequ}' class='entitycontent' style='display: {$initialDisplay};'>{$parms}".$description;
if (!$fullsingle){
   $desc .= "<br/><a href=\"{$CFG->wwwroot}/mod/project/view.php?id={$cmid}&amp;view=view_detail&amp;objectId={$requirement->id}&amp;objectClass=requirement\" >".get_string('seedetail','project')."</a>"; 
}
$desc .='</div>';

$table = new html_table();
$table->class = 'entity';    
$table->cellpadding = 1;
$table->cellspacing = 1;
$table->head    = array ($head);
$table->width    = '100%';
$table->align = array ("left");
$table->data[] = array($desc);
$table->rowclass[] = 'description';

if ($canedit) {
   $link = array();
   $link[] = "<a href='view.php?id={$cmid}&amp;work=add&amp;fatherid={$requirement->id}&amp;view=requirements'>
   <img src='".$OUTPUT->pix_url('/p/newnode', 'project')."' alt=\"".get_string('addsubrequ', 'project')."\" /></a>";
   $link[] = "<a href='view.php?id={$cmid}&amp;work=update&amp;requid={$requirement->id}&amp;view=requirements'>
   <img src='".$OUTPUT->pix_url('/t/edit')."' alt=\"".get_string('update')."\" /></a>";
   $link[] = "<a href='view.php?id={$cmid}&amp;work=delete&amp;requid={$requirement->id}&amp;view=requirements'>
   <img src='".$OUTPUT->pix_url('/t/delete')."' alt=\"".get_string('delete')."\" /></a>";
   if ($requirement->ordering > 1)
      $link[] = "<a href='view.php?id={$cmid}&amp;work=up&amp;requid={$requirement->id}&amp;view=requirements#node{$requirement->id}'>
  <img src='".$OUTPUT->pix_url('/t/up')."' alt=\"".get_string('up', 'project')."\" /></a>";
  if ($requirement->ordering < $setSize)
      $link[] = "<a href='view.php?id={$cmid}&amp;work=down&amp;requid={$requirement->id}&amp;view=requirements#node{$requirement->id}'>
  <img src='".$OUTPUT->pix_url('/t/down')."' alt=\"".get_string('down', 'project')."\" /></a>";
  if ($requirement->fatherid != 0)
      $link[] = "<a href='view.php?id={$cmid}&amp;work=left&amp;requid={$requirement->id}&amp;view=requirements#node{$requirement->id}'>
  <img src='".$OUTPUT->pix_url('/t/left')."' alt=\"".get_string('left', 'project')."\" /></a>";
  if ($requirement->ordering > 1)
      $link[] = "<a href='view.php?id={$cmid}&amp;work=right&amp;requid={$requirement->id}&amp;view=requirements#node{$requirement->id}'>
  <img src='".$OUTPUT->pix_url('/t/right')."' alt=\"".get_string('right', 'project')."\" /></a>";
  $table->data[] = array($indent . implode(' ', $link));
  $table->rowclass[] = 'controls';
}

$table->style = "generaltable";
project_print_project_table($table);
	// echo html_writer::table($table);
unset($table);
}

/**
* prints a task entry with its tree branch
* @param project the current project
* @param group the group of students
* @fatherid the father node
* @param numtask the propagated autonumbering prefix
* @param cmid the module id (for urls)
*/
function project_print_tasks($project, $group, $fatherid, $cmid, $propagated=null){
	global $CFG, $USER, $DB, $OUTPUT;
	static $level = 0;
	static $startuplevelchecked = false;

	project_check_startup_level('task', $fatherid, $level, $startuplevelchecked);
	
	// get current level task nodes
	$query = "
   SELECT 
   t.*,
   m.abstract as milestoneabstract,
   c.collapsed
   FROM 
   {project_task} t
   LEFT JOIN
   {project_milestone} m
   ON
   t.milestoneid = m.id
   LEFT JOIN
   {project_collapse} c
   ON
   t.id = c.entryid AND
   c.entity = 'tasks' AND
   c.userid = $USER->id
   WHERE 
   t.groupid = {$group} AND 
   t.projectid = {$project->id} AND 
   t.fatherid = {$fatherid}
   ORDER BY 
   t.ordering
   ";
   if ($tasks = $DB->get_records_sql($query)) {
      foreach($tasks as $task){
        echo "<div class=\"nodelevel{$level}\">";
        $level++;
        $propagatedroot = $propagated;
        if ($propagated == null || !isset($propagated->milestoneid) && $task->milestoneid) {
          $propagatedroot->milestoneid = $task->milestoneid;
          $propagatedroot->milestoneabstract = $task->milestoneabstract;
      }
      else{
       $task->milestoneid = $propagated->milestoneid;
       $task->milestoneabstract = $propagated->milestoneabstract;
       $task->milestoneforced = 1;
   }
   if (!@$propagated->collapsed || !$CFG->enablecourseajax || !$CFG->enableajax){
       project_print_single_task($task, $project, $group, $cmid, count($tasks), false, '');
   }

			if ($task->collapsed) $propagatedroot->collapsed = true; // give signal for lower branch
          $visibility = ($task->collapsed) ? 'display: none' : 'display: block' ; 
          echo "<div id=\"sub{$task->id}\" style=\"$visibility\" >";
          project_print_tasks($project, $group, $task->id, $cmid, $propagatedroot);
          echo "</div>";
          $level--;
          echo "</div>";
      }
  } else {
    if ($level == 0){
       echo $OUTPUT->box_start();
       print_string('notasks', 'project');
       echo $OUTPUT->box_end();
   }
}
}

/**
* prints a single task object
* @param task the current task to print
* @param project the current project
* @param group the current group
* @param cmid the current coursemodule (useful for making urls)
* @param setSize the size of the set of objects we are printing an item of
* @param fullsingle true if prints a single isolated element
* @param style some command input to change things in output.
*
* style uses values : SHORT_WITHOUT_ASSIGNEE, SHORT_WITHOUT_TYPE, SHORT_WITH_ASSIGNEE_ORDERED
*
* // TODO clean up $fullsingle and $style commands
*/
function project_print_single_task($task, $project, $group, $cmid, $setSize, $fullsingle = false, $style='', $nocollapse = false){
  global $CFG, $USER, $SESSION, $DB, $OUTPUT;

  $TIMEUNITS = array(get_string('unset','project'),get_string('hours','project'),get_string('halfdays','project'),get_string('days','project'));
  $context = context_module::instance($cmid);
  $canedit = ($USER->editmode == 'on') && has_capability('mod/project:changetasks', $context) && !preg_match("/NOEDIT/", $style);
    /*if (!has_capability('mod/project:changenotownedtasks', $context)){
        if ($task->owner != $USER->id) $canedit = false;
    }*/
    $hasMasters = $DB->count_records('project_task_dependency', array('slave' => $task->id));
    $hasSlaves = $DB->count_records('project_task_dependency', array('master' => $task->id));
    $taskDependency = "<img src=\"{$CFG->wwwroot}/mod/project/pix/p/task_alone.gif\" title=\"".get_string('taskalone','project')."\" />";
    if ($hasSlaves && $hasMasters){
     $taskDependency = "<img src=\"".$OUTPUT->pix_url('/p/task_middle', 'project')."\" title=\"".get_string('taskmiddle','project')."\" />";
 } else if ($hasMasters){
     $taskDependency = "<img src=\"".$OUTPUT->pix_url('/p/task_end', 'project')."\" title=\"".get_string('taskend','project')."\" />";
 } else if ($hasSlaves){
     $taskDependency = "<img src=\"".$OUTPUT->pix_url('/p/task_start', 'project')."\" title=\"".get_string('taskstart','project')."\" />";
 }

 $numtask = implode('.', project_tree_get_upper_branch('project_task', $task->id, true, true));
 if (!$fullsingle && !$nocollapse){
     if (project_count_subs('project_task', $task->id) > 0) {
        $ajax = $CFG->enableajax && $CFG->enablecourseajax;
        $hidesub = "<a href=\"javascript:toggle('{$task->id}','sub{$task->id}', '$ajax', '$CFG->wwwroot');\"><img name=\"img{$task->id}\" src=\"".$OUTPUT->pix_url('/p/switch_minus', 'project')."\" alt=\"collapse\" /></a>";
    } else {
        $hidesub = "<img src=\"".$OUTPUT->pix_url('/p/empty', 'project')."\" />";
    }
} else {
   $hidesub = '';
}

$tasklevel = count(explode('.', $numtask)) - 1;
$indent = (!$fullsingle) ? str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $tasklevel) : '' ;

$taskcount = project_print_entitycount('project_task', 'project_task_to_spec', $project->id, $group, 'task', 'spec', $task->id);
$delivcount = project_print_entitycount('project_task', 'project_task_to_deliv', $project->id, $group, 'task', 'deliv', $task->id);
$checkbox = ($canedit) ? "<input type=\"checkbox\" id=\"sel{$task->id}\" name=\"ids[]\" value=\"{$task->id}\" /> " : '' ;

$over = ($task->planned && $task->planned < $task->used) ? floor((($task->used - $task->planned) / $task->planned) * 60) : 0 ;
        $barwidth = ($task->planned) ? 60 : 0 ; // unplanned tasks should not provide progress bar
        $completion = project_bar_graph_over($task->done, $over, $barwidth, 5);
        $milestonepix = (isset($task->milestoneforced)) ? 'milestoneforced' : 'milestone' ; 
        $milestone = ($task->milestoneid) ? "<img src=\"".$OUTPUT->pix_url("/p/{$milestonepix}", 'project')."\" title=\"".format_string(@$task->milestoneabstract)."\" />" : '';
        if (!$fullsingle || $fullsingle === 'HEAD'){
           $hideicon = (!empty($task->description)) ? 'hide' : 'hide_shadow' ;
           $hidetask = "<a href=\"javascript:toggle_show('{$numtask}','{$numtask}', '{$CFG->wwwroot}');\"><img name=\"eye{$numtask}\" src=\"".$OUTPUT->pix_url("/p/{$hideicon}", 'project')."\" alt=\"collapse\" /></a>";
       } else {
           $hidetask = '';
       }
       $assigneestr = '';
       $headdetaillink = '';
       $timeduestr = '';
       if (!preg_match('/SHORT_WITHOUT_ASSIGNEE/', $style) && $task->assignee){
           $assignee = $DB->get_record('user', array('id' => $task->assignee));
           $assigneestr = "<span class=\"taskassignee\">({$assignee->lastname} {$assignee->firstname})</span>";
           if ($task->taskendenable) {
              $tasklate = ($task->taskend < time()) ? 'toolate' : 'futuretime' ;
              $timeduestr = "<span class=\"$tasklate timedue\">[".userdate($task->taskend)."]</span>";
          } 
      } else {
        $headdetaillink = "<a href=\"{$CFG->wwwroot}/mod/project/view.php?id={$cmid}&amp;view=view_detail&amp;objectId={$task->id}&amp;objectClass=task\" ><img src=\"{$CFG->wwwroot}/mod/project/pix/p/hide.gif\" title=\"".get_string('detail', 'project')."\" /></a>";
    }

    $worktypeicon = '';
    $worktypeoption = project_get_option_by_key('worktype', $project->id, $task->worktype);
    if ($style == '' || !$style === 'SHORT_WITHOUT_TYPE'){
     if (file_exists("{$CFG->dirroot}/mod/project/pix/p/".@$worktypeoption->code.".gif")){
       $worktypeicon = "<img src=\"".$OUTPUT->pix_url("/p/{$worktypeoption->code}", 'project')."\" title=\"".$worktypeoption->label."\" height=\"24\" align=\"middle\" />";
   }
}
$orderCell = '';
if (preg_match('/SHORT_WITH_ASSIGNEE_ORDERED/', $style)){
  static $order;
  if (!isset($order)) 
    $order = 1;
else 
    $order++;
$priorityDesc = project_get_option_by_key('priority', $project->id, $task->priority);
$orderCell = "<td class=\"ordercell_{$priorityDesc->label}\" width=\"3%\" align=\"center\" title=\"{$priorityDesc->description}\">{$order}</td>";
}

$head = "<table width='100%' class=\"nodecaption\"><tr>{$orderCell}<td align='left' width='70%'>&nbsp;{$worktypeicon} <span class=\"level{$tasklevel} {$style}\">{$checkbox}{$indent}{$hidesub} <a name=\"node{$task->id}\"></a>T{$numtask} - ".format_string($task->abstract)." {$headdetaillink} {$assigneestr} {$timeduestr}</span></td><td align='right' width='30%'> {$taskcount} {$delivcount} {$completion} {$milestone} {$taskDependency} {$hidetask}</td></tr></table>";

$statusoption = project_get_option_by_key('taskstatus', $project->id, $task->status);

	//affichage de la task
unset($innertable);
$innertable = new html_table();
$innertable->width = '100%';
$innertable->style = array('parmname', 'parmvalue');
$innertable->align = array ('left', 'left');
$innertable->data[] = array(get_string('worktype','project'), $worktypeoption->label);
$innertable->data[] = array(get_string('status','project'), $statusoption->label);
$innertable->data[] = array(get_string('costrate','project'), $task->costrate);
$planned = $task->planned . ' ' . $TIMEUNITS[$project->timeunit];
if (@$project->useriskcorrection){
  $planned .= "<span class=\"riskshifted\">(".($task->planned * (1 + ($task->risk / 100))) . ' ' . $TIMEUNITS[$project->timeunit].")</span>";
}
$innertable->data[] = array(get_string('costplanned','project'), $planned);
$quote = $task->quoted . ' ' . $project->costunit;
if ($project->useriskcorrection){
  $quote .= "<span class=\"riskshifted\">(".($task->quoted * (1 + ($task->risk / 100))) . ' ' . $project->costunit.")</span>";
}
$innertable->data[] = array(get_string('quoted','project'), $quote);
$innertable->data[] = array(get_string('risk','project'), $task->risk);
$innertable->data[] = array(get_string('done','project'), $task->done . '%');
$innertable->data[] = array(get_string('used','project'), $task->used . ' ' . $TIMEUNITS[$project->timeunit]);
$innertable->data[] = array(get_string('spent','project'), $task->spent . ' ' . $project->costunit);
$innertable->data[] = array(get_string('mastertasks','project'), $hasMasters);
$innertable->data[] = array(get_string('slavetasks','project'), $hasSlaves);
$parms = project_print_project_table($innertable, true);
$description = file_rewrite_pluginfile_urls($task->description, 'pluginfile.php', $context->id, 'mod_project', 'taskdescription', $task->id);

if (!$fullsingle || $fullsingle === 'HEAD'){
  $initialDisplay = 'none';
  $description = close_unclosed_tags(shorten_text(format_text($description, $task->descriptionformat), 800));
} else {
  $initialDisplay = 'block';
  $description = format_string(format_text($description, $task->descriptionformat));
}
$desc = "<div id='{$numtask}' class='entitycontent' style='display: {$initialDisplay};'>{$parms}".$description;
if (!$fullsingle || $fullsingle === 'SHORT' || $fullsingle === 'SHORT_WITHOUT_TYPE'){
   $desc .= "<br/><a href=\"{$CFG->wwwroot}/mod/project/view.php?id={$cmid}&amp;view=view_detail&amp;objectId={$task->id}&amp;objectClass=task\" >".get_string('seedetail','project')."</a></p>"; 
}
$desc .= "</div>";

$table = new html_table();
$table->class = 'entity';    
$table->head    = array ($head);
$table->cellspacing = 1;
$table->cellpadding = 1;
$table->width = "100%";
$table->align = array ("left");
$table->data[] = array($desc);
$table->rowclass[] = 'description';

if ($canedit) {
   $link = array();
   $link[] = "<a href='view.php?id={$cmid}&amp;work=add&amp;fatherid={$task->id}&amp;view=tasks'>
   <img src='".$OUTPUT->pix_url('/p/newnode', 'project')."' title=\"".get_string('addsubtask', 'project')."\" /></a>";
   $link[] = "<a href='view.php?id={$cmid}&amp;work=update&amp;taskid={$task->id}&amp;view=tasks'>
   <img src='".$OUTPUT->pix_url('/t/edit')."' title=\"".get_string('updatetask', 'project')."\" /></a>";
   $templateicon = ($task->id == @$SESSION->project->tasktemplateid) ? $OUTPUT->pix_url('/p/activetemplate', 'project') : $OUTPUT->pix_url('/p/marktemplate', 'project') ;
   $link[] = "<a href='view.php?id={$cmid}&amp;work=domarkastemplate&amp;taskid={$task->id}&amp;view=tasks#node{$task->id}'>
   <img src='$templateicon' title=\"".get_string('markastemplate', 'project')."\" /></a>";
   $link[] = "<a href='view.php?id={$cmid}&amp;work=delete&amp;taskid={$task->id}&amp;view=tasks'>
   <img src='".$OUTPUT->pix_url('/t/delete')."' title=\"".get_string('deletetask', 'project')."\" /></a>";
   if ($task->ordering > 1)
      $link[] = "<a href='view.php?id={$cmid}&amp;work=up&amp;taskid={$task->id}&amp;view=tasks#node{$task->id}'>
  <img src='".$OUTPUT->pix_url('/t/up')."' title=\"".get_string('up', 'project')."\" /></a>";
  if ($task->ordering < $setSize)
      $link[] = "<a href='view.php?id={$cmid}&amp;work=down&amp;taskid={$task->id}&amp;view=tasks#node{$task->id}'>
  <img src='".$OUTPUT->pix_url('/t/down')."' title=\"".get_string('down', 'project')."\" /></a>";
  if ($task->fatherid != 0)
      $link[] = "<a href='view.php?id={$cmid}&amp;work=left&amp;taskid={$task->id}&amp;view=tasks#node{$task->id}'>
  <img src='".$OUTPUT->pix_url('/t/left')."' title=\"".get_string('left', 'project')."\" /></a>";
  if ($task->ordering > 1)
      $link[] = "<a href='view.php?id={$cmid}&amp;work=right&amp;taskid={$task->id}&amp;view=tasks#node{$task->id}'>
  <img src='".$OUTPUT->pix_url('/t/right')."' title=\"".get_string('right', 'project')."\" /></a>";
  $table->data[] = array($indent . implode(' ', $link));
  $table->rowclass[] = 'controls';
}

$table->style = "generaltable";
project_print_project_table($table);
	// echo html_writer::table($table);
unset($table);
}

/**
* prints a milestone entry
* @param project the current project
* @param group the group of students
* @param numstage the propagated autonumbering prefix
* @param cmid the module id (for urls)
*/
function project_print_milestones($project, $group, $numstage, $cmid){
	global $CFG, $USER, $DB, $OUTPUT;
	
  $TIMEUNITS = array(get_string('unset','project'),get_string('hours','project'),get_string('halfdays','project'),get_string('days','project'));
  $context = context_module::instance($cmid);
  $canedit = $USER->editmode == 'on' && has_capability('mod/project:changemilestone', $context);
	//bug editmode a resoudre avant de l'ajouter...
	//$canvalidatemilestone = $USER->editmode == 'on' && has_capability('mod/project:validatemilestone', $context);
	//$canaskvalidatemilestone = $USER->editmode == 'on' && has_capability('mod/project:askvalidatemilestone', $context);
  $canvalidatemilestone = has_capability('mod/project:validatemilestone', $context);
  $canaskvalidatemilestone = has_capability('mod/project:askvalidatemilestone', $context);

  if ($milestones = $DB->get_records_select('project_milestone', "projectid = ? AND groupid = ? ", array($project->id, $group),'ordering ASC' )) {
    $i = 1;
    echo "<table width='100%' class=\"nodecaption recapmilestones\">
    <thead>
    <tr>
    <th class='leftcel header'>Etapes</th>
    <th class='leftcel header'>Dates clé</th>
    <th class='leftcel header'>Statut</th>
    <th class='leftcel header'></th>
    </tr>
    </thead>
    <tbody>";
    foreach($milestones as $milestone){		
		  //printing milestone
      $passed = ($milestone->deadline < usertime(time())) ? 'toolate' : 'passedtime' ;
      $difference = "";
      if($milestone->deadline - time()>0){
        $nbJours = round(($milestone->deadline - time())/(24*3600));
        if($nbJours>1){
          $difference = "dans ".$nbJours." jours";
      }
      else{
          $difference = "dans ".$nbJours." jour";
      }
  }else{
    $difference = " en retard";
}
$milestonedeadline = ($milestone->deadlineenable) ? "<span class='{$passed}'>" . userdate($milestone->deadline)." ".$difference . '</span>': '<i>durée illimité</i>' ;
$checkbox = ($canedit) ? "<input type=\"checkbox\" name=\"ids[]\" value=\"{$milestone->id}\" />" : '' ;

$deliverables = $DB->get_records('project_deliverable', array('milestoneid' => $milestone->id, 'projectid' => $project->id,'typeelm' => 1), '', 'abstract,id');
$ressources = $DB->get_records('project_deliverable', array('milestoneid' => $milestone->id, 'projectid' => $project->id,'typeelm' => 0), '', 'id');
			//$taskcount = "<img src=\"".$OUTPUT->pix_url('/p/task', 'project')."\" />[".$taskCount."]";
			//$deliverablecount = " <img src=\"".$OUTPUT->pix_url('/p/deliv', 'project')."\" />[".$delivCount."]";
echo "<tr class='milestonehead'>";
echo "<td><img style='vertical-align:middle;margin-right:3px;' src=\"{$CFG->wwwroot}/mod/project/pix/etape_16x16.png\" /><span class='step-title'>Etape {$i} - ".format_string($milestone->abstract)."</span></td>";

echo "<td>".$milestonedeadline."</td>";
$statutLabel = array('En travaux','En cours de validation','En révision','<span class="passedtime">Validé</span>');
echo "<td>".$statutLabel[$milestone->statut]."</td>";
			// $hide = "<a href=\"javascript:toggle_show('{$i}','{$i}');\"><img name=\"eye{$i}\" src=\"".$OUTPUT->pix_url('/p/hide', 'project')."\" alt=\"collapse\" /></a>";
			/*
			$hide = '';
			$head = "<table width='100%' class=\"nodecaption\"><tr><td align='left' width='80%'>{$checkbox} <a name=\"mile{$milestone->id}\"></a>M{$i} - ".format_string($milestone->abstract)." {$milestonedeadline}</td><td align='right' width='30%'>{$taskcount} {$deliverablecount} {$completion} {$hide}</td></tr></table>";
			*/
			$controls='';
			$actionsEtape='';
			if ($canedit) {
             $link = array();
             $link[] = "<a title='".get_string('update')."' href='view.php?id={$cmid}&amp;work=update&amp;milestoneid={$milestone->id}&amp;view=milestones'>
             <img src='".$OUTPUT->pix_url('/t/edit')."' alt=\"".get_string('update')."\" /></a>";
                                //if ($toptasks->count == 0 || $project->allowdeletewhenassigned)
				        /*$link[] = "<a title='".get_string('delete')."' href='view.php?id=$cmid&amp;work=delete&amp;milestoneid={$milestone->id}&amp;view=milestones'>
                     <img src='".$OUTPUT->pix_url('/t/delete')."' alt=\"".get_string('delete')."\" /></a>";*/
                     $link[] = "<p style=\"cursor:pointer;display:inline;\" onclick=\"javascript:var r=confirm('Etes vous sure de vouloir supprimer ".addslashes($milestone->abstract)." ?');if (r==true){window.location.href='view.php?id=$cmid&amp;work=delete&amp;milestoneid={$milestone->id}&amp;view=milestones';}\"><img src='".$OUTPUT->pix_url('/t/delete')."' alt=\"".get_string('delete')."\" /></p>";
                     if ($i > 1)
                        $link[] = "<a title='".get_string('up', 'project')."' href='view.php?id={$cmid}&amp;work=up&amp;milestoneid={$milestone->id}&amp;view=milestones'>
                    <img src='".$OUTPUT->pix_url('/t/up')."' alt=\"".get_string('up', 'project')."\" /></a>";
                    if ($i < count($milestones))
                        $link[] = "<a title='".get_string('down', 'project')."' href='view.php?id={$cmid}&amp;work=down&amp;milestoneid={$milestone->id}&amp;view=milestones'>
                    <img src='".$OUTPUT->pix_url('/t/down')."' alt=\"".get_string('down', 'project')."\" /></a>";
				//$table->data[] = array(implode(' ', $link));
                    $controls = implode(' ', $link);
                }
                $query = "
                SELECT
                count(*) as count
                FROM
                {project_milestone} as m
                WHERE
                m.projectid = {$project->id} AND
                m.groupid = {$group} AND
                m.ordering < {$milestone->ordering} AND
                m.statut < 3
                ";
                $milestoneActiveForValidate = false;
                if ($res = $DB->get_record_sql($query)){ 
                  if ($res->count== 0){
                        //la milestoe en question est la première de la liste (on verifie la présence de milestone qui sont avant et non terminée)
                    $milestoneActiveForValidate=true;
                }
            }

            if ($canvalidatemilestone && $milestone->statut == 1) {
                    //Si on peut valider une étape et que le statut de l'étape est demande de validation
              $actionsEtape = "<p onclick=\"javascript:var r=confirm('Etes vous sure de vouloir valider l\'étape ".addslashes($milestone->abstract)." ?');if (r==true){window.location.href='view.php?id={$cmid}&amp;work=valider&amp;milestoneid={$milestone->id}&amp;view=milestones';}\" class='btn-action'>
              <img src='".$OUTPUT->pix_url('/valide32', 'project')."' alt=\"".get_string('VALIDERMILESTONE', 'project')."\" /></p>";
              $actionsEtape .= "<p onclick=\"javascript:var r=confirm('Etes vous sure de vouloir mettre en révision l\'étape ".addslashes($milestone->abstract)." ?');if (r==true){window.location.href='view.php?id={$cmid}&amp;work=refuser&amp;milestoneid={$milestone->id}&amp;view=milestones';}\" class='btn-action'>
              <img src='".$OUTPUT->pix_url('/refu32', 'project')."' alt=\"".get_string('REFUSERMILESTONE', 'project')."\" /></p>";
          }
          elseif($milestoneActiveForValidate && $canaskvalidatemilestone && count($deliverables)>0 && ($milestone->statut == 0 || $milestone->statut == 2 )){
                    //Si on peut demander une validation et que le statut de l'étape est travaux en cours ou en révisison
              $actionsEtape = "<p onclick=\"javascript:var r=confirm('Etes vous sure de vouloir demander la validation de l\'étape ".addslashes($milestone->abstract)." ?');if (r==true){window.location.href='view.php?id={$cmid}&amp;work=askvalider&amp;milestoneid={$milestone->id}&amp;view=milestones';}\" class='btn-action'>
              <img src='".$OUTPUT->pix_url('/askvalide32', 'project')."' alt=\"".get_string('ASKVALIDERMILESTONE', 'project')."\" /></p>";
          }
          echo "<td>".$actionsEtape."</td></tr>";
          echo "<tr class='milestonedet'><td>";
                //affichage des ressources
          $ressources = $DB->get_records('project_deliverable', array('milestoneid' => $milestone->id, 'projectid' => $project->id,'typeelm' => 0), '', 'abstract,id');
          echo "<table><tbody>";
          $k = 0;
          if(count($ressources)>0){
              foreach($ressources as $ressource){
                 if($k!=0){
                    echo "<tr><td>&nbsp;</td>";
                }else{
                    echo "<tr><td>Ressources&nbsp;:</td>";
                    $k=1;
                }
					//$fs = get_file_storage();
                $abstract = '';
					/*
					//Si génération d'un lien pour chaque livrable/ressource
					$files = $fs->get_area_files($context->id, 'mod_project', 'deliverablelocalfile', $ressource->id, 'sortorder DESC, id ASC', false);
					if(!empty($files)){
						$file = reset($files);
						$path = '/'.$context->id.'/mod_project/deliverablelocalfile/'.$file->get_itemid().$file->get_filepath().$file->get_filename();
						$url = moodle_url::make_file_url('/pluginfile.php', $path, '');
						$abstract = html_writer::link($url, $ressource->abstract);
					}else{
						$abstract = $ressource->abstract;
					}
					*/
					$abstract= "<a href=\"view.php?id={$cmid}&amp;work=update&amp;delivid={$ressource->id}&amp;view=deliverables\">".$ressource->abstract."</a>";
					echo "<td>".$abstract."</td></tr>";
				}
			}else{
				echo "<tr><td>Ressources&nbsp;:</td><td></td></tr>";
			}
			echo "</tbody></table></td><td><table><tbody>";
			//affichage des livrables
			$j=0;
			if(count($deliverables)>0){
				foreach($deliverables as $deliverable){
					if($j!=0){
						echo "<tr><td>&nbsp;</td>";
					}else{
						echo "<tr><td>Livrables&nbsp;:</td>";
						$j=1;
					}
					$abstract= "<a href=\"view.php?id={$cmid}&amp;work=update&amp;delivid={$deliverable->id}&amp;view=deliverables\">".$deliverable->abstract."</a>";
					echo "<td>".$abstract."</td></tr>";
				}
				echo "<tr><td colspan='2'></td></tr>";
			}else{
				echo "<tr><td>Livrables&nbsp;:</td><td></td></tr><tr><td colspan='2'></td></tr>";
			}
			//Affichage des archive de version de l'étape si il y en a
			$fs = get_file_storage();
			$files = $fs->get_area_files($context->id, 'mod_project', 'deliverablearchive', $milestone->id, 'sortorder DESC, id ASC', false);
			if(!empty($files)){
				$j=0;
				foreach($files as $file){
					if($j!=0){
						echo "<tr><td>&nbsp;</td>";
					}else{
						echo "<tr><td>Version&nbsp;:</td>";
						$j=1;
					}
					$path = '/'.$context->id.'/mod_project/deliverablearchive/'.$file->get_itemid().$file->get_filepath().$file->get_filename();
					$url = moodle_url::make_file_url('/pluginfile.php', $path, '');
					$lienArchive = html_writer::link($url, $file->get_filename());
					echo "<td>".$lienArchive."</td></tr>";
				}
			}

			echo "</tbody></table></td><td></td><td></td>";
			echo "<tr><td>".$controls."</td><td></td><td></td></tr>";
			echo "<tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>";//ligne vide pour espacer
			//$table->style = "generaltable";
		        //echo "<div class=\"nodelevel0\">";
			//project_print_project_table($table);
		        //echo "</div>";
			//unset($table);

			$i++;
		}
		echo "</tbody></table>";
	} else {
		echo $OUTPUT->box_start();
		print_string('nomilestones', 'project');
		echo $OUTPUT->box_end();
	}
}

/**
* prints a deliverable entry with its tree branch
* @param project the current project
* @param group the group of students
* @fatherid the father node
* @param numspec the propagated autonumbering prefix
* @param cmid the module id (for urls)
* @uses $CFG
* @uses $USER
*/
/*function project_print_deliverables($project, $group, $fatherid, $cmid, $propagated=null){
	global $CFG, $USER, $DB, $OUTPUT;
	static $level = 0;
	static $startuplevelchecked = false;

	project_check_startup_level('deliverable', $fatherid, $level, $startuplevelchecked);	
	
	echo "<div class='sepbloc'></div>";
	project_print_bloc_elem($project, $group,$fatherid, $cmid,$propagated, 1);
	project_print_bloc_elem($project, $group,$fatherid, $cmid,$propagated, 0);
	
	echo "<div class='sepbloc'></div>";
}*/

function project_print_bloc_elem($project, $group,$fatherid, $cmid, $typeelm, $propagated=null){
	global $CFG, $USER, $DB, $OUTPUT;
	static $level = 0;
	static $startuplevelchecked = false;
  echo "<div class='sepbloc'></div>";
  if($typeelm==1){
    project_check_startup_level('deliverable', $fatherid, $level, $startuplevelchecked);
    $classBloc = "blocDelivrable";
    $lblBloc = "livrable";
}else{
    project_check_startup_level('ressources', $fatherid, $level, $startuplevelchecked);
    $classBloc = "blocRessource";
    $lblBloc = "ressource";
}
$query = "
SELECT 
d.*,
m.abstract as milestoneabstract,
c.collapsed
FROM 
{project_deliverable} as d
LEFT JOIN
{project_milestone} as m
ON
d.milestoneid = m.id
LEFT JOIN
{project_collapse} as c
ON
d.id = c.entryid AND
c.entity = 'deliverables' AND
c.userid = $USER->id
WHERE 
d.groupid = {$group} AND 
d.projectid = {$project->id} AND 
d.fatherid = {$fatherid} AND
d.typeelm = {$typeelm}
ORDER BY 
d.ordering
";
echo "<div class='".$classBloc."'><h3 class='header'><img src='".$OUTPUT->pix_url($lblBloc, 'project')."' alt='".get_string($lblBloc, 'project')."' />".get_string($lblBloc, 'project')."</h3>";
if ($deliverables = $DB->get_records_sql($query)) {
    foreach($deliverables as $deliverable){
      $level++;
      echo "<div class=\"nodelevel{$level}\">";
      $propagatedroot = $propagated;
      if (!$propagated || (!isset($propagated->milestoneid) && $deliverable->milestoneid)) {
        $propagatedroot = new stdClass;
        $propagatedroot->milestoneid = $deliverable->milestoneid;
        $propagatedroot->milestoneabstract = $deliverable->milestoneabstract;
    } else {
        $deliverable->milestoneid = $propagated->milestoneid;
        $deliverable->milestoneabstract = $propagated->milestoneabstract;
        $deliverable->milestoneforced = 1;
    }
    project_print_single_deliverable($deliverable, $project, $group, $cmid, count($deliverables), $typeelm);

    $visibility = ($deliverable->collapsed) ? 'display: none' : 'display: block' ; 
    echo "<div id=\"sub{$deliverable->id}\" style=\"$visibility\" >";
    echo "</div>";
    $level--;
    echo "</div>";
}
} else {
    if ($level == 0){
      echo $OUTPUT->box_start();
      if($typeelm==1){
        print_string('nodeliverables', 'project');
    }else{
        print_string('noressources', 'project');
    }
    echo $OUTPUT->box_end();
}
}
echo "</div>";
echo "<div class='sepbloc'></div>";
}
/**
* prints a single task object
* @param task the current task to print
* @param project the current project
* @param group the current group
* @param cmid the current coursemodule (useful for making urls)
* @param setSize the size of the set of objects we are printing an item of
* @param fullsingle true if prints a single isolated element
*/
function project_print_single_deliverable($deliverable, $project, $group, $cmid, $setSize, $typeelm, $fullsingle = false){
  global $CFG, $USER, $DB, $OUTPUT;

  $context = context_module::instance($cmid);
  $canedit = $USER->editmode == 'on' && has_capability('mod/project:editdeliverables', $context);
  $numdeliv = implode('.', project_tree_get_upper_branch('project_deliverable', $deliverable->id, true, true));
  $stringelm = ($typeelm == 0) ? 'ressources' : 'deliverables';

	/*
	if (!$fullsingle){
        	if (project_count_subs('project_deliverable', $deliverable->id) > 0) {
        		$ajax = $CFG->enableajax && $CFG->enablecourseajax;
        		$hidesub = "<a href=\"javascript:toggle('{$deliverable->id}','sub{$deliverable->id}', '$ajax', '$CFG->wwwroot');\"><img name=\"img{$deliverable->id}\" src=\"{$CFG->wwwroot}/mod/project/pix/p/switch_minus.gif\" alt=\"collapse\" /></a>";
        	} else {
        		$hidesub = "<img src=\"{$CFG->wwwroot}/mod/project/pix/p/empty.gif\" />";
        	}
        }
        else{
             $hidesub = '';
        }
	*/

        $delivlevel = count(explode('.', $numdeliv)) - 1;
        $indent = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $delivlevel);

        // get completion indicator for deliverables through assigned tasks
        /*
	$query = "
             SELECT
                     count(*) as count,
                     SUM(done) as done,
                     SUM(planned) as planned,
                     SUM(quoted) as quoted,
                     SUM(used) as used,
                     SUM(spent) as spent
             FROM
                    {project_task} as t,
                    {project_task_to_deliv} as ttd
             WHERE
                    t.projectid = {$project->id} AND
                    t.groupid = $group AND
                    t.id = ttd.taskid AND
                    ttd.delivid = {$deliverable->id}
        ";
        $completion = '';
        if ($res = $DB->get_record_sql($query)){ 
                 if ($res->count != 0){
		        $deliverable->done = ($res->count != 0) ? round($res->done / $res->count, 1) : 0 ;
                        $over = ($res->planned && $res->planned < $res->used) ? floor((($res->used - $res->planned) / $res->planned) * 60) : 0 ;
                        $completion = project_bar_graph_over($deliverable->done, $over, 60, 5);
                }
        }

	$completion = '';
        $milestonepix = (isset($deliverable->milestoneforced)) ? 'milestoneforced' : 'milestone' ; 
        $milestone = ($deliverable->milestoneid) ? "<img src=\"".$OUTPUT->pix_url("/p/{$milestonepix}", 'project')."\" title=\"".@$deliverable->milestoneabstract."\" />" : '';
	*/
        $milestone ='';
        $taskcount = project_print_entitycount('project_deliverable', 'project_task_to_deliv', $project->id, $group, 'deliv', 'task', $deliverable->id);
        $checkbox = ($canedit) ? "<input type=\"checkbox\" name=\"ids[]\" value=\"{$deliverable->id}\" />" : '' ;
        if (!$fullsingle){
           $hideicon = (!empty($deliverable->description)) ? 'hide' : 'hide_shadow' ;
	        //$hidedeliv = "<a href=\"javascript:toggle_show('{$numdeliv}','{$numdeliv}', '$CFG->wwwroot');\"><img name=\"eye{$numdeliv}\" src=\"".$OUTPUT->pix_url("/p/{$hideicon}", 'project')."\" alt=\"collapse\" /></a>";
           $hidedeliv = '';
       } else {
           $hidedeliv = '';
       }
	/*
	if ($deliverable->localfile){
		$fs = get_file_storage();
		
		$files = $fs->get_area_files($context->id, 'mod_project', 'deliverablelocalfile', $deliverable->id, '', true);
		if(count($files)>0){
			$file = reset($files);
			//$url = moodle_url::make_pluginfile_url($context->id, 'project', 'introimg', $project->id, '', $imgFileName);
			$path = '/'.$context->id.'/mod_project/deliverablelocalfile/'.$file->get_itemid().$file->get_filepath().$file->get_filename();
			$url = moodle_url::make_file_url('/pluginfile.php', $path, '');
			
			//$localfile = "{$project->course}/moddata/project/{$project->id}/".md5("project{$project->id}_{$group}")."/{$deliverable->localfile}";
		     // $abstract = "<a href=\"{$CFG->wwwroot}/file.php/{$localfile}\" target=\"_blank\">{$deliverable->abstract}</a>";
		     $abstract = html_writer::link($url, $file->get_filename());
		}else{
			$abstract = format_string($deliverable->abstract);
		}
	}
	else if ($deliverable->url){
	        $abstract = "<a href=\"{$deliverable->url}\" target=\"_blank\">{$deliverable->abstract}</a>";
	} else {
	     $abstract = format_string($deliverable->abstract);
        }
	*/
        $abstract = "<a href=\"view.php?id={$cmid}&amp;work=update&amp;delivid={$deliverable->id}&amp;view=".$stringelm."&amp;typeelm=".$typeelm."\">{$deliverable->abstract}</a>";
	//$head = "<table width='100%' class=\"nodecaption\"><tr><td align='left' width='70%'><b>{$checkbox} {$indent}<span class=\"level{$delivlevel}\"><a name=\"node{$deliverable->id}\"></a>D{$numdeliv} - {$abstract}</span></b></td><td align='right' width='30%'>{$taskcount}{$milestone} {$hidedeliv}</td></tr></table>";
        $customTable = "<table width='100%' class=\"nodecaption\"><tr><td align='left' width='70%'><span class=\"level{$delivlevel}\">{$abstract}</span></td></tr>";
/*
	$statusoption = project_get_option_by_key('delivstatus', $project->id, $deliverable->status);


        unset($innertable);
        $innertable = new html_table();
        $innertable->width = '100%';
        $innertable->style = array('parmname', 'parmvalue');
	$innertable->align = array ('left', 'left');
        //$innertable->data[] = array(get_string('status','project'), $statusoption->label);
        //$innertable->data[] = array(get_string('fromtasks','project'), $taskcount);
        $parms = project_print_project_table($innertable, true);
        $description = file_rewrite_pluginfile_urls($deliverable->description, 'pluginfile.php', $context->id, 'mod_project', 'deliverabledescription', $deliverable->id);

        if (!$fullsingle || $fullsingle === 'HEAD'){
                $initialDisplay = 'none';
                $description = close_unclosed_tags(shorten_text(format_text($description, $deliverable->descriptionformat), 800));
        } else {
                $initialDisplay = 'block';
                $description = format_string(format_text($description, $deliverable->descriptionformat));
        }
	
	$desc = "<div id='{$numdeliv}' class='entitycontent' style='display: {$initialDisplay};'>{$parms}".$description;
        if (!$fullsingle){
	        //$desc .= "<br/><a href=\"{$CFG->wwwroot}/mod/project/view.php?id={$cmid}&amp;view=view_detail&amp;objectId={$deliverable->id}&amp;objectClass=deliverable\" >".get_string('seedetail','project')."</a></div>"; 
        }
        $desc .= "</div>";

	$table = new html_table();
        $table->class = 'entity';    
	$table->head    = array ($head);
	$table->cellspacing = 1;
	$table->cellpadding = 1;
	$table->width = '100%';
	$table->align = array ('left');
	$table->data[] = array($desc);
	$table->rowclass[] = 'description';
	*/
	if ($canedit) {
     $link = array();
		/*$link[] = "<a href=\"view.php?id={$cmid}&amp;work=add&amp;fatherid={$deliverable->id}&amp;view=deliverables\">
     <img src=\"".$OUTPUT->pix_url('/p/newnode', 'project')."\" alt=\"".get_string('addsubdeliv', 'project')."\" /></a>";*/
     $link[] = "<a href=\"view.php?id={$cmid}&amp;work=update&amp;delivid={$deliverable->id}&amp;view=".$stringelm."&amp;typeelm=".$typeelm."\">
     <img src=\"".$OUTPUT->pix_url('/t/edit')."\" alt=\"".get_string('update')."\" /></a>";
     $link[] = "<p style=\"cursor:pointer;display:inline;\" onclick=\"javascript:var r=confirm('Etes vous sure de vouloir supprimer ".addslashes($deliverable->abstract)." ?');if (r==true){window.location.href='view.php?id={$cmid}&amp;work=delete&amp;delivid={$deliverable->id}&amp;view=".$stringelm."';}\"><img src=\"".$OUTPUT->pix_url('/t/delete')."\" alt=\"".get_string('delete')."\" /></p>";
		/*$link[] = "<a href=\"view.php?id={$cmid}&amp;work=delete&amp;delivid={$deliverable->id}&amp;view=deliverables\">
     <img src=\"".$OUTPUT->pix_url('/t/delete')."\" alt=\"".get_string('delete')."\" /></a>";*/
		/*
		if ($deliverable->ordering > 1)
		        $link[] = "<a href=\"view.php?id={$cmid}&amp;work=up&amp;delivid={$deliverable->id}&amp;view=deliverables#node{$deliverable->id}\">
				 <img src=\"".$OUTPUT->pix_url('/t/up')."\" alt=\"".get_string('up', 'project')."\" /></a>";
		if ($deliverable->ordering < $setSize)
		        $link[] = "<a href=\"view.php?id={$cmid}&amp;work=down&amp;delivid={$deliverable->id}&amp;view=deliverables#node{$deliverable->id}\">
				 <img src=\"".$OUTPUT->pix_url('/t/down')."\" alt=\"".get_string('down', 'project')."\" /></a>";
	        if ($deliverable->fatherid != 0)
		        $link[] = "<a href=\"view.php?id={$cmid}&amp;work=left&amp;delivid={$deliverable->id}&amp;view=deliverables#node{$deliverable->id}\">
				 <img src=\"".$OUTPUT->pix_url('/t/left')."\" alt=\"".get_string('left', 'project')."\" /></a>";
		if ($deliverable->ordering > 1)
		        $link[] = "<a href=\"view.php?id={$cmid}&amp;work=right&amp;delivid={$deliverable->id}&amp;view=deliverables#node{$deliverable->id}\">
             <img src=\"".$OUTPUT->pix_url('/t/right')."\" alt=\"".get_string('right', 'project')."\" /></a>";*/
		//$table->data[] = array($indent . implode (' ' , $link));
	     // $table->rowclass[] = 'controls';
             $customTable .= "<tr><td>".implode (' ' , $link)."</td></tr>";
         }
         $customTable .= "</table>";
         echo $customTable;



	//$table->style = "generaltable";
	//project_print_project_table($table);
	//unset($table);
     }

/**
* prints the heading section of the project
* @param project the project object, passed by reference (read only)
* @param group the actual group
* @return void prints only viewable sequences
*/
function project_print_heading(&$project, $group){
  global $CFG, $DB, $OUTPUT;

  $projectheading = $DB->get_record('project_heading', array('projectid' => $project->id, 'groupid' => $group));

        // if missing create one
  if (!$projectheading){
     $projectheading = new StdClass;
     $projectheading->id = 0;
     $projectheading->projectid = $project->id;
     $projectheading->groupid = $group;
     $projectheading->title = '';
     $projectheading->abstract = '';
     $projectheading->rationale = '';
     $projectheading->environment = '';
     $projectheading->organisation = '';
     $projectheading->department = '';                
     $DB->insert_record('project_heading', $projectheading);
 }
        //echo $OUTPUT->heading(get_string('projectis', 'project') . $projectheading->title);
 echo '<br/>';
 echo $OUTPUT->box_start('center', '100%');
 if ($projectheading->organisation != ''){
  echo $OUTPUT->heading(format_string($projectheading->organisation), 3);
  echo $OUTPUT->heading(format_string($projectheading->department), 4);
}
echo $OUTPUT->heading(get_string('abstract', 'project'), 2);
     // echo (empty($projectheading->abstract)) ? $project->intro : $projectheading->abstract ;
if ($projectheading->rationale != ''){
  echo $OUTPUT->heading(get_string('rationale', 'project'), 2);
  echo $projectheading->rationale, false;
}
if ($projectheading->environment != ''){
  echo $OUTPUT->heading(get_string('environment', 'project'), 2);
  echo format_string($projectheading->environment, false);
}
echo $OUTPUT->box_end();
}

/************************/
function project_print_resume($project, $currentGroupId, $fatherid, $numresume){

}

/**
* gets any option domain as an array of records. The domain defaults to the option set
* defined for a null projectid.
* @param string $domain the domain table to fetch
* @param int $projectid the project id the option set id for
* @return the array of domain records
*/
function project_get_options($domain, $projectid){
	global $DB;
	
  if (!function_exists('getLocalized')){
    function getLocalized(&$var){
      $var->label = get_string($var->label, 'project');
      $var->description = get_string($var->description, 'project');
  }

  function getFiltered(&$var){
      $var->label = format_string($var->label, 'project');
      $var->description = format_string($var->description, 'project');
  }
}

if (!$options = $DB->get_records_select('project_qualifier', " domain = ? AND    projectid = ? ", array($domain,$projectid))){
 if ($siteoptions = $DB->get_records_select('project_qualifier', " domain = ? AND    projectid = 0 ", array($domain))){
   $options = array_values($siteoptions);
   for($i = 0 ; $i < count($options) ; $i++){
     getLocalized($options[$i]);
 }
} else {
    $options = array();
}
} else {
  for($i = 0 ; $i < count($options) ; $i++){
    getFiltered($options[$i]);
}
}
return $options;
}

/**
* gets any option domain as an array of records. The domain defaults to the option set
* defined for a null projectid.
* @param string $domain the domain subtable to fetch
* @param int $projectid the project id the option set id for
* @param string $value the reference value
* @return an array with a single object
*/
function project_get_option_by_key($domain, $projectid, $value){
	global $DB;
	
  if (!function_exists('getLocalized')){
    function getLocalized(&$var){
      $var->truelabel = $var->label;
      $var->label = get_string($var->label, 'project');
      $var->description = get_string(@$var->description, 'project');
  }
}

if (!$option = $DB->get_record('project_qualifier', array('domain' => $domain, 'projectid' => $projectid, 'code' => $value))){
    if ($option = $DB->get_record('project_qualifier', array('domain' => $domain, 'projectid' => 0, 'code' => $value))){
      getLocalized($option);
  } else {
   $option->id = 0;
   $option->truelabel = 'default';
   $option->label = get_string('unqualified', 'project');
   $option->description = '';
}
}
return $option;
}

/**
* bronts a graphical bargraph with overhead signalling
* @param value the current value claculated against the regular width of the bargraph
* @param over the value of the overhead, in the width based scaling
* @param width the physical width of the bargraph (in pixels)
* @param height the physical height of the bargraph (in pixels)
* @param maxover the overhead width limit. Will produce an alternate overhead rendering if over is over.
*
*/
function project_bar_graph_over($value, $over, $width = 50, $height = 4, $maxover = 60){
  global $CFG;

  if ($value == -1) return "<img src=\"{$CFG->wwwroot}/mod/project/pix/p/graypixel.gif\" title=\"".get_string('nc','project')."\" width=\"{$width}\" height=\"{$height}\" />";
  $done = floor($width * $value / 100);
  $todo = floor($width * ( 1 - $value / 100));
  $bargraph = "<img src=\"{$CFG->wwwroot}/mod/project/pix/p/greenpixel.gif\" title=\"{$value}%\" width=\"{$done}\" height=\"{$height}\" />";
  $bargraph .= "<img src=\"{$CFG->wwwroot}/mod/project/pix/p/bluepixel.gif\" title=\"{$value}%\" width=\"{$todo}\" height=\"{$height}\" />";
  if ($over){
    $displayOver = (round($over/$width*100))."%";
    if ($over < $maxover){
      $bargraph .= "<img src=\"{$CFG->wwwroot}/mod/project/pix/p/redpixel.gif\" title=\"".get_string('overdone','project').':'.$displayOver."\" width=\"{$over}\" height=\"{$height}\" />";
  } else {
      $bargraph .= "<img src=\"{$CFG->wwwroot}/mod/project/pix/p/maxover.gif\" title=\"".get_string('overoverdone','project').':'.$displayOver."\" height=\"{$height}\" width=\"{$width}\" />";
  }
}        
return $bargraph;
}


/**
* checks for some circularities in the dependencies
* @param int $taskid the current task
* @param int $masterid the master task to be checked for
* @return boolean true/false
*/
function project_check_task_circularity($taskid, $masterid){
	global $DB;
	
  if ($slavetasks = $DB->get_records('project_task_dependency', array('master' => $taskid))){
    foreach($slavetasks as $aTask){
      if ($aTask->id == $masterid) return true;
      if (project_check_task_circularity($aTask->id, $masterid)) return true;
  }
}
return false;
}

/**
* prints an indicator of how much related objects there are
* @param table1 the table-tree of the requiring entity
* @param table2 the table where cross dependencies is
* @param project the current project context
* @param group the current gorup in project
* @param what what to search for (first key of crossdependency)
* @param relwhat relative to which other entity (second key of cross dependency)
* @param id an item root of the query. Will be expansed to all its subtree.
* @param whatlist a list of nodes resulting of a previous id expansion
*/
function project_print_entitycount($table1, $table2, $projectid, $groupid, $what, $relwhat, $id, $whatList = ''){
  global $CFG, $DB;

        // get concerned subtree if not provided
  if (!isset($whatList) || empty($whatList)){
     $whatList = str_replace(",", "','", project_get_subtree_list($table1, $id));
 }

	// assigned reqs by subspecs count
 $query = "
 SELECT 
 COUNT(*) as subs
 FROM
 {{$table2}}
 WHERE
 {$what}id IN ('{$whatList}') AND
 projectid = {$projectid} AND
 groupid = {$groupid}
 ";
 $res = $DB->get_record_sql($query);
 $subcount = "[" . $res->subs . "]";

	// directly assigned reqs count (must count separately)
 $query = "
 SELECT
 COUNT(t2.{$relwhat}Id) as subs
 FROM
 {{$table1}} AS t1
 LEFT JOIN
 {{$table2}} AS t2
 ON
 t1.id = t2.{$what}Id
 WHERE 
 t1.groupid = {$groupid} AND 
 t1.projectid = {$projectid} AND 
 t1.id = {$id}
 GROUP BY 
 t1.id
 ";
 $res = $DB->get_record_sql($query);
 if (!$res) 
     $res->subs = 0;
 else
     $res->subs += 0;
 if ($res->subs > 0 || $subcount > 0){
     $output = "<img src=\"{$CFG->wwwroot}/mod/project/pix/p/{$relwhat}.gif\" title=\"".get_string('bounditems', 'project', $relwhat)."\" />(".$res->subs."){$subcount}";
 } else {
     $output = '';
 }
 return $output;
}

/**
* prints a select box with commands applicable to the item selection
* @param additional an additional set of commands if needed
*
*/
function project_print_group_commands($additional = ''){
  global $CFG;

  $optionList[''] = get_string('choosewhat', 'project');
  $optionList['deleteitems'] = get_string('deleteselected', 'project');
  $optionList['copy'] = get_string('copyselected', 'project');
  $optionList['move'] = get_string('moveselected', 'project');
  $optionList['export'] = get_string('xmlexportselected', 'project');
  if (!empty($additional)){
    foreach($additional as $aCommand){
      $optionList[$aCommand] = get_string($aCommand.'selected', 'project');
  }
}
echo '<p>'.get_string('withchosennodes', 'project');
echo html_writer::select($optionList, 'cmd', '', array('' => get_string('choosewhat', 'project')), array('onchange' => 'sendgroupdata()'));
echo '</p>';
}
/**
 * Print a nicely formatted table. Hack from the original print_table from weblib.php
 *
 * @param array $table is an object with several properties.
 *         <ul<li>$table->head - An array of heading names.
 *         <li>$table->align - An array of column alignments
 *         <li>$table->size    - An array of column sizes
 *         <li>$table->wrap - An array of "nowrap"s or nothing
 *         <li>$table->data[] - An array of arrays containing the data.
 *         <li>$table->width    - A percentage of the page
 *         <li>$table->tablealign    - Align the whole table
 *         <li>$table->cellpadding    - Padding on each cell
 *         <li>$table->cellspacing    - Spacing between cells
 * </ul>
 * @param bool $return whether to return an output string or echo now
 * @return boolean or $string
 * @todo Finish documenting this function
 */
function project_print_project_table($table, $return=false) {
  $output = '';

  if (isset($table->align)) {
    foreach ($table->align as $key => $aa) {
      if ($aa) {
        $align[$key] = ' align="'. $aa .'"';
    } else {
        $align[$key] = '';
    }
}
}
if (isset($table->size)) {
    foreach ($table->size as $key => $ss) {
      if ($ss) {
        $size[$key] = ' width="'. $ss .'"';
    } else {
        $size[$key] = '';
    }
}
}
if (isset($table->wrap)) {
    foreach ($table->wrap as $key => $ww) {
      if ($ww) {
        $wrap[$key] = ' nowrap="nowrap" ';
    } else {
        $wrap[$key] = '';
    }
}
}

if (empty($table->width)) {
    $table->width = '80%';
}

if (empty($table->tablealign)) {
    $table->tablealign = 'center';
}

if (empty($table->cellpadding)) {
    $table->cellpadding = '5';
}

if (empty($table->cellspacing)) {
    $table->cellspacing = '1';
}

if (empty($table->class)) {
    $table->class = 'generaltable';
}

$tableid = empty($table->id) ? '' : 'id="'.$table->id.'"';

$output .= '<table width="'.$table->width.'" border="0" align="'.$table->tablealign.'" ';
$output .= " cellpadding=\"$table->cellpadding\" cellspacing=\"$table->cellspacing\" class=\"$table->class\" $tableid>\n";

$countcols = 0;

if (!empty($table->head)) {
    $countcols = count($table->head);
    $output .= '<tr>';
    foreach ($table->head as $key => $heading) {

      if (!isset($size[$key])) {
        $size[$key] = '';
    }
    if (!isset($align[$key])) {
        $align[$key] = '';
    }
    $output .= '<th valign="top" '. $align[$key].$size[$key] .' nowrap="nowrap" class="c'.$key.'">'. $heading .'</th>';
}
$output .= '</tr>'."\n";
}

if (!empty($table->data)) {
    $oddeven = 1;
    foreach ($table->data as $key => $row) {
      $oddeven = $oddeven ? 0 : 1;
      $output .= '<tr class="r'.$oddeven.'">'."\n";
      if ($row == 'hr' and $countcols) {
        $output .= '<td colspan="'. $countcols .'"><div class="tabledivider"></div></td>';
                        } else {    /// it's a normal row of data
                        foreach ($row as $key => $item) {
                          if (!isset($size[$key])) {
                            $size[$key] = '';
                        }
                        if (!isset($align[$key])) {
                            $align[$key] = '';
                        }
                        if (!isset($wrap[$key])) {
                            $wrap[$key] = '';
                        }
                        $output .= '<td '. $align[$key].$size[$key].$wrap[$key] .' class="'.$table->style[$key].'">'. $item .'</td>';
                    }
                }
                $output .= '</tr>'."\n";
            }
        }
        $output .= '</table>'."\n";

        if ($return) {
            return $output;
        }

        echo $output;
        return true;
    }

/**
* calculates the autograde.
* Autograde is the mean of : 
* - the ratio of uncovered requirements (full chain to deliverables)
* - the ratio of uncovered deliverables (full chain to reqauirements)
* - the completion ratio over requirements
* - the balance of charge between members
* @param object $project the project
* @param int group
* @return the grade
*/
function project_autograde($project, $groupid){
  global $CFG, $DB;

  echo $OUTPUT->heading(get_string('autograde', 'project'));
        // get course module
  $module = $DB->get_record('modules', array('name' => 'project'));
  $cm = $DB->get_record('course_modules', array('course' => $project->course, 'instance' => $project->id, 'module' => $module->id));
  $course = $DB->get_record('course', array('id' => $project->course));
  $coursecontext = context_course::instance($course->id);
        // step 1 : get requirements to cover as an Id list
  $rootRequirements = $DB->get_records_select('project_requirement', "projectid = ? AND groupid = ? AND fatherid = 0", array($project->id, $group));
  $effectiveRequirements = array();
  foreach($rootRequirements as $aRoot){
    $effectiveRequirements = array_merge($effectiveRequirements, project_count_leaves('project_requirement', $aRoot->id, true));
}
$effectiveRequirementsCount = count($effectiveRequirements);
        // now we know how many requirements are to be covered
        // For each of those elements, do we have a chain to deliverables ?
        // chain origin can start from an upper requirement
$coveredReqs = 0;
foreach($effectiveRequirements as $aRequirement){
    $upperBranchList = project_tree_get_upper_branch('project_requirement', $aRequirement, true, false);
    $upperBranchList = str_replace(',', "','", $upperBranchList);
    $query = "
    SELECT
    COUNT(*) as coveringChains
    FROM
    {project_spec_to_req} as str,
    {project_task_to_spec} as tts,
    {project_task_to_deliv} as ttd
    WHERE
    str.reqid IN ('{$upperBranchList}') AND
    str.specid = tts.specid AND 
    tts.taskid = ttd.taskid AND
    str.projectid = {$project->id} AND
    str.groupid = {$groupid}
    ";
    $res = $DB->get_record_sql($query);
    if($res->coveringChains > 0){
      $coveredReqs++;
  }
}
$requRate = ($effectiveRequirementsCount) ? $coveredReqs / $effectiveRequirementsCount : 0 ;
echo '<br/><b>'.get_string('requirementsrate', 'project').' :</b> '.$coveredReqs.' '.get_string('over', 'project').' '.$effectiveRequirementsCount.' : '.sprintf("%.02f", $requRate);
        // now we know how many requirements are really covered directly or indirectly

        // step 2 : get deliverables to cover as an Id list
$rootDeliverables = $DB->get_records_select('project_deliverable', "projectid = ? AND groupid = ? AND fatherid = 0", array($project->id, $group));
$effectiveDeliverables = array();
foreach($rootDeliverables as $aRoot){
    $effectiveDeliverables = array_merge($effectiveDeliverables, project_count_leaves('project_deliverable', $aRoot->id, true));
}
$effectiveDeliverablesCount = count($effectiveDeliverables);
        // now we know how many deliverables are to be covered
        // For each of those elements, do we have a chain to requirements ?
        // chain origin can start from an upper deliverable
$coveredDelivs = 0;
foreach($effectiveDeliverables as $aDeliverable){
    $upperBranchList = project_tree_get_upper_branch('project_deliverable', $aDeliverable, true, false);
    $upperBranchList = str_replace(',', "','", $upperBranchList);
    $query = "
    SELECT
    COUNT(*) as coveringChains
    FROM
    {project_spec_to_req} as str,
    {project_task_to_spec} as tts,
    {project_task_to_deliv} as ttd
    WHERE
    str.specid = tts.specid AND 
    tts.taskid = ttd.taskid AND
    ttd.delivid IN ('{$upperBranchList}') AND
    str.projectid = {$project->id} AND
    str.groupid = {$groupid}
    ";
    $res = $DB->get_record_sql($query);
    if($res->coveringChains > 0){
      $coveredDelivs++;
  }
}
$delivRate = ($effectiveDeliverablesCount) ? $coveredDelivs / $effectiveDeliverablesCount : 0 ;
echo '<br/><b>'.get_string('deliverablesrate', 'project').' :</b> '.$coveredDelivs.' '.get_string('over', 'project').' '.$effectiveDeliverablesCount.' : '.sprintf("%.02f", $delivRate);
        // now we know how many deliverables are really covered directly or indirectly     
        // step 3 : calculating global completion indicator on tasks (only meaning root tasks is enough)
$rootTasks = $DB->get_records_select('project_task', "projectid = ? AND groupid = ? AND fatherid = 0", array($project->id, $group)); 
$completion = 0;
if ($rootTasks){
    foreach($rootTasks as $aTask){
      $completion += $aTask->done;
  }
  $done = (count($rootTasks)) ? sprintf("%.02f", $completion / count($rootTasks) / 100) : 0 ;
  echo '<br/><b>'.get_string('completionrate', 'project').' :</b> '.$done;
}

        // step 4 : calculating variance (balance) of task assignation between members
if ($rootTasks){
    $leafTasks = array();
                // get leaves
    foreach($rootTasks as $aTask){
      $leafTasks = array_merge($leafTasks, project_count_leaves('project_task', $aTask->id, true));
  }
                // collecting and accumulating charge planned
                // get student list
  if (!groups_get_activity_groupmode($cm, $course)){
      $groupStudents = get_users_by_capability($coursecontext, 'mod/project:canbeevaluated', 'u.id; u.firstname, u.lastname, u.mail, u.picture', 'u.lastname');
  } else {
      $groupmembers = get_group_members($groupid);
      $groupStudents = array();
      if ($groupmembers){
        foreach($groupmembers as $amember){
          if (has_capability('mod/project:becomennoted', $coursecontext, $amember->id)){
            $groupStudents[] = clone($amember);
        }
    }
}
}

                // intitializes charge table
foreach($groupStudents as $aStudent){
  $memberCharge[$aStudent->id] = 0;
}
                // getting real charge
foreach($leafTasks as $aLeaf){
  $memberCharge[$aLeaf->assignee] = @$memberCharge[$aLeaf->assignee] + $aLeaf->planned;
}
                // calculating charge mean and variance
$totalCharge = array_sum(array_values($memberCharge));
$assigneeCount = count(array_keys($memberCharge));
$meanCharge = ($assigneeCount == 0) ? 0 : $totalCharge / $assigneeCount ;
$quadraticSum = 0;
foreach(array_values($memberCharge) as $aCharge){
  $quadraticSum += ($aCharge - $meanCharge) * ($aCharge - $meanCharge);
}
$sigma = sqrt($quadraticSum/$assigneeCount);
echo '<br/><b>' . get_string('chargedispersion', 'project') . ' :</b> ' . sprintf("%.02f", $sigma);
}
$totalGrade = round((0 + @$done + @$requRate + @$delivRate) / 3, 2);
echo '<br/><b>' . get_string('mean', 'project') . ' :</b> ' . sprintf("%.02f", $totalGrade);
if ($project->grade > 0){
    echo '<br/><b>' . get_string('scale', 'project') . ' :</b> ' . $project->grade;
    echo '<br/><b>' . get_string('grade', 'project') . ' :</b> ' . round($project->grade * $totalGrade);
}
return $totalGrade;
}

/**
* get all ungrouped students in a course.
* @param int $courseid
* @return an array of users
*/
function project_get_users_not_in_group($courseid){
  $coursecontext = context_course::instance($courseid);
  $users = get_users_by_capability($coursecontext, 'mod/project:beassignedtasks', 'u.id, firstname,lastname,picture,email', 'lastname');
  if ($users){
    if ($groups = groups_get_all_groups($courseid)){
      foreach($groups as $aGroup){
         if ($aGroup->id == 0) continue;
         $groupset = groups_get_members($aGroup->id);
         if ($groupset){
            foreach(array_keys($groupset) as $userid){
               unset($users[$userid]);
           }
       }
   }
}
}
return $users;
}

/**
* get group users according to group situation
* @param int $courseid
* @param object $cm
* @param int $groupid
* @return an array of users
*/
function project_get_group_users($courseid, $cm, $groupid){
	global $DB;
	
  $course = $DB->get_record('course', array('id' => $courseid));
  if (!groups_get_activity_groupmode($cm, $course)){
    $coursecontext = context_course::instance($courseid);
    $users = get_users_by_capability($coursecontext, 'mod/project:beassignedtasks', 'u.id, firstname,lastname,picture,email', 'lastname');
                // $users = get_course_users($courseid);
}
else{
    if ($groupid){
      $users = groups_get_members($groupid);
  }
  else{
                        // we could not rely on the legacy function
      $users = project_get_users_not_in_group($courseid);
  }
  if($users){
      $context = context_module::instance($cm->id);
             // equ of array_filter, but needs variable parameter so we cound not use it.
      foreach($users as $userid => $user){
        if (!has_capability('mod/project:beassignedtasks', $context, $user->id)){
          unset($users[$userid]);
      }
  }
}
}
return $users;
}

/**
*
* @param object $project
* @param int $groupid
* @uses $COURSE
*/
function project_get_full_xml(&$project, $groupid){
  global $COURSE, $CFG, $DB;

  include_once "xmllib.php";
        // getting heading
  $heading = $DB->get_record('project_heading', array('projectid' => $project->id, 'groupid' => $groupid));
  $projects[$heading->projectid] = $heading;
  $xmlheading = recordstoxml($projects, 'project', '', false, null);

        // getting requirements
  project_tree_get_tree('project_requirement', $project->id, $groupid, $requirements, 0);
  $strengthes = $DB->get_records_select('project_qualifier', " projectid = ? AND domain = 'strength' ", array($project->id));
  if (empty($strenghes)){
    $strengthes = $DB->get_records_select('project_qualifier', " projectid = 0 AND domain = 'strength' ", array());
}
$xmlstrengthes = recordstoxml($strengthes, 'strength', '', false, 'project');
$xmlrequs = recordstoxml($requirements, 'requirement', $xmlstrengthes, false);

        // getting specifications
project_tree_get_tree('project_specification', $project->id, $groupid, $specifications, 0);
$priorities = $DB->get_records_select('project_qualifier', " projectid = ? AND domain = 'priority' ", array($project->id));
if (empty($priorities)){
    $priorities = $DB->get_records_select('project_qualifier', " projectid =    0 AND domain = 'priority' ", array());
}
$severities = $DB->get_records_select('project_qualifier', " projectid = ? AND domain = 'severity' ", array($project->id));
if (empty($severities)){
    $severities = $DB->get_records_select('project_qualifier', " projectid = 0 AND domain = 'severity' ", array());
}
$complexities = $DB->get_records_select('project_qualifier', " projectid = ? AND domain = 'complexity' ", array($project->id));
if (empty($complexities)){
    $complexities = $DB->get_records_select('project_qualifier', " projectid = 0 AND domain = 'complexity' ", array());
}
$xmlpriorities = recordstoxml($priorities, 'priority_option', '', false, 'project');
$xmlseverities = recordstoxml($severities, 'severity_option', '', false, 'project');
$xmlcomplexities = recordstoxml($complexities, 'complexity_option', '', false, 'project');
$xmlspecs = recordstoxml($specifications, 'specification', $xmlpriorities.$xmlseverities.$xmlcomplexities, false, null);

        // getting tasks
project_tree_get_tree('project_task', $project->id, $groupid, $tasks, 0);
if (!empty($tasks)){
 foreach($tasks as $taskid => $task){
    $tasks[$taskid]->taskstart = ($task->taskstart) ? usertime($task->taskstart) : 0 ;
    $tasks[$taskid]->taskend = ($task->taskend) ? usertime($task->taskend) : 0 ;
}
}
$worktypes = $DB->get_records_select('project_qualifier', " projectid = ? AND domain = 'worktype' ", array($project->id));
if (empty($worktypes)){
  $worktypes = $DB->get_records_select('project_qualifier', " projectid = 0 AND domain = 'worktype' ", array());
}
$taskstatusses = $DB->get_records_select('project_qualifier', " projectid = ? AND domain = 'taskstatus' ", array($project->id));
if (empty($taskstatusses)){
  $taskstatusses = $DB->get_records_select('project_qualifier', " projectid = 0 AND domain = 'taskstatus' ");
}
$xmlworktypes = recordstoxml($worktypes, 'worktype_option', '', false, 'project');
$xmltaskstatusses = recordstoxml($taskstatusses, 'task_status_option', '', false, 'project');
$xmltasks = recordstoxml($tasks, 'task', $xmlworktypes.$xmltaskstatusses, false, null);

        // getting deliverables
project_tree_get_tree('project_deliverable', $project->id, $groupid, $deliverables, 0);
$delivstatusses = $DB->get_records_select('project_qualifier', " projectid = ? AND domain = 'delivstatus' ", array($project->id));
if (empty($delivstatusses)){
  $delivstatusses = $DB->get_records_select('project_qualifier', " projectid = 0 AND domain = 'delivstatus' ", array());
}
$xmldelivstatusses = recordstoxml($delivstatusses, 'deliv_status_option', '', false, 'project');
$xmldelivs = recordstoxml($deliverables, 'deliverable', $xmldelivstatusses, false, null);

        // getting milestones
project_tree_get_list('project_milestone', $project->id, $groupid, $milestones, 0);
$xmlmiles = recordstoxml($milestones, 'milestone', '', false, null);

        /// Finally, get the master record and make a full XML with it
$project = $DB->get_record('project', array('id' => $project->id));
$project->wwwroot = $CFG->wwwroot;
$projects[$project->id] = $project;
$project->xslfilter = (empty($project->xslfilter)) ? $CFG->dirroot.'/mod/project/xsl/default.xsl' : $CFG->dataroot."/{$COURSE->id}/moddata/project/{$project->id}/{$project->xslfilter}" ;
$project->cssfilter = (empty($project->cssfilter)) ? $CFG->dirroot.'/mod/project/xsl/default.css' : $CFG->dataroot."/{$COURSE->id}/moddata/project/{$project->id}/{$project->cssfilter}" ;
$xmlstylesheet = "<?xml-stylesheet href=\"{$project->xslfilter}\" type=\"text/xsl\"?>\n";
$xml = recordstoxml($projects, 'project', $xmlheading.$xmlrequs.$xmlspecs.$xmltasks.$xmldelivs.$xmlmiles, true, null, $xmlstylesheet);

return $xml;        
}

/**
* utility functions for cleaning user-edited text which would break XHTML rules.
*
*/

/**
*
* @param string $text the input text fragment to be checked
* @param string $taglist a comma separated list of tag name that should be checked for correct closure
*/
function close_unclosed_tags($text, $taglist='p,b,i,li'){
  $tags = explode(',', $taglist);
  foreach($tags as $aTag){
    $text = closeUnclosed($text, "<{$aTag}>", "</{$aTag}>");
}
return $text;
}

/**
* this is an internal function called by close_unclosed_tags
* @param string $string the input HTML string
* @param string $opentag an opening HTML tag we want to check closed
* @param string $closetag what to close with
*/
function closeUnclosed($string, $opentag, $closetag) {
  $count = 0;
  $opensizetags = 0;
  $closedsizetags = 0;
  for($i = 0; $i <= strlen($string); $i++) {
    $pos = strpos($string, $opentag, $count);
    if(!($pos === false)) {
      $opensizetags++;
      $count = ($pos += 1);
  }
}
$count = 0;
for($i = 0; $i <= strlen($string); $i++) {
    $pos = strpos($string, $closetag, $count);
    if(!($pos === false)) {
      $closedsizetags++;
      $count = ($pos += 1);
  }
}
while($closedsizetags < $opensizetags) {
    $string .= "$closetag\n";
    $closedsizetags++;
}
return $string;
}

/**
* Get qualifier domain or a domain value
* @param string $domain the qualifier domain name
* @param int $id if id is given returns a single value
* @param boolean $how this parameter tels how to search results. When an id is given it tells what the id is as an identifier (a Mysql record id or a code). 
* @param int $scope the value scope which is assimilable to a project id or 0 if global scope
* @param string $sortby 
*/
function project_get_domain($domain, $id, $how = false, $scope, $sortby = 'label'){
	global $DB;

  if (empty($id)){

                // internationalize if needed (for array walks)
    if (!function_exists('format_string_walk')){
      function format_string_walk(&$a){
        $a = $OUTPUT->format_string($a);
    }
}

if ($how == 'menu'){
  if ($records = $DB->get_records_select_menu("project_qualifier", " projectid = ? AND domain = ? ", array($scope, $domain), $sortby, 'id, label')){
    array_walk($records, 'format_string_walk');
    return $records;
} else {
    return null;
}
} else {
  return $DB->get_records_select("project_qualifier", " projectid = ? AND domain = ? ", array($scope, $domain), $sortby);
}
}
if ($how == 'bycode'){
    return format_string($DB->get_field("project_qualifier", 'label', array('domain' => $domain, 'projectid' => $scope, 'code' => $id)));
} else {
    return format_string($DB->get_field("project_$domain", 'label', array('domain' => $domain, 'projectid' => $scope, 'id' => $id)));
}
}

/**
* print validation will print a requirement tree with validation columns
*
*/
function project_print_validations($project, $groupid, $fatherid, $cmid){
	global $CFG, $USER, $DB, $OUTPUT;

	static $level = 0;

  if ($validationsessions = $DB->get_records_select('project_valid_session', " projectid = ? AND groupid = ? ", array($project->id, $groupid))){
    $validationcaptions = '';
    $deletestr = '<span title="'.get_string('delete').'" style="color:red">x</span>';
    $closestr = get_string('close', 'project');
    $updatestr = get_string('update', 'project');
    foreach($validationsessions as $sessid => $session){
      $validationsessions[$sessid]->states = $DB->get_records('project_valid_state', array('validationsessionid' => $session->id), '', 'reqid,status,comment');
      $validationcaption = '&lt;'.userdate($session->datecreated).'&gt;';
      if (has_capability('mod/project:managevalidations', context_module::instance($cmid))){
         $validationcaption .= " <a href=\"{$CFG->wwwroot}/mod/project/view.php?id=$cmid&view=validations&work=dodelete&validid={$sessid}\">$deletestr</a>";
     }
     if ($session->dateclosed == 0){
         if (has_capability('mod/project:managevalidations', context_module::instance($cmid))){
            $validationcaption .= " <a href=\"{$CFG->wwwroot}/mod/project/view.php?id=$cmid&view=validations&work=close&validid={$sessid}\">$closestr</a>";
        }
        if (has_capability('mod/project:validate', context_module::instance($cmid))){
            $validationcaption .= " <a href=\"{$CFG->wwwroot}/mod/project/view.php?id=$cmid&view=validation&validid={$sessid}\">$updatestr</a>";
        }
    }
    $validationcaptions .= "<td>$validationcaption</td>";
}
if ($level == 0){
 $caption = "<table width='100%' class=\"validations\"><tr><td align='left' width='50%'></td>$validationcaptions</tr></table>";
 echo $caption;
}
if (!empty($project->projectusesrequs)){
 $entityname = 'requirement';
} elseif (!empty($project->projectusesspecs)){
 $entityname = 'specification';
} elseif (!empty($project->projectusesdelivs)) {
 $entityname = 'deliverable';
} else {
 print_error('errornovalidatingentity', 'project');
}
$query = "
SELECT 
e.*,
c.collapsed
FROM 
{project_{$entityname}} e
LEFT JOIN
{project_collapse} c
ON
e.id = c.entryid AND
c.entity = '{$entityname}s' AND
c.userid = $USER->id
WHERE 
e.groupid = $groupid AND 
e.projectid = {$project->id} AND 
fatherid = $fatherid
GROUP BY
e.id
ORDER BY 
ordering
";
if ($entities = $DB->get_records_sql($query)) {
 $i = 1;
 foreach($entities as $entity){
   echo "<div class=\"nodelevel{$level}\">";
   $level++;
   project_print_single_entity_validation($validationsessions, $entity, $project, $groupid, $cmid, count($entities), $entityname);
   $visibility = ($entity->collapsed) ? 'display: none' : 'display: block' ; 
   echo "<div id=\"sub{$entity->id}\" class=\"treenode\" style=\"$visibility\" >";
   project_print_validations($project, $groupid, $entity->id, $cmid);
   echo "</div>";
   $level--;
   echo "</div>";
}
} else {
 if ($level == 0){
    echo $OUTPUT->box_start();
    print_string('emptyproject', 'project');
    echo $OUTPUT->box_end();
}
}
} else {
  if ($level == 0){
     echo $OUTPUT->box_start();
     print_string('novalidationsession', 'project');
     echo $OUTPUT->box_end();
 }
}
}

/**
* prints a single validation entity object
* @param entity the current entity to print
* @param project the current project
* @param group the current group
* @param cmid the current coursemodule (useful for making urls)
* @param setSize the size of the set of objects we are printing an item of
* @param fullsingle true if prints a single isolated element
*/
function project_print_single_entity_validation(&$validationsessions, &$entity, &$project, $group, $cmid, $countentities, $entityname){
  global $CFG, $USER, $DB;

  static $classswitch = 'even';

  $context = context_module::instance($cmid);
  $canedit = has_capability('mod/project:validate', $context);
  $numrec = implode('.', project_tree_get_upper_branch('project_'.$entityname, $entity->id, true, true));
  if (project_count_subs('project_'.$entityname, $entity->id) > 0) {
    $hidesub = "<a href=\"javascript:toggle('{$entity->id}','sub{$entity->id}');\"><img name=\"img{$entity->id}\" src=\"{$CFG->wwwroot}/mod/project/pix/p/switch_minus.gif\" alt=\"collapse\" /></a>";
} else {
    $hidesub = "<img src=\"{$CFG->wwwroot}/mod/project/pix/p/empty.gif\" />";
}
$validations = $DB->get_records_select('project_valid_state', " projectid = ? AND groupid = ? AND reqid = ? ", array($project->id, $group, $entity->id));

$level = count(explode('.', $numrec)) - 1;
$indent = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $level);

$validationcells = '';
foreach($validationsessions as $session){
    if (!isset($session->states[$entity->id])){
       $newstate->projectid = $project->id;
       $newstate->reqid = $entity->id;
       $newstate->groupid = $group;
       $newstate->validationsessionid = $session->id;
       $newstate->validatorid = $USER->id;
       $newstate->lastchangeddate = time();
       $newstate->status = 'UNTRACKED';
       $newstate->comment = '';
       $stateid = $DB->insert_record('project_valid_state', $newstate);
       $session->states[$entity->id] = $newstate;
   }
   $colwidth = floor(50 / count($validationsessions));
   $validationcells .= '<td width="'.$colwidth.'%" class="validationrowbordered '.$classswitch.' validation-'.$session->states[$entity->id]->status.'">';
   $validationcells .= '<span title="'.$session->states[$entity->id]->comment.'">'.get_string(strtolower($session->states[$entity->id]->status), 'project').'</span>';
   $validationcells .= '</td>';
}

$entitymark = strtoupper(substr($entityname, 0, 1));
$head = "<table width='100%' class=\"nodecaption\"><tr valign=\"top\"><td align='left' width='50%' class=\"validationrow $classswitch\"><span class=\"level{$level}\">{$indent}{$hidesub} <a name=\"req{$entity->id}\"></a>{$entitymark}{$numrec} - ".format_string($entity->abstract)."</span></td>$validationcells</tr></table>";
$classswitch = ($classswitch == 'odd') ? 'even' : 'odd' ;

echo $head;
}

function project_print_validation_states_form($validsessid, &$project, $groupid, $fatherid = 0, $cmid = 0){
	global $CFG, $USER, $DB;
	static $level = 0;

	if (!empty($project->projectusesrequs)){
		$entityname = 'requirement';
	} elseif (!empty($project->projectusesspecs)){
		$entityname = 'specification';
	} elseif (!empty($project->projectusesdelivs)) {
		$entityname = 'deliverable';
	} else {
		print_error('errornovalidatingentity', 'project');
	}
	$sql = "
  SELECT 
  vs.*,
  c.collapsed,
  e.abstract,
  e.fatherid
  FROM
  {project_{$entityname}} e
  LEFT JOIN
  {project_collapse} c
  ON
  e.id = c.entryid AND
  c.entity = '{$entityname}s' AND
  c.userid = $USER->id
  LEFT JOIN
  {project_valid_state} vs
  ON
  e.id = vs.reqid AND
  vs.projectid = {$project->id} AND
  vs.validationsessionid = $validsessid
  WHERE 
  e.groupid = $groupid AND 
  e.projectid = {$project->id} AND 
  e.fatherid = $fatherid
  GROUP BY
  e.id
  ORDER BY 
  ordering
  ";
  if ($states = $DB->get_records_sql($sql)){
     echo "<form name=\"statesform\" action=\"#\" method=\"POST\" >";
     $i = 1;
     foreach($states as $state){
        echo "<div class=\"nodelevel{$level}\">";
        $level++;
        project_print_single_validation_form($state, $entityname);

        $visibility = ($state->collapsed) ? 'display: none' : 'display: block' ; 
        echo "<div id=\"sub{$state->reqid}\" class=\"treenode\" style=\"$visibility\" >";
        project_print_validation_states_form($validsessid, $project, $groupid, $state->reqid, $cmid);
        echo "</div>";
        $level--;
        echo "</div>";
    }

    if ($level == 0){
     $updatestr = get_string('update');
     echo "<center><input type=\"submit\" name=\"go_btn\" value=\"$updatestr\" >";
     echo "</form>";
 }
}
}

/**
*
*
*/
function project_print_single_validation_form($state, $entityname){
	global $CFG;

  $numentity = implode('.', project_tree_get_upper_branch('project_'.$entityname, $state->reqid, true, true));
  if (project_count_subs('project_'.$entityname, $state->reqid) > 0) {
    $hidesub = "<a href=\"javascript:toggle('{$state->reqid}','sub{$state->reqid}');\"><img name=\"img{$state->reqid}\" src=\"{$CFG->wwwroot}/mod/project/pix/p/switch_minus.gif\" alt=\"collapse\" /></a>";
} else {
    $hidesub = "<img src=\"{$CFG->wwwroot}/mod/project/pix/p/empty.gif\" />";
}

$entitylevel = count(explode('.', $numentity)) - 1;
$indent = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $entitylevel);

echo '<table width="100%" >';
echo '<tr valign="top">';
echo "<td width=\"*\" class=\"level{$entitylevel}\">$indent $hidesub $numentity $state->abstract</td>";

echo '<td width="20%" class="validation-'.$state->status.'">';
$validationstatus['UNTRACKED'] = get_string('untracked', 'project');
$validationstatus['REFUSED'] = get_string('refused', 'project');
$validationstatus['MISSING'] = get_string('missing', 'project');
$validationstatus['BUGGY'] = get_string('buggy', 'project');
$validationstatus['TOENHANCE'] = get_string('toenhance', 'project');
$validationstatus['ACCEPTED'] = get_string('accepted', 'project');
$validationstatus['REGRESSION'] = get_string('regression', 'project');
echo html_writer::select($validationstatus, 'state_'.$state->id, $state->status);
echo '</td>';
echo '<td width="25%">';
echo "<textarea name=\"comment_{$state->id}\" rows=\"3\" cols=\"20\">{$state->comment}</textarea>";
echo '</td>';			
echo '</tr>';
echo '</table>';

}

/**
* check milestone constraints
*
*/
function milestone_checkConstraints($project, $milestone){
  global $CFG, $DB;

  $control = NULL;
  switch($project->timeunit){
    case HOURS : $plannedtime = 3600 ; break ;
    case HALFDAY : $plannedtime = 3600 * 12 ; break ;
    case DAY : $plannedtime = 3600 * 24 ; break ;
    default : $plannedtime = 0;
}
        // checking too soon task
$query = "
SELECT
id,
abstract,
MAX(taskend) as latest
FROM
{project_task}
WHERE
milestoneid = {$milestone->id}
GROUP BY
milestoneid
";
$latestTask = $DB->get_record_sql($query);
if($latestTask && $milestone->deadline < $latestTask->latest){
    $control['milestonedeadline'] = get_string('assignedtaskendsafter','project') . '<br/>' . userdate($latestTask->latest);
}
return $control;
}

/**
*
*
*
*/
        /**
        * a form constraint checking function
        * @param object $project the surrounding project cntext
        * @param object $task form object to be checked
        * @return a control hash array telling error statuses
        */
        function task_checkConstraints($project, $task){
          $control = NULL;
          switch($project->timeunit){
            case HOURS : $plannedtime = 3600 ; break ;
            case HALFDAY : $plannedtime = 3600 * 12 ; break ;
            case DAY : $plannedtime = 3600 * 24 ; break ;
            default : $plannedtime = 0;
        }
                // checking too soon task
        if ($task->taskstartenable && $task->taskstart < $project->projectstart){
            $control['taskstartdate'] = get_string('tasktoosoon','project') . '<br/>' . userdate($project->projectstart);
        }
                // task too late (planned to milestone)
        if($task->taskstartenable && $task->milestoneid){
            $milestone = $DB->get_record('project_milestone', array('projectid' => $project->id, 'id' => $task->milestoneid));
            if ($milestone->deadlineenable && ($task->taskstart + $plannedtime > $milestone->deadline)){
              $control['taskstartdate'] = get_string('taskstartsaftermilestone','project') . '<br/>' . userdate($milestone->deadline);
          }
      }
                // task too late (absolute)
      elseif($task->taskstartenable && ($task->taskstart + $plannedtime > $project->projectend)){
        $control['taskstartdate'] = get_string('tasktoolate','project') . '<br/>' . userdate($project->projectend);
    }
                // checking too late end
    elseif($task->taskendenable && $task->milestoneid){
        $milestone = $DB->get_record('project_milestone', array('projectid' => $project->id, 'id' => $task->milestoneid));
        if ($milestone->deadlineenable && ($task->taskend > $milestone->deadline)){
          $control['taskenddate'] = get_string('taskfinishesaftermilestone','project') . '<br/>' . userdate($milestone->deadline);
      }
  }
                // checking too late end
  elseif($task->taskendenable && $task->taskend > $project->projectend){
    $control['taskenddate'] = get_string('taskfinishestoolate','project') . '<br/>' . userdate($project->projectend);
}
                // checking switched end and start
elseif($task->taskendenable && $task->taskstartenable && $task->taskend <= $task->taskstart){
    $control['taskenddate'] = get_string('taskfinishesbeforeitstarts','project');
}
                // checking unfeseabletask
elseif($task->taskendenable && $task->taskstartenable && $task->taskend < $task->taskstart + $plannedtime){
    $control['taskenddate'] = get_string('tasktooshort','project') . '<br/> >> ' . userdate($task->taskstart + $plannedtime);
}
return $control;
}

function project_check_startup_level($entity, $fatherid, &$level, &$startuplevelcheck){
   global $DB;

   if (!$startuplevelcheck){
      if (!$fatherid){
         $level = 0;
     } else {
         $level = 1;
         $rec->fatherid = $fatherid;
         while($rec->fatherid){
            $rec = $DB->get_record('project_'.$entity, array('id' => $rec->fatherid), 'id, fatherid');
            $level++;
        }
    }
    $startuplevelcheck = true;
}
}

function project_print_localfile($deliverable, $cmid, $type=NULL, $align="left") {
   global $CFG, $DB, $OUTPUT;

   if (!$context = get_context_instance(CONTEXT_MODULE, $cmid)) {
     return '';
 }

 $fs = get_file_storage();

 $imagereturn = '';
 $output = '';

 if ($files = $fs->get_area_files($context->id, 'mod_project', 'localfile', $deliverable->id, "timemodified", false)) {
     foreach ($files as $file) {
       $filename = $file->get_filename();
       $mimetype = $file->get_mimetype();
       $iconimage = '<img src="'.$OUTPUT->pix_url(file_mimetype_icon($mimetype)).'" class="icon" alt="'.$mimetype.'" />';
       $path = file_encode_url($CFG->wwwroot.'/pluginfile.php', '/'.$context->id.'/mod_project/localfile/'.$deliverable->id.'/'.$filename);

       if ($type == 'html') {
         $output .= "<a href=\"$path\">$iconimage</a> ";
         $output .= "<a href=\"$path\">".s($filename)."</a>";
         $output .= "<br />";

     } else if ($type == 'text') {
         $output .= "$strattachment ".s($filename).":\n$path\n";

     } else {
         if (in_array($mimetype, array('image/gif', 'image/jpeg', 'image/png'))) {
	                                        // Image attachments don't get printed as links
           $imagereturn .= "<br /><img src=\"$path\" alt=\"\" />";
       } else {
           $output .= "<a href=\"$path\">$iconimage</a> ";
           $output .= format_text("<a href=\"$path\">".s($filename)."</a>", FORMAT_HTML, array('context'=>$context));
           $output .= '<br />';
       }
   }
}
}

if ($type) {
 return $output;
} else {
 echo $output;
 return $imagereturn;
}
}
function project_print_messages($project, $group, $cmid, $parent, $propagated=null){
   global $CFG, $USER, $DB, $OUTPUT;
   echo "<h2>".get_string('disculiste','project')."</h2>";
   $query = "
   SELECT 
   d.*
   FROM 
   {project_messages} as d
   WHERE 
   d.groupid = {$group} AND 
   d.projectid = {$project->id} AND 
   d.parent = {$parent}
   ORDER BY 
   d.ordering
   ";	

   if ($discussions = $DB->get_records_sql($query)) {
      echo "<table width='100%' id='recapediscu'>
      <thead>
      <tr>
      <th class='header'>".get_string('discu','project')."</th>
      <th class='header' colspan='2'>".get_string('discupar','project')."</th>
      <th class='header'>".get_string('nbreponse','project')."</th>
      <th class='header'>".get_string('derniermessage','project')."</th>
      </tr>
      </thead>";
      foreach($discussions as $discussion){
		//affichages des discussions 
        project_print_single_message($discussion, $project, $group, $cmid, count($discussions));
    }
    echo "</table>";
} else {
  echo "<p>".get_string('nodiscu','project')."</p>";
}
}

//fonction d'affichage d'une discussion
function project_print_single_message($discussion, $project, $group, $cmid, $setSize){
    global $CFG, $USER, $DB, $OUTPUT;
	//$status = $DB->get_record('project_messages', array('id' => $discussion->id));
    $context = context_module::instance($cmid);
	$affDiscuName = "<a href=\"view.php?id={$cmid}&amp;work=add&amp;parent={$discussion->id}&amp;view=messages\">".$discussion->abstract."</a>";//Nom de la discussion
	$query = "
   SELECT 
   count(*) as count
   FROM 
   {project_messages}
   WHERE 
   parent = {$discussion->id}
   ";	

   $nbRep = $DB->get_records_sql($query);
   $nbReponse = array_pop($nbRep)->count;
	$affNbReponse = "<a href='view.php?id={$cmid}&amp;work=add&amp;parent={$discussion->id}&amp;view=messages'>".$nbReponse."</a>";//Nombre de réponse à la discussion
	
	$userCreator = $DB->get_record('user', array('id' => $discussion->userid));
        $picture = $OUTPUT->user_picture($userCreator, array('courseid'=>$project->course));//genere l'avatar
        $fullname = '<a href="'.$CFG->wwwroot.'/user/view.php?id='.$userCreator->id.'">'.fullname($userCreator, has_capability('moodle/site:viewfullnames', $context)).'</a>';
        
	$affLanceeParimg = $picture;//Lancée par avatar
	$affLanceePar = $fullname;//Lancée par lien nom de l'auteur
	
	$dateType = "%a %d %b %Y, %H:%M";
	$affDerMessage = '';
	if($nbReponse>0){
		$query = "
		SELECT 
     d.*
     FROM 
     {project_messages} as d
     WHERE 
     d.groupid = {$group} AND 
     d.projectid = {$project->id} AND 
     d.parent = {$discussion->id}
     ORDER BY 
     d.modified
     ";
     if ($messages = $DB->get_records_sql($query)) {
       $lastMessage= array_pop($messages);
       $userCreator = $DB->get_record('user', array('id' => $lastMessage->userid));
       $affDerMessage = '<a href="'.$CFG->wwwroot.'/user/view.php?id='.$lastMessage->userid.'">'.
       fullname($userCreator, has_capability('moodle/site:viewfullnames', $context)).'</a><br />';
       $affDerMessage .= "<a href='view.php?id={$cmid}&amp;work=add&amp;parent={$discussion->id}&amp;view=messages'>".userdate($lastMessage->modified, $dateType)."</a>";
   }
}else{
  $affDerMessage = '<a href="'.$CFG->wwwroot.'/user/view.php?id='.$discussion->userid.'">'.
  fullname($userCreator, has_capability('moodle/site:viewfullnames', $context)).'</a><br />';
  $affDerMessage .= "<a href='view.php?id={$cmid}&amp;work=add&amp;parent={$discussion->id}&amp;view=messages'>".userdate($discussion->modified, $dateType)."</a>";
}

	//Affichage de la row discussion
echo "<tr>\n";
echo "<td align='center'>".$affDiscuName."</td>\n";
echo "<td align='right'>".$affLanceeParimg."</td>\n";
echo "<td align='left'>".$affLanceePar."</td>\n";
echo "<td align='center'>".$affNbReponse."</td>\n";
echo "<td align='right'>".$affDerMessage."</td>\n";
echo "</tr>\n";



$canedit = $USER->editmode == 'on' && has_capability('mod/project:communicate', $context);
if ($canedit) {
   $actionsDiscu='';
   $actionsDiscu = "<a href=\"view.php?id={$cmid}&amp;work=add&amp;parent={$discussion->id}&amp;view=messages\">
   <img src=\"".$OUTPUT->pix_url('/p/newnode', 'project')."\" alt=\"".get_string('addsubdeliv', 'project')."\" /></a>";
   $actionsDiscu .= "<a href=\"view.php?id={$cmid}&amp;work=update&amp;messageid={$discussion->id}&amp;view=messages\">
   <img src=\"".$OUTPUT->pix_url('/t/edit')."\" alt=\"".get_string('update')."\" /></a>";
		/*$actionsDiscu .= "<a href=\"view.php?id={$cmid}&amp;work=delete&amp;messageid={$discussion->id}&amp;view=messages\">
     <img src=\"".$OUTPUT->pix_url('/t/delete')."\" alt=\"".get_string('delete')."\" /></a>";*/
     $actionsDiscu .= "<p style=\"cursor:pointer;display:inline;\" onclick=\"javascript:var r=confirm('Etes vous sure de vouloir supprimer ".addslashes($discussion->abstract)." ?');if (r==true){window.location.href='view.php?id={$cmid}&amp;work=delete&amp;messageid={$discussion->id}&amp;view=messages';}\"><img src=\"".$OUTPUT->pix_url('/t/delete')."\" alt=\"".get_string('delete')."\" /></p>";
     if ($discussion->ordering > 1)
        $actionsDiscu .= "<a href=\"view.php?id={$cmid}&amp;work=up&amp;messageid={$discussion->id}&amp;view=messages#node{$discussion->id}\">
    <img src=\"".$OUTPUT->pix_url('/t/up')."\" alt=\"".get_string('up', 'project')."\" /></a>";
    if ($discussion->ordering < $setSize)
        $actionsDiscu .= "<a href=\"view.php?id={$cmid}&amp;work=down&amp;messageid={$discussion->id}&amp;view=messages#node{$discussion->id}\">
    <img src=\"".$OUTPUT->pix_url('/t/down')."\" alt=\"".get_string('down', 'project')."\" /></a>";
	        /*if ($discussion->parent != 0)
		        $actionsDiscu .= "<a href=\"view.php?id={$cmid}&amp;work=left&amp;messageid={$discussion->id}&amp;view=messages#node{$discussion->id}\">
				 <img src=\"".$OUTPUT->pix_url('/t/left')."\" alt=\"".get_string('left', 'project')."\" /></a>";
				 */
		/*if ($discussion->ordering > 1)
		        $actionsDiscu .= "<a href=\"view.php?id={$cmid}&amp;work=right&amp;messageid={$discussion->id}&amp;view=messages#node{$discussion->id}\">
				 <img src=\"".$OUTPUT->pix_url('/t/right')."\" alt=\"".get_string('right', 'project')."\" /></a>";
				 */
                echo "<tr><td colspan='5' align='left'>".$actionsDiscu."</td></tr>";

            }
        }
        function project_print_projects($project, $group, $cmid){
         global $CFG, $USER, $DB, $OUTPUT;
         if($project->projectgrpid==0){
            echo "<p>Erreur dans la définition du groupe projet pour ce projet</p>";
        }else{
            $query = "
            SELECT 
            d.*
            FROM 
            {project} as d
            WHERE 
            d.projectgrpid = {$project->projectgrpid} AND 
            d.typeprojet > 0
            ORDER BY 
            d.timecreated
            ";
            $roleprojectgrp = $DB->get_record('role', array('id' => $project->projectgrpid));
            echo "<h3 id='vueprojetstitle'><img src='".$OUTPUT->pix_url('vue-projets', 'project')."' alt='' />".get_string('vueprojetstitle', 'project').$roleprojectgrp->name."</h3>";
            if ($projects = $DB->get_records_sql($query)) {
               if(count($projects>0)){
                  echo "<div id='projectsheader' class='projectitem'><p>Nom du projet</p><p>Etape</p><p>Etape en cours et statut</p><p>&nbsp;</p><div class='sepbloc'></div></div>";
                  foreach($projects as $projectitem){
					//affichages des projets 
                     echo "<div class='projectitem'>";
                     $projectName = $projectitem->name;
					if($cmtmp = get_coursemodule_from_instance('project', $projectitem->id)){ //parfois impossible de récuperer le cm associé au projet... et donc pas de lien possible
						$projectName = "<a href=\"".$CFG->wwwroot."/mod/project/view.php?id=".$cmtmp->id."&view=description"."\" title=\"Voir ce projet\">".$projectitem->name."</a>";
					}
					echo "<p>".$projectName."</p>";
					$query = "
                  SELECT *
                  FROM
                  {project_milestone} as m
                  WHERE
                  projectid = $projectitem->id
                  ORDER BY ordering desc
                  ";
                  $milestones = $DB->get_records_sql($query);
                  $statutLabel = array('En travaux','En cours de validation','En révision','Validé');
                  $nbi = 0;
                  $nbmilestones = count($milestones);
                  $projetfini = false;
                  if ($nbmilestones>0){
                    $milestoneFound = false;
                    while(!$milestoneFound && $nbi<$nbmilestones){
                       $nbi++;
                       $milestone= array_pop($milestones);
                       if($milestone->statut!=3){
                          $milestoneFound=true;
                      }
                  }
                  if($nbi==$nbmilestones && $milestone->statut==3){
                     $projetfini = true;
                 }
                 echo "<p>Etape ".$nbi."/".$nbmilestones."</p>";
                 echo "<p>".$milestone->abstract.", ".$statutLabel[$milestone->statut]."</p>";
             }else{
                echo "<p>Etape 0/0</p><p>&nbsp;</p>";
                $projetfini = true;
            }

					if($projectitem->etat==0 && $projetfini){// etat 0 = projet pas fermé
						echo "<p><span style=\"cursor:pointer;display:inline;\" onclick=\"javascript:var r=confirm('Etes vous sure de vouloir clore le projet ".addslashes($projectitem->name)." ?');if (r==true){window.location.href='{$CFG->wwwroot}/mod/project/view.php?id={$cmid}&amp;view=projects&amp;work=close&amp;projectid=".$projectitem->id."';}\">Clore le projet</span></p>";
					}elseif($projectitem->etat==0){
						echo "<p>&nbsp;</p>";
					}else{
						echo "<p>Projet clos</p>";
					}
					echo "<div class='sepbloc'></div>";
					echo "</div>";
				}
			}else{//ne doit jamais arriver en théorie puisque vue possible que depuis un projet
				echo "<p>Aucun projet dans ce groupe</p>";
			}
		}
	}
}

/*
* Fonction d'export des project d'un groupe en XML
*/
function project_print_projects_xml($projectgrpid){
  global $CFG, $DB;

  $xml = new DOMDocument('1.0', 'utf-8');

  $recs = $DB->get_records('project', array('projectgrpid' => $projectgrpid,'etat' => 1));
  $projects = $xml->createElement('projets');
  $roleprojectgrp = $DB->get_record('role', array('id' => $projectgrpid));

        //$formatoptions = new stdClass();
  foreach ($recs as $rec) {
		//$item = new stdClass();
    $project = $xml->createElement('project');
		//$user = new stdClass();
    $titre = $xml->createElement('titre',trim($rec->name));
    $project->appendChild($titre);
		//$item->title = format_string($rec->name);
		//$item->author = fullname($user);//mettre le nom du groupe projet
		//$item->author = $roleprojectgrp->name;//mettre le nom du groupe projet
		     /* $message = file_rewrite_pluginfile_urls($rec->postmessage, 'pluginfile.php', $context->id,
					'mod_project', 'post', $rec->postid);
					*/
			//$formatoptions->trusted = $rec->posttrust;

$contexttmp;
		if($cmtmp = get_coursemodule_from_instance('project', $rec->id)){ //parfois impossible de récuperer le cm associé au projet... et donc pas de lien possible
			$contexttmp = context_module::instance($cmtmp->id);
			//$item->link = $CFG->wwwroot."/mod/project/view.php?id=".$contexttmp->id."&view=description";
			$lien = $CFG->wwwroot."/mod/project/view.php?id=".$cmtmp->id."&view=description";
			$lienprojet = $xml->createElement('url',trim($lien));
			$project->appendChild($lienprojet);
		}
		$description = $xml->createElement('description');
		$descriptionData = $xml->createCDATASection(trim($rec->intro));
		$description->appendChild($descriptionData);
		$project->appendChild($description);
		
		//AJOUT AFFICHAGE DES ARCHIVES ETAPES
		$archives = $xml->createElement('archives');
		if($rec->projectconfidential==0){
			$milestones = $DB->get_records_select('project_milestone', "projectid = ?", array($rec->id),'ordering ASC' );
			$fs = get_file_storage();
			foreach($milestones as $milestone){
				$files = $fs->get_area_files($contexttmp->id, 'mod_project', 'deliverablearchive', $milestone->id, 'sortorder DESC, id ASC', false);
				if(!empty($files)){
					$file = array_pop($files);//on prend le dernier fichier archive
					$path = '/'.$contexttmp->id.'/mod_project/deliverablearchive/'.$file->get_itemid().$file->get_filepath().$file->get_filename();
					$url = moodle_url::make_file_url('/pluginfile.php', $path, '');
					
					//$lienArchive = html_writer::link($url, $file->get_filename());
					$archive = $xml->createElement('archive');
					$etape = $xml->createElement('etape',$milestone->abstract);
					$lienArchive = $xml->createElement('url',trim($url));
					$archive->appendChild($etape);
					$archive->appendChild($lienArchive);
					$archives->appendChild($archive);
				}
			}
			$project->appendChild($archives);
		}
		// FIN AJOUT
		//$formatoptions->trusted = $rec->posttrust;
		//AJOUT AFFICHAGE DE L EQUIPE
		//$description = strip_tags($rec->intro);
		/*$assignableroles = $DB->get_records('role', array(), '', 'id,name,shortname');
		$roles = array('student','editingteacher','teacher');
		$rolesName = array('Etudiants Projet','Tuteurs enseignant','Tuteurs entreprise');
		//$description .= "<span>Composition de l'équipe :</span><br />";
		$equipes = $xml->createElement('equipes');
		for ($i=0;$i<3;$i++){
			//$rolempty = true;
			//$roleNom = $rolesName[$i];
			foreach ($assignableroles as $role) {
				if($role->shortname==$roles[$i]){
					$roleusers = '';
					if(isset($contexttmp->id)&& $contexttmp->id>0){
						$roleusers = get_role_users($role->id, $contexttmp, false, 'u.id, u.firstname, u.lastname, u.email');
						if (!empty($roleusers)) {
							$equipe = $xml->createElement('equipe');
							$nomEquipe = $xml->createElement('nomEquipe',$rolesName[$i]);
							$equipe->appendChild($nomEquipe);
							//$rolempty = false;
							//$listeUsers ='';
							foreach ($roleusers as $user) {
								//$listeUsers .= '<li>' . fullname($user) . '</li>';
								$personne = $xml->createElement('personne',fullname($user));
								$equipe->appendChild($personne);
							}
							$equipes->appendChild($equipe);
						}
					}
				}
			}
			/*if(!$rolempty){
				$description .= "<span>".$roleNom." :</span><br />";
				$description .=    "<ul>";
				$description .=    $listeUsers;
				$description .=    "</ul>";
			}
			*//*
		}
		
		$project->appendChild($equipes);
		$projects->appendChild($project);*/
		//FIN AJOUT
		//$item->description = format_text($description, $rec->introformat, $formatoptions, $rec->course);
		//$item->pubdate = $rec->timecreated;

		//$items[] = $item;
	}
	
	
	header("Content-Type: application/xml; charset=utf-8");
	$xml->appendChild($projects);
	echo $xml->saveXML();
	exit;
}
?>