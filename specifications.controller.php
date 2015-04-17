<?php

/// Controller
/** ********************** **/
	if ($work == 'delete') {
		$specid = required_param('specid', PARAM_INT);
		project_tree_delete($specid, 'project_specification');

        // delete related records
		$DB->delete_records('project_spec_to_req', array('specid' => $specid));
        add_to_log($course->id, 'project', 'changespecification', "view.php?id=$cm->id&amp;view=specifications&amp;group={$currentGroupId}", 'delete', $cm->id);
	}
/** ********************** **/
	elseif ($work == 'domove' || $work == 'docopy') {
		$ids = required_param('ids', PARAM_INT);
		$to = required_param('to', PARAM_ALPHA);
		$autobind = false;
		$bindtable = '';
		switch($to){
		    case 'requs' : 
		    	$table2 = 'project_requirement'; 
		    	$redir = 'requirement';
		    	break;
		    case 'requswb' : 
		    	$table2 = 'project_requirement'; 
		    	$redir = 'requirement'; 
		    	$autobind = true; 
		    	$bindtable = 'project_spec_to_req';
		    	break;
		    case 'specs' : 
		    	$table2 = 'project_specification'; 
		    	$redir = 'specification'; 
		    	break;
		    case 'tasks' : 
		    	$table2 = 'project_task'; 
		    	$redir = 'task';
		    	break;
		    case 'taskswb' : 
		    	$table2 = 'project_task'; 
		    	$redir = 'task'; 
		    	$autobind = true ; 
		    	$bindtable = 'project_task_to_spec';
		    	break;
		    case 'deliv' : 
		    	$table2 = 'project_deliverable'; 
		    	$redir = 'deliverable'; 
		    	break;
		}
		project_tree_copy_set($ids, 'project_specification', $table2, 'description,format,abstract,projectid,groupid,ordering', $autobind, $bindtable);
        add_to_log($course->id, 'project', "change{$redir}", "view.php?id={$cm->id}&amp;view={$redir}s&amp;group={$currentGroupId}", 'copy/move', $cm->id);
		if ($work == 'domove'){
		    // bounce to deleteitems
		    $work = 'dodeleteitems';
		    $withredirect = 1;
		}
		else{
		    redirect("{$CFG->wwwroot}/mod/project/view.php?id={$cm->id}&amp;view={$redir}s", get_string('redirectingtoview', 'project') . ' : ' . get_string($redir, 'project'));
	    }
	}
/** ********************** **/
   	elseif ($work == 'domarkastemplate') {
   		$specid = required_param('specid', PARAM_INT);
   		$SESSION->project->spectemplateid = $specid;
   	}
/** ********************** **/
   	elseif ($work == 'doapplytemplate') {
   		$specids = required_param('ids', PARAM_INT);
   		$templateid = $SESSION->project->spectemplateid;
   		$ignoreroot = ! optional_param('applyroot', false, PARAM_BOOL);

   		foreach($specids as $specid){
   			tree_copy_rec('specification', $templateid, $specid, $ignoreroot);
   		}
   	}
/** ********************** **/
	if ($work == 'dodeleteitems') {
		$ids = required_param('ids', PARAM_INT);
		foreach($ids as $anItem){
    	    // save record for further cleanups and propagation
    	    $oldRecord = $DB->get_record('project_specification', array('id' => $anItem));
		    $childs = $DB->get_records('project_specification', array('fatherid' => $anItem));
		    // update fatherid in childs 
		    $query = "
		        UPDATE
		            {project_specification}
		        SET
		            fatherid = $oldRecord->fatherid
		        WHERE
		            fatherid = $anItem
		    ";
		    $DB->execute($query);

    		$DB->delete_records('project_specification', array('id' => $anItem));
            // delete all related records
    		$DB->delete_records('project_spec_to_req', array('projectid' => $project->id, 'groupid' => $currentGroupId, 'specid' => $anItem));
    		$DB->delete_records('project_task_to_spec', array('projectid' => $project->id, 'groupid' => $currentGroupId, 'specid' => $anItem));
    	}
        add_to_log($course->id, 'project', 'deletespecification', "view.php?id={$cm->id}&amp;view=specifications&amp;group={$currentGroupId}", 'deleteItems', $cm->id);
    	if (isset($withredirect) && $withredirect){
		    redirect("{$CFG->wwwroot}/mod/project/view.php?id={$cm->id}&amp;view={$redir}s", get_string('redirectingtoview', 'project') . ' : ' . get_string($redir, 'project'));
		}
	}
/** ********************** **/
	elseif ($work == 'doclearall') {
        // delete all records. POWERFUL AND DANGEROUS COMMAND.
		$DB->delete_records('project_specification', array('projectid' => $project->id));
		$DB->delete_records('project_task_to_spec', array('projectid' => $project->id));
		$DB->delete_records('project_spec_to_req', array('projectid' => $project->id));
        add_to_log($course->id, 'project', 'changespecification', "view.php?id={$cm->id}&amp;view=specifications&amp;group={$currentGroupId}", 'clear', $cm->id);
	}
/** ********************** **/
	elseif ($work == 'doexport') {
	    $ids = required_param('ids', PARAM_INT);
	    $idlist = implode("','", $ids);
	    $select = "
	       id IN ('$idlist')
	    ";
	    $specifications = $DB->get_records_select('project_specification', $select);
	    $priorities = $DB->get_records('project_priority', array('projectid' => $project->id));
	    if (empty($priorities)){
	        $priorities = $DB->get_records('project_priority', array('projectid' => 0));
	    }
	    $severities = $DB->get_records('project_severity', array('projectid' => $project->id));
	    if (empty($severities)){
	        $severities = $DB->get_records('project_severity', array('projectid' => 0));
	    }
	    $complexities = $DB->get_records('project_complexity', array('projectid' => $project->id));
	    if (empty($complexities)){
	        $complexities = $DB->get_records('project_complexity', array('projectid' => 0));
	    }
	    include "xmllib.php";
	    $xmlpriorities = recordstoxml($priorities, 'priority_option', '', false, 'project');
	    $xmlseverities = recordstoxml($severities, 'severity_option', '', false, 'project');
	    $xmlcomplexities = recordstoxml($complexities, 'complexity_option', '', false, 'project');
	    $xml = recordstoxml($specifications, 'specification', $xmlpriorities.$xmlseverities.$xmlcomplexities, true, null);
	    $escaped = str_replace('<', '&lt;', $xml);
	    $escaped = str_replace('>', '&gt;', $escaped);
	    echo $OUTPUT->heading(get_string('xmlexport', 'project'));
	    print_simple_box("<pre>$escaped</pre>");
        add_to_log($course->id, 'project', 'readspecification', "view.php?id={$cm->id}&amp;view=specifications&amp;group={$currentGroupId}", 'export', $cm->id);
        echo $OUTPUT->continue_button("view.php?view=specifications&amp;id=$cm->id");
        return;
	}
/** ********************** **/
	elseif ($work == 'up') {
		$specid = required_param('specid', PARAM_INT);
		project_tree_up($project, $currentGroupId,$specid, 'project_specification');
	}
/** ********************** **/
	elseif ($work == 'down') {
		$specid = required_param('specid', PARAM_INT);
		project_tree_down($project, $currentGroupId,$specid, 'project_specification');
	}
/** ********************** **/
	elseif ($work == 'left') {
		$specid = required_param('specid', PARAM_INT);
		project_tree_left($project, $currentGroupId,$specid, 'project_specification');
	}
/** ********************** **/
	elseif ($work == 'right') {
		$specid = required_param('specid', PARAM_INT);
		project_tree_right($project, $currentGroupId,$specid, 'project_specification');
	}
