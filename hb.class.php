<?php
class HageveldBot {
	public function __construct()
	{
		require ('config.php');
		require ('vendor/autoload.php');

		$this->conn1 = mysqli_connect($this->db1["host"], $this->db1["username"], $this->db1["password"], $this->db1["database"]);
		$this->conn2 = mysqli_connect($this->db2["host"], $this->db2["username"], $this->db2["password"], $this->db2["database"]);
		$this->ig = new \InstagramAPI\Instagram($this->insta["debug"]);
		$this->ig->setUser($this->insta["username"], $this->insta["password"]);
		$this->ig->login();
	}

	public function sendMessage($pk, $message)
	{
		$pk = mysqli_real_escape_string($this->conn1, $pk);
		$bericht = mysqli_real_escape_string($this->conn1, $bericht);
		mysqli_query($this->conn1, "INSERT INTO insta_m VALUES ('','$pk','$bericht','" . time() . "','send','false')");
	}

	public function receiveMessage($pk, $message)
	{
		$pk = mysqli_real_escape_string($this->conn1, $pk);
		$bericht = mysqli_real_escape_string($this->conn1, $bericht);
		mysqli_query($this->conn1, "INSERT INTO insta_m VALUES ('','$pk','$bericht','" . time() . "','receive','true')");
	}

	public function checkFollowers()
	{
		$followers = [];
		$maxId = null;
		do {
			$response = $this->ig->getSelfUserFollowers($maxId);
			$followers = array_merge($followers, $response->getUsers());
			$maxId = $response->getNextMaxId();
		}

		while ($maxId !== null); 
		foreach($followers as $follower) {
			$username = mysqli_real_escape_string($this->conn1, $follower->getUsername());
			$image = mysqli_real_escape_string($this->conn1, $follower->getProfilePicUrl());
			$fullname = mysqli_real_escape_string($this->conn1, $follower->getFullName());
			if (strpos($fullname, ' ') !== false) {
				$voornaam = explode(' ', $fullname) [0];
				$voornaam = ucfirst(strtolower($voornaam));
			}
			else {
				$voornaam = $username;
			}

			$pk = mysqli_real_escape_string($this->conn1, $follower->getPk());
			if (mysqli_num_rows(mysqli_query($this->conn1, "SELECT * FROM insta WHERE pk='$pk'")) == 0) {
				$this->sendMessage($follower->getPk(),str_replace("{name}",$voornaam,$this->response["intro"]));
				mysqli_query($this->conn1, "INSERT INTO insta VALUES ('','$username','$image','$fullname','$pk','','','','" . time() . "')");
			}
		}
	}

	public function checkInbox()
	{
		$messages = [];
		$maxId = null;
		do {
			$response = $this->ig->getV2Inbox($maxId);
			$followers = array_merge($messages, $response->fullResponse->inbox);
			$maxId = $response->getNextMaxId();
		}

		while ($maxId !== null); 
			foreach($messages->threads as $thread) {
				foreach($thread->items as $item) {
					$userid = mysqli_real_escape_string($this->conn1, $item->user_id);
					$type = mysqli_real_escape_string($this->conn1, $item->item_type);
					$text = mysqli_real_escape_string($this->conn1, $item->text);
					if ($userid != $this->ig->getCurrentUser()->user->pk && $type == "text") {
						$this->receiveMessage($userid,$text);
						if (mysqli_num_rows(mysqli_query($this->conn1, "SELECT * FROM insta WHERE pk='$userid' AND klas=''")) > 0) {
							if (ctype_digit($text)) {
								$query = mysqli_query($this->conn2, "SELECT * FROM personen2 WHERE Leerlingnummer='$text'");
								if (mysqli_num_rows($query) == 1) {
									$info = mysqli_fetch_assoc($query);
									$klas = strtoupper(mysqli_real_escape_string($this->conn2, $info['Klas']));
									$realname = mysqli_real_escape_string($this->conn2, $info['namechange']);
									mysqli_query($this->conn1, "UPDATE insta SET klas='$klas',realname='$realname',lln='$text' WHERE pk='$userid'");
									$this->sendMessage($userid,str_replace("{klas}",$klas,$this->response["llnsuccess"]));
								}
								else {
									$this->sendMessage($userid,str_replace("{text}",$text,$this->response["llnfail1"]));
								}
							}
							else {
								$this->sendMessage($userid,str_replace("{text}",$text,$this->response["llnfail2"]));
							}
						}
					}
				}
			}
	}
	
	public function roosterUpdate($klas,$bericht) {
		$klas = mysqli_real_escape_string($this->conn1,$klas);
		$bericht = mysqli_real_escape_string($this->conn1,$bericht);
		$query = mysqli_query($this->conn1,"SELECT * FROM insta WHERE klas LIKE '%$klas%'");
		while($row = mysqli_fetch_assoc($query)) {
			$this->sendMessage($row['pk'],$bericht);
		}
	}

	public function pollMessages()
	{
		$query = mysqli_query($this->conn1, "SELECT * FROM insta_m WHERE type='send' AND done='false'");
		while ($row = mysqli_fetch_assoc($query)) {
			sleep(5);
			$this->ig->directMessage($row['userid'], $row['text']);
			mysqli_query($this->conn1, "UPDATE insta_m SET done='" . time() . "' WHERE ID='$row[ID]'");
		}
	}


	public function close()
	{
		mysqli_close($this->conn1);
		mysqli_close($this->conn2);
	}
}
?>