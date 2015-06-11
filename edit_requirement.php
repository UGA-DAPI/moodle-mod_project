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

	require_once($CFG->dirroot."/mod/project/forms/form_requirement.class.php");
	
	$requid = optional_param('requid', '', PARAM_INT);
	
	$mode = ($requid) ? 'update' : 'add' ;
	
	$url = $CFG->wwwroot.'/mod/project/view.php?id='.$id.'#node'.$requid;
	$mform = new Requirement_Form($url, $project, $mode, $requid);
	
	if ($mform->is_cancelled()){
		redirect($url);
	}
	
	if ($data = $mform->get_data()){
		$data->groupid = $currentGroupId;
		$data->projectid = $project->id;	
		$data->userid = $USER->id;
		$data->modified = time();
		$data->descriptionformat = $data->description_editor['format'];
		$data->description = $data->description_editor['text'];
		$data->lastuserid = $USER->id;

		// editors pre save processing
		$draftid_editor = file_get_submitted_draft_itemid('description_editor');
		$data->description = file_save_draft_area_files($draftid_editor, $context->id, 'mod_project', 'requirementdescription', $data->id, array('subdirs' => true), $data->description);
	    $data = file_postupdate_standard_editor($data, 'description', $mform->descriptionoptions, $context, 'mod_project', 'requirementdescription', $data->id);

		if ($data->reqid) {
			$data->id = $data->reqid; // id is course module id
			$DB->update_record('project_requirement', $data);
            //add_to_log($course->id, 'project', 'changerequirement', "view.php?id=$cm->id&view=requirements&group={$currentGroupId}", 'update', $cm->id);

    		$spectoreq = optional_param_array('spectoreq', null, PARAM_INT);
    		if (count($spectoreq) > 0){
    		    // removes previous mapping
    		    $DB->delete_records('project_spec_to_req', array('projectid' => $project->id, 'groupid' => $currentGroupId, 'reqid' => $data->id));
    		    // stores new mapping
        		foreach($spectoreq as $aSpec){
        		    $amap->id = 0;
        		    $amap->projectid = $project->id;
        		    $amap->groupid = $currentGroupId;
        		    $amap->specid = $aSpec;
        		    $amap->reqid = $data->id;
        		    $res = $DB->insert_record('project_spec_to_req', $amap);
        		}
        	}
		} else {
			$data->created = time();
    		$data->ordering = project_tree_get_max_ordering($project->id, $currentGroupId, 'project_requirement', true, $data->fatherid) + 1;
			unset($data->id); // id is course module id
			$data->id = $DB->insert_record('project_requirement', $data);
        	//add_to_log($course->id, 'project', 'addreq', "view.php?id=$cm->id&view=requirements&group={$currentGroupId}", 'add', $cm->id);

       		if( $project->allownotifications){
       		    project_notify_new_requirement($project, $cm->id, $data, $currentGroupId);
           	}
		}
		redirect($url);
	}

	echo $pagebuffer;
	if ($mode == 'add'){
		$requirement = new StdClass;
		$requirement->fatherid = required_param('fatherid', PARAM_INT);
		$reqtitle = ($requirement->fatherid) ? 'addsubrequ' : 'addrequ';
		$requirement->id = $cm->id; // course module
		$requirement->projectid = $project->id;
		$requirement->descriptionformat = FORMAT_HTML;
		$requirement->description = '';

		echo $OUTPUT->heading(get_string($reqtitle, 'project'));
	} else {
		if(! $requirement = $DB->get_record('project_requirement', array('id' => $requid))){
			print_error('errorrequirement','project');
		}
		$requirement->reqid = $requirement->id;
		$requirement->id = $cm->id;
		
		echo $OUTPUT->heading(get_string('updaterequ','project'));
	}

	$mform->set_data($requirement);
	$mform->display();	
		
	