<?php

/**
* This library is a third-party proposal for standardizing mail
* message constitution for third party modules. It is actually used
* by all ethnoinformatique.fr module. It relies on mail and message content
* templates tha should reside in a mail/{$lang}_utf8 directory within the 
* module space.
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

/**
* useful templating functions from an older project of mine, hacked for Moodle
* @param template the template's file name from $CFG->sitedir
* @param infomap a hash containing pairs of parm => data to replace in template
* @return a fully resolved template where all data has been injected
*/
function project_compile_mail_template($template, $infomap, $module = 'project') {
    $notification = implode('', project_get_mail_template($template, $module));
    foreach($infomap as $aKey => $aValue){
        $notification = str_replace("<%%$aKey%%>", $aValue, $notification);
    }
    return $notification;
}

/*
* resolves and get the content of a Mail template, acoording to the user's current language.
* @param virtual the virtual mail template name
* @param module the current module
* @param lang if default language must be overriden
* @return string the template's content or false if no template file is available
*/
function project_get_mail_template($virtual, $modulename, $lang = ''){
    global $CFG;

    if ($lang == '') {
        $lang = $CFG->lang;
    }
    if (preg_match('/^auth_/', $modulename)){
        $location = 'auth';
        $modulename = str_replace('auth_', '', $modulename);
    } elseif (preg_match('/^block_/', $modulename)){
        $location = 'blocks';
        $modulename = str_replace('block_', '', $modulename);
    } else {
        $location = 'mod';
    }
    $templateName = "{$CFG->dirroot}/{$location}/{$modulename}/mails/{$lang}/{$virtual}.tpl";
    if (file_exists($templateName))
        return file($templateName);

    notice("template $templateName not found");
    return array();
}
?>