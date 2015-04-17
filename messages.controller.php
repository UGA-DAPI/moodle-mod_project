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
/// Controller

	if ($work == 'new') {
		$message->groupid = $currentGroupId;
		$message->projectid = $project->id;
		$message->abstract = required_param('abstract', PARAM_TEXT);
		$message->message = required_param('message', PARAM_CLEANHTML);
		$message->parent = required_param('parent', PARAM_INT);
		$message->userid = $USER->id;
		$message->created = time();
		$message->modified = time();
		$message->lastuserid = $USER->id;
		
        if (!empty($message->abstract)){
            $message->ordering = project_tree_get_max_ordering($project->id, $currentGroupId, 'project_messages', true, $message->parent) + 1;
		    $returnid = $DB->insert_record('project_messages', $message);
            add_to_log($course->id, 'project', 'changemessage', "view.php?id={$cm->id}&amp;view=deliverables&amp;group={$currentGroupId}", 'add', $cm->id);
		}
	} elseif ($work == 'doupdate') {
		$message->id = required_param('messageid', PARAM_INT);
		$message->abstract = required_param('abstract', PARAM_TEXT);
		$message->message = required_param('message', PARAM_TEXT);
 		$message->modified = time();
		$message->lastuserid = $USER->id;
        $uploader = new upload_manager('FILE_0', false, false, $course->id, true, 0, true);
        $uploader->preprocess_files();
        $deliverable->localfile = $uploader->get_new_filename();
		if (!empty($deliverable->abstract)){
    		$res = $DB->update_record('project_messages', $message );
            add_to_log($course->id, 'project', 'changemessage', "view.php?id={$cm->id}&amp;view=messages&amp;group={$currentGroupId}", 'update', $cm->id);
    	}
	} elseif ($work == 'dodelete' || $work == 'delete') {
		$messageid = required_param('messageid', PARAM_INT);
		project_tree_delete_messages($messageid, 'project_messages');
        add_to_log($course->id, 'project', 'changemessage', "view.php?id={$cm->id}&amp;view=messages&amp;group={$currentGroupId}", 'delete', $cm->id);
	} elseif ($work == 'domove' || $work == 'docopy') {
		$ids = required_param('ids', PARAM_INT);
		$to = required_param('to', PARAM_ALPHA);
		switch($to){
		    case 'requs' : { $table2 = 'project_requirement'; $redir = 'requirement'; } break;
		    case 'specs' : { $table2 = 'project_specification'; $redir = 'specification'; } break;
		    case 'tasks' : { $table2 = 'project_task'; $redir = 'task'; } break;
		    case 'deliv' : { $table2 = 'project_messages'; $redir = 'deliverable'; } break;
		}
		project_tree_copy_set($ids, 'project_messages', $table2);
        add_to_log($course->id, 'project', 'change{$redir}', "view.php?id={$cm->id}&amp;view={$redir}s&amp;group={$currentGroupId}", 'copy/move', $cm->id);
		if ($work == 'domove'){
		    // bounce to deleteitems
		    $work = 'dodeleteitems';
		    $withredirect = 1;
		} else {
		    redirect("{$CFG->wwwroot}/mod/project/view.php?id={$cm->id}&amp;view={$redir}s", get_string('redirectingtoview', 'project') . get_string($redir, 'project'));
	    }
	}
	if ($work == 'dodeleteitems') {
		$ids = required_param('ids', PARAM_INT);
		foreach($ids as $anItem){
    	    // save record for further cleanups and propagation
    	    $oldRecord = $DB->get_record('project_messages', array('id' => $anItem));
		    $childs = $DB->get_records('project_messages', array('parent' => $anItem));
		    // update parent in childs 
		    $query = "
		        UPDATE
		            {project_messages}
		        SET
		            parent = $oldRecord->parent
		        WHERE
		            parent = $anItem
		    ";
		    $DB->execute($query);
    		$DB->delete_records('project_messages', array('id' => $anItem));
            // delete all related records
    		$DB->delete_records('project_messages', array('parent' => $anItem));
    	}
        add_to_log($course->id, 'project', 'changemessage', "view.php?id={$cm->id}&amp;view=messages&amp;group={$currentGroupId}", 'deleteItems', $cm->id);
    	if (isset($withredirect) && $withredirect){
		    redirect("{$CFG->wwwroot}/mod/project/view.php?id={$cm->id}&amp;view={$redir}s", get_string('redirectingtoview', 'project') . ' : ' . get_string($redir, 'project'));
		}
	} elseif ($work == 'doclearall') {
        // delete all records. POWERFUL AND DANGEROUS COMMAND.
		//$DB->delete_records('project_messages', array('projectid' => $project->id));
	} elseif ($work == 'doexport') {
	    $ids = required_param('ids', PARAM_INT);
	    $idlist = implode("','", $ids);
	    $select = "
	       id IN ('$idlist')	       
	    ";
	    $messages = $DB->get_records_select('project_messages', $select);
	   /* $delivstatusses = $DB->get_records_select('project_qualifier', " domain = 'delivstatus' AND projectid = $project->id ");
	    if (empty($delivstatusses)){
	        $delivstatusses = $DB->get_records_select('project_qualifier', " domain = 'delivstatus' AND projectid = 0 ");
	    }*/
	    include "xmllib.php";
	    //$xmldelivstatusses = recordstoxml($delivstatusses, 'deliv_status_option', '', false, 'project');
	    $xml = recordstoxml($messages, 'message', $xmldelivstatusses, true, null);
	    $escaped = str_replace('<', '&lt;', $xml);
	    $escaped = str_replace('>', '&gt;', $escaped);
	    echo $OUTPUT->heading(get_string('xmlexport', 'project'));
	    print_simple_box("<pre>$escaped</pre>");
        add_to_log($course->id, 'project', 'readmessage', "view.php?id={$cm->id}&amp;view=messages&amp;group={$currentGroupId}", 'export', $cm->id);
        echo $OUTPUT->continue_button("view.php?view=messages&amp;id=$cm->id");
        return;
	} elseif ($work == 'up') {
		$messageid = required_param('messageid', PARAM_INT);
		project_tree_up_messages($project, $currentGroupId,$messageid, 'project_messages');
	} elseif ($work == 'down') {
		$messageid = required_param('messageid', PARAM_INT);
		project_tree_down_messages($project, $currentGroupId,$messageid, 'project_messages');
	} elseif ($work == 'left') {
		$messageid = required_param('messageid', PARAM_INT);
		project_tree_left($project, $currentGroupId,$messageid, 'project_messages');
	} elseif ($work == 'right') {
		$messageid = required_param('messageid', PARAM_INT);
		project_tree_right($project, $currentGroupId,$messageid, 'project_messages');
	}
	
	
