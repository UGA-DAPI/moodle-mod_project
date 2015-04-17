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
include_once 'locallib.php';

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once($CFG->dirroot.'/course/moodleform_mod.php');

require_once($CFG->libdir . '/pagelib.php');

class mod_project_mod_form extends moodleform_mod {

	function definition() {

		global $CFG, $COURSE, $DB, $PAGE, $USER;
		$mform =& $this->_form;
		
		$yesnooptions[0] = get_string('no');
		$yesnooptions[1] = get_string('yes');
		
		//ajout w3c2i include de js pour gestion form
		$PAGE->requires->js( new moodle_url('/mod/project/js/formprojet.js'));
//-------------------------------------------------------------------------------
//$contextss = get_context_instance(CONTEXT_SYSTEM);
$contextss = get_context_instance(CONTEXT_COURSE, $COURSE->id);//context du cours
$canAddTypeProject = false;
$forceTypeProject= false;
if(has_capability('mod/project:addtypeinstance', $contextss)){//capacité system d'ajouter un type projet !
$canAddTypeProject = true;
}
/* Ancien code pour check droit du role projectgrp ...
// Check du role pour le groupe projectgrp qui permet d'ajouter un type de projet
$assignableroles = $DB->get_records('role', array(), '', 'id,name,shortname');
foreach ($assignableroles as $role) {
	if($role->shortname=='projectgrp'){
		$roleusers = '';
		$roleusers = get_role_users($role->id, $contextss, false, 'u.id');
		if (!empty($roleusers)) {
			$listeUsers ='';
			foreach ($roleusers as $checkUser) {
				if($checkUser->id == $USER->id){
					$canAddTypeProject = true;
				}
			}
		}
	}
}
*/
//--------------------------------------------------------------------------------
    /// Adding the "general" fieldset, where all the common settings are showed
$mform->addElement('header', 'general', get_string('general', 'form'));

        //Ajout w3c2i selection choix type deprojet ou projet
$typeoptions[1]=get_string('CREATYPEPROJET', 'project');
$typeoptions[2]=get_string('CREAPROJET', 'project');
$typeprojetoptions = array();
$types = 0;
		if(isset($this->current->update)){//On laisse le choix du type ou projet que a la création
			if($this->current->typeprojet==0){//on set le choix projet ou type projet suivant l'édition de l'un ou l'autre
			$mform->addElement('hidden', 'choixprojet','1');
		}else{
			$mform->addElement('hidden', 'choixprojet','2');
		}
			if($this->current->typeprojet==0){//si on est sur un type projet
				//$mform->addElement('static', 'typeprojet', '', 'Type de projet :');
				$mform->addElement('static', 'or', '', 'Type de projet :');
				$mform->addElement('hidden', 'typeprojet','0');
				$forceTypeProject=true;
			}else{
				//$mform->addElement('static', 'typeprojet', '', 'Projet :');
				$mform->addElement('static', 'or', '', 'Projet :');
				$mform->addElement('hidden', 'typeprojet',$this->current->typeprojet);
				$mform->addElement('hidden', 'hideproject','1');
			}
		}else{//cas d'un ajout
		$types = $DB->get_records_select('project', "typeprojet = ? ", array(0), 'name ASC', 'id, name');
		if (count($types)>0){
			foreach($types as $aType){
				$typeprojetoptions[$aType->id] = $aType->name;
			}
			if($canAddTypeProject){
				$mform->addElement('hidden', 'typeprojet');
				$mform->addElement('select', 'choixprojet', get_string('choixprojet', 'project'), $typeoptions);
			}else{
				$mform->addElement('hidden', 'typeprojet');
				$mform->addElement('hidden', 'choixprojet','2');
				$mform->addElement('static', 'or', '', 'Création d\'un projet :');
			}
			}else{//Si aucun type de projet n'est définit on ne peut pas choisir une création de projet
			if($canAddTypeProject){
				$mform->addElement('hidden', 'typeprojet','0');
					//$mform->addElement('static', 'typeprojet', '', "Création d'un type de projet :");
				$mform->addElement('static', 'or', '', 'Création d\'un type de projet :');
				$mform->addElement('hidden', 'choixprojet','1');
				}else{//si user n'a pas la capacité on refuse la création type de projet
				notice("Création interdite, aucun type de projet n'est définit.", "$CFG->wwwroot/course/view.php?id=$COURSE->id");
			}
		}
	}
		/*
        $types = $DB->get_records_select('project', "typeprojet = ? ", array(0), 'name ASC', 'id, name');
        $typeprojetoptions = array();
        if (count($types)>0){
        	foreach($types as $aType){
        		$typeprojetoptions[$aType->id] = $aType->name;
        	}
			$mform->addElement('hidden', 'typeprojet');//par defaut choix est un type de projet
        }else{
			if(!isset($this->current->update)){
				$mform->addElement('select', 'choixprojet', get_string('choixprojet', 'project'), $typeoptions);
				$mform->addElement('static', 'or', '', get_string('oruploadfile','project'));
			}else{
				$mform->addElement('hidden', 'choixprojet','1');
				$mform->addElement('static', 'typeproject', 'hghfh', 'Type de projet :'); 
			}
        	$typeprojetoptions[0] = get_string('notype', 'project');
			$mform->addElement('hidden', 'typeprojet','0');//par defaut choix est un type de projet
        }
		*/
        $mform->addElement('select', 'type', get_string('type', 'project'), $typeprojetoptions);
        $mform->addHelpButton('type', 'type', 'project');
        
        
    /// Adding the standard "name" field
        $mform->addElement('text', 'name', get_string('name'), array('size'=>'64'));
        if (!empty($CFG->formatstringstriptags)) {
        	$mform->setType('name', PARAM_TEXT);
        } else {
        	$mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

    /// Adding the required "intro" field to hold the description of the instance
        $this->add_intro_editor(true, get_string('introproject', 'project'));
        
        if(!isset($this->current->update) && $types==0 && $canAddTypeProject){
        	$mform->addElement('hidden', 'commanditaire','');
        }else{
        	$mform->addElement('text', 'commanditaire', get_string('commanditaire','project'), array('size'=>'64'));
        }
        $introimgoptions = array('maxbytes' =>2000000, 'maxfiles'=> 1,'accepted_types' => array('.jpeg', '.jpg', '.png','return_types'=>FILE_INTERNAL));
		//element fonctionnel
        $mform->addElement('filemanager', 'introimg_filemanager', get_string('INTROIMG', 'project'), null, $introimgoptions);
        
        
        $mform->addElement('date_time_selector', 'projectstart', get_string('projectstart', 'project'), array('optional'=>true));
        $mform->setDefault('projectstart', time());
        // $mform->addHelpButton('projectstart', 'projectstart', 'project');
        $mform->addElement('date_time_selector', 'projectend', get_string('projectend', 'project'), array('optional'=>true));
        $mform->setDefault('projectend', time()+90*DAYSECS);
        // $mform->addHelpButton('projectend', 'projectend', 'project');

		//projet confidentiel ou non ?
        if($forceTypeProject){
        	$mform->addElement('hidden', 'projectconfidential','0');
        }else{
        	$mform->addElement('select', 'projectconfidential', get_string('CONFIDENTIAL', 'project'), $yesnooptions);
        }
		//Ajout champ caché pour le role du createur
        if($canAddTypeProject){
			//$roleuser = get_user_roles( $contextss, $USER->id);
        	$roleuser = $DB->get_records('role', array(), '', 'id,name,shortname');
        	$roleProjectgrpid = 0;
        	$projectgrpoptions = array();
        	if (!empty($roleuser)){
        		foreach ($roleuser as $rolecourse) {
        			if(preg_match_all('#projectgrp#', $rolecourse->shortname, $matches)){
        				$projectgrpoptions[$rolecourse->id] = $rolecourse->name;
						//$roleProjectgrpid = $rolecourse->id;
        			}
        		}
        	}
        	if(count($projectgrpoptions)==0){
        		$mform->addElement('html', "<p><strong>Aucun rôle de type groupe project n'est créé.</strong></p>");
        	}
        	$mform->addElement('select', 'projectgrpid', get_string('projectgrp', 'project'), $projectgrpoptions);
			//$mform->addElement('hidden', 'projectgrp',$roleProjectgrpid);
        }else{
        	$mform->addElement('hidden', 'projectgrpid','0');
        }
        
        $this->standard_coursemodule_elements();
        
        $mform->addElement('date_time_selector', 'assessmentstart', get_string('assessmentstart', 'project'), array('optional'=>true));
        $mform->setDefault('assessmentstart', time()+75*DAYSECS);
        $mform->addHelpButton('assessmentstart', 'assessmentstart', 'project');

        $unitoptions[HOURS] = get_string('hours', 'project');
        $unitoptions[HALFDAY] = get_string('halfdays', 'project');
        $unitoptions[DAY] = get_string('days', 'project');
        $mform->addElement('select', 'timeunit', get_string('timeunit', 'project'), $unitoptions); 

        $mform->addElement('text', 'costunit', get_string('costunit', 'project')); 

        $mform->addElement('select', 'allownotifications', get_string('allownotifications', 'project'), $yesnooptions); 
        $mform->addHelpButton('allownotifications', 'allownotifications', 'project');

        $mform->addElement('select', 'enablecvs', get_string('enablecvs', 'project'), $yesnooptions); 
        $mform->addHelpButton('enablecvs', 'enablecvs', 'project');

        $mform->addElement('select', 'useriskcorrection', get_string('useriskcorrection', 'project'), $yesnooptions); 
        $mform->addHelpButton('useriskcorrection', 'useriskcorrection', 'project');

        $mform->addElement('header', 'features', get_string('features', 'project'));
        $mform->addElement('checkbox', 'projectusesrequs', get_string('requirements', 'project')); 
        $mform->addElement('checkbox', 'projectusesspecs', get_string('specifications', 'project')); 
        $mform->addElement('checkbox', 'projectusesdelivs', get_string('deliverables', 'project')); 
        $mform->addElement('checkbox', 'projectusesvalidations', get_string('validations', 'project')); 

        $mform->addElement('header', 'headeraccess', get_string('access', 'project'));


        $mform->addElement('select', 'guestsallowed', get_string('guestsallowed', 'project'), $yesnooptions); 
        $mform->addHelpButton('guestsallowed', 'guestsallowed', 'project');

        $mform->addElement('select', 'guestscanuse', get_string('guestscanuse', 'project'), $yesnooptions); 
        $mform->addHelpButton('guestscanuse', 'guestscanuse', 'project');

        $mform->addElement('select', 'ungroupedsees', get_string('ungroupedsees', 'project'), $yesnooptions); 
        $mform->addHelpButton('ungroupedsees', 'ungroupedsees', 'project');

        $mform->addElement('select', 'allowdeletewhenassigned', get_string('allowdeletewhenassigned', 'project'), $yesnooptions); 
        $mform->addHelpButton('allowdeletewhenassigned', 'allowdeletewhenassigned', 'project');

        $mform->addElement('static', 'tudentscanchange', get_string('studentscanchange', 'project'), get_string('seecapabilitysettings', 'project')); 

        $mform->addElement('header', 'headergrading', get_string('grading', 'project'));
        $mform->addElement('select', 'teacherusescriteria', get_string('teacherusescriteria', 'project'), $yesnooptions); 
        $mform->addHelpButton('teacherusescriteria', 'teacherusescriteria', 'project');
        $mform->addElement('select', 'autogradingenabled', get_string('autogradingenabled', 'project'), $yesnooptions); 
        $mform->addHelpButton('autogradingenabled', 'autogradingenabled', 'project');

        $mform->addElement('text', 'autogradingweight', get_string('autogradingweight', 'project')); 
        $mform->addHelpButton('autogradingweight', 'autogradingweight', 'project');

        $this->standard_grading_coursemodule_elements();

        //$this->standard_coursemodule_elements();

        $this->add_action_buttons();
        
    }
    function set_data($defaults){
    	$introimgoptions = array('maxbytes' =>2000000, 'maxfiles'=> 1,'accepted_types' => array('.jpeg', '.jpg', '.png','return_types'=>FILE_INTERNAL));
    	$defaults = file_prepare_standard_filemanager($defaults, 'introimg', $introimgoptions, $this->context, 'mod_project', 'introimg', $defaults->id);
    	
    	parent::set_data($defaults);
    }
}

?>