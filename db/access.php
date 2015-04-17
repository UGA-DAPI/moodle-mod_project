<?php
//
// Capability definitions for the project module.
//
// The capabilities are loaded into the database table when the module is
// installed or updated. Whenever the capability definitions are updated,
// the module version number should be bumped up.
//
// The system has four possible values for a capability:
// CAP_ALLOW, CAP_PREVENT, CAP_PROHIBIT, and inherit (not set).
//
//
// CAPABILITY NAMING CONVENTION
//
// It is important that capability names are unique. The naming convention
// for capabilities that are specific to modules and blocks is as follows:
//   [mod/block]/<component_name>:<capabilityname>
//
// component_name should be the same as the directory name of the mod or block.
//
// Core moodle capabilities are defined thus:
//    moodle/<capabilityclass>:<capabilityname>
//
// Examples: mod/forum:viewpost
//           block/recent_activity:view
//           moodle/site:deleteuser
//
// The variable name for the capability definitions array follows the format
//   $<componenttype>_<component_name>_capabilities
//
// For the core capabilities, the variable is $moodle_capabilities.

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

$capabilities = array(
    //créer un projet
	'mod/project:addinstance' => array(
		'captype' => 'write',
		'contextlevel' => CONTEXT_MODULE,   // pourquoi pas CONTEXT_COURSE ???
		'legacy' => array(
			'editingteacher' => CAP_ALLOW,
			'manager' => CAP_ALLOW
			)
		),
	//voir le projet, ainsi que son avancement
	'mod/project:view' => array(
		'captype' => 'write',
		'contextlevel' => CONTEXT_MODULE,   // pourquoi pas CONTEXT_COURSE ???
		'legacy' => array(
			'editingteacher' => CAP_ALLOW,
			'teacher' => CAP_ALLOW,
			'student' => CAP_ALLOW,
			'manager' => CAP_ALLOW
			)
		),
    //   ||creer un/rendre le||   projet duplicable ? => a voir et eventuellement a changer
	'mod/project:addtypeinstance' => array(
		'captype' => 'write',
		'contextlevel' => CONTEXT_MODULE,
		'legacy' => array(
			'editingteacher' => CAP_ALLOW,
			'manager' => CAP_ALLOW
			)
		),

	'mod/project:changemiles' => array(
		'captype' => 'write',
		'contextlevel' => CONTEXT_MODULE,
		'legacy' => array(
			'student' => CAP_ALLOW,
			'teacher' => CAP_ALLOW,
			'editingteacher' => CAP_ALLOW,
			'manager' => CAP_ALLOW
			)
		),
    //edition d'un livrable
	'mod/project:editdeliverables' => array(
		'captype' => 'write',
		'contextlevel' => CONTEXT_MODULE,
		'legacy' => array(
			'editingteacher' => CAP_ALLOW,
			'manager' => CAP_ALLOW
			)
		),
    //dition d'une ressource
	'mod/project:editressouces' => array(
		'captype' => 'write',
		'contextlevel' => CONTEXT_MODULE,
		'legacy' => array(
			'editingteacher' => CAP_ALLOW,
			'manager' => CAP_ALLOW
			)
		),
    //valider une etape
	'mod/project:validatemilestone' => array(
		'captype' => 'write',
		'contextlevel' => CONTEXT_MODULE,
		'legacy' => array(
			'student' => CAP_PROHIBIT,
			'teacher' => CAP_ALLOW,
			'editingteacher' => CAP_ALLOW,
			'manager' => CAP_ALLOW
			)
		),
    //demander la validation d'une étape
	'mod/project:askvalidatemilestone' => array(
		'captype' => 'write',
		'contextlevel' => CONTEXT_MODULE,
		'legacy' => array(
			'student' => CAP_ALLOW,
			'teacher' => CAP_PROHIBIT,
			'editingteacher' => CAP_PROHIBIT,
			'manager' => CAP_PROHIBIT
			)
		),
	//messagerie
	'mod/project:editdiscussion' => array(
		'captype' => 'write',
		'contextlevel' => CONTEXT_MODULE,
		'legacy' => array(
			'teacher' => CAP_ALLOW,
			'editingteacher' => CAP_ALLOW,
			'student' => CAP_ALLOW,
			'manager' => CAP_ALLOW
			)
		)
	//archiver un projet
	'mod/project:archive' => array(
		'captype' => 'write',
		'contextlevel' => CONTEXT_MODULE,
		'legacy' => array(
			'editingteacher' => CAP_ALLOW,
			'manager' => CAP_ALLOW
			)
		)
	//teecharger un livrable. interdit aux techers -> tuteurs, dans le cadre de la confidentialité ??
	'mod/project:downloaddeliverable' => array(
		'captype' => 'write',
		'contextlevel' => CONTEXT_MODULE,
		'legacy' => array(
			'student' => CAP_ALLOW,
			'editingteacher' => CAP_ALLOW,
			'teacher' => CAP_PREVENT,
			'manager' => CAP_ALLOW
			)
		)
	// etre noté. pourquoi les editingteacher peuvent etre notés ??
	'mod/project:becomennoted' => array(
		'captype' => 'write',
		'contextlevel' => CONTEXT_MODULE,
		'legacy' => array(
			'student' => CAP_ALLOW,
			'editingteacher' => CAP_ALLOW,
			'manager' => CAP_ALLOW
			)
		)
	//ajouter une etape au projet 
	'mod/project:addmilestone' => array(
		'captype' => 'write',
		'contextlevel' => CONTEXT_MODULE,
		'legacy' => array(
			'editingteacher' => CAP_ALLOW,
			'manager' => CAP_ALLOW
			)
		)

	);


	?>
