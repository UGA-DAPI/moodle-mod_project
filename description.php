<?php

    /**
    *
    * Prints a desciption of the project (heading).
    *
	*
	* @package mod-project
	* @category mod
	* @author Yohan Thomas - W3C2i (support@w3c2i.com)
	* @date 30/09/2013
	* @version 3.0
	* @license http://www.gnu.org/copyleft/gpl.html GNU Public License
	*
	*/
    
    include_once 'forms/form_description.class.php';
    
    $mform = new Description_Form($url, $project, $work);

    if ($work == 'doexport'){
    	    $heading = $DB->get_record('project_heading', array('projectid' => $project->id, 'groupid' => $currentGroupId));
    	    $projects[$heading->projectid] = $heading;
    	    include_once "xmllib.php";
    	    $xml = recordstoxml($projects, 'project', '', true, null);
    	    $escaped = str_replace('<', '&lt;', $xml);
    	    $escaped = str_replace('>', '&gt;', $escaped);
    	    echo $OUTPUT->heading(get_string('xmlexport', 'project'));
    	    echo $OUTPUT->box("<pre>$escaped</pre>");
            add_to_log($course->id, 'project', 'readdescription', "view.php?id={$cm->id}&amp;view=description&amp;group={$currentGroupId}", 'export', $cm->id);
            echo $OUTPUT->continue_button("view.php?view=description&amp;id=$cm->id");
            return;
    }

/// Header editing form ********************************************************
if ($work == 'edit'){

	if ($mform->is_cancelled()){
        redirect($url);
	}

    if ($heading = $mform->get_data()){
    	
        $heading->abstract = $heading->abstract_editor['text'];
        $heading->rationale = $heading->rationale_editor['text'];
        $heading->environment = $heading->environment_editor['text'];

		$abstract_draftid_editor = file_get_submitted_draft_itemid('abstract_editor');
		$heading->abstract = file_save_draft_area_files($abstract_draftid_editor, $context->id, 'mod_project', 'abstract', $heading->id, array('subdirs' => true), $heading->abstract);

		$rationale_draftid_editor = file_get_submitted_draft_itemid('rationale_editor');
		$heading->rationale = file_save_draft_area_files($rationale_draftid_editor, $context->id, 'mod_project', 'rationale', $heading->id, array('subdirs' => true), $heading->rationale);

		$environment_draftid_editor = file_get_submitted_draft_itemid('environment_editor');
		$heading->environment = file_save_draft_area_files($environment_draftid_editor, $context->id, 'mod_project', 'environment', $heading->id, array('subdirs' => true), $heading->environment);

        $heading->id = $heading->headingid;
        $heading->projectid = $project->id;
        $heading->groupid = $currentGroupId;
        $heading->title = $heading->title;
        $heading->organisation = $heading->organisation;
        $heading->department = $heading->department;

	    $heading = file_postupdate_standard_editor($heading, 'abstract', $mform->editoroptions, $context, 'mod_project', 'absract', $heading->id);
	    $heading = file_postupdate_standard_editor($heading, 'rationale', $mform->editoroptions, $context, 'mod_project', 'rationale', $heading->id);
	    $heading = file_postupdate_standard_editor($heading, 'environment', $mform->editoroptions, $context, 'mod_project', 'environment', $heading->id);

        $DB->update_record('project_heading', $heading);
        redirect($url);
    }

    $projectheading = $DB->get_record('project_heading', array('projectid' => $project->id, 'groupid' => $currentGroupId));

	// Start ouptuting here
	echo $pagebuffer;
	echo $OUTPUT->heading(get_string('editheading', 'project'));
	$projectheading->headingid = $projectheading->id;
	$projectheading->id = $cm->id;
	$projectheading->format = FORMAT_HTML;
	$projectheading->projectid = $project->id;

	$mform->set_data($projectheading);
	$mform->display();

} else {
	// Start ouptuting here
	echo $pagebuffer;
	echo "<h2 class='titlecenter'>".$project->name."</h2>";
	if(isset($project->commanditaire) && $project->commanditaire!=''){
		echo "<p><b>Commanditaire du projet : </b><i>".$project->commanditaire."</i></p>";
	}
	echo "<div id='left-intro'><div id='desc-summary'><p style='font-weight:bold;'>".get_string('summary', 'project')." :</p>";
	echo $project->intro."</div></div>";
	echo "<div id='right-intro'>";
	$fs = get_file_storage();
	$files = $fs->get_area_files($context->id, 'mod_project', 'introimg', $project->id, 'sortorder DESC, id ASC', false);
	//var_dump($files);
	if(!empty($files)){
		$file = reset($files);
		//$url = moodle_url::make_pluginfile_url($context->id, 'project', 'introimg', $project->id, '', $imgFileName);
		$path = '/'.$context->id.'/mod_project/introimg/'.$file->get_itemid().$file->get_filepath().$file->get_filename();
		$url = moodle_url::make_file_url('/pluginfile.php', $path, '');
		//echo "<img width='400' src='".file_encode_url($CFG->wwwroot . '/pluginfile.php', '/'.$context->id.'/mod_project/introimg/'.$project->introimg)."/".$imgFileName."' />";
		//echo html_writer::link($url, $file->get_filename());
		echo "<img style='max-width: 400px;' src='".file_encode_url($CFG->wwwroot . '/pluginfile.php', $path)."' />";
	}
	if((int)$project->projectconfidential==1){
		echo "<p id='descconfidential'>Projet confidentiel</p>";
	}
	echo "</div><div id='sepbloc'></div>";
	project_print_assignement_info($project, false);
	
   // project_print_heading($project, $currentGroupId);
    echo "<center>";
    if ($USER->editmode == 'on' && has_capability('mod/project:addinstance', $context)) {
        echo "<br/><a href=\"view.php?work=edit&amp;id={$cm->id}\" >".get_string('editheading','project')."</a>";
        echo " - <a href=\"view.php?work=doexport&amp;id={$cm->id}\" >".get_string('exportheadingtoXML','project')."</a>";
    }
    //echo "<br/><a href=\"xmlview.php?id={$cm->id}\" target=\"_blank\">".get_string('gettheprojectfulldocument','project')."</a>";
    echo "</center>";
}