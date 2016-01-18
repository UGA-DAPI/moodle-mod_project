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

$plugin->version  = 2015061902;  // The current plugin version (Date: YYYYMMDDXX)
$plugin->requires = 2012062500;  // Requires this Moodle version
$plugin->component = 'mod_project';   // Full name of the plugin (used for diagnostics)
$plugin->cron     = 0;           // Period for cron to check this plugin (secs)
$plugin->maturity = MATURITY_BETA;
$plugin->release = '3.2.0 (Build 2015061902)';

