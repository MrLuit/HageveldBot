<?php
$this->db1 = array();
$this->db2 = array();
$this->insta = array();
$this->response = array();

$this->db1["host"] = "";
$this->db1["username"] = "";
$this->db1["password"] = "";
$this->db1["database"] = "";

$this->db2["host"] = "";
$this->db2["username"] = "";
$this->db2["password"] = "";
$this->db2["database"] = "";

$this->insta["username"] = "";
$this->insta["password"] = "";
$this->insta["debug"] = false;

$this->response["intro"] = "Hoi, {name}. Voordat ik je berichten kan sturen moet ik eerst weten in welke klas je zit. Antwoord met je leerlingnummer:";
$this->response["llnsuccess"] = "Klas '{klas}' ingesteld. Je krijgt vanaf nu een DM als er een nieuwe roosterwijziging voor jouw klas is.";
$this->response["llnfail1"] = "Leerlingnummer '{text}' staat niet in het systeem. Vul een correct leerlingnummer in zoals: 11111";
$this->response["llnfail2"] = "Leerlingnummer '{text}' wordt niet herkend. Vul een correct leerlingnummer in zoals: 11111";