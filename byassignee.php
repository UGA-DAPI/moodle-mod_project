<?php

    /**
    *
    * This screen show tasks plan by assignee. Unassigned tasks are shown 
    * below assigned tasks
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

	if (!defined('MOODLE_INTERNAL'))  die('You cannot use this script that way');

	echo $pagebuffer;

    $TIMEUNITS = array(get_string('unset','project'),get_string('hours','project'),get_string('halfdays','project'),get_string('days','project'));
    $haveAssignedTasks = false;
    if (!groups_get_activity_groupmode($cm, $project->course)){
        $groupusers = get_users_by_capability($context, 'mod/project:beassignedtasks', 'u.id, u.firstname, u.lastname, u.email, u.picture', 'u.lastname');
    } else {
        if ($currentGroupId){
            $groupusers = groups_get_members($currentGroupId);
        } else {
            // we could not rely on the legacy function
            $groupusers = project_get_users_not_in_group($project->course);
        }
    }
    if (!isset($groupusers) || count($groupusers) == 0 || empty($groupusers)){
        echo $OUTPUT->box(get_string('noassignee','project'), 'center');
    } else {
        echo $OUTPUT->heading(get_string('assignedtasks','project'));
        echo '<br/>';
        echo $OUTPUT->box_start('center', '100%');
        foreach($groupusers as $aUser){
    ?>
    <table width="100%">
        <tr>
            <td class="byassigneeheading level1">
    <?php 
			$hidesub = "<a href=\"javascript:toggle('{$aUser->id}','sub{$aUser->id}');\"><img name=\"img{$aUser->id}\" src=\"{$CFG->wwwroot}/mod/project/pix/p/switch_minus.gif\" alt=\"collapse\" style=\"background-color : #E0E0E0\" /></a>";
            echo $hidesub.' '.get_string('assignedto','project').' '.fullname($aUser).' '.$OUTPUT->user_picture($USER);
    ?>
            </td>
            <td class="byassigneeheading level1" align="right">
    <?php
            $query = "
               SELECT 
                  SUM(planned) as planned,
                  SUM(done) as done,
                  SUM(spent) as spent,
                  COUNT(*) as count
               FROM
                  {project_task} as t
               WHERE
                  t.projectid = {$project->id} AND
                  t.groupid = {$currentGroupId} AND
                  t.assignee = {$aUser->id}
               GROUP BY
                  t.assignee
            ";
            $res = $DB->get_record_sql($query);
            if ($res){
                $over = ($res->planned) ? round((($res->spent - $res->planned) / $res->planned) * 100) : 0 ;
                // calculates a local alarm for lateness
                $hurryup = '';
                if ($res->planned && ($res->spent <= $res->planned)){
                    $hurryup = (round(($res->spent / $res->planned) * 100) > ($res->done / $res->count)) ? "<img src=\"{$CFG->wwwroot}/mod/project/pix/p/late.gif\" title=\"".mb_convert_encoding(get_string('hurryup','project'), 'UTF8', 'ISO-8859-1')."\" />" : '' ;
                }
                $lateclass = ($over > 0) ? 'toolate' : 'intime';
                $workplan = get_string('assignedwork','project').' '.(0 + $res->planned).' '.$TIMEUNITS[$project->timeunit];
                $realwork = get_string('realwork','project')." <span class=\"{$lateclass}\">".(0 + $res->spent).' '.$TIMEUNITS[$project->timeunit].'</span>';
        	    $completion = ($res->count != 0) ? project_bar_graph_over($res->done / $res->count, $over, 100, 10) : project_bar_graph_over(-1, 0);
                echo "{$workplan} - {$realwork} {$completion} {$hurryup}";
    	    }
    ?>
            </td>
        </tr>
    </table>
    <table id="<?php echo "sub{$aUser->id}" ?>" width="100%">
    <?php
            // get assigned tasks
            $query = "
               SELECT
                  t.*,
                  qu.label as statuslabel,
                  COUNT(tts.specid) as specs
               FROM
                  {project_qualifier} as qu,
                  {project_task} as t
               LEFT JOIN
                  {project_task_to_spec} as tts
               ON
                  tts.taskid = t.id
               WHERE
                  t.projectid = {$project->id} AND
                  t.groupid = {$currentGroupId} AND
                  qu.domain = 'taskstatus' AND
                  qu.code = t.status AND
                  t.assignee = {$aUser->id}
               GROUP BY
                  t.id
            ";
            $tasks = $DB->get_records_sql($query);
            if (!isset($tasks) || count($tasks) == 0 || empty($tasks)){
    ?>
        <tr>
            <td>
                <?php print_string('notaskassigned', 'project') ?>
            </td>
        </tr>
    <?php        
            } else {
                foreach($tasks as $aTask){
                    $haveAssignedTasks = true;
                    // feed milestone titles for popup display
                    if ($milestone = $DB->get_record('project_milestone', array('id' => $aTask->milestoneid))){
                        $aTask->milestoneabstract = $milestone->abstract;
                    }
    ?>
        <tr>
            <td class="level2">
            <?php project_print_single_task($aTask, $project, $currentGroupId, $cm->id, count($tasks), true, 'SHORT_WITHOUT_ASSIGNEE_NOEDIT'); ?>
            </td>
        </tr>
    <?php
                }
            }
    ?>
    </table>
    <?php
        }
        echo $OUTPUT->box_end();
    }
    // get unassigned tasks
    $query = "
       SELECT
          *
       FROM
          {project_task}
       WHERE
          projectid = {$project->id} AND
          groupid = {$currentGroupId} AND
          assignee = 0
    ";
    $unassignedtasks = $DB->get_records_sql($query);
    echo $OUTPUT->heading(get_string('unassignedtasks','project'));
    ?>
    <br/>
    <?php
    echo $OUTPUT->box_start('center', '100%');
    ?>
    <center>
    <table width="100%">
    <?php
    if (!isset($unassignedtasks) || count($unassignedtasks) == 0 || empty($unassignedtasks)){
    ?>
        <tr>
            <td>
                <?php print_string('notaskunassigned', 'project') ?>
            </td>
        </tr>
    <?php        
    } else {
        foreach($unassignedtasks as $aTask){
    ?>
        <tr>
            <td class="level2">
                <?php
                $branch = project_tree_get_upper_branch('project_task', $aTask->id, true, true);
                echo 'T'.implode('.', $branch) . '. ' . $aTask->abstract ;
                echo "&nbsp;<a href=\"view.php?id={$cm->id}&amp;view=view_detail&amp;objectClass=task&amp;objectId=$aTask->id\"><img src=\"{$CFG->wwwroot}/mod/project/pix/p/hide.gif\" title=\"".get_string('detail','project')."\" /></a>";
                ?>
            </td>
            <td>
            </td>
        </tr>
    <?php
        }
    }
    ?>
    </table>
    </center>
<?php 
    echo $OUTPUT->box_end();
?>