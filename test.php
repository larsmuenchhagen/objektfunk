<?php
/*
 * Copyright (c) 2021  Lars Münchhagen
 * email: lars.muenchhagen@outlook.de
 */

use system\Database;
const ROOT = __DIR__;
require_once './system/Autoloader.php';

\system\Autoloader::register();

$adr = new \modules\mod_Adressen\Adressen();
$adr->setId(1);
echo var_dump($adr->getAdresse());

$adr->setName('BAB A 111');
$adr->setNummer("<b>Müller's Straße</b>");
$adr->setLat();
$adr->setLng();
$adr->update();
$numb = htmlentities($adr->getNummer(),ENT_HTML5 |ENT_QUOTES, "UTF-8");
echo $numb;
echo "<pre>\n";
echo "Fehler:\n";
print_r($adr->getError());
echo "Variablen der aktuellen Instanz:\n";
print_r($adr->getAdresse());
echo "</pre>\n";

/*$firma = new \modules\mod_firma\Firma();
$firma->setId(2);
$web = "https://www.example.com";
try {
    $result = $firma->setNewWeb($web);
} catch (ErrorException $e) {
    print_r ($e->getMessage());
}*/

/*echo "<pre>";
print_r ('Abfrage erfolgreich: '.$result."\n");
print_r ($firma->getFirma());
print_r ($firma->getError());
echo "</pre>";*/

/*$numbers = [
    "+43 911 6348-24",
    "(030) 86402357",
    "089 4359045",
    "0 22 56 / 4 35 90 45",
    "0030-795-463872",
    "+49 (030) 387 20 w 531"
];*/
# $valid = "/[0-9\+\-\/\(\)\s]*/";
/*
foreach ($numbers as $number) {
    if (preg_match($valid, $number, $match)) {

        echo "<pre>".$number."\n";
        print_r($match);
        if ($number !== $match[0]) echo 'falsch';
        echo "\n</pre>";
    }
}*/