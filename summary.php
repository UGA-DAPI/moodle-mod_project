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
/// counting tasks

$tasks = $DB->get_records_select('project_task', "projectid = $project->id AND groupid = $currentGroupId AND fatherid = 0 ", array($project->id, $currentGroupId), '', 'id,abstract');
$leafTasks = array();
$leafTaskList = '';
if ($tasks){
    foreach($tasks as $aTask){
        $leafTasks = array_merge($leafTasks, project_count_leaves('project_task', $aTask->id, true)) ;
    }
    $leafTaskList = implode("','", $leafTasks);
}
$counttask = count($leafTasks);
/// counting deliverables

$deliverables = $DB->get_records_select('project_deliverable', "projectid = $project->id AND groupid = $currentGroupId AND fatherid = 0 ", array($project->id, $currentGroupId), '', 'id,abstract');
$leafDelivs = array();
$leafDelivList = '';
if ($deliverables){
    foreach($deliverables as $aDeliverable){
        $leafDelivs = array_merge($leafDelivs, project_count_leaves('project_deliverable', $aDeliverable->id, true)) ;
    }
    $leafDelivList = implode("','", $leafDelivs);
}
$countdeliv = count($leafDelivs);

$projectheading = $DB->get_record('project_heading', array('projectid' => $project->id, 'groupid' => $currentGroupId));
    // if missing create one
if (!$projectheading){
  $projectheading = new stdClass;
  $projectheading->id = 0;
  $projectheading->projectid = $project->id;
  $projectheading->groupid = $currentGroupId;
  $projectheading->title = '';
  $projectheading->abstract = '';
  $projectheading->rationale = '';
  $projectheading->environment = '';
  $projectheading->organisation = '';
  $projectheading->department = '';        
  $DB->insert_record('project_heading', $projectheading);
}
/********************************************* Start producing summary ***************************/

echo $pagebuffer;
echo '<center>';
echo $OUTPUT->box_start('center', '100%');
?>
<table width="80%">
    <tr valign="top">
        <th align="left" width="60%">
            <?php 
            echo "<img style='vertical-align:middle;margin-right:4px;' src='".$OUTPUT->pix_url('equipe', 'project')."' alt='".get_string('equipe', 'project')."' />Equipe du projet : ".$project->name;
                /*print_string('summaryforproject', 'project');
                echo $OUTPUT->help_icon('leaves', 'project', true); */
                ?>
            </th>
            <th align="left" width="40%">
                <?php //echo $projectheading->title ; ?>
            </th>
        </tr>
    </table>
    <?php
	//récupération des rôles et des users affectés
	//list($assignableroles, $assigncounts, $nameswithcounts) = get_assignable_roles($context, ROLENAME_BOTH, true);
    $assignableroles = $DB->get_records('role', array(), '', 'id,name,shortname');
    $roles = array('projectetu','projectens','projectent');
    $rolesName = array('Etudiants Projet','Tuteurs enseignant','Tuteurs entreprise');
    for ($i=0;$i<3;$i++){
      $rolempty = true;
      $roleNom = $rolesName[$i];
      foreach ($assignableroles as $role) {
         if($role->shortname==$roles[$i]){
            $roleusers = '';
            $roleusers = get_role_users($role->id, $context, false, 'u.id, u.firstname, u.lastname, u.email');
            if (!empty($roleusers)) {
               $rolempty = false;
               $listeUsers ='';
               $mailtoUsers = array();
               foreach ($roleusers as $user) {
                  $mailtoUsers[] = $user->email;
                  $listeUsers .= '<li><a href="' . $CFG->wwwroot . '/user/view.php?id=' . $user->id . '" >' . fullname($user) . '</a></li>';
              }
          }
      }
  }
  if(!$rolempty){
     echo "<p class='summary-email'><a href='mailto:".implode(',',$mailtoUsers)."' title='Envoyer un email à tous'><img src='".$OUTPUT->pix_url('/new_mail', 'project')."' alt=\"Envoyer un email à tous\" /></a>".$roleNom." :</p>";
     echo "<ul class='summary-list'>";
     echo $listeUsers;
     echo "</ul>";
 }else{
     echo "<p class='summary-email-empty'>".$roleNom." :</p><p class='summary-list'>Aucun rôle attribué.</p>";
 }
}
echo $OUTPUT->box_end();
global $COURSE;
	$contextss = get_context_instance(CONTEXT_COURSE, $COURSE->id);//context du cours
	if(has_capability('mod/project:addtypeinstance', $contextss)){//capacité system d'ajouter un type projet !
  echo "<p style='text-align:left;'><a href=\"{$CFG->wwwroot}/admin/roles/assign.php?contextid={$context->id}\" title=\"Gérer l'attribution des rôles\">Gérer l'attribution des rôles pour le projet</a></p>";
}
echo '</center>';

?>