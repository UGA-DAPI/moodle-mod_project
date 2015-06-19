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

class Deliverable_Form extends moodleform {

    var $mode;
    var $project;
    var $current;
    var $descriptionoptions;
    var $currentGroupId;

    function __construct($action, $mode, &$project, $delivid, $currentGroupId){
        global $DB;
        
        $this->mode = $mode;
        $this->project = $project;
        $this->currentGroupId = $currentGroupId;
        if ($delivid){
            $this->current = $DB->get_record('project_deliverable', array('id' => $delivid));
        }
        parent::__construct($action);
    }

    function definition(){
        global $COURSE, $DB, $PAGE;

        $mform = $this->_form;
        
        $PAGE->requires->js( new moodle_url('/mod/project/js/formdeliv.js'));
        $typeelm = required_param('typeelm', PARAM_INT);
        $groupid = $this->currentGroupId;
        $modcontext = context_module::instance($this->project->cmid);
        $canEdit=false; 
        // just in case
        if ($typeelm==0) {
            $canEdit = has_capability('mod/project:editressources', $modcontext);
        }else{
            $canEdit = has_capability('mod/project:editdeliverables', $modcontext);
        }
        
        $maxfiles = 1;                
        // TODO: add some setting
        $maxbytes = $COURSE->maxbytes; 
        // TODO: add some setting    
        $this->descriptionoptions = array('trusttext' => true, 'subdirs' => false, 'maxfiles' => $maxfiles, 'maxbytes' => $maxbytes, 'context' => $modcontext);
        $this->attachmentoptions = array('subdirs' => false, 'maxfiles' => $maxfiles, 'maxbytes' => $maxbytes);

        

        $mform->addElement('hidden', 'id');
        $mform->addElement('hidden', 'fatherid');
        $mform->addElement('hidden', 'groupid', $groupid);
        $mform->addElement('hidden', 'delivid');
        $mform->addElement('hidden', 'work');
        $mform->setDefault('work', $this->mode);

        if($canEdit){
            $deliverytypes = array();
            $deliverytypes[] = 'Ressource';
            $deliverytypes[] = 'Livrable';

            if(isset($typeelm) && $typeelm==1){
                $mform->addElement('text', 'abstract', get_string('delivtitle', 'project'), array('size' => "100%"));
            }else{
                $mform->addElement('text', 'abstract', get_string('ressourcetitle', 'project'), array('size' => "100%"));
            }
            /*
            $statusses = project_get_options('delivstatus', $this->project->id);
            $deliverystatusses = array();
            foreach($statusses as $aStatus){
                $deliverystatusses[$aStatus->code] = '['. $aStatus->code . '] ' . $aStatus->label;
            }
            $mform->addElement('select', 'status', get_string('status', 'project'), $deliverystatusses);
            $mform->addHelpButton('status', 'deliv_status', 'project');
            */
            $mform->addElement('hidden', 'status','CRE');
            $query = "SELECT id, abstract, ordering FROM {project_milestone} WHERE projectid = {$this->project->id} AND groupid = {$this->currentGroupId} ORDER BY ordering";
            $milestones = $DB->get_records_sql($query);
            $milestonesoptions = array();
            $nomilestone=false;
            if(count($milestones)>0){
                foreach($milestones as $aMilestone){
                    $milestonesoptions[$aMilestone->id] = format_string($aMilestone->abstract);
                }
                $nomilestone=false;
            }else{
                $nomilestone=true;
                $milestonesoptions[0] = get_string('nomilestone', 'project');
            }
            $mform->addElement('select', 'milestoneid', get_string('milestone', 'project'), $milestonesoptions);
            if (isset($this->current)){
                $mform->addElement('html', '<div hidden>');
                $select = $mform->addElement('select', 'typeelm', get_string('typeelm', 'project'), $deliverytypes);
                $select->setSelected($this->current->typeelm);
                $mform->addHelpButton('typeelm', 'typeelm', 'project');
                $mform->addElement('html', '</div>');
            }
            else{
                $mform->addElement('html', '<div hidden>');
                $select = $mform->addElement('select', 'typeelm', get_string('typeelm', 'project'), $deliverytypes);
                $select->setSelected($typeelm);
                $mform->addHelpButton('typeelm', 'typeelm', 'project');
                $mform->addElement('html', '</div>');
            }

            $mform->addElement('editor', 'description_editor', get_string('description', 'project'), null, $this->descriptionoptions);
            $mform->setType('decription_editor', PARAM_RAW);
            
        }else{
        //Si l'user ne peut pas éditer mais juste voir, dl la ressource/livrable et commenter
            $mform->addElement('hidden', 'typeelm');
            $mform->addElement('hidden', 'status');
            $mform->addElement('hidden', 'milestoneid');
            $delivDetails = "<table>";
            $delivDetails .= "<tr><td>Intitulé</td><td><b>".$this->current->abstract."</b></td></tr>";
            $milestonetmp = $DB->get_record('project_milestone', array('id' => $this->current->milestoneid));
            $delivDetails .= "<tr><td>Etape</td><td>".$milestonetmp->abstract."</td></tr>";
            if($this->current->typeelm==0){
                $typeLbl = 'Ressource';
            }else{
                $typeLbl = 'Livrable';
            }
            $delivDetails .= "<tr><td>Type</td><td>".$typeLbl."</td></tr>";
            $delivDetails .= "<tr><td colspan='2'>description :</td></tr>";
            $delivDetails .= "<tr><td colspan='2'>".$this->current->description."</td></tr>";
            $delivDetails .= "</table>";
            $mform->addElement('html', $delivDetails);
            if($this->current->typeelm==1){
            //c'est un livrable
                $mform->addElement('editor', 'commentaire_editor', get_string('commentaire', 'project'), null, $this->descriptionoptions);              
                $mform->setType('commentaire_editor', PARAM_RAW);
            }
        }

        if(!$canEdit && $this->current->typeelm==0){
            $mform->addElement('header', 'headerupload', get_string('downloadressource', 'project'));
        }else{
            if ($typeelm==0) {
                $mform->addElement('header', 'headerupload', get_string('ressource', 'project'));
            }
            else{
                $mform->addElement('header', 'headerupload', get_string('delivered', 'project'));
            }
        }               
        /*
        if ($this->mode == 'update'){
            if (!empty($this->current->url)) {
                //$mform->addElement('static', 'uploaded', get_string('deliverable', 'project'), "<a href=\"{$deliverable->url}\" target=\"_blank\">{$deliverable->url}</a>");
            } else if ($this->current->localfile) {
                // TODO : using file API give access to locally stored file
            } else {
                //$mform->addElement('static', 'uploaded', print_string('notsubmittedyet','project'));
            }
        }
        */
        if(!$canEdit && $this->current->typeelm==0){
        //si l'user peut pas editer la ressource/livrable mais que c'est une ressource ==> étudiant regardant une ressource
            $delivData='';
            if ($this->current->localfile) {
                $fs = get_file_storage();
                $files = $fs->get_area_files($modcontext->id, 'mod_project', 'deliverablelocalfile', $this->current->id, 'sortorder DESC, id ASC', false);
                if(!empty($files)){
                    $file = reset($files);
                    $path = '/'.$modcontext->id.'/mod_project/deliverablelocalfile/'.$file->get_itemid().$file->get_filepath().$file->get_filename();
                    $url = moodle_url::make_file_url('/pluginfile.php', $path, '');
                    $delivData .= html_writer::link($url, $this->current->abstract);
                }else{
                    $delivData .= $this->current->abstract;
                }
            }elseif(!empty($this->current->url)){
                $delivData .= "<a href=\"{$this->current->url}\" target=\"_blank\">{$this->current->url}</a>";
            }else{
                $delivData .= "Pas de fichier à télécharger ou à voir.";
            }
            $mform->addElement('html', $delivData);
            $this->add_action_buttons(false,'Retour');
        }else{
            if(isset($milestonetmp->statut)){
                if(!$canEdit && $this->current->typeelm==1 && $milestonetmp->statut==1){
                //si l'user peut pas editer mais que c'est un livrable => étudiant qui voir le livrable , mais si c'est un livrable d'une étape en cours de validation, on bloque l'accès.
                    $mform->addElement("html", "<p>L'étape associée a ce livrable est en cours de validation, il ne peut pas être modifié.</p>");
                    $this->add_action_buttons(false,'Retour');
                }else{
                    $mform->addElement('text', 'url', get_string('url','project'));
                    $mform->addElement('static', 'or', '', get_string('oruploadfile','project'));
                    $mform->addElement('filemanager', 'localfile_filemanager', get_string('uploadfile', 'project'), null, $this->attachmentoptions);
                    $this->add_action_buttons(true);
                }
            }else{
                $mform->addElement('text', 'url', get_string('url','project'));
                $mform->addElement('static', 'or', '', get_string('oruploadfile','project'));
                $mform->addElement('filemanager', 'localfile_filemanager', get_string('uploadfile', 'project'), null, $this->attachmentoptions);
                $this->add_action_buttons(true);
            }
        }
    }

    function set_data($defaults){
        //var_dump($defaults);die();
        $context = context_module::instance($this->project->cmid);

        $draftid_editor = file_get_submitted_draft_itemid('description_editor');
        $currenttext = file_prepare_draft_area($draftid_editor, $context->id, 'mod_project', 'description_editor', $defaults->id, array('subdirs' => true), $defaults->description);
        $defaults = file_prepare_standard_editor($defaults, 'description', $this->descriptionoptions, $context, 'mod_project', 'deliverabledescription', $defaults->id);
        
        $draftid_editor_com = file_get_submitted_draft_itemid('commentaire_editor');
        $currenttext_com = file_prepare_draft_area($draftid_editor_com, $context->id, 'mod_project', 'commentaire_editor', $defaults->id, array('subdirs' => true), $defaults->commentaire);
        $defaults = file_prepare_standard_editor($defaults, 'commentaire', $this->descriptionoptions, $context, 'mod_project', 'commentaire', $defaults->id);
        
        if(isset($defaults->delivid)){
            $delivid= $defaults->delivid;
        }else{
            $delivid= 0;
        }
        $defaults = file_prepare_standard_filemanager($defaults, 'localfile', $this->attachmentoptions, $context, 'mod_project', 'deliverablelocalfile', $delivid);
        
        //$draftitemid = file_get_submitted_draft_itemid('localfile_filemanager');
        //file_prepare_draft_area($draftitemid, $context->id, 'mod_project', 'localfile_filemanager', $this->project->id, $this->attachmentoptions);
        
        $defaults->description = array('text' => $currenttext, 'format' => $defaults->descriptionformat, 'itemid' => $draftid_editor);

        parent::set_data($defaults);
    }
}
