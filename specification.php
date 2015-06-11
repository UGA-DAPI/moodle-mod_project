<?php

    /**
    *
    * Specification operations.
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
    

if ($work == 'add' || $work == 'update') {
	include 'edit_specification.php';
/// Group operation form *********************************************************
} elseif($work == 'groupcmd') {
	echo $pagebuffer;
    $ids = required_param('ids', PARAM_INT);
    $cmd = required_param('cmd', PARAM_ALPHA);

?>

<center>
<?php echo $OUTPUT->heading(get_string('groupoperations', 'project')); ?>
<?php echo $OUTPUT->heading(get_string("group$cmd", 'project'), 'h3'); ?>
<script type="text/javascript">
//<![CDATA[
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
            if (@$project->projectusesrequs) $options['requs'] = get_string('requirements', 'project');
            if (@$project->projectusesrequs) $options['requswb'] = get_string('requirementswithbindings', 'project');
            $options['tasks'] = get_string('tasks', 'project');
            $options['taskswb'] = get_string('taskswithbindings', 'project');
            if (@$project->projectusesdelivs) $options['deliv'] = get_string('deliverables', 'project');
            echo html_writer::select($options, 'to', '', 'choose');
        }

        if ($cmd == 'applytemplate'){
			echo '<input type="checkbox" name="applyroot" value="1" /> '.get_string('alsoapplyroot', 'project');
			echo '<br/>';
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
		include 'specifications.controller.php';
	}
	echo $pagebuffer;
    $PAGE->requires->yui2_lib('yui_connection');
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
        if ($USER->editmode == 'on' && has_capability('mod/project:changespecs', $context)) {
    		echo "<br/><a href='view.php?id={$cm->id}&amp;work=add&amp;fatherid=0'>".get_string('addspec','project')."</a>&nbsp; ";
    	}
		project_print_specifications($project, $currentGroupId, 0, $cm->id);
        if ($USER->editmode == 'on' && has_capability('mod/project:changespecs', $context)) {
	    	echo "<br/><a href='javascript:selectall(document.forms[\"groupopform\"])'>".get_string('selectall','project')."</a>&nbsp;";
	    	echo "<a href='javascript:unselectall(document.forms[\"groupopform\"])'>".get_string('unselectall','project')."</a>&nbsp;";
    		echo "<a href='view.php?id={$cm->id}&amp;work=add&amp;fatherid=0'>".get_string('addspec','project')."</a>&nbsp; ";
    		if (@$SESSION->project->spectemplateid){
        		project_print_group_commands(array('applytemplate'));
        	} else {
	    		project_print_group_commands();
	    	}
    	}
?>
</form>
<?php
	}
?>