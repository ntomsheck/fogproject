<?php
header("Content-type: text/plain");
require_once('../../commons/base.inc.php');
print "#!ipxe\n";
print "console\n";
print "set fog-ip ".$FOGCore->getSetting('FOG_WEB_HOST')."\n";
print "set fog-webroot ".basename($FOGCore->getSetting('FOG_WEB_ROOT'))."\n";
print "cpair --foreground 7 --background 2 2\n";
print "console --picture http://\${fog-ip}/\${fog-webroot}/service/ipxe/bg.png --left 100 --right 80\n";
print $FOGCore->getSetting('FOG_PXE_ADVANCED');
