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

require_once($CFG->dirroot."/mod/project/forms/form_deliverable.class.php");

$delivid = optional_param('delivid', '', PARAM_INT);

$mode = ($delivid) ? 'update' : 'add' ;

$url = $CFG->wwwroot.'/mod/project/view.php?id='.$id.'#node'.$delivid;

if($mode=='add' && (!has_capability('mod/project:editdeliverables', $context) || !has_capability('mod/project:editdeliverables', $context))){
	//si un étudiant tente de créer un libvrable/ressource on le redirige
	redirect($url);
}
$mform = new Deliverable_Form($url, $mode, $project, $delivid);

if ($mform->is_cancelled()){
	redirect($url);
}

if ($data = $mform->get_data()){
	$data->groupid = $currentGroupId;
	$data->projectid = $project->id;	
	$data->userid = $USER->id;
	$data->modified = time();
	$data->lastuserid = $USER->id;

	if($data->typeelm==0 && !has_capability('mod/project:editdeliverables', $context)){
		//si un étudiant consultait juste une ressource
		redirect($url);
	}
	if(isset($data->description_editor)){
		//si c'est un livrable et qu'on peut éditer ==> c'est un type enseignant
		$data->descriptionformat = $data->description_editor['format'];
		$data->description = $data->description_editor['text'];
			// editors pre save processing
		$draftid_editor = file_get_submitted_draft_itemid('description_editor');
		$data->description = file_save_draft_area_files($draftid_editor, $context->id, 'mod_project', 'deliverabledescription', $data->id, array('subdirs' => true), $data->description);
		$data = file_postupdate_standard_editor($data, 'description', $mform->descriptionoptions, $context, 'mod_project', 'deliverabledescription', $data->id);
	}elseif(!isset($data->description_editor) && $data->delivid){
		//on doit récupérer l'ancien commentaire
		$deliverabletmp = $DB->get_record('project_deliverable', array('id' => $data->delivid));
		$data->description = $deliverabletmp->description;
		$data->descriptionformat = $deliverabletmp->descriptionformat;
	}else{
		$data->descriptionformat = FORMAT_HTML;
		$data->description = '';
	}
	
	if(isset($data->commentaire_editor)){
		//si c'est un livrable et qu'on peut pas éditer ==> c'est un type étudiant
		$data->commentaireformat = $data->commentaire_editor['format'];
		$data->commentaire = $data->commentaire_editor['text'];
		$draftid_editor_com = file_get_submitted_draft_itemid('commentaire_editor');
		$data->commentaire = file_save_draft_area_files($draftid_editor_com, $context->id, 'mod_project', 'commentaire', $data->id, array('subdirs' => true), $data->commentaire);
		$data = file_postupdate_standard_editor($data, 'commentaire', $mform->descriptionoptions, $context, 'mod_project', 'commentaire', $data->id);
	}elseif(!isset($data->commentaire_editor) && $data->delivid){
		//on doit récupérer l'ancien commentaire
		$deliverabletmp = $DB->get_record('project_deliverable', array('id' => $data->delivid));
		$data->commentaire = $deliverabletmp->commentaire;
		$data->commentaireformat = FORMAT_HTML;
	}else{
		$data->commentaireformat = FORMAT_HTML;
		$data->commentaire = '';
	}
	

	
	if ($data->delivid) {
		//cas d'une edition
			$data->id = $data->delivid; // id is course module id
			$DB->update_record('project_deliverable', $data);
			add_to_log($course->id, 'project', 'changedeliverable', "view.php?id=$cm->id&view=deliverables&group={$currentGroupId}", 'update', $cm->id);

			/*
    		$tasktodeliv = optional_param_array('tasktodeliv', null, PARAM_INT);
    		if (count($tasktodeliv) > 0){
    		    // removes previous mapping
    		    $DB->delete_records('project_task_to_deliv', array('projectid' => $project->id, 'groupid' => $currentGroupId, 'delivid' => $data->id));
    		    // stores new mapping
        		foreach($tasktodeliv as $aTask){
        		    $amap->id = 0;
        		    $amap->projectid = $project->id;
        		    $amap->groupid = $currentGroupId;
        		    $amap->taskid = $aTask;
        		    $amap->delivid = $data->id;
        		    $res = $DB->insert_record('project_task_to_deliv', $amap);
        		}
				
        	}*/
        } else {
        	$data->created = time();
        	$data->ordering = project_tree_get_max_ordering($project->id, $currentGroupId, 'project_deliverable', true, $data->fatherid) + 1;
			unset($data->id); // id is course module id
			$data->id = $DB->insert_record('project_deliverable', $data);
			add_to_log($course->id, 'project', 'adddeliv', "view.php?id=$cm->id&view=deliverables&group={$currentGroupId}", 'add', $cm->id);
			
			/*
       		if( $project->allownotifications){
       		    project_notify_new_deliverable($project, $cm->id, $data, $currentGroupId);
           	}
			*/
           }
		$data = file_postupdate_standard_filemanager($data, 'localfile', $mform->attachmentoptions, $context, 'mod_project', 'deliverablelocalfile', $data->id);// <== on fait le lien entre le fichier et la ressource/livrable par l'id de la ressource/livrable qui est en tant qu'itemid dans la table _files
		//une fois le localfile set a 1 si il y a eu upload on update le record
		$DB->update_record('project_deliverable', $data);
		
		redirect($url);
	}

	echo $pagebuffer;
	if ($mode == 'add'){
		$deliverable = new StdClass;
		$deliverable->fatherid = required_param('fatherid', PARAM_INT);
		$delivtitle = ($deliverable->fatherid) ? 'addsubdeliv' : 'adddeliv';
		echo $OUTPUT->heading(get_string($delivtitle, 'project'));
		$deliverable->id = $cm->id; // course module
		$deliverable->projectid = $project->id;
		$deliverable->descriptionformat = FORMAT_HTML;
		$deliverable->description = '';
		$deliverable->commentaireformat = FORMAT_HTML;
		$deliverable->commentaire = '';
	} else {
		if(! $deliverable = $DB->get_record('project_deliverable', array('id' => $delivid))){
			print_error('errordeliverable','project');
		}
		$deliverable->delivid = $deliverable->id;
		$deliverable->id = $cm->id;
		if($deliverable->typeelm==1){
		//type livrable
			echo $OUTPUT->heading(get_string('updatedeliv','project'));
		}else if(has_capability('mod/project:editdeliverables', $context)){
		//ressource vu enseignant
			echo $OUTPUT->heading(get_string('updateressource','project'));
		}else{
			echo $OUTPUT->heading(get_string('viewressource','project'));
		}
	}

	$mform->set_data($deliverable);
	$mform->display();	
	
	