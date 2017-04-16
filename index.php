<?php
require('hb.class.php');
$hb = new HageveldBot;
if(isset($_GET['rooster'])) {
	$hb->roosterUpdate($_GET['klas'],$_GET['bericht']);
}
else {
	$hb->checkFollowers();
	$hb->checkInbox();
	$hb->pollMessages();
}
$hb->close();