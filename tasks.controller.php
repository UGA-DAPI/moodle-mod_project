<?php

	/**
	* This task controller addresses all group commands including deletion.
	* @see edit_task.php for single record operations.
	*
	*/

   	if ($work == 'dodelete') {
        $taskid = required_param('taskid', PARAM_INT);
   	    // save record for further cleanups
   	    $oldtask = $DB->get_record('project_task', array('id' => $taskid));
        // delete all related records
   		project_tree_delete($taskid, 'project_task');
        //add_to_log($course->id, 'project', 'changetask', "view.php?id={$cm->id}&amp;view=tasks&amp;group={$currentGroupId}", 'delete', $cm->id);
        //reset indicators 
        $oldtask->done      = 0;
        $oldtask->planned   = 0;
        $oldtask->quoted    = 0;
        $oldtask->spent     = 0;
        $oldtask->used      = 0;
        $DB->update_record('project_task', addslashes_recursive($oldtask));
   		// if was subtask, update branch annulation
   		if ($oldtask->fatherid != 0){
   		    project_tree_propagate_up('project_task', 'done', $oldtask->id, '~');
   		    project_tree_propagate_up('project_task', 'planned', $oldtask->id, '+');
   		    project_tree_propagate_up('project_task', 'quoted', $oldtask->id, '+');
   		    project_tree_propagate_up('project_task', 'used', $oldtask->id, '+');
   		    project_tree_propagate_up('project_task', 'spent', $oldtask->id, '+');
   		}
           // now can delete records
   		$DB->delete_records('project_task_to_spec', array('projectid' => $project->id, 'groupid' => $currentGroupId, 'taskid' => $taskid));
   		$DB->delete_records('project_task_dependency', array('projectid' => $project->id, 'groupid' => $currentGroupId, 'master' => $taskid));
   		$DB->delete_records('project_task_dependency', array('projectid' => $project->id, 'groupid' => $currentGroupId, 'slave' => $taskid));   

/************************ Mark as 100% done ************************/

   	} elseif ($work == 'domarkasdone') {    	
        // just completes a task with 100% done indicator.
   		$ids = required_param('ids', PARAM_INT);
   		if (is_array($ids)){
       		foreach($ids as $anItem){
       		    unset($object);
       		    $object->id = $anItem;
       		    $object->done = 100;
                   $DB->update_record('project_task', $object);
       		}
       	}
   	}

/************************ Recalculate all indicators *****************/

   	// full fills a task with planned values and 100% done indicator.
   	elseif ($work == 'recalc') {
	    project_tree_propagate_down($project, 'project_task', 'done', 0, '~');
	    project_tree_propagate_down($project, 'project_task', 'planned', 0, '+');
	    project_tree_propagate_down($project, 'project_task', 'quoted', 0, '+');
	    project_tree_propagate_down($project, 'project_task', 'used', 0, '+');
	    project_tree_propagate_down($project, 'project_task', 'spent', 0, '+');

/************************ Fullfills a task *****************/

   	} elseif ($work == 'fullfill') {
   		$ids = required_param('ids', PARAM_INT);
   		if (is_array($ids)){
   		    $task = $DB->get_record('project_task', array('id' => $anItem));
       		foreach($ids as $anItem){
       		    unset($object);
       		    $object->id     = $task->id;
       		    $object->done   = 100;
       		    $object->quoted = $task->planned * $task->costrate;
       		    $object->used   = $task->planned;
       		    $object->spent  = $task->used * $task->costrate;
                $DB->update_record('project_task', $object);
       		}
       	}
   	}

/************************ Move and Copy ********************/

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
   		    case 'specs' : 
   		    	$table2 = 'project_specification'; 
   		    	$redir = 'specification'; 
   		    	break;
   		    case 'specswb' : 
   		    	$table2 = 'project_specification'; 
   		    	$redir = 'specification' ; 
   		    	$autobind = true ; 
   		    	$bindtable = 'project_spec_to_task';
   		    	break;
   		    // case 'tasks' : { $table2 = 'project_task'; $redir = 'task'; } break;
   		    case 'deliv' : 
   		    	$table2 = 'project_deliverable'; 
   		    	$redir = 'deliverable'; 
   		    	break;
   		    case 'delivwb' : 
   		    	$table2 = 'project_deliverable'; 
   		    	$redir = 'deliverable'; 
   		    	$autobind = true ; 
   		    	$bindtable = 'project_task_to_deliv';
   		    	break;
   		}
   		project_tree_copy_set($ids, 'project_task', $table2, 'description,format,abstract,projectid,groupid,ordering', $autobind, $bindtable);
           //add_to_log($course->id, 'project', 'change{$redir}', "view.php?id={$cm->id}&amp;view={$redir}s&amp;group={$currentGroupId}", 'copy/move', $cm->id);
   		if ($work == 'domove'){
   		    // bounce to deleteitems
   		    $work = 'dodeleteitems';
   		    $withredirect = 1;
   		} else {
   		    redirect("{$CFG->wwwroot}/mod/project/view.php?id={$cm->id}&amp;view={$redir}s", get_string('redirectingtoview', 'project') . ' : ' . get_string($redir, 'project'));
   	    }

/************************ Mark this task as template ******************/

   	} elseif ($work == 'domarkastemplate') {
   		$taskid = required_param('taskid', PARAM_INT);
   		$SESSION->project->tasktemplateid = $taskid;

/************************ Apply template *********************/

   	} elseif ($work == 'doapplytemplate') {
   		$taskids = required_param('ids', PARAM_INT);
   		$templateid = $SESSION->project->tasktemplateid;
   		$ignoreroot = ! optional_param('applyroot', false, PARAM_BOOL);

   		foreach($taskids as $taskid){
   			tree_copy_rec('task', $templateid, $taskid, $ignoreroot);
   		}
   	}

/************************ Delete multiple items ******************/

   	if ($work == 'dodeleteitems') {
   		$ids = required_param('ids', PARAM_INT);
   		foreach($ids as $anItem){		    
       	    // save record for further cleanups and propagation
       	    $oldtask = $DB->get_record('project_task', array('id' => $anItem));
   		    $childs = $DB->get_records('project_task', array('fatherid' => $anItem));
   		    // update fatherid in childs 
   		    $query = "
   		        UPDATE
   		            {project_task}
   		        SET
   		            fatherid = $oldtask->fatherid
   		        WHERE
   		            fatherid = $anItem
   		    ";
   		    $DB->execute($query);
               //reset indicators 
               $oldtask->done    = 0;
               $oldtask->planned = 0;
               $oldtask->quoted  = 0;
               $oldtask->used    = 0;
               $oldtask->spent   = 0;
               $DB->update_record('project_task', addslashes_recursive($oldtask));
       		// if was subtask, update branch propagation
       		if ($oldtask->fatherid != 0){
       		    project_tree_propagate_up('project_task', 'done', $oldtask->id, '~');
       		    project_tree_propagate_up('project_task', 'planned', $oldtask->id, '+');
       		    project_tree_propagate_up('project_task', 'quoted', $oldtask->id, '+');
       		    project_tree_propagate_up('project_task', 'used', $oldtask->id, '+');
       		    project_tree_propagate_up('project_task', 'spent', $oldtask->id, '+');
       		}
               // delete record for this item
       		$DB->delete_records('project_task', array('id' => $anItem));
            //add_to_log($course->id, 'project', 'changetask', "view.php?id={$cm->id}&amp;view=tasks&amp;group={$currentGroupId}", 'deleteItems', $cm->id);

               // delete all related records
       		$DB->delete_records('project_task_to_spec', array('projectid' => $project->id, 'groupid' => $currentGroupId, 'taskid' => $anItem));
       		$DB->delete_records('project_task_dependency', array('projectid' => $project->id, 'groupid' => $currentGroupId, 'master' => $anItem));
       		$DB->delete_records('project_task_dependency', array('projectid' => $project->id, 'groupid' => $currentGroupId, 'slave' => $anItem));
       		// must rebind child dependencies to father 
       		if ($oldtask->fatherid != 0 && $childs){
           		foreach($childs as $aChild){
           		    $aDependency->id        = 0;
           		    $aDependency->projectid = $project->id;
           		    $aDependency->groupid   = $currentGroupId;
           		    $aDependency->master    = $oldtask->fatherid;
           		    $aDependency->slave     = $aChild->id;
           		    $DB->insert_record('project_task_dependency', $aDependency);
           		}
           	}   
       	}
       	if (isset($withredirect) && $withredirect){
   		    redirect("{$CFG->wwwroot}/mod/project/view.php?id={$cm->id}&amp;view={$redir}s", get_string('redirectingtoview', 'project') . ' : ' . get_string($redir, 'project'));
   		}

/************************ clear all **********************/

   	} elseif ($work == 'doclearall') {
           // delete all related records. POWERFUL AND DANGEROUS COMMAND.
           // deletes for the current group. 
   		$DB->delete_records('project_task', array('projectid' => $project->id, 'groupid' => $currentGroupId));
   		$DB->delete_records('project_task_to_spec', array('projectid' => $project->id, 'groupid' => $currentGroupId));
   		$DB->delete_records('project_task_to_deliv', array('projectid' => $project->id, 'groupid' => $currentGroupId));
   		$DB->delete_records('project_task_dependency', array('projectid' => $project->id, 'groupid' => $currentGroupId));
           //add_to_log($course->id, 'project', 'changetask', "view.php?id={$cm->id}&amp;view=tasks&amp;group={$currentGroupId}", 'clear', $cm->id);
   	} elseif ($work == 'doexport') {
   	    $ids = required_param('ids', PARAM_INT);
   	    $idlist = implode("','", $ids);
   	    $select = "
   	       id IN ('$idlist')	       
   	    ";
   	    $tasks = $DB->get_records_select('project_task', $select);
   	    $worktypes = $DB->get_records('project_worktype', array('projectid' => $project->id));
   	    if (empty($worktypes)){
   	        $worktypes = $DB->get_records('project_worktype', array('projectid' => 0));
   	    }
   	    $taskstatusses = $DB->get_records_select('project_qualifier', " projectid = $project->id AND domain = 'taskstatus' ");
   	    if (empty($taskstatusses)){
   	        $taskstatusses = $DB->get_records('project_qualifier', null);
   	    }
   	    include "xmllib.php";
   	    $xmlworktypes = recordstoxml($worktypes, 'worktype_option', '', false, 'project');
   	    $xmltaskstatusses = recordstoxml($taskstatusses, 'task_status_option', '', false, 'project');
   	    $xml = recordstoxml($tasks, 'task', $xmlworktypes.$xmltaskstatusses, true, null);
   	    $escaped = str_replace('<', '&lt;', $xml);
   	    $escaped = str_replace('>', '&gt;', $escaped);
   	    echo $OUTPUT->heading(get_string('xmlexport', 'project'));
   	    print_simple_box("<pre>$escaped</pre>");
           //add_to_log($course->id, 'project', 'readtask', "view.php?id={$cm->id}&amp;view=tasks&amp;group={$currentGroupId}", 'export', $cm->id);
           echo $OUTPUT->continue_button("view.php?view=tasks&amp;id=$cm->id");
           return;

/************************ Raises up in level ********************/

   	} elseif ($work == 'up') {
   	    $taskid = required_param('taskid', PARAM_INT);
   		project_tree_up($project, $currentGroupId, $taskid, 'project_task');

/************************ Lowers down in levzel ***********************/

   	} elseif ($work == 'down') {
   	    $taskid = required_param('taskid', PARAM_INT);
   		project_tree_down($project, $currentGroupId, $taskid, 'project_task');

/************************ Raising one level up  *********************/

   	} elseif ($work == 'left') {
   	    $taskid = required_param('taskid', PARAM_INT);
   		project_tree_left($project, $currentGroupId, $taskid, 'project_task');
   	    project_tree_propagate_up('project_task', 'done', $taskid, '~');
   	    project_tree_propagate_up('project_task', 'planned', $taskid, '+');
   	    project_tree_propagate_up('project_task', 'quoted', $taskid, '+');
   	    project_tree_propagate_up('project_task', 'used', $taskid, '+');
   	    project_tree_propagate_up('project_task', 'spent', $taskid, '+');

/************************ Diving one level down ***************************/

   	} elseif ($work == 'right') {
   	    $taskid = required_param('taskid', PARAM_INT);
   		project_tree_right($project, $currentGroupId, $taskid, 'project_task');
   	}

?>