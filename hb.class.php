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

    public function sendMessage($pk, $bericht)
    {
        $pk = mysqli_real_escape_string($this->conn1, $pk);
        $bericht = mysqli_real_escape_string($this->conn1, $bericht);
        mysqli_query($this->conn1, "INSERT INTO insta_m VALUES ('','$pk','$bericht','" . time() . "','send','false')");
    }

    public function receiveMessage($pk, $bericht)
    {
        $pk = mysqli_real_escape_string($this->conn1, $pk);
        $bericht = mysqli_real_escape_string($this->conn1, $bericht);
        if(mysqli_num_rows(mysqli_query($this->conn1,"SELECT * FROM insta_m WHERE text='$bericht' AND userid='$pk'")) == 0) {
            //mysqli_query($this->conn1, "INSERT INTO insta_m VALUES ('','$pk','$bericht','" . time() . "','receive','true')");
        }
    }

    public function isBanned($pk) {
        $banned = array();
        if(in_array($pk,$banned)) {
            return true;
        }
        else {
            return false;
        }
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
            $messages = $this->ig->getV2Inbox()->fullResponse->inbox;
            foreach($messages->threads as $thread) {
                foreach($thread->items as $item) {
                    $userid = mysqli_real_escape_string($this->conn1, $item->user_id);
                    $type = mysqli_real_escape_string($this->conn1, $item->item_type);
                    $text = mysqli_real_escape_string($this->conn1, $item->text);
                    if ($userid != $this->ig->getCurrentUser()->user->pk && $type == "text" && !$this->isBanned($userid)) {
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
                        elseif($this->parse($text,'[["+wie","+ben","+jij"],["+wie","+ben","+je"]]')) {
                            $this->sendMessage($userid,$this->response["info"]);
                        }
                        elseif($this->parse($text,'[["+wie heeft","+gemaakt"],["+wie is","+maker"]]')) {
                            $this->sendMessage($userid,$this->response["maker"]);
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