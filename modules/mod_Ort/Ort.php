<?php
/**
 * Klasse für den Zugriff auf die Datenbanktabelle orte.
 *
 * Die Klasse ermöglicht lesenden und schreibenden Zugriff auf die Tabelle orte.
 * Es ist möglich neue Orte hinzuzufügen und vorhandene zu aktualisieren.
 * Weiterhin ermöglicht die Klasse eine Ausgabe des gesamten Tabelleninhalts,
 * sowie die Filterung nach Orten, die mit bestimmten Zahlen beginnen.
 *
 * Die Klasse verwendet den Standardkonstruktor.
 *
 * @author Lars Münchhagen <lars.muenchhagen@outlook.de>
 * @version 0.0.1
 *
 */


namespace modules\mod_Ort;


/*--------------------TODO and FIX----------*/
# TODO Methoden:
#
# TODO Bei Eintrag in die Datenbank Duplikate verhindern
# TODO Absichern vor unbefugten Zugriff
# TODO Orte mit Plz als m:n verknüpfen
### TODO zusätzliche Tabelle erforderlich
/*--------------------REQUIREMENTS----------*/
# require '../../system/Database.php';

use ErrorException;
use PDO;
use system\Database;

class Ort
{
    private string  $ortName    = '';
    private int     $ortId      = 0;
    private array   $ortObject  = [];
    private array   $error      = [];

    private array   $errorCodes = [
        '1' => 'Keine ID oder kein Name angegeben!',
        '2' => 'Keine oder keine gültige ID angegeben!',
        '3' => 'Fehler bei der Datenbankabfrage!',
        '4' => 'Kein oder kein gültiger Name angegeben!',
    ];

/*--------------------SETTER----------------*/
    /**
     * Setzt die ID des Ortes für die aktuelle Instanz.
     * Handelt es sich um eine valide ID, werden die Daten
     * in den Instanzvariablen hinterlegt.
     * @param int $ortId
     * @throws ErrorException
     */
    public function setOrtId(int $ortId = 0): void
    {
        if (is_numeric($ortId) AND $ortId != 0){
            $this->ortId = $ortId;
            $abfrage = $this->readOrtFromDatabase();
            if (!$abfrage) $this->error[] = $this->errorCodes['2'];
        }else{
            $this->error[] = $this->errorCodes['2'];
        }

    }

    /**
     * Setzt den Namen des Ortes für die aktuelle Instanz.
     * Handelt es sich um einen validen Namen, werden die
     * Daten in den Instanzvariablen hinterlegt.
     * @param string $ortName
     * @throws ErrorException
     */
    public function setOrtName(string $ortName = ''): void
    {
        if ($ortName != '') {
            $this->ortName = $ortName;
            $abfrage = $this->readOrtFromDatabase();
            if (!$abfrage) $this->error[] = $this->errorCodes['2'];
        }else{
            $this->error[] = $this->errorCodes['2'];
        }
    }

    /**
     * Fügt einen neuen Ort in die Datenbank ein.
     * Prüft ob der Eingabestring leer ist.
     * Nach Eintrag in die Datenbank wird die Instanz auf den
     * neuen Ort gesetzt.
     * @param string $name
     * @throws ErrorException
     */
    public function setNewOrt(string $name = ''){
        if($name == ''){
            $this->error[] = $this->errorCodes['4'];
        }else{
            $this->ortName = $name;
            $ausgabe = $this->insertIntoOrte();
            if(!$ausgabe) {
                $this->error[] = $this->errorCodes['3'];
            }else{
                $this->readOrtFromDatabase();
            }
        }
    }

    /**
     * aktualisiert den Ortsnamen
     * @param string $ort
     * @return bool
     * @throws ErrorException
     */
    public function changeOrtName(string $ort=''): bool
    {
        if ($ort == '' OR $this->ortId == 0){
            return false;
        }else{
            $this->ortName = $ort;

            $result = $this->updateOrtFromDatabase();
            if(!$result){
                return false;
            }else{
                return $this->readOrtFromDatabase();
            }
        }
}

/*--------------------GETTER----------------*/
    /**
     * Gibt die ID des Ortes der aktuellen Instanz zurück.
     * @return int
     */
    public function getOrtId(): int
    {
        return $this->ortId;
    }

    /**
     * Gibt den Namen des Ortes der aktuellen Instanz zurück.
     * @return string
     */
    public function getOrtName(): string
    {
        return $this->ortName;
    }

    /**
     * Gibt die Fehlermeldungen zurück.
     * @return array
     */
    public function getError(): array
    {
        return $this->error;
    }

    /**
     * Gibt alle Zeilen der Tabelle orte zurück.
     * Sortierung nach Name und abhängig vom Parameter
     * auf- oder absteigend.
     * @param string $sort
     * @return array
     * @throws ErrorException
     */
    public function getOrtsliste(string $sort='ASC'): array
    {
        if(strtoupper($sort) == 'DESC'){
            $sql = "SELECT id, name FROM orte ORDER BY name DESC ";
        }else{
            $sql = "SELECT id, name FROM orte ORDER BY name";
        }
        $result = Database::readFromDatabase($sql);
        if (!$result) $this->error[]= $this->errorCodes['3'];
        return $result;
    }

    /**
     * Gibt die Daten der Instanz als Array zurück.
     * @return array
     */
    public function getOrtObject(): array
    {
        return $this->ortObject;
    }

    /**
     * Gibt eine Liste aller Orte der Tabelle orte zurück, die
     * mit dem Suchstring beginnen.
     * Kann kein Datensatz gefunden werden oder im Fehlerfall wird
     * ein leeres Array zurückgegeben.
     * @param string $ort
     * @return array
     * @throws ErrorException
     */
    public function searchOrt(string $ort=''):array{

        # prüfe ob ein Suchstring übergeben wurde
        if ($ort == ''){
            # kein Suchstring Rückgabe ist ein leeres Array
            $this->error[] = $this->errorCodes['4'];
            return array();
        }else {
            # Übergabe an Methode
            $result = $this->searchOrtInOrte($ort);
            # prüfe ob Datenbankaktion erfolgreich war
            if (!$result){
                # Übergabe eines Fehlercodes
                $this->error[] = $this->errorCodes['3'];
                return array();
            }else{
                # Übergabe des Abfrageergebnisses
                return $result;
            }
        }
    }

    /**
     * Gibt die Anzahl der Zeilen der Tabelle orte zurück.
     *
     * @return int
     */
    public function countOrte(): int
    {
        $sql = "SELECT COUNT(*) FROM orte";
        $pdo = Database::connectDB();
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchColumn();
        Database::closeDB();
        return $result;
    }

/*--------------------private ---------------*/

    /**
     * Fügt einen neuen Datensatz in die Tabelle orte ein.
     * Gibt im Erfolgsfall true zurück, sonst false.
     *
     * @return bool
     * @throws \Exception
     */
    private function insertIntoOrte():bool{

        # überprüfen ob Voraussetzungen erfüllt und
        # erstellen des sql-Strings und des Paramaterarrays

        $sql        = 'INSERT INTO orte SET name = :name';
        $sqlArgs    = array(':name'=>$this->ortName);

            $result = Database::insertIntoDatabase($sql, $sqlArgs);
            if($result){
                return true;
            }else{
                return false;
            }

    }

    /**
     * Aktualisiert den Namen des Orts der aktuellen Instanz
     * basierend auf der ID.
     * Gibt im Erfolgsfall true zurück, sonst false.
     * @return bool
     * @throws ErrorException
     */
    private function updateOrtFromDatabase(): bool
    {
        $name = $this->ortName;
        $id = $this->ortId;

        $sql = "UPDATE orte set name = :name WHERE id = :id";
        $sqlArgs = compact('name','id');

        return Database::updateDatabase($sql, $sqlArgs);
    }

    /**
     * sucht Orte aus der aktuellen Tabelle anhand einer Übereinstimmung
     * des Anfangs von name und dem übergebenen Suchstring
     * @param string $suche
     * @return array
     * @throws ErrorException
     */
    private function searchOrtInOrte(string $suche): array {
        $sql = "SELECT id, name FROM orte WHERE name LIKE :name";
        $sqlArgs = array('name' => $suche . '%');

        return Database::readFromDatabase($sql, $sqlArgs);
    }

    /**
     * Liest einen Datensatz aus der Tabelle orte, basierend
     * auf ID oder Name, aus und schreibt diese in die Instanz.
     * Gibt im Erfolgsfall true, sonst false zurück.
     *
     * @return bool
     * @throws ErrorException
     */
    private function readOrtFromDatabase():bool{

        # überprüfen ob Voraussetzungen erfüllt und
        # erstellen des sql-Strings und des Paramaterarrays

        if ($this->ortId == 0 AND $this->ortName == ''){
            $this->error[] = $this->errorCodes['1'];
            return false;
        }else {
            if ($this->ortName != '') {
                $sql = 'SELECT id, name FROM orte WHERE name = :name';
                $sqlArgs = array(':name' => $this->ortName);
            }else{
                $sql = 'SELECT id, name FROM orte WHERE id = :id';
                $sqlArgs = array(':id' => $this->ortId);
            }
        }

        # Datenbankoperationen

        $result = Database::readFromDatabase($sql, $sqlArgs);

        # Verarbeitung der Ergebnisse

        if (!$result){
            $this->error[] = $this->errorCodes['3'];
            return false;
        }else{
            $this->ortObject = $result[0];
            $this->ortId = $result[0]['id'];
            $this->ortName = $result[0]['name'];
            return true;
        }

    }

}