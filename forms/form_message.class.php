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
require_once($CFG->libdir.'/formslib.php');

class Message_Form extends moodleform {

	var $mode;
	var $project;
	var $current;
	var $descriptionoptions;

	function __construct($action, $mode, &$project, $messageid, $messageParent){
		global $DB;
		
		$this->mode = $mode;
		$this->project = $project;
		$this->parent = $messageParent;
		if ($messageid){
			$this->current = $DB->get_record('project_messages', array('id' => $messageid));
		}
		parent::__construct($action);
	}
    	
	function definition(){
		global $COURSE, $DB, $OUTPUT;

    	$mform = $this->_form;
    	
    	$modcontext = context_module::instance($this->project->cmid);

		$maxfiles = 0;                // TODO: add some setting
		$maxbytes = $COURSE->maxbytes; // TODO: add some setting	
		$this->descriptionoptions = array('trusttext' => true, 'subdirs' => false, 'maxfiles' => $maxfiles, 'maxbytes' => $maxbytes, 'context' => $modcontext);
		$this->attachmentoptions = array('subdirs' => false, 'maxfiles' => $maxfiles, 'maxbytes' => $maxbytes);
    	
    	$currentGroup = 0 + groups_get_course_group($COURSE);

    	$mform->addElement('hidden', 'id');
    	$mform->addElement('hidden', 'parent');
    	$mform->addElement('hidden', 'messageid');
    	$mform->addElement('hidden', 'work');
    	$mform->setDefault('work', $this->mode);
		
		if($this->mode=='add' && $this->parent>0){//Si on ajoute une réponse (un id de parent existe)
			$discussionDetails='';
			$dateType = "%a %d %b %Y, %H:%M";
			$query = "
			SELECT 
				d.*
			FROM 
				{project_messages} as d
			WHERE 
				d.groupid = {$currentGroup} AND 
				d.projectid = {$this->project->id} AND 
				d.parent = {$this->parent}
			ORDER BY 
				d.modified
			";
			$discussion = $DB->get_record('project_messages', array('id' => $this->parent));
			if($discussion->message!=''){
				$userCreator = $DB->get_record('user', array('id' => $discussion->userid));
				$fullname = fullname($userCreator);
				$discussionDetails .= "<div class='messagebox'>\n";
				$discussionDetails .= "<p class='messageintro header'>".get_string('ecritpar', 'project')." <b>".$fullname."</b>, ".get_string('ecritle', 'project')." ".userdate($discussion->modified, $dateType)."</p>\n";
				$discussionDetails .= "<div class='messagecorp'>".$discussion->message."</div>\n";
				$discussionDetails .= "</div>\n";
			}
			if ($messages = $DB->get_records_sql($query)) {
				foreach($messages as $message){
				//affichages des messages
					$userCreator = $DB->get_record('user', array('id' => $message->userid));
					//$picture = $OUTPUT->user_picture($userCreator);//genere l'avatar
					$fullname = fullname($userCreator);
					
					$discussionDetails .= "<div class='messagebox'>\n";
					$discussionDetails .= "<p class='messageintro header'>".get_string('ecritpar', 'project')." <b>".$fullname."</b>, ".get_string('ecritle', 'project')." ".userdate($message->modified, $dateType)."</p>\n";
					$discussionDetails .= "<div class='messagecorp'>".$message->message."</div>\n";
					$discussionDetails .= "</div>\n";
				}
			}
			$discussionDetails .= "<p>".get_string('repondrediscu', 'project')." :</p>";
			$mform->addElement('html', $discussionDetails);
			
			$mform->addElement('hidden', 'abstract');//on ajoute un champ pour le sujet qui sera vide, car on need que le message vu que c'est une réponse
		}else{
			$mform->addElement('text', 'abstract', get_string('messagetitle', 'project'), array('size' => "100%"));
		}
		
		
    	$mform->addElement('editor', 'message_editor', get_string('message', 'project'), null, $this->descriptionoptions);
        $mform->setType('message_editor', PARAM_RAW);
		
 		$this->add_action_buttons(true);
    }
    
    function set_data($defaults){
		//var_dump($defaults);die();
		$context = context_module::instance($this->project->cmid);

		$draftid_editor = file_get_submitted_draft_itemid('message_editor');
		$currenttext = file_prepare_draft_area($draftid_editor, $context->id, 'mod_project', 'message_editor', $defaults->id, array('subdirs' => true), $defaults->message);
		$defaults = file_prepare_standard_editor($defaults, 'message', $this->descriptionoptions, $context, 'mod_project', 'message', $defaults->id);
				
		$defaults->message = array('text' => $currenttext, 'format' => $defaults->messageformat, 'itemid' => $draftid_editor);

    	parent::set_data($defaults);
    }
}
