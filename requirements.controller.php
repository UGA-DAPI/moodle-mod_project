<?php
/*
*
* @package mod-project
* @category mod
* @author Yann Ducruy (yann[dot]ducruy[at]gmail[dot]com). Contact me if needed
* @date 12/06/2015
* @version 3.2
* @license http://www.gnu.org/copyleft/gpl.html GNU Public License
*
*/
	if ($work == 'dodelete') {
		$requid = required_param('requid', PARAM_INT);
		project_tree_delete($requid, 'project_requirement');

        // delete all related records
		$DB->delete_records('project_spec_to_req', array('reqid' => $requid));
        //add_to_log($course->id, 'project', 'changerequirement', "view.php?id={$cm->id}&amp;view=requirements&amp;group={$currentGroupId}", 'delete', $cm->id);
	}
	elseif ($work == 'domove' || $work == 'docopy') {
		$ids = required_param('ids', PARAM_INT);
		$to = required_param('to', PARAM_ALPHA);
		$autobind = false;
		$bindtable = '';
		switch($to){
		    case 'specs' :
		    	$table2 = 'project_specification'; 
		    	$redir = 'specification'; 
		    	$autobind = false;
		    	break;
		    case 'specswb' :
		    	$table2 = 'project_specification'; 
		    	$redir = 'specification'; 
		    	$autobind = true;
		    	$bindtable = 'project_spec_to_req';
		    	break;
		    case 'tasks' : 
		    	$table2 = 'project_task'; 
		    	$redir = 'task'; 
		    	break;
		    case 'deliv' : 
		    	$table2 = 'project_deliverable'; 
		    	$redir = 'deliverable'; 
		    	break;
		    default:
		    	error('Bad copy case', $CFG->wwwroot."/mod/project/view.php?id=$cm->id");
		}
		project_tree_copy_set($ids, 'project_requirement', $table2, 'description,format,abstract,projectid,groupid,ordering', $autobind, $bindtable);
        //add_to_log($course->id, 'project', "change{$redir}", "view.php?id={$cm->id}&amp;view={$redir}s&amp;group={$currentGroupId}", 'delete', $cm->id);
		if ($work == 'domove'){
		    // bounce to deleteitems
		    $work = 'dodeleteitems';
		    $withredirect = 1;
		} else {
		    redirect("{$CFG->wwwroot}/mod/project/view.php?id={$cm->id}&amp;view={$redir}s", get_string('redirectingtoview', 'project') . ' : ' . get_string($redir, 'project'));
	    }
	}
	if ($work == 'dodeleteitems') {
		$ids = required_param('ids', PARAM_INT);
		foreach($ids as $anItem){

    	    // save record for further cleanups and propagation
    	    $oldRecord = $DB->get_record('project_requirement', array('id' => $anItem));
		    $childs = $DB->get_records('project_requirement', array('fatherid' => $anItem));
		    // update fatherid in childs 
		    $query = "
		        UPDATE
		            {project_requirement}
		        SET
		            fatherid = $oldRecord->fatherid
		        WHERE
		            fatherid = $anItem
		    ";
		    $DB->execute($query);

            // delete record for this item
    		$DB->delete_records('project_requirement', array('id' => $anItem));
            // delete all related records for this item
    		$DB->delete_records('project_spec_to_req', array('projectid' => $project->id, 'groupid' => $currentGroupId, 'reqid' => $anItem));
    	}
        //add_to_log($course->id, 'project', 'deleterequirement', "view.php?id={$cm->id}&amp;view=requirements&amp;group={$currentGroupId}", 'deleteItems', $cm->id);
    	if (isset($withredirect) && $withredirect){
		    redirect("{$CFG->wwwroot}/mod/project/view.php?id={$cm->id}&amp;view={$redir}s", get_string('redirectingtoview', 'project') . ' : ' . get_string($redir, 'project'));
		}
	}
	elseif ($work == 'doclearall') {
        // delete all records. POWERFUL AND DANGEROUS COMMAND.
		$DB->delete_records('project_requirement', array('projectid' => $project->id, 'groupid' => $currentGroupId));
		$DB->delete_records('project_spec_to_req', array('projectid' => $project->id, 'groupid' => $currentGroupId));
        //add_to_log($course->id, 'project', 'changerequirement', "view.php?id={$cm->id}&amp;view=requirements&amp;group={$currentGroupId}", 'clear', $cm->id);
	}
	elseif ($work == 'doexport') {
	    $ids = required_param('ids', PARAM_INT);
	    $idlist = implode("','", $ids);
	    $select = "
	       id IN ('$idlist')	       
	    ";
	    $requirements = $DB->get_records_select('project_requirement', $select);
	    $strengthes = $DB->get_records_select('project_qualifier', " projectid = $project->id AND domain = 'strength' ");
	    if (empty($strenghes)){
	        $strengthes = $DB->get_records_select('project_qualifier', " projectid = 0 AND domain = 'strength' ");
	    }
	    include "xmllib.php";
	    $xmlstrengthes = recordstoxml($strengthes, 'strength', '', false, 'project');
	    $xml = recordstoxml($requirements, 'requirement', $xmlstrengthes);
	    $escaped = str_replace('<', '&lt;', $xml);
	    $escaped = str_replace('>', '&gt;', $escaped);
	    echo $OUTPUT->heading(get_string('xmlexport', 'project'));
	    print_simple_box("<pre>$escaped</pre>");
        //add_to_log($course->id, 'project', 'changerequirement', "view.php?id={$cm->id}&amp;view=requirements&amp;group={$currentGroupId}", 'export', $cm->id);
        echo $OUTPUT->continue_button("view.php?view=requirements&amp;id=$cm->id");
        return;
	}
	elseif ($work == 'up') {
		$requid = required_param('requid', PARAM_INT);
		project_tree_up($project, $currentGroupId, $requid, 'project_requirement');
	}
	elseif ($work == 'down') {
		$requid = required_param('requid', PARAM_INT);
		project_tree_down($project, $currentGroupId, $requid, 'project_requirement');
	}
	elseif ($work == 'left') {
		$requid = required_param('requid', PARAM_INT);
		project_tree_left($project, $currentGroupId, $requid, 'project_requirement');
	}
	elseif ($work == 'right') {
		$requid = required_param('requid', PARAM_INT);
		project_tree_right($project, $currentGroupId, $requid, 'project_requirement');
	}
