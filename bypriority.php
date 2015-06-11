<?php

    /**
    *
    * This screen show tasks plan ordered by decreasing priority.
    *
	*
	* @package mod-project
	* @category mod
	* @author Yann Ducruy (yann[dot]ducruy[at]gmail[dot]com). Contact me if needed
	* @date 12/06/2015
	* @version 3.2
	* @license http://www.gnu.org/copyleft/gpl.html GNU Public License
	*
	*/

	if (!defined('MOODLE_INTERNAL')) die('You cannot use this script that way');

	echo $pagebuffer;

    $TIMEUNITS = array(get_string('unset','project'),get_string('hours','project'),get_string('halfdays','project'),get_string('days','project'));
    /** useless
    if (!groups_get_activity_groupmode($cm, $project->course)){
        $groupusers = get_users_by_capability($project->course);
    } else {
        $groupusers = get_group_users($currentGroupId);
    }
    */
    //memorizes current page - typical session switch
    $viewmode = optional_param('viewMode', '', PARAM_ALPHA);
    if (!empty($viewmode)) {
    	$_SESSION['viewmode'] = $viewmode;
    } elseif (empty($_SESSION['viewmode'])) {
    	$_SESSION['viewmode'] = 'alltasks';
    }
    $viewmode = $_SESSION['viewmode'];
    /*
    * priority is deduced from task_to_spec mapping. Priority of a trask is the priority of its
    * highest prioritary spec
    */
    echo "<center>";
    $tabs = array();
    $tabs[0][] = new tabobject('alltasks', "view.php?id={$cm->id}&amp;viewMode=alltasks", get_string('viewalltasks', 'project'));
    $tabs[0][] = new tabobject('onlyleaves', "view.php?id={$cm->id}&amp;viewMode=onlyleaves", get_string('viewonlyleaves', 'project'));
    $tabs[0][] = new tabobject('onlymasters', "view.php?id={$cm->id}&amp;viewMode=onlymasters", get_string('viewonlymasters', 'project'));
    print_tabs($tabs, $_SESSION['viewmode'], NULL, NULL, false);
    // get assigned tasks
    $query = "
       SELECT
          t.*,
          MAX(s.priority) as taskpriority
       FROM
          {project_task} as t,
          {project_task_to_spec} as tts,
          {project_specification} as s
       WHERE
          t.id = tts.taskid AND
          s.id = tts.specid AND
          t.projectid = {$project->id} AND
          t.groupid = {$currentGroupId}
       GROUP BY
          t.id
       ORDER BY
          taskpriority DESC
       LIMIT 0, 10
    ";
    ?>
    <script type="text/javascript">
    function sendgroupdata(){
        document.groupopform.submit();
    }
    </script>
    <form name="groupopform" action="view.php" method="post" style="text-align : left">
    <input type="hidden" name="id" value="<?php p($cm->id) ?>" />
    <input type="hidden" name="work" value="groupcmd" />
    <input type="hidden" name="view" value="tasks" />
    <?php    
    if ($tasks = $DB->get_records_sql($query)){
        foreach($tasks as $aTask){
            if ($aTask->priority = $DB->get_record('project_qualifier', array('code' => $aTask->taskpriority, 'domain' => 'priority', 'projectid' => $project->id))){
                $aTask->priority = $DB->get_record('project_qualifier', array('code' => $aTask->taskpriority, 'domain' => 'priority', 'projectid' => 0));
            }
            if (($viewmode == 'onlyleaves' || $viewmode == 'onlyslaves') && project_count_subs('project_task', $aTask->id) != 0) continue;
            if ($viewmode == 'onlymasters' && $DB->count_records('project_task_dependency', array('slave' => $aTask->id)) != 0) continue;
            project_print_single_task($aTask, $project, $currentGroupId, $cm->id, count($tasks), 'HEAD', 'SHORT_WITH_ASSIGNEE_ORDERED');
        }
    }
    // get unassigned tasks
    $query = "
       SELECT
          t.*,
          COUNT(tts.specid) as specs
       FROM
          {project_task} as t
       LEFT JOIN
          {project_task_to_spec} as tts
       ON
          t.id = tts.taskid
       WHERE
          tts.specid IS NULL AND
          t.projectid = {$project->id} AND
          t.groupid = {$currentGroupId}
       GROUP BY
          t.id
       HAVING 
          specs = 0
    ";
    // echo $query;
    if ($unassignedtasks = $DB->get_records_sql($query)){
        echo $OUTPUT->heading(get_string('unspecifiedtasks','project') . ' ' . $OUTPUT->help_icon('unspecifiedtasks', 'project', false));
        foreach($unassignedtasks as $aTask){
            if (($viewmode == 'onlyleaves' || $viewmode == 'onlyslaves') && project_count_subs('project_task', $aTask->id) != 0) continue;
            if ($viewmode == 'onlymasters' && $DB->count_records('project_task_dependency', array('slave' => $aTask->id)) != 0) continue;
            project_print_single_task($aTask, $project, $currentGroupId, $cm->id, count($unassignedtasks), 'SHORT', 'dithered', 'SHORT_WITHOUT_TYPE_NOEDIT');
        }
    }
    if (($tasks || $unassignedtasks) && $USER->editmode == 'on' && has_capability('mod/project:changetasks', $context)){
        echo '<br/>';
    	project_print_group_commands(array('markasdone', 'fullfill'));
    }
?>
</form>
</center>