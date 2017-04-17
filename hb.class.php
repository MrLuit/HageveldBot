<?php
class HageveldBot {
    public function __construct()
    {
        require ('config.php');
        require ('vendor/autoload.php');

        $this->conn = mysqli_connect($this->db["host"], $this->db["username"], $this->db["password"], $this->db["database"]);
        $this->ig = new \InstagramAPI\Instagram($this->insta["debug"]);
        $this->ig->setUser($this->insta["username"], $this->insta["password"]);
        $this->ig->login();
    }

    public function sendMessage($pk, $bericht)
    {
		if (mysqli_num_rows(mysqli_query($this->conn, "SELECT * FROM insta WHERE pk='$userid' AND active='true' AND banned='false'")) > 0) {
			$pk = mysqli_real_escape_string($this->conn, $pk);
			$bericht = mysqli_real_escape_string($this->conn, $bericht);
			mysqli_query($this->conn, "INSERT INTO insta_m VALUES ('','$pk','$bericht','" . time() . "','send','false')");
		}
    }

    public function receiveMessage($pk, $bericht)
    {
        /*$pk = mysqli_real_escape_string($this->conn, $pk);
        $bericht = mysqli_real_escape_string($this->conn, $bericht);
        if(mysqli_num_rows(mysqli_query($this->conn,"SELECT * FROM insta_m WHERE text='$bericht' AND userid='$pk'")) == 0) {
            mysqli_query($this->conn, "INSERT INTO insta_m VALUES ('','$pk','$bericht','" . time() . "','receive','true')");
        }*/
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
            $username = mysqli_real_escape_string($this->conn, $follower->getUsername());
            $image = mysqli_real_escape_string($this->conn, $follower->getProfilePicUrl());
            $fullname = mysqli_real_escape_string($this->conn, $follower->getFullName());
            if (strpos($fullname, ' ') !== false) {
                $voornaam = explode(' ', $fullname) [0];
                $voornaam = ucfirst(strtolower($voornaam));
            }
			elseif($fullname != "" && $fullname != false) {
				$voornaam = $fullname;
			}
            else {
                $voornaam = $username;
            }

            $pk = mysqli_real_escape_string($this->conn, $follower->getPk());
			$user = mysqli_query($this->conn, "SELECT * FROM insta WHERE pk='$pk'");
			$userinfo = mysqli_fetch_assoc($user);
            if (mysqli_num_rows($user) == 0) {
                $this->sendMessage($follower->getPk(),str_replace("{name}",$voornaam,$this->response["intro"]));
                mysqli_query($this->conn, "INSERT INTO insta VALUES ('$pk','$username','$image','$fullname','','','','" . time() . "','true','false','','','','')");
            }
			elseif($userinfo['active'] == "false") {
				mysqli_query($this->conn, "UPDATE insta SET active='true' WHERE pk='$userinfo[pk]'");
			}
        }
    }
	
	public function updateFollowers() {
		$query = mysqli_query($this->conn,"SELECT * FROM insta");
		while($row = mysqli_fetch_assoc($query)) {
			$user = $this->ig->getUserInfoById($row['pk'])->user;
			$media = $user->media_count;
			$followers = $user->follower_count;
			$following = $user->following_count;
			$bio = mysqli_real_escape_string($this->conn, $user->biography);
			$fullname = mysqli_real_escape_string($this->conn, $user->full_name);
			$username = $user->username;
			if(count($user->hd_profile_pic_versions) > 0) {
				foreach($user->hd_profile_pic_versions as $pf) {
					$img = $pf->url;
				}
			}
			else {
				$img = $user->profile_pic_url;
			}
			mysqli_query($this->conn,"UPDATE insta SET media='$media',followers='$followers',following='$following',bio='$bio',fullname='$fullname',username='$username',image='$img' WHERE pk='$row[pk]'");
			if($this->ig->getUserFriendship($row['pk'])->followed_by != "1") {
				mysqli_query($this->conn,"UPDATE insta SET active='false' WHERE pk='$row[pk]'");
			}
		}
	}

    public function checkInbox()
    {
            $messages = $this->ig->getV2Inbox()->fullResponse->inbox;
            foreach($messages->threads as $thread) {
                foreach($thread->items as $item) {
                    $userid = mysqli_real_escape_string($this->conn, $item->user_id);
                    $type = mysqli_real_escape_string($this->conn, $item->item_type);
                    $text = mysqli_real_escape_string($this->conn, $item->text);
                    if ($userid != $this->ig->getCurrentUser()->user->pk && $type == "text") {
                        $this->receiveMessage($userid,$text);
                        if (mysqli_num_rows(mysqli_query($this->conn, "SELECT * FROM insta WHERE pk='$userid' AND klas='' AND active='true' AND banned='false'")) > 0) {
                            if (ctype_digit($text)) {
                                $query = mysqli_query($this->conn, "SELECT * FROM hageveld WHERE Leerlingnummer='$text'");
                                if (mysqli_num_rows($query) == 1) {
                                    $info = mysqli_fetch_assoc($query);
                                    $klas = strtoupper(mysqli_real_escape_string($this->conn, $info['Klas']));
                                    $realname = mysqli_real_escape_string($this->conn, $info['Naam']);
                                    mysqli_query($this->conn, "UPDATE insta SET klas='$klas',realname='$realname',lln='$text' WHERE pk='$userid'");
                                    $this->sendMessage($userid,str_replace("{klas}",$klas,$this->response["llnsuccess"]));
									$this->sendMessage($userid,$this->response["warning"]);
                                }
                                else {
                                    $this->sendMessage($userid,str_replace("{text}",$text,$this->response["llnfail1"]));
                                }
                            }
                            else {
                                $this->sendMessage($userid,str_replace("{text}",$text,$this->response["llnfail2"]));
                            }
                        }
						elseif(mysqli_num_rows(mysqli_query($this->conn, "SELECT * FROM insta WHERE pk='$userid' AND banned='false'")) > 0) {
							if($this->parse($text,'[["+wie","+ben","+jij"],["+wie","+ben","+je"]]')) {
								$this->sendMessage($userid,$this->response["info"]);
							}
							elseif($this->parse($text,'[["+wie heeft","+gemaakt"],["+wie is","+maker"]]')) {
								$this->sendMessage($userid,$this->response["maker"]);
							}
						}
                    }
                }
            }
    }
    
    public function roosterUpdate($klas,$bericht) {
        $klas = mysqli_real_escape_string($this->conn,$klas);
        $bericht = mysqli_real_escape_string($this->conn,$bericht);
        $query = mysqli_query($this->conn,"SELECT * FROM insta WHERE klas LIKE '%$klas%' AND active='true'");
        while($row = mysqli_fetch_assoc($query)) {
            $this->sendMessage($row['pk'],$bericht);
        }
    }

    public function pollMessages()
    {
        $query = mysqli_query($this->conn, "SELECT * FROM insta_m WHERE type='send' AND done='false'");
        while ($row = mysqli_fetch_assoc($query)) {
            sleep(5);
            $this->ig->directMessage($row['userid'], $row['text']);
            mysqli_query($this->conn, "UPDATE insta_m SET done='" . time() . "' WHERE ID='$row[ID]'");
        }
    }


    public function close()
    {
        mysqli_close($this->conn);
    }
    
    public function parse($text,$json) {
        $array = json_decode($json,true);
        if(is_array($array)) {
            foreach($array as $doublearray) {
                if(is_array($doublearray)) {
                    foreach($doublearray as $var) {
                        if(str_split($var)[0] == "-") {
                            if(stripos($text,substr($var,1)) !== false) {
                                $dontexec = true;
                                break;
                            }
                        }
                        else {
                            if(str_split($var)[0] == "+") {
                                $var = substr($var,1);
                            }
                            if(stripos($text,$var) === false) {
                                $dontexec = true;
                                break;
                            }
                        }
                    }
                    if(!isset($dontexec)) { 
                        return true;
                    }
                }
                else {
                    return false;
                }
                unset($dontexec);
            }
        }
        else {
            return false;
        }
    }
}
?>