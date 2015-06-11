<?php // $Id: version.php,v 1.2 2012-08-12 22:01:36 vf Exp $

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

/////////////////////////////////////////////////////////////////////////////////
///  Code fragment to define the version of project
///  This fragment is called by moodle_needs_upgrading() and /admin/index.php
/////////////////////////////////////////////////////////////////////////////////

defined('MOODLE_INTERNAL') || die;

$module->version  = 2015060800;  // The current module version (Date: YYYYMMDDXX)
$module->requires = 2012062500;  // Requires this Moodle version
$module->component = 'mod_project';   // Full name of the plugin (used for diagnostics)
$module->cron     = 0;           // Period for cron to check this module (secs)
$module->maturity = MATURITY_BETA;
$module->release = '3.2.0 (Build 2015060800)';

