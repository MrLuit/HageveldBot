<?php
class HageveldBot {
    public function __construct() {
        if($this->keepRunning()) {
            require ('config.php');
            require ('vendor/autoload.php');
            $this->conn = mysqli_connect($this->db["host"], $this->db["username"], $this->db["password"], $this->db["database"]);
            $this->ig = new \InstagramAPI\Instagram($this->insta["debug"],$this->insta["truncateddebug"], [
                "storage"      => "mysql",
                "dbhost"       => $this->db["host"],
                "dbname"   => $this->db["database"],
                "dbusername"   => $this->db["username"],
                "dbpassword"   => $this->db["password"],
            ]);
            $this->ig->setUser($this->insta["username"], $this->insta["password"]);
            $this->ig->login();
        }
    }

    public function naam($array) {
        foreach($array as $naam) {
            //$naam = preg_replace('/^[a-z0-9 .\-]+/', '', $naam);
            if($naam != "" && $naam != " ") {
                if(strpos($naam, ' ') !== false) {
                    $naam = explode(' ', $naam)[0];
                    $naam = ucfirst(strtolower($naam));
                }
                return $naam;
            }
        }
    }
    
    public function keepRunning() {
        if(file_get_contents('kill.txt') == "false") {
            return true;
        }
        else {
            return false;
        }
    }

    public function uploadStory($url,$caption) {
        $this->ig->uploadStoryPhoto($url, ['caption' => $caption]);
    }

    public function sendMessage($pk, $bericht) {
        if (mysqli_num_rows(mysqli_query($this->conn, "SELECT * FROM insta WHERE pk='$pk' AND active='true' AND banned='false'")) > 0 && $this->keepRunning()) {
            $pk = mysqli_real_escape_string($this->conn, $pk);
            $bericht = mysqli_real_escape_string($this->conn, $bericht);
            mysqli_query($this->conn, "INSERT INTO insta_m VALUES ('','$pk','$bericht','" . time() . "','send','false')");
        }
    }
    
    public function checkFollowers() {
        $followers = [];
        $maxId = null;
        do {
            $response = $this->ig->getSelfUserFollowers($maxId);
            $followers = array_merge($followers, $response->getUsers());
            $maxId = $response->getNextMaxId();
        }

        while ($maxId !== null); 
        foreach($followers as $follower) {
            if($this->keepRunning()) {
                $pk = mysqli_real_escape_string($this->conn, $follower->getPk());
                $user = mysqli_query($this->conn, "SELECT * FROM insta WHERE pk='$pk'");
                $userinfo = mysqli_fetch_assoc($user);
                if (mysqli_num_rows($user) == 0) {
                    $username = mysqli_real_escape_string($this->conn, $follower->getUsername());
                    $fullname = mysqli_real_escape_string($this->conn, $follower->getFullName());
                    mysqli_query($this->conn, "INSERT INTO insta VALUES ('$pk','$username','','$fullname','','','','" . time() . "','true','false','','','','','0')");
                }
                elseif (mysqli_num_rows($user) > 0 && $userinfo['status'] == "0") {
                    $username = mysqli_real_escape_string($this->conn, $follower->getUsername());
                    $fullname = mysqli_real_escape_string($this->conn, $follower->getFullName());
                    $this->sendMessage($follower->getPk(),str_replace("{name}",$this->naam(array($fullname,$username)),$this->response["intro"]));
                    mysqli_query($this->conn, "UPDATE insta SET status='1' WHERE pk='$pk'");
                }
                mysqli_query($this->conn, "UPDATE insta SET active='true' WHERE pk='$userinfo[pk]'");
            }
        }
    }
    
    public function updateFollowers() {
        $query = mysqli_query($this->conn,"SELECT * FROM insta ORDER BY rand() LIMIT " . $this->limit["updatefollowers"]);
        while(($row = mysqli_fetch_assoc($query)) && $this->keepRunning()) {
            usleep($this->delay["updatefollowers"]);
            try {
                $pk = $row['pk'];
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
                mysqli_query($this->conn,"UPDATE insta SET media='$media',followers='$followers',following='$following',bio='$bio',fullname='$fullname',username='$username',image='$img' WHERE pk='$pk'");
                if($this->ig->getUserFriendship($pk)->followed_by != "1") {
                    mysqli_query($this->conn,"UPDATE insta SET active='false' WHERE pk='$pk'");
                }
            } catch (Exception $e) {
                mysqli_query($this->conn,"UPDATE insta SET active='false' WHERE pk='$pk'");
            }
        }
    }

    public function checkInbox() {
            $messages = $this->ig->getV2Inbox()->fullResponse->inbox;
            foreach($messages->threads as $thread) {
                foreach($thread->items as $item) {
                    if($item->item_type == "text" && $this->keepRunning()) {
                        $userid = mysqli_real_escape_string($this->conn, $item->user_id);
                        $text = mysqli_real_escape_string($this->conn, $item->text);
                        if ($userid != $this->ig->getCurrentUser()->user->pk) {
                            if (mysqli_num_rows(mysqli_query($this->conn, "SELECT * FROM insta WHERE pk='$userid' AND status='1' AND active='true' AND banned='false'")) > 0) {
                                if (ctype_digit($text)) {
                                    $query = mysqli_query($this->conn, "SELECT * FROM hageveld WHERE Leerlingnummer='$text'");
                                    if (mysqli_num_rows($query) == 1) {
                                        $info = mysqli_fetch_assoc($query);
                                        $klas = strtoupper(mysqli_real_escape_string($this->conn, $info['Klas']));
                                        $realname = mysqli_real_escape_string($this->conn, $info['Naam']);
                                        mysqli_query($this->conn, "UPDATE insta SET klas='$klas',realname='$realname',lln='$text',status='2' WHERE pk='$userid'");
                                        $this->sendMessage($userid,str_replace("{klas}",$klas,$this->response["llnsuccess"]));
                                        $this->sendMessage($userid,$this->response["warning"]);
                                    }
                                    elseif(mysqli_num_rows(mysqli_query($this->conn, "SELECT * FROM hageveld WHERE Leerlingnummer='$text' AND VanSchoolAf='true'")) == 1) {
                                        $this->sendMessage($userid,$this->response["llnfail3"]);
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
                    elseif($item->item_type == "like") { }
                    elseif($item->item_type == "media") { }
                    else {
                        error_log("Onbekend media type: " . $item->item_type);
                    }
                }
            }
    }
    
    public function roosterUpdate($klas,$bericht) {
        $klas = mysqli_real_escape_string($this->conn,$klas);
        $bericht = mysqli_real_escape_string($this->conn,$bericht);
        if(strpos($klas,', ') !== false) {
            $klas = explode(', ',$klas);
            foreach($klas as $k) {
                $query = mysqli_query($this->conn,"SELECT * FROM insta WHERE klas LIKE '%$k%' AND active='true' AND banned='false'");
                while($row = mysqli_fetch_assoc($query)) {
                    $this->sendMessage($row['pk'],$bericht);
                }
            }
        }
        elseif(strpos($klas,',') !== false) {
            $klas = explode(',',$klas);
            foreach($klas as $k) {
                $query = mysqli_query($this->conn,"SELECT * FROM insta WHERE klas LIKE '%$k%' AND active='true' AND banned='false'");
                while($row = mysqli_fetch_assoc($query)) {
                    $this->sendMessage($row['pk'],$bericht);
                }
            }
        }
        else {
            $query = mysqli_query($this->conn,"SELECT * FROM insta WHERE klas LIKE '%$klas%' AND active='true' AND banned='false'");
            while($row = mysqli_fetch_assoc($query)) {
                print_r($row);
                $this->sendMessage($row['pk'],$bericht);
            }
        }
    }

    public function pollMessages() {
        $query = mysqli_query($this->conn, "SELECT * FROM insta_m WHERE type='send' AND done='false' LIMIT " . $this->limit["send"]);
        while ($row = mysqli_fetch_assoc($query)) {
            usleep($this->delay["send"]);
            $this->ig->directMessage($row['userid'], $row['text']);
            mysqli_query($this->conn, "UPDATE insta_m SET done='" . time() . "' WHERE ID='$row[ID]'");
        }
    }

    public function close() {
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