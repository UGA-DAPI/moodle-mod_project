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

	require_once($CFG->dirroot."/mod/project/forms/form_message.class.php");
	
	$messageid = optional_param('messageid', 0, PARAM_INT);
	if($messageid==0){
		$messageParent = required_param('parent', PARAM_INT);
	}else{
		$messageParent =0;
	}
	
	if($messageid==0 && $messageParent==0){//cas ajout discussion
		$mode = 'add';
		$headingTitle= get_string('adddiscu','project');
	}elseif($messageid==0 && $messageParent>0){//cas ajout d'une réponse
		$mode = 'add';
		//$headingTitle= get_string('addmessage','project');
		$discussion = $DB->get_record('project_messages', array('id' => $messageParent));
		$headingTitle= $discussion->abstract;
	}elseif($messageid>0 && $messageParent==0){//cas modif d'une discution
		$mode = 'update';
		$headingTitle= get_string('updatediscu','project');
	}elseif($messageid>0 && $messageParent>0){//cas modif d'une réponse
		$mode = 'update';
		$headingTitle= get_string('updatemessage','project');
	}
	//$mode = ($messageid) ? 'update' : 'add' ;
	
	$url = $CFG->wwwroot.'/mod/project/view.php?id='.$id.'#node'.$messageid;
	$mform = new Message_Form($url, $mode, $project, $messageid, $messageParent);
	
	if ($mform->is_cancelled()){
		redirect($url);
	}
	if ($data = $mform->get_data()){
		$data->groupid = $currentGroupId;
		$data->projectid = $project->id;	
		$data->userid = $USER->id;
		$data->modified = time();
		$data->messageformat = $data->message_editor['format'];
		$data->message = $data->message_editor['text'];
		$data->lastuserid = $USER->id;
		// editors pre save processing
		//gestoin fichier sur le champ message enlevé car maxfile = 0 et inutile ... 19/09/2013
		//$draftid_editor = file_get_submitted_draft_itemid('message_editor');
		//$data->message = file_save_draft_area_files($draftid_editor, $context->id, 'mod_project', 'message', $data->id, array('subdirs' => true), $data->message);
	    //$data = file_postupdate_standard_editor($data, 'message', $mform->descriptionoptions, $context, 'mod_project', 'message', $data->id);
		
		if ($data->messageid) {
			$data->id = $data->messageid; // id is course module id
			$DB->update_record('project_messages', $data);
            //add_to_log($course->id, 'project', 'changemessage', "view.php?id=$cm->id&view=messages&group={$currentGroupId}", 'update', $cm->id);

		} else {
			$data->created = time();
    		$data->ordering = project_tree_get_max_ordering_message($project->id, $currentGroupId, 'project_messages', true, $data->parent) + 1;
			unset($data->id); // id is course module id
			if ($data->groupid == 0) {
                $groups = groups_get_all_groups($COURSE->id);
                foreach ($groups as $group) {
                    $data->groupid = $group->id;
                    $data->id = $DB->insert_record('project_messages', $data);
                }
            }
            else{
                $data->id = $DB->insert_record('project_messages', $data);
            }
        	//add_to_log($course->id, 'project', 'addmessage', "view.php?id=$cm->id&view=messages&group={$currentGroupId}", 'add', $cm->id);
			
			/*
       		if( $project->allownotifications){
       		    project_notify_new_message($project, $cm->id, $data, $currentGroupId);
           	}
			*/
		}
		redirect($url);
	}
	echo $pagebuffer;
	if ($mode == 'add'){
		$message = new StdClass;
		$message->parent = $messageParent;
		//$messagetitle = ($message->parent) ? 'addmessage' : 'adddiscu';
		echo $OUTPUT->heading($headingTitle);
		$message->id = $cm->id; // course module
		$message->projectid = $project->id;
		$message->messageformat = FORMAT_HTML;
		$message->message = '';
	} else {
		if(! $message = $DB->get_record('project_messages', array('id' => $messageid))){
			print_error('errormessage','project');
		}
		$message->messageid = $message->id;
		$message->id = $cm->id;
		
		echo $OUTPUT->heading($headingTitle);
	}

	$mform->set_data($message);
	$mform->display();	
		
	