<?php

/**
*
* used in restorelib.php for restoring entities.
*
*
* @package mod-project
* @category mod
* @author Yann Ducruy (yann[dot]ducruy[at]gmail[dot]com). Contact me if needed
* @date 12/06/2015
* @version 3.2
* @license http://www.gnu.org/copyleft/gpl.html GNU Public License
*
*/

global $SITE;
$SITE->project_BACKUP_FIELDS['requirement'] = 'abstract,description,format,strength,heavyness';
$SITE->project_BACKUP_FIELDS['specification'] = 'abstract,description,format,priority,severity,complexity';
$SITE->project_BACKUP_FIELDS['task'] = 'owner,assignee,abstract,description,format,worktype,status,costrate,planned,quoted,done,used,spent,risk,,milestoneid,taskstartenable,taskstart,taskendenable,taskend';
$SITE->project_BACKUP_FIELDS['milestone'] = 'abstract,description,format,deadline,deadlineenable';
$SITE->project_BACKUP_FIELDS['deliverable'] = 'abstract,description,format,status,milestoneid,localfile,url';

// used in restorelib.php for restoring associations.
$SITE->project_ASSOC_TABLES['specid'] = 'project_specification';
$SITE->project_ASSOC_TABLES['reqid'] = 'project_requirement';
$SITE->project_ASSOC_TABLES['delivid'] = 'project_deliverable';
$SITE->project_ASSOC_TABLES['taskid'] = 'project_task';
$SITE->project_ASSOC_TABLES['master'] = 'project_task';
$SITE->project_ASSOC_TABLES['slave'] = 'project_task';

if (!function_exists('backup_get_new_id')){

    /**
    * an utility function for cleaning restorelib.php code
    * @param restore the restore info structure
    * @return the new integer id
    */
    function backup_get_new_id($restorecode, $tablename, $oldid){
        $status = backup_getid($restorecode, $tablename, $oldid);
        if (is_object($status))
            return $status->new_id;
        return 0;
    }
}
?>