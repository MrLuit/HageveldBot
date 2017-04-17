<?php
$this->db = array();
$this->insta = array();
$this->response = array();

$this->db["host"] = "";
$this->db["username"] = "";
$this->db["password"] = "";
$this->db["database"] = "";

$this->insta["username"] = "";
$this->insta["password"] = "";
$this->insta["debug"] = false;

$this->response["intro"] = "Hoi, {name}. Voordat ik je berichten kan sturen moet ik eerst weten in welke klas je zit. Antwoord met je leerlingnummer:";
$this->response["llnsuccess"] = "Klas '{klas}' ingesteld. Je krijgt vanaf nu een DM als er een nieuwe roosterwijziging voor jouw klas is.";
$this->response["llnfail1"] = "Leerlingnummer '{text}' staat niet in het systeem. Vul een correct leerlingnummer in zoals: 11111";
$this->response["llnfail2"] = "Leerlingnummer '{text}' wordt niet herkend. Vul een correct leerlingnummer in zoals: 11111";
$this->response["llnfail3"] = "Je leerlingnummer is niet actief. Waarschijnlijk ben je al van school af.";
$this->response["info"] = "Ik ben HageveldBot en ik stuur automatisch meldingen die met Hageveld te maken hebben.";
$this->response["maker"] = "Ik ben gemaakt door @lu1t. https://luithollander.nl";
$this->response["warning"] = "Waarschuwing: Het blijft je eigen verantwoordelijkheid om de informatie te controleren.";