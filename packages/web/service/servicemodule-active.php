<?php
require('../commons/base.inc.php');
try
{
	$HostManager = new HostManager();
	$MACs = HostManager::parseMacList($_REQUEST['mac']);
	if (!$MACs)
		throw new Exception('#!im');
	// Get the Host
	$Host = $HostManager->getHostByMacAddresses($MACs);
	if (!$Host->isValid())
		throw new Exception('#!er:No Host Found');
	// get the module id
	if (empty($_REQUEST['moduleid']))
		throw new Exception('#!um');
	// Associate the moduleid param with the global name.
	$moduleName = array(
		'dircleanup' => 'FOG_SERVICE_DIRECTORYCLEANER_ENABLED',
		'usercleanup' => 'FOG_SERVICE_USERCLEANUP_ENABLED',
		'displaymanager' => 'FOG_SERVICE_DISPLAYMANAGER_ENABLED',
		'autologout' => 'FOG_SERVICE_AUTOLOGOFF_ENABLED',
		'greenfog' => 'FOG_SERVICE_GREENFOG_ENABLED',
		'hostnamechanger' => 'FOG_SERVICE_HOSTNAMECHANGER_ENABLED',
		'snapin' => 'FOG_SERVICE_SNAPIN_ENABLED',
		'clientupdater' => 'FOG_SERVICE_CLIENTUPDATER_ENABLED',
		'hostregister' => 'FOG_SERVICE_HOSTREGISTER_ENABLED',
		'printermanager' => 'FOG_SERVICE_PRINTERMANAGER_ENABLED',
		'taskreboot' => 'FOG_SERVICE_TASKREBOOT_ENABLED',
		'usertracker' => 'FOG_SERVICE_USERTRACKER_ENABLED',
	);
	// Get the module status for the host level
	$moduleID = current($FOGCore->getClass('ModuleManager')->find(array('shortName' => $_REQUEST['moduleid'])));
	// If it's globally disabled, return that so the client doesn't keep trying it.
	if ($FOGCore->getSetting($moduleName[$_REQUEST['moduleid']]) == 1)
	{
		foreach((array)$Host->get('modules') AS $Module)
		{
			if (($Module && $Module->isValid()) && ($moduleID && $moduleID->isValid()))
			{
				if ($Module->get('id') == $moduleID->get('id'))
				{
					$modState = current($FOGCore->getClass('ModuleAssociationManager')->find(array('moduleID' => $Module->get('id'))));
					print (($modState && $modState->isValid()) && $modState->get('state') ? '#!ok' : '#!nh');
				}
			}
			if ((!$Module || !$Module->isValid()) || (!$moduleID || !$moduleID->isValid()))
				print '#!nh';
		}
	}
	else
		throw new Exception('#!ng');
}
catch(Exception $e)
{
	print $e->getMessage();
}
