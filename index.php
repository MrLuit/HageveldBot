<?php
set_time_limit(0);
try {
	require('hb.class.php');
	$hb = new HageveldBot;
	if(isset($_GET['rooster'])) {
		$hb->roosterUpdate($_GET['klas'],$_GET['bericht']);
	}
	elseif(isset($_GET['update'])) {
		$hb->updateFollowers();
	}
	else {
		$hb->checkFollowers();
		$hb->checkInbox();
		$hb->pollMessages();
	}
	$hb->close();
} catch(Exception $e) {
	print_r($e);
	error_log(json_encode($e));
}