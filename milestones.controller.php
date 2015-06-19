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
$context = context_module::instance($cm->id);
if ($work == 'dodelete' OR $work == 'delete') {
	$milestoneid = required_param('milestoneid', PARAM_INT);
    	project_tree_delete($milestoneid, 'project_milestone', 0); // uses list option switch
    	// cleans up any assigned task
    	$query = "UPDATE {project_task} SET milestoneid = '0' WHERE milestoneid = $milestoneid";
    	$DB->execute($query);

    	// cleans up any assigned deliverable
    	$query = "UPDATE {project_deliverable} SET milestoneid = '0' WHERE milestoneid = $milestoneid";
    	$DB->execute($query);
    	//add_to_log($course->id, 'project', 'changemilestone', "view.php?id=$cm->id&view=milestone&group={$currentGroupId}", 'delete', $cm->id);
    } elseif ($work == 'doclearall') {
        // delete all records. POWERFUL AND DANGEROUS COMMAND.
    	$DB->delete_records('project_milestone', array('projectid' => $project->id));

        // do reset all milestone assignation in project
    	$query = "
    	UPDATE
    	{project_task}
    	SET
    	milestoneid = NULL
    	WHERE
    	projectid = {$project->id} AND
    	groupid = {$currentGroupId}
    	";
    	$DB->execute($query);

        // do reset all milestone assignation in project
    	$query = "
    	UPDATE
    	{project_deliverable}
    	SET
    	milestoneid = NULL
    	WHERE
    	projectid = {$project->id} AND
    	groupid = {$currentGroupId}
    	";
    	$DB->execute($query);
    	//add_to_log($course->id, 'project', 'changemilestones', "view.php?id=$cm->id&view=milestone&group={$currentGroupId}", 'clear', $cm->id);
    } elseif ($work == 'up') {
    	$milestoneid = required_param('milestoneid', PARAM_INT);
    	project_tree_up($project, $currentGroupId,$milestoneid, 'project_milestone', 0);
    } elseif ($work == 'down') {
    	$milestoneid = required_param('milestoneid', PARAM_INT);
    	project_tree_down($project, $currentGroupId,$milestoneid, 'project_milestone', 0);
    } elseif ($work == 'sortbydate'){
    	$milestones = array_values($DB->get_records_select('project_milestone', "projectid = {$project->id} AND groupid = {$currentGroupId}"));

    	function sortByDate($a, $b){
    		if ($a->deadline == $b->deadline) return 0;
    		return ($a->deadline > $b->deadline) ? 1 : -1 ; 
    	}

    	usort($milestones, 'sortByDate');
        // reorders in memory and stores back
    	$ordering = 1;
    	foreach($milestones as $aMilestone){
    		$aMilestone->ordering = $ordering;
    		$DB->update_record('project_milestone', $aMilestone);
    		$ordering++;
    	}
    } elseif ($work == 'valider') {//Valider une étape
	/*
	 * Statuts :
	 * 0 => en travaux
	 * 1 => en cours de validation
	 * 2 => en révision
	 * 3 => validée
	 *	
	*/
	if(has_capability('mod/project:validatemilestone', $context)){
		$milestoneid = required_param('milestoneid', PARAM_INT);
		$query = "
		UPDATE
		{project_milestone}
		SET
		statut = 3
		WHERE
		id = $milestoneid
		";
		$DB->execute($query);
			project_notify_milestone_change($project, $milestoneid, 3 , $cm->id, $currentGroupId);//Alerte email aux étudiants que l'étape a été validé
			$url = $CFG->wwwroot.'/mod/project/view.php?view=milestones&id='.$cm->id;
		}
		redirect($url);
		
    } elseif ($work == 'refuser') {//demande de corrections pour une étape
    	if(has_capability('mod/project:validatemilestone', $context)){
    		$milestoneid = required_param('milestoneid', PARAM_INT);
    		$query = "
    		UPDATE
    		{project_milestone}
    		SET
    		statut = 2
    		WHERE
    		id = $milestoneid
    		";
    		$DB->execute($query);
    		
    		$milestone = $DB->get_record('project_milestone', array('id' => $milestoneid));
    		
			//Création de la discution dans partie messages
    		$newMessage = new StdClass;
    		$newMessage->id = $cm->id;
    		$newMessage->groupid = $currentGroupId;
    		$newMessage->projectid = $project->id;
    		$newMessage->abstract = get_string('milestone', 'project')." ".$milestone->ordering.", ".$milestone->abstract." : ".get_string('revisionask', 'project')." pour la version ".$milestone->numversion;
    		$newMessage->message = '';
    		$newMessage->messageformat = FORMAT_HTML;
    		$newMessage->parent = 0;
    		$newMessage->userid = $USER->id;
    		$newMessage->created = time();
    		$newMessage->modified = time();
    		$newMessage->lastuserid = $USER->id;
    		$newMessage->ordering = project_tree_get_max_ordering_message($project->id, $currentGroupId, 'project_messages', true, 0) + 1;
    		$returnid = $DB->insert_record('project_messages', $newMessage);
    		
			project_notify_milestone_change($project, $milestoneid, 2 , $cm->id, $currentGroupId);//Alerte email aux étudiants que l'étape a été mise en révision
			//$url = $CFG->wwwroot.'/mod/project/view.php?view=milestones&id='.$cm->id;
			$url = $CFG->wwwroot."/mod/project/view.php?id={$cm->id}&amp;work=update&amp;messageid={$returnid}&amp;view=messages";//redirection vers la discussion correspondante
		}
		//redirect($url);
		
    }elseif($work == 'askvalider'){//Demande de validation d'une étape
    if(has_capability('mod/project:askvalidatemilestone', $context)){
    	$milestoneid = required_param('milestoneid', PARAM_INT);
    	$milestone = $DB->get_record('project_milestone', array('id' => $milestoneid));
    	$numVersion = (int)$milestone->numversion+1;
    	
    	$query = "
    	UPDATE
    	{project_milestone}
    	SET
    	statut = 1
    	WHERE
    	id = $milestoneid
    	";
    	$DB->execute($query);
    	$query = "
    	UPDATE
    	{project_milestone}
    	SET
    	numversion = $numVersion
    	WHERE
    	id = $milestoneid
    	";
    	$DB->execute($query);
    	
			//Création de la discution dans partie messages
    	$newMessage = new StdClass;
    	$newMessage->id = $cm->id;
    	$newMessage->groupid = $currentGroupId;
    	$newMessage->projectid = $project->id;
    	$newMessage->abstract = get_string('milestone', 'project')." ".$milestone->ordering.", ".$milestone->abstract." : ".get_string('validationask', 'project')." version ".$numVersion;
    	$newMessage->message = '';
    	$newMessage->messageformat = FORMAT_HTML;
    	$newMessage->parent = 0;
    	$newMessage->userid = $USER->id;
    	$newMessage->created = time();
    	$newMessage->modified = time();
    	$newMessage->lastuserid = $USER->id;
    	$newMessage->ordering = project_tree_get_max_ordering_message($project->id, $currentGroupId, 'project_messages', true, 0) + 1;
    	$returnid = $DB->insert_record('project_messages', $newMessage);
    	
			project_notify_milestone_change($project, $milestoneid, 1 , $cm->id, $currentGroupId);//Alerte email aux enseignants que une demande de validation est faite pour l'étape
			
			// zip generation
			$zip = new ZipArchive();
			$numDigit = str_pad($numVersion,3, "0",STR_PAD_LEFT);//passage du numéro de version sur 3 digit
			$archiveName = "M".$milestone->ordering."-".$numDigit.".zip";
			$folderName ="M".$milestone->ordering."-".$numDigit;
			if($zip->open($CFG->tempdir.'/'.$archiveName, ZipArchive::CREATE) === true)
			{
				$zip->addEmptyDir($folderName);
				
				$fs = get_file_storage();
				$deliverables = $DB->get_records('project_deliverable', array('milestoneid' => $milestoneid, 'projectid' => $project->id,'typeelm' => 1,'groupid' => $currentGroupId), '', 'abstract,localfile,url,id');
				foreach($deliverables as $deliverable){
					if($deliverable->localfile){//Si le livrable est un fichier
						$files = $fs->get_area_files($context->id, 'mod_project', 'deliverablelocalfile', $deliverable->id, 'sortorder DESC, id ASC', false);
						if(!empty($files)){
							$file = reset($files);
							//$path = file_encode_url($CFG->wwwroot.'/pluginfile.php', '/'.$context->id.'/mod_project/deliverablelocalfile/'.$file->get_itemid().$file->get_filepath().$file->get_filename());
							//$zip->addFile($path, $folderName.'/'.$file->get_filename());
							$zip->addFromString($folderName.'/'.$file->get_filename(),$file->get_content());
						}			
					}elseif($deliverable->url!=''){//Si le livrable est un lien (url)
						$zip->addFromString(makeAlias($deliverable->abstract), 'Lien du livrable :\n'.$deliverable->url);
					}
				}
				
				// Et on referme l’archive.
				$zip->close();
				
				//Enregistrement de l'archive dans moodle lié au context des étapes
				$file_record = array('contextid'=>$context->id, 'component'=>'mod_project', 'filearea'=>'deliverablearchive',
					'itemid'=>$milestoneid, 'filepath'=>'/', 'filename'=>$archiveName,
					'timecreated'=>time(), 'timemodified'=>time());
				$fs->create_file_from_pathname($file_record, $CFG->tempdir.'/'.$archiveName);
				// Envoi en téléchargement.
				//header('Content-Transfer-Encoding: binary'); //Transfert en binaire (fichier).
				//header('Content-Disposition: attachment; filename="'.$archiveName.'"'); //Nom du fichier.
				//header('Content-Length: '.filesize($CFG->tempdir.'/'.$archiveName)); //Taille du fichier.
			}
			
			
			//$url = $CFG->wwwroot.'/mod/project/view.php?view=milestones&id='.$cm->id;
			$url = $CFG->wwwroot."/mod/project/view.php?id={$cm->id}&amp;work=update&amp;messageid={$returnid}&amp;view=messages";//redirection vers la discussion correspondante
		}
		//echo $OUTPUT->confirm('Are you sure?', $url, $CFG->wwwroot."/mod/project/view.php?id={$cm->id}&amp;view=milestones");
		//redirect($url);
	}
	
	function makeAlias($string){
		$alias = strtolower($string);
		$alias = str_replace(" ", "-", $alias);
		$alias = preg_replace('/[-]{2,}/', '-', $alias);
		$alias = trim($alias, '-');
		return $alias;
	}
