<?php
require('../commons/base.inc.php');
try
{
	// Error checking
	// NOTE: Most of these validity checks should never fail as checks are made during Task creation - better safe than sorry!
	// MAC Address
	$MACAddress = new MACAddress($_REQUEST['mac']);
	if (!$MACAddress->isValid())
		throw new Exception( _('Invalid MAC address') );
	// Host for MAC Address
	$Host = $MACAddress->getHost();
	if (!$Host->isValid())
		throw new Exception( _('Invalid Host') );
	// Task for Host
	$Task = current($Host->get('task'));
	if (!$Task->isValid())
		throw new Exception( sprintf('%s: %s (%s)', _('No Active Task found for Host'), $Host->get('name'), $MACAddress) );
	// Check-in Host
	if ($Task->get('stateID') == 1)
		$Task->set('stateID', '2')->set('checkInTime', time())->save();
	// Storage Group
	$StorageGroup = $Task->getStorageGroup();
	if (!$StorageGroup->isValid())
		throw new Exception( _('Invalid StorageGroup') );
	// Storage Node
	$StorageNodes = $StorageGroup->getStorageNodes();
	if (!$StorageNodes)
		throw new Exception( _('Could not find a Storage Node. Is there one enabled within this Storage Group?') );
	// Forced to start
	if ($Task->get('isForced'))
	{
		if (!$Task->set('stateID', '3' )->save())
			throw new Exception(_('Forced Task: Failed to update Task'));
	}
	// Queue checks
	$totalSlots = $StorageGroup->getTotalSupportedClients();
	$usedSlots = $StorageGroup->getUsedSlotCount();
	$inFrontOfMe = $Task->getInFrontOfHostCount();
	$groupOpenSlots = $totalSlots - $usedSlots;
	// Fail if all Slots are used
	if (! $Task->get('isForced')) {
	    if ($usedSlots >= $totalSlots)
		    throw new Exception(sprintf('%s, %s %s', _('Waiting for a slot'), $inFrontOfMe, _('PCs are in front of me.')));
	    // At this point we know there are open slots, but are we next in line for that slot (or has the next is line timed out?)
	    if ($groupOpenSlots <= $inFrontOfMe)
		    throw new Exception(sprintf('%s %s %s', _('There are open slots, but I am waiting for'), $inFrontOfMe, _('PCs in front of me.')));
    }
	// Determine the best Storage Node to use - based off amount of clients connected
	
	$messageArray = array();
	$winner = null;
	foreach( $StorageNodes AS $StorageNode ) {
	    if ( $StorageNode->get('maxClients') > 0 ) {
	        if ( $winner == null ) {
	            $winner = $StorageNode;
	        } else if ( $StorageNode->getClientLoad() < $winner->getClientLoad() ) {
	            if ($StorageNode->getNodeFailure($Host) === null)
			    {
                    $winner = $StorageNode;
			    }
			    else
				    $messageArray[] = sprintf("%s '%s' (%s) %s", _('Storage Node'), $StorageNode->get('name'), $StorageNode->get('ip'), _('is open, but has recently failed for this Host'));
	        }
	    }
	}
	// Failed to find a Storage Node - this should only occur if all Storage Nodes in this Storage Group have failed
	if (!isset($winner) || !$winner->isValid())
	{
		// Print failed node messages if we are unable to find a valid node
		if (count($messageArray))
			print implode(PHP_EOL, $messageArray) . PHP_EOL;
		throw new Exception(_("Unable to find a suitable Storage Node for transfer!"));
	}
	// All tests passed! Almost there!
	$Task->set('stateID', '3')
		 ->set('NFSMemberID', $winner->get('id'));
	// Update Task State ID -> Update Storage Node ID -> Save
	if (!$Task->save())
		throw new Exception(_('Failed to update Task'));
	// Success!
	$il = new ImagingLog(array('hostID' => $Host->get('id'),
							   'start' => date('Y-m-d H:i:s'),
							   'image' => $Host->getImage()->get('name'),
							   'type' => $_REQUEST['type'],
							  )
	);
	$il->save();
	// Task Logging.
	$TaskLog = new TaskLog(array(
		'taskID' => $Task->get('id'),
		'taskStateID' => $Task->get('stateID'),
		'createTime' => $Task->get('createTime'),
		'createdBy' => $Task->get('createdBy'),
	));
	$TaskLog->save();
	print '##@GO';
}
catch (Exception $e)
{
	print $e->getMessage();
}
