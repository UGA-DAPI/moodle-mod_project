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
include_once 'locallib.php';

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
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
        //$PAGE->requires->js( new moodle_url('/mod/project/js/formprojet.js'));
//-------------------------------------------------------------------------------
        $contextss = get_context_instance(CONTEXT_COURSE, $COURSE->id);
//--------------------------------------------------------------------------------
        /// Adding the "general" fieldset, where all the common settings are showed
        $mform->addElement('header', 'general', get_string('general', 'form'));
        //$mform->addHelpButton('type', 'type', 'project');

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
        if(!isset($this->current->update)){
            $mform->addElement('hidden', 'commanditaire','');
        }else{
            $mform->addElement('text', 'commanditaire', get_string('commanditaire','project'), array('size'=>'64'));
        }
        $introimgoptions = array('maxbytes' =>2000000, 'maxfiles'=> 1,'accepted_types' => array('.jpeg', '.jpg', '.png','return_types'=>FILE_INTERNAL));
        //element fonctionnel
        $mform->addElement('filemanager', 'introimg_filemanager', get_string('INTROIMG', 'project'), null, $introimgoptions);
        $mform->addElement('date_time_selector', 'projectstart', get_string('projectstart', 'project'), array('optional'=>true));
        $mform->setDefault('projectstart', time());
        $mform->addElement('date_time_selector', 'projectend', get_string('projectend', 'project'), array('optional'=>true));
        $mform->setDefault('projectend', time()+90*DAYSECS);
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
        //features category. affect directly the project.
        $mform->addElement('header', 'features', get_string('features', 'project'));
        //note that those are advanced checkbox because we want it to return 0 when unchecked, and a regular checkbox don't do that.
        $mform->addElement('advcheckbox', 'projectconfidential', get_string('CONFIDENTIAL', 'project'));
        $mform->setDefault('projectconfidential', true); //set it a checked
        $mform->addElement('advcheckbox', 'projectusesrequs', get_string('requirements', 'project')); //the page related to this is broken
        $mform->addElement('advcheckbox', 'projectusestasks', get_string('tasks', 'project')); 
        $mform->setDefault('projectusestasks', true); //set it a checked
        $mform->addElement('advcheckbox', 'projectusesspecs', get_string('specifications', 'project'));  //the page related to this is broken
        $mform->addElement('advcheckbox', 'projectusesdelivs', get_string('ressources', 'project')." & ".get_string('deliverables', 'project')); 
        $mform->setDefault('projectusesdelivs', true); //set it a checked
        $mform->addElement('advcheckbox', 'projectusesvalidations', get_string('validations', 'project')); //the page related to this is broken
        //this serve to customize the worktype options, replacing the default one that are suited for informatic modules
        $mform->addElement('text', 'howtoworktype', get_string('howtoworktype', 'project'));
        $mform->addHelpButton('howtoworktype', 'howtoworktype', 'project');

        /* Access options, i feel it's useless considering we have strict access rules
        $mform->addElement('header', 'headeraccess', get_string('access', 'project'));
        $mform->addElement('select', 'guestsallowed', get_string('guestsallowed', 'project'), $yesnooptions); 
        $mform->addHelpButton('guestsallowed', 'guestsallowed', 'project');
        $mform->addElement('select', 'guestscanuse', get_string('guestscanuse', 'project'), $yesnooptions); 
        $mform->addHelpButton('guestscanuse', 'guestscanuse', 'project');
        $mform->addElement('select', 'ungroupedsees', get_string('ungroupedsees', 'project'), $yesnooptions); 
        $mform->addHelpButton('ungroupedsees', 'ungroupedsees', 'project');
        $mform->addElement('select', 'allowdeletewhenassigned', get_string('allowdeletewhenassigned', 'project'), $yesnooptions); 
        $mform->addHelpButton('allowdeletewhenassigned', 'allowdeletewhenassigned', 'project');
        $mform->addElement('static', 'studentscanchange', get_string('studentscanchange', 'project'), get_string('seecapabilitysettings', 'project'));
        */

        /* Grading. useable ?
        $mform->addElement('header', 'headergrading', get_string('grading', 'project'));
        $mform->addElement('select', 'teacherusescriteria', get_string('teacherusescriteria', 'project'), $yesnooptions); 
        $mform->addHelpButton('teacherusescriteria', 'teacherusescriteria', 'project');
        $mform->addElement('select', 'autogradingenabled', get_string('autogradingenabled', 'project'), $yesnooptions); 
        $mform->addHelpButton('autogradingenabled', 'autogradingenabled', 'project');
        $mform->addElement('text', 'autogradingweight', get_string('autogradingweight', 'project')); 
        $mform->addHelpButton('autogradingweight', 'autogradingweight', 'project');
        */
        $this->standard_grading_coursemodule_elements();
        $this->add_action_buttons();

    }
    function set_data($defaults){
        $introimgoptions = array('maxbytes' =>2000000, 'maxfiles'=> 1,'accepted_types' => array('.jpeg', '.jpg', '.png','return_types'=>FILE_INTERNAL));
        $defaults = file_prepare_standard_filemanager($defaults, 'introimg', $introimgoptions, $this->context, 'mod_project', 'introimg', $defaults->id);
        
        parent::set_data($defaults);
    }
}
?>