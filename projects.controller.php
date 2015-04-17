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
/// Controller

	if ($work == 'close') {
		// $currentGroupId;
		// $project->id;
		$projectidtoclose = required_param('projectid', PARAM_INT);
		if($projectidtoclose>0){
			$query = "
			   UPDATE
				  {project}
			   SET
				  etat = 1
			   WHERE
				  id = {$projectidtoclose}
			";
			$DB->execute($query);
			$url = $CFG->wwwroot.'/mod/project/view.php?view=projects&id='.$cm->id;
			redirect($url);
		}
	}
	
	
