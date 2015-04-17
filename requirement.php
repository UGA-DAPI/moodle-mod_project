<?php

    /**
    *
    * Requirements operations.
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

/// Controller
if ($work == "add" || $work == "update") {
	include 'edit_requirement.php';
/// Group operation form *********************************************************
} elseif ($work == "groupcmd") {
	echo $pagebuffer;
    $ids = required_param('ids', PARAM_INT);
    $cmd = required_param('cmd', PARAM_ALPHA);
    ?>

    <center>
    <?php echo $OUTPUT->heading(get_string('groupoperations', 'project')); ?>
    <?php echo $OUTPUT->heading(get_string("group$cmd", 'project'), 'h3'); ?>
    <script type="text/javascript">
    //<!{CDATA{
    function senddata(cmd){
        document.forms['groupopform'].work.value="do" + cmd;
        document.forms['groupopform'].submit();
    }
    function cancel(){
        document.forms['groupopform'].submit();
    }
    //]]>
    </script>
    <form name="groupopform" method="post" action="view.php">
    <input type="hidden" name="id" value="<?php p($cm->id) ?>" />
    <input type="hidden" name="work" value="" />
    <?php
        foreach($ids as $anId){
            echo "<input type=\"hidden\" name=\"ids[]\" value=\"{$anId}\" />\n";
        }
        if (($cmd == 'move')||($cmd == 'copy')){
            echo get_string('to', 'project');
            if (@$project->projectusesspecs) $options['specs'] = get_string('specifications', 'project');
            if (@$project->projectusesspecs) $options['specswb'] = get_string('specificationswithbindings', 'project');
            $options['tasks'] = get_string('tasks', 'project');
            if (@$project->projectusesdelivs) $options['deliv'] = get_string('deliverables', 'project');
            choose_from_menu($options, 'to', '', 'choose');
        }
        echo '<br/>';
    ?>
    <input type="button" name="go_btn" value="<?php print_string('continue') ?>" onclick="senddata('<?php p($cmd) ?>')" />
    <input type="button" name="cancel_btn" value="<?php print_string('cancel') ?>" onclick="cancel()" />
    </form>
    </center>
<?php
} else {
	if ($work) {
		include 'requirements.controller.php';
	}
	echo $pagebuffer;
?>
    <script type="text/javascript">
    //<![CDATA[
    function sendgroupdata(){
        document.forms['groupopform'].submit();
    }
    //]]>
    </script>
    <form name="groupopform" method="post" action="view.php">
    <input type="hidden" name="id" value="<?php p($cm->id) ?>" />
    <input type="hidden" name="work" value="groupcmd" />
    <?php
        if ($USER->editmode == 'on' && has_capability('mod/project:changerequs', $context)) {
        	echo "<br/><a href='view.php?id={$cm->id}&amp;work=add&amp;fatherid=0'>".get_string('addrequ','project')."</a>&nbsp;";
        }
    	project_print_requirements($project, $currentGroupId, 0, $cm->id);
        if ($USER->editmode == 'on' && has_capability('mod/project:changerequs', $context)) {
        	echo "<br/><a href='javascript:selectall(document.forms[\"groupopform\"])'>".get_string('selectall','project')."</a>&nbsp;";
        	echo "<a href='javascript:unselectall(document.forms[\"groupopform\"])'>".get_string('unselectall','project')."</a>&nbsp;";
        	echo "<a href='view.php?id={$cm->id}&amp;work=add&amp;fatherid=0'>".get_string('addrequ','project')."</a>&nbsp;";
        	project_print_group_commands();
        }
    ?>
    </form>
<?php
}
?>