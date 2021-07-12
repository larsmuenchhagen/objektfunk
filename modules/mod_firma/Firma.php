<?php
/**
 * Klasse zur Verwaltung der Kontakte zu Fremdfirmen
 * @author Lars Münchhagen <lars.muenchhagen@outlook.de>
 * @version 0.0.1
 *
 */


namespace modules\mod_firma;


/*--------------------TODO AND FIX----------*/
# TODO Email validieren
# TODO Telefon validieren
# FIX http prefix web
/*--------------------REQUIREMENTS----------*/

use ErrorException;
use Exception;
use system\Database;

class Firma
{

    private int     $id         = 0;
    private string  $name       = '';
    private string  $mail       = '';
    private string  $telefon    = '';
    private string  $web        = '';
    private array   $firma      = [];
    private array   $error      = [];


    /*--------------------PUBLIC----------------*/
    /**
     * Methode zum Aktualisieren der Firmendaten
     * Die neuen Daten müssen vorher über die jeweiligen
     * set-Methoden gesetzt werden.
     * Die Aktualisierung erfolgt als Paket.
     * @return bool
     * @throws ErrorException
     */
    public function updateFirmaDaten():bool{
        return $this->updateFirma();
    }

    /*--------------------SETTER----------------*/
    /**
     * Setze die Firmen-ID für die Instanz
     * Prüft ob $id eine Zahl ungleich 0 ist
     * @param int $id
     * @throws ErrorException
     */
    public function setId(int $id = 0): void
    {
        if (is_numeric($id) AND $id !== 0) $this->leseFirmaNachId($id);

    }
    /**
     * Setze den Firmennamen für die Instanz
     * @param string $name
     * @throws ErrorException
     */
    public function setName(string $name = ''):void{
        if ($name !== ''){
            $this->leseFirmaNachName($name);
        }
    }
    /**
     * Setzt einen neuen Firmennamen für die aktuelle Instanz
     * Die Methode überprüft die Eingabe, ob ein Name angegeben und
     * ob der Name bereits vergeben ist.
     * @param string $name
     * @return bool
     * @throws ErrorException
     */
    public function setNewName(string $name = ''): bool
    {
        if ($this->id == 0){
            $this->error[] = 'Keine Firma ausgewählt!';
            return false;
        }
        if ($name == ''){
            $this->error[] = 'Der Name darf nicht leer sein!';
            return false;
        }else{
            $sql = 'SELECT COUNT(*) AS anzahl FROM firmen WHERE UPPER(name) = UPPER(:name)';
            $sqlArgs = array('name'=>$name);
            $abfrage = Database::readFromDatabase($sql,$sqlArgs);

            if ($abfrage[0]['anzahl']){
                $this->error[] = 'Der Name existiert bereits!';
                return false;
            }else{

                $this->name = $name;
                $result = $this->updateFirma();
                if ($result){
                    $this->leseFirmaNachId($this->id);
                    return true;
                }else{
                    return false;
                }
            }
        }
    }
    /**
     * Setzt eine neue Mailadresse für die aktuelle Instanz
     * @param string $mail
     * @return bool
     * @throws ErrorException
     */
    public function setNewMail(string $mail = ''): bool
    {
        if ($this->id == 0){
            $this->error[] = 'Keine Firma ausgewählt!';
            return false;
        }
        if ($mail == ''){
            $this->error[] = 'Keine Mailadresse angegeben!';
            return false;
        }else{

            if(!filter_var($mail,FILTER_VALIDATE_EMAIL)){
                $this->error[] = 'Keine gültige Mailadresse!';
                return false;
            }else {
                $this->mail = $mail;
                $result = $this->updateFirma();
                if (!$result) {
                    return false;
                } else {
                    $this->leseFirmaNachId($this->id);
                    return true;
                }
            }
        }
    }
    /**
     * Setzt eine neue Telefonnummer für die aktuelle Firma
     * @param string $telefon
     * @return bool
     * @throws ErrorException
     */
    public function setNewTelefon(string $telefon = ''): bool
    {
        if ($this->id == 0){
            $this->error[] = 'Keine Firma ausgewählt!';
            return false;
        }
        if ($telefon == ''){
            $this->error[] = "Keine Telefonnummer angegeben!";
            return false;
        }else{
            preg_match(FONREGEX,$telefon,$match);
            if ($telefon !== $match[0]){
                $this->error[] = 'Ungültige Zeichen in der Telefonnummer!';
                return false;
            }
            $this->telefon = $telefon;
            $result = $this->updateFirmaDaten();
            if (!$result){
                return false;
            }else{
                $this->leseFirmaNachId($this->id);
                return true;
            }
        }
    }
    /**
     * Setzt eine neue Webadresse für die aktuelle Firma
     * @param string $web
     * @return bool
     * @throws ErrorException
     */
    public function setNewWeb(string $web = ''): bool
    {
        if ($this->id == 0){
            $this->error[] = 'Keine Firma ausgewählt!';
            return false;
        }
        if($web ==''){
            $this->error[] = 'Keine neue Webadresse angegeben!';
            return false;
        }else{
            $web = (substr_count($web,'htt'==0)) ? 'https://'.$web : $web;
            if (!filter_var($web,FILTER_VALIDATE_URL)){
                $this->error[] = "Keine gültige URL!";
                return false;
            }else{
                $this->web = $web;
                $result = $this->updateFirmaDaten();
                if (!$result){
                    return false;
                }else{
                    $this->leseFirmaNachId($this->id);
                    return true;
                }
            }
        }
    }
    /**
     * Anlegen einer neuen Firma
     * $telefon soll die Telefonnummer der Zentrale erhalten, Durchwahlen werden
     * bei den Mitarbeiterkontakten erfasst
     * @param string $name
     * @param string $mail
     * @param string $telefon
     * @param string $web
     * @return bool
     * @throws ErrorException
     */
    public function setNewFirma(string $name = '', string $mail = '', string $telefon = '', string $web = ''): bool
    {
        if ($name == ''){
            $this->error [] = 'Kein Name angegeben!';
            return false;
        }else{
            $sqlArgs = compact('name','mail','telefon','web');
            $result = $this->createFirma($sqlArgs);
            if (!$result){
                return false;
            }else{
                $this->leseFirmaNachName($name);
                return true;
            }
        }
    }

    /*--------------------GETTER----------------*/
    /**
     * Gibt die Fehlermeldungen als Array zurück
     * @return array
     */
    public function getError(): array
    {
        return $this->error;
    }
    /**
     * Gibt die Id der aktuellen Firmeninstanz zurück
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }
    /**
     * Gibt alle Firmendaten der aktuellen Instanz zurück
     * @return array
     */
    public function getFirma(): array
    {
        return $this->firma;
    }
    /**
     * Gibt die Mail der Firma der aktuellen Instanz zurück
     * @return string
     */
    public function getMail(): string
    {
        return $this->mail;
    }
    /**
     * Gibt den Namen der Firma der aktuellen Instanz zurück
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }
    /**
     * Gibt die Telefonnummer der aktuellen Instanz der Firma zurück
     * @return string
     */
    public function getTelefon(): string
    {
        return $this->telefon;
    }
    /**
     * Gibt die Webadresse der aktuellen Firmeninstanz zurück
     * @return string
     */
    public function getWeb(): string
    {
        return $this->web;
    }

    /*--------------------PRIVATE---------------*/
    /**
     * Setzt die Instanzvariablen auf die Werte, die aus der Datenbank
     * zurückgegeben wurden
     * @param array $firma
     */
    private function setzeFirmaVars(array $firma){
        if (!empty($firma)){
            $this->firma    = $firma;
            $this->id       = $firma['id'];
            $this->name     = $firma['name'];
            $this->mail     = $firma['mail'];
            $this->telefon  = $firma['telefon'];
            $this->web      = $firma['web'];
        }
    }
    /**
     * Holt die Firmendaten anhand ihrer ID aus der Datenbank
     * @param int $id
     * @return void
     * @throws ErrorException
     */
    private function leseFirmaNachId(int $id = 0): void
    {
            $sql = 'SELECT id, name, mail, telefon, web FROM firmen WHERE id = :id';
            $sqlArgs = array(':id'=>$id);
            $result = Database::readFromDatabase($sql, $sqlArgs);
            if ($result){
                $this->setzeFirmaVars($result[0]);
            }else{
                $this->error[] = 'Fehler beim Lesen der Datenbank!';
            }
    }
    /**
     * Holt die Firmendaten anhand des Namens aus der Datenbank
     * @param string $name
     * @throws ErrorException
     */
    private function leseFirmaNachName(string $name = ''): void{
        $sql = 'SELECT id, name, mail, telefon, web FROM firmen WHERE UPPER(name) = UPPER(:name)';
        $sqlArgs = array(':name'=>$name);
        $result = Database::readFromDatabase($sql, $sqlArgs);
        if ($result){
            $this->setzeFirmaVars($result[0]);
        }else{
            $this->error[] = 'Fehler beim Lesen der Datenbank!';
        }
    }
    /**
     * Legt einen neuen Eintrag für eine Firma an
     * @param array $sqlArgs
     * @return bool
     * @throws ErrorException
     * @throws Exception
     */
    private function createFirma(array $sqlArgs = []): bool
    {

        if (empty($sqlArgs)){
            return false;
        }else{
            $sql = "INSERT INTO firmen (name, mail, telefon, web) VALUES(:name, :mail, :telefon,:web)";
            $count = Database::readFromDatabase('SELECT COUNT(*) AS anzahl from firmen WHERE name = :name',array(':name'=>$sqlArgs['name']));
            if ($count[0]['anzahl']){
                $this->error[] = 'Diese Firma existiert bereits in der Datenbank!';
                return false;
            }else {
                $result = Database::insertIntoDatabase($sql, $sqlArgs);

                if (!$result) {
                    $this->error[] = 'Fehler beim Eintrag in die Datenbank!';
                    return false;
                } else {

                    return true;
                }
            }
        }
    }
    /**
     * Aktualisiert den Datenbankeintrag
     * @return bool
     * @throws ErrorException
     */
    private function updateFirma():bool{
        $sqlArgs = array(
            'id'        =>$this->id,
            'name'      =>$this->name,
            'mail'      =>$this->mail,
            'telefon'   =>$this->telefon,
            'web'       =>$this->web
        );
        $sql = 'UPDATE firmen 
                SET 
                    name    = :name,
                    mail    = :mail,
                    telefon = :telefon,
                    web     = :web
                WHERE
                    id      = :id
                ';

        $result = Database::updateDatabase($sql, $sqlArgs);
        if (!$result){
            $this->error[] = 'Fehler beim Aktualisieren des Datenbankeintrags!';
            return false;
        }else{
            return true;
        }
    }
}