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

    /**
    * Requires and includes
    */
    include_once '../../lib/uploadlib.php';

/// Controller
/*
	if ($work == 'close' || $work == 'update'){
		 include 'edit_message.php';
		 
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
		<form name="groupopform" method="get" action="view.php">
		<input type="hidden" name="id" value="<?php p($cm->id) ?>" />
		<input type="hidden" name="work" value="" />
		<input type="button" name="go_btn" value="<?php print_string('continue') ?>" onclick="senddata('<?php p($cmd) ?>')" />
		<input type="button" name="cancel_btn" value="<?php print_string('cancel') ?>" onclick="cancel()" />
		</form>
		</center>
		<?php
	} else {
	*/
		if ($work){
			include 'projects.controller.php';
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
    
    	$context = context_module::instance($cm->id);
    	project_print_projects($project, $project->course);
		
        //if ($USER->editmode == 'on') {
		require_once($CFG->libdir . '/rsslib.php');
		echo "<p>";
		//detournement des fonctions moodle, utilisation du projectgroupid ala place du projectid pour générer les flux :
		// on génére les flux en fonction du groupe de projet et non pas d'une instance d'un projet
		echo "<a href='".rss_get_url($context->id, $USER->id, 'mod_project',$project->projectgrpid)."'>".get_string('projectsrss','project')."</a>";
		echo rss_get_link($context->id, $USER->id, 'mod_project',$project->projectgrpid, get_string('projectsrss','project'));
		echo "<br /><a href='view.php?expxml=1&amp;id={$cm->id}'>".get_string('projectsexport','project')."</a>";
		//echo "<br /><i>Seul les projets clos et non confidentiels sont exportés</i></p>";
    	//project_print_group_commands();
    ?>
    </form>
    <?php
   // }
?>