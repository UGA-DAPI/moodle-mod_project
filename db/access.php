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
    //create & edit a project. also used for criteria(for whatever reason)
	'mod/project:addinstance' => array(
		'captype' => 'write',
		'contextlevel' => CONTEXT_COURSE,
		'legacy' => array(
			'editingteacher' => CAP_ALLOW,
			'manager' => CAP_ALLOW
			)
		),
	//voir le projet, ainsi que son avancement
	'mod/project:view' => array(
		'captype' => 'write',
		'contextlevel' => CONTEXT_COURSE,
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
	// ajouter / editer les étapes
	'mod/project:changemilestone' => array(
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
    //edition d'une ressource
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
	'mod/project:communicate' => array(
		'captype' => 'write',
		'contextlevel' => CONTEXT_MODULE,
		'legacy' => array(
			'teacher' => CAP_ALLOW,
			'editingteacher' => CAP_ALLOW,
			'student' => CAP_ALLOW,
			'manager' => CAP_ALLOW
			)
		),
	//archiver un projet
	'mod/project:archive' => array(
		'captype' => 'write',
		'contextlevel' => CONTEXT_MODULE,
		'legacy' => array(
			'editingteacher' => CAP_ALLOW,
			'manager' => CAP_ALLOW
			)
		),
	//telecharger un livrable. interdit aux teachers (=tuteurs) dans le cadre de la confidentialité ??
	'mod/project:downloaddeliverable' => array(
		'captype' => 'write',
		'contextlevel' => CONTEXT_MODULE,
		'legacy' => array(
			'student' => CAP_ALLOW,
			'editingteacher' => CAP_ALLOW,
			'teacher' => CAP_PREVENT,
			'manager' => CAP_ALLOW
			)
		),
	// etre noté. pourquoi les editingteacher peuvent etre notés ??
	'mod/project:becomennoted' => array(
		'captype' => 'write',
		'contextlevel' => CONTEXT_MODULE,
		'legacy' => array(
			'student' => CAP_ALLOW,
			'editingteacher' => CAP_ALLOW,
			'manager' => CAP_ALLOW
			)
		),

	
	//noter
	'mod/project:note' => array(
		'captype' => 'write',
		'contextlevel' => CONTEXT_MODULE,
		'legacy' => array(
			'editingteacher' => CAP_ALLOW,
			'manager' => CAP_ALLOW
			)
		),

	

	//gestion du cvs. n'est pas implementé pour l'instant
	'mod/project:cvs' => array(
		'captype' => 'write',
		'contextlevel' => CONTEXT_MODULE,
		'legacy' => array(
			'editingteacher' => CAP_ALLOW,
			'manager' => CAP_ALLOW
			)
		),

	//gestion des criteres.
	'mod/project:criteria' => array(
		'captype' => 'write',
		'contextlevel' => CONTEXT_MODULE,
		'legacy' => array(
			'editingteacher' => CAP_ALLOW,
			'manager' => CAP_ALLOW
			)
		),

	//gestion des import/export CSS XSL .
	'mod/project:imports' => array(
		'captype' => 'write',
		'contextlevel' => CONTEXT_MODULE,
		'legacy' => array(
			'editingteacher' => CAP_ALLOW,
			'manager' => CAP_ALLOW
			)
		),

	//changer les taches, et a qui elle sont assignées. pas demandé dans le cahier des charges
	'mod/project:changetasks' => array(
		'captype' => 'write',
		'contextlevel' => CONTEXT_MODULE,
		'legacy' => array(
			'editingteacher' => CAP_ALLOW,
			'student' => CAP_ALLOW,
			'manager' => CAP_ALLOW
			)
		),

	//todo ? seem it's used to display the ful name if you have the right ... i'll add this to everyone by default
	'mod/project:viewfullnames' => array(
		'captype' => 'write',
		'contextlevel' => CONTEXT_MODULE,
		'legacy' => array(
			'editingteacher' => CAP_ALLOW,
			'teacher' => CAP_ALLOW,
			'student' => CAP_ALLOW,
			'manager' => CAP_ALLOW
			)
		),

	//gestion de la copie. inutilisé ?
	'mod/project:copy' => array(
		'captype' => 'write',
		'contextlevel' => CONTEXT_MODULE,
		'legacy' => array(
			'editingteacher' => CAP_ALLOW,
			'manager' => CAP_ALLOW
			)
		)

	);


	?>
