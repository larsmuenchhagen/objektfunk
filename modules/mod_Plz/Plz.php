<?php
/**
 * Klasse für den Zugriff auf die Datenbanktabelle plz.
 *
 * Die Klasse ermöglicht lesenden und schreibenden Zugriff auf die Tabelle plz.
 * Es ist möglich neue Plz hinzuzufügen und vorhandene zu aktualisieren.
 * Weiterhin ermöglicht die Klasse eine Ausgabe des gesamten Tabelleninhalts,
 * sowie die Filterung nach Plz, die mit bestimmten Zahlen beginnen.
 *
 * Die Klasse verwendet den Standardkonstruktor.
 *
 * Die Variable $id entspricht der ID in der Tabelle und ist vom Typ int.
 * Die Variable $plzName entspricht der Spalte name in der Tabelle und
 * symbolisiert die Plz als Typ int(5) mit führenden Nullen bei Bedarf.
 *
 * @author Lars Münchhagen <lars.muenchhagen@outlook.de>
 * @version 0.0.1
 *
 */


namespace modules\mod_Plz;

use ErrorException;
use PDO;
use system\Database;

if (!isset($_SESSION)) session_start();

/*------------------TODO AND FIX--------*/
# TODO Absicherung gegen unberechtigten Zugriff
/*------------------REQUIREMENTS--------*/

class Plz
{
    private array   $plzObject = [];
    private int     $plzName   = 0;
    private int     $id        = 0;


    /*------------------Setter--------*/

    /**
     * Aktualisiert die Plz mit der übergebenen Variablen $plzNewName.
     * Die Id der betreffenden Plz muss der Instanz bekannt sein bzw
     * muss über setId() bekannt gegeben werden.
     * @param int|mixed $plzNewName
     * @throws ErrorException
     */
    public function setNewPlzName(int $plzNewName=0): void
    {
        if($plzNewName !== 0) {
            $result = $this->updatePlz($this->id, $plzNewName);
            if ($result) $this->getPlzArray();
        }
    }

    /**
     * Ermittelt über die Id die gewünschte Plz für die Instanz.
     * Ist die Id vorhanden, wird anschließend die Instanz mit den Datenbankinhalten gefüllt.
     * Es wird kein Wert zurückgegeben.
     * @param int|mixed $id
     * @throws ErrorException
     */
    public function setId($id): void
    {
        $this->id = $id;
        $this->getPlzArray();
    }

    /**
     * Ermittelt über den Namen die gewünschte Plz für die Instanz.
     * Ist die Plz in der Datenbank vorhanden, wird die Instanz mit den vorhandenen Daten
     * gefüllt. Es wird kein Wert zurückgegeben.
     * @param int $plz
     * @return bool
     * @throws ErrorException
     */
    public function setPlz(int $plz=0): bool
    {
        if(!is_numeric($plz) OR $plz == 0){
            return false;
        }else{
            $this->plzName = $plz;
            return $this->getPlzArray();
        }
}

    /**
     * Fügt eine neue Plz der Datenbank hinzu.
     * Eine fehlerhafte Ausführung ergibt ein false als Rückgabewert, eine erfolgreiche
     * Ausführung gibt true zurück.
     * @param int $plz
     * @return bool
     * @throws ErrorException
     * @throws \Exception
     */
    public function setNewPlz(int $plz=0):bool{
        if(!is_numeric($plz)){
            return false;
        }else{
            if ($plz == 0){
                return false;
            }else{
                $this->plzName = $plz;
                $result = $this->insertPlz();
                if(!$result){
                    return false;
                }else{
                    $this->getPlzArray();
                    return true;
                }
            }
        }
    }

    /*------------------Getter--------*/

    /**
     * Gibt die aktuelle Plz als Array zurück.
     * Die Keys sind id und name.
     * @return array
     */
    public function getPlzObject(): array
    {
        return $this->plzObject;
    }

    /**
     * Gibt die Id der aktuellen Plz zurück.
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Gibt den Inhalt der Datenbanktabelle plz als Array zurück.
     * Die Abfrage erfolgt mit ASC aufsteigend (Standardwert) oder mit DESC absteigend
     * nach Plz.
     * @param string $sortDirection
     * @return array
     * @throws ErrorException
     */
    public function getPlzList(string $sortDirection = 'ASC'):array{

        $sql = "SELECT id, name FROM plz ORDER BY name ";
        if (strtoupper($sortDirection) == 'DESC') $sql = "SELECT id, name FROM plz ORDER BY name DESC ";

        return Database::readFromDatabase($sql);
    }

    /**
     * Gibt den Inhalt der Datenbanktabelle plz, der mit $plz beginnt, als Array zurück.
     * Die Filterung erfolgt über LIKE, die Wildcards stehen am Ende.
     * @param int $plz
     * @return array|void
     * @throws ErrorException
     */
    public function getPlzLikeList(int $plz)
    {
        if (!is_numeric($plz)){
            return;
        }else{
            $sql = "SELECT id, name FROM plz WHERE name LIKE :name";
            $args = array('name'=>$plz.'%');

            return Database::readFromDatabase($sql, $args);
        }
    }

    /**
     * Gibt die Anzahl der Zeilen bzw. Datensätze in der Tabelle plz zurück.
     * @return int
     * @throws ErrorException
     */
    public function countAllPlz():int{
        $sql = "SELECT COUNT(*) FROM plz";
        $result = Database::readFromDatabase($sql);
        return $result[0]['COUNT(*)'];
    }

    /*------------------privates--------*/
    /**
     * Gibt den Datenbankeintrag für eine Id oder eine Plz zurück.
     * Das Ergebnis der Abfrage ist ein Array mit den Inhalten der entsprechenden Zeile
     * oder, wenn es keine Übereinstimmung gibt, ein leeres Array. Der Rückgabewert ist
     * in diesen Fällen true.
     * Im Fehlerfall wird false zurückgegeben.
     * @return bool
     * @access private
     * @throws ErrorException
     */
    private function getPlzArray(): bool
    {

        if (($this->id == 0)    AND ($this->plzName == 0)) {
            return false;
        }else {
            if (($this->id == 0) AND ($this->plzName !== 0)){
                $sql = 'SELECT id, name FROM plz WHERE name = :name';
                $args = array(':name'=> $this->plzName);
            }else {
                $sql = 'SELECT id, name FROM plz WHERE id = :id';
                $args = array(':id'=>$this->id);
            }
        }
        $result = Database::readFromDatabase($sql,$args);
        if ($result){
            $this->plzObject = $result[0];
            return true;
        }else{
            return false;
        }

    }

    /**
     * Aktualisiert eine Plz abhängig von der Id.
     * Ist die Id der Instanz nicht bekannt, wird false zurückgegeben.
     * Im Erfolgsfall ist der Rückgabewert true.
     * @param int id
     * @param int plz
     * @access private
     * @throws ErrorException
     */
    private function updatePlz(int $id = 0, int $plz = 0): bool
    {
        if($id == 0 OR $plz == 0){
            return false;
        }else{
            if(!is_numeric($id) OR !is_numeric($plz)){
                return false;
            }else{
                $sql = "UPDATE plz SET name = :name WHERE id = :id";
                $args= array(':name'=>$plz, ':id'=>$id);

                return Database::updateDatabase($sql,$args);
            }
        }
    }

    /**
     * Fügt der Datenbanktabelle plz einen neuen Eintrag hinzu.
     * Abhängig vom Ergebnis ist der Rückgabewert true oder false.
     * @return bool
     * @throws \Exception
     * @access private
     */
    private function insertPlz(): bool
    {
        $sql = "INSERT INTO plz SET name=:name";
        $args = array(':name'=>$this->plzName);
        return Database::insertIntoDatabase($sql,$args);
    }
}