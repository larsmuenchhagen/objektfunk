<?php
/**
 * Die Klasse schreibt, liest und aktualisiert die Tabelle bezirke in der Datenbank.
 * Es ist ebenfalls möglich sich eine Liste aller Bezirke, aller Bezirke nach Ort oder
 * aller Bezirke anhand einer enthaltenden Buchstabenfolge (Suche) ausgeben zu lassen.
 *
 * @author Lars Münchhagen <lars.muenchhagen@outlook.de>
 * @version 0.0.1
 *
 */


namespace modules\mod_Bezirke;


/*--------------------TODO AND FIX----------*/

/*--------------------REQUIREMENTS----------*/

use ErrorException;
use system\Database;

class Bezirke
{
    private int $id             = 0;
    private string $name        = '';
    private int $orte_id        = 0;
    private string $orte_name   = '';
    private array $bezirk       = [];
    private array $error        = [];

    /*--------------------PUBLIC----------------*/

    /**
     * suche alle Bezirke die den String $name enthalten und gib die Liste aus
     * @param string $name
     * @return array
     * @throws ErrorException
     */
    public function searchBezirk(string $name = ''): array
    {
        if ($name == ''){
            return [];
        }else{
            $result = $this->sucheBezirkeNachName($name);
            if ($result){
                return $result;
            }else{
                $this->error[] = 'Kein Datensatz gefunden!';
                return [];
            }
        }
    }
    /**
     * Gebe Bezirke für einen Ort aus
     * @param int $orte_id
     * @param string $sort
     * @return array
     * @throws ErrorException
     */
    public function getBezirkeByOrt(int $orte_id = 0, string $sort = "ASC"): array
    {
        if (!is_numeric($orte_id)){
            return [];
        }else{
            return $this->sucheBezirkeNachOrt($orte_id,$sort);
        }
    }
    /**
     * Zeige alle Bezirke sortiert
     * @param string $sort
     * @return array
     * @throws ErrorException
     */
    public function getAlleBezirke(string $sort = 'ASC'): array
    {
        return $this->zeigeBezirkeSortiert($sort);
    }
    /*--------------------SETTER----------------*/

    /**
     * Setze Id
     * @param int $id
     * @throws ErrorException
     */
    public function setId(int $id = 0): void
    {
        # Prüfe ob Id gesetzt und Int ist
        if (is_numeric($id) AND $id !== 0){
            $result = $this->leseBezirkNachId($id);
            if ($result) $this->id = $id;
        }

    }
    /**
     * Setze Name
     * @param string $name
     * @throws ErrorException
     */
    public function setName(string $name = ''): void
    {
        # Prüfe ob Name gesetzt ist
        if ($name !== ''){
            $result = $this->leseBezirkNachName($name);
            if ($result) $this->name = $name;
        }
    }
    /**
     * Setze einen neuen Bezirk
     * @param string $name
     * @param int $orte_id
     * @return bool
     * @throws ErrorException
     */
    public function setNewBezirk(string $name = '', int $orte_id = 0): bool
    {
        if ($name == '' OR $orte_id = 0){
            $this->error[] = 'Keinen neuen Bezirk oder keinen Ort angegeben!';
            return false;
        }else{
            # schreibe Datensatz
            return $this->createBezirk($name, $orte_id);
        }
    }
    /**
     * Aktualisiert den Bezirksnamen
     * @param string $name
     * @return bool
     * @throws ErrorException
     */
    public function setNewNameBezirk (string $name = ''): bool
    {
        # prüfe ob Instanz auf einen Bezirk verweist
        if ($this->id == 0){
            $this->error[] = 'Kein Bezirk ausgewählt';
            return false;
        }
        # prüfe ob ein neuer Name übergeben wird
        if ($name == ''){
            $this->error[] = 'Kein neuer Name für den Bezirk angegeben!';
            return false;
        }
        # aktualisiere Datensatz und aktualisiere die Instanz
        $sql = 'UPDATE bezirke SET name = :name WHERE id = :id';
        $sqlArgs = array('name'=>$name, 'id'=>$this->id);
        $result = $this->updateBezirk($sql, $sqlArgs);
        if (!$result){
            $this->error[] ='Fehler dei der Aktualisierung des Datensatzes!';
            return false;
        }else {
            $this->setId($this->id);
            return true;
        }
    }
    /**
     * Aktualisiert die Ortszugehörigkeit eines Bezirks
     * @param int $orte_id
     * @return bool
     * @throws ErrorException
     */
    public function setNewOrtBezirk(int $orte_id = 0): bool{
        # prüfe ob Instanz auf einen Bezirk verweist
        if ($this->id == 0){
            $this->error[] = 'Kein Bezirk ausgewählt!';
            return false;
        }
        # prüfe ob ein neuer Ort gesetzt ist
        if($orte_id == 0){
            $this->error[] = 'Kein neuer Ort angegeben!';
            return false;
        }

        # aktualisiere Bezirk
        $sql = 'UPDATE bezirke SET orte_id = :orte_id WHERE id = :id';
        $sqlArgs = array(':id'=>$this->id, 'orte_id'=>$orte_id);
        $result = $this->updateBezirk($sql,$sqlArgs);
        if (!$result){
            $this->error[] = 'Fehler bei der Aktualisierung des Datensatzes!';
            return false;
        }else{
            return $this->leseBezirkNachId($this->id);
        }
    }
    /*--------------------GETTER----------------*/

    /**
     * Gebe die Id der Instanz zurück
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }
    /**
     * Gebe Name der Instanz zurück
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }
    /**
     * Gebe Daten von Bezirk als Array zurück
     * @return array
     */
    public function getBezirk(): array
    {
        return $this->bezirk;
    }
    /**
     * Gebe Id des Orts der Instanz zurück
     * @return int
     */
    public function getOrteId(): int
    {
        return $this->orte_id;
    }
    /**
     * Gibt den entsprechenden Ortsnamen der Instanz zurück
     * @return string
     */
    public function getOrteName(): string
    {
        return $this->orte_name;
    }
    /**
     * Gebe Fehlermeldungen als Array zurück
     * @return array
     */
    public function getError(): array
    {
        if (!empty($this->error)){
            return $this->error;
        }else{
            return [];
        }
    }

    /*--------------------PRIVATE---------------*/

    /**
     * Setze die Instanzvariablen aus Array $bezirk
     * @param array $bezirk
     */
    private function setBezirkVars(array $bezirk = []){
        if(!empty($bezirk)) {
            $this->bezirk       = $bezirk;
            $this->id           = $bezirk['id'];
            $this->name         = $bezirk['name'];
            $this->orte_id      = $bezirk['orte_id'];
            $this->orte_name    = $bezirk['orte_name'];
        }
    }
    /**
     * Lese Datensatz anhand der übergebenen Id
     * @param int $id
     * @return bool
     * @throws ErrorException
     */
    private function leseBezirkNachId(int $id = 0): bool
    {
        # Prüfe ob Id oder Name gesetzt sind
        if ($id == 0) return false;

        # Datenbankabfrage
        $sql = "SELECT bezirke.id AS id, bezirke.name AS name, bezirke.orte_id AS orte_id, orte.name AS orte_name 
                FROM bezirke 
                INNER JOIN orte ON bezirke.orte_id = orte.id
                WHERE bezirke.id = :id";
        $sqlArgs = array(':id'=>$id);

        $result = Database::readFromDatabase($sql, $sqlArgs);

        # Prüfen ob Abfrage erfolgreich oder leeres Array

        if(empty($result)){
            $this->error[] = 'ID ist nicht vorhanden!';
            return false;
        }else{
            $this->setBezirkVars($result[0]);
            return true;
        }

    }
    /**
     * Lese Datensatz anhand des übergebenen Namens
     * @param string $name
     * @return bool
     * @throws ErrorException
     */
    private function leseBezirkNachName(string $name=''): bool
    {
        # Prüfe ob Id oder Name gesetzt sind
        if ($name == '') return false;

        # Datenbankabfrage
        $sql = "SELECT bezirke.id AS id, bezirke.name AS name, bezirke.orte_id AS orte_id, orte.name AS orte_name 
                FROM bezirke 
                INNER JOIN orte ON bezirke.orte_id = orte.id
                WHERE UPPER(bezirke.name) = UPPER(:name)";
        $sqlArgs = array(':name'=>$name);

        $result = Database::readFromDatabase($sql, $sqlArgs);

        # Prüfen ob Abfrage erfolgreich oder leeres Array

        if(empty($result)){
            $this->error[] = 'Bezirk ist nicht vorhanden!';
            return false;
        }else{
            $this->setBezirkVars($result[0]);
            return true;
        }
    }
    /**
     * Gebe eine Liste der Bezirke die den Namensstring enthalten aus
     * @param string $name
     * @return array|false
     * @throws ErrorException
     */
    private function sucheBezirkeNachName(string $name){

        $sql = "SELECT bezirke.id AS id, bezirke.name AS name, bezirke.orte_id AS orte_id, orte.name AS orte_name 
                FROM bezirke 
                JOIN orte ON bezirke.orte_id = orte.id
                WHERE UPPER(bezirke.name) LIKE UPPER(:name)";
        $sqlArgs = array(':name'=>'%'.$name.'%');

        return Database::readFromDatabase($sql, $sqlArgs);
    }
    /**
     * Gibt eine Liste aller Bezirke eines Ortes aus.
     * @param int $orte_id
     * @param string $sort
     * @return array
     * @throws ErrorException
     */
    private function sucheBezirkeNachOrt(int $orte_id = 0, string $sort='ASC'):array{
        if ($orte_id == 0){
            return [];
        }else{
            if (strtoupper($sort) == "DESC"){
                $sql = "SELECT id, name FROM bezirke WHERE orte_id = :orte_id ORDER BY name DESC";
            }else{
                $sql = "SELECT id, name FROM bezirke WHERE orte_id = :orte_id ORDER BY name ASC";
            }
            $sqlArgs = array(':orte_id' => $orte_id);
            return Database::readFromDatabase($sql, $sqlArgs);
        }
    }
    /**
     * Gebe eine Liste aller Bezirke sortiert zurück
     * @param string $sort
     * @return array
     * @throws ErrorException
     */
    private function zeigeBezirkeSortiert(string $sort='ASC'):array{
        if (strtoupper($sort)=='DESC'){
            return Database::readFromDatabase('SELECT id, name FROM bezirke ORDER BY name DESC');
        }else{
            return Database::readFromDatabase('SELECT id, name FROM bezirke ORDER BY name ASC');
        }
    }
    /**
     * Schreibt einen neuen Bezirk in die Datenbank.
     * Die Id des Ortes muss bekannt sein.
     * @param string $name
     * @param int $orte_id
     * @return bool
     * @throws ErrorException
     */
    private function createBezirk(string $name = '', int $orte_id = 0): bool
    {
        # prüfe ob Parameter gesetzt
        if ($name == '' OR $orte_id = 0){
            return false;
        }else{
            # prüfe ob bereits ein Datensatz besteht
            $sql = 'SELECT id FROM bezirke WHERE name = :name AND orte_id = :orte_id';
            $sqlArgs = compact('name', 'orte_id');
            if (!empty(Database::readFromDatabase($sql, $sqlArgs))) {
                # ja -> Fehlermeldung
                $this->error[] = 'Der Bezirk ist bereits vorhanden!';
                return false;
            }else {
                # nein -> Datensatz schreiben
                $sql = 'INSERT INTO bezirke (name, orte_id) VALUES (:name, :orte_id)';
                $result = Database::insertIntoDatabase($sql, $sqlArgs);
                if (!$result){
                    $this->error[] = 'Fehler beim Eintrag in die Datenbank!';
                    return false;
                }else{
                    # Datensatz an die Instanz übergeben
                    return $this->leseBezirkNachName($name);
                }
            }
        }
    }
    /**
     * aktualisiere einen Eintrag in der Tabelle bezirke
     * @param string $sql
     * @param array $sqlArgs
     * @return bool
     * @throws ErrorException
     */
    private function updateBezirk(string $sql, array $sqlArgs): bool
    {
        return Database::updateDatabase($sql, $sqlArgs);
    }
}