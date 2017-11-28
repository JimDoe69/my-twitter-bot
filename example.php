#!/usr/bin/php
<?php
date_default_timezone_set('America/New York');
require('TwitterAutoReply.php');
// Consumer key and consumer secret
$twitter = new TwitterAutoReply('******', '************************');
// Token and secret
$twitter->setToken('886682112820162560-qoz6YcKKIjgJevt9w4rJA2931IUsci9
', 'XFH9xy1vh0qW9OVgJu3BzCt1aQGOybqGr09hYoRUHGGPl');
$twitter->addReply('over 9000', 'WHAT?! NINE THOUSAND?!');
$twitter->addReply('over nine thousand', 'WHAT?! NINE THOUSAND?!');
$twitter->run();
?>
