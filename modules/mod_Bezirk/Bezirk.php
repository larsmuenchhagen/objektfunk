<?php
/**
 * Die Klasse schreibt, liest und aktualisiert die Tabelle bezirke in der Datenbank.
 * Es ist ebenfalls möglich sich eine Liste aller Bezirke, aller Bezirke nach Ort oder
 * aller Bezirke anhand einer enthaltenden Buchstabenfolge (Suche) ausgeben zu lassen.
 *
 * @author Lars Münchhagen <lars.muenchhagen@outlook.de>
 * @version 0.0.2
 *
 */


namespace modules\mod_Bezirk;


use ErrorException;
use Exception;
use InvalidArgumentException;
use PDO;
use system\Database;

class Bezirk
{
    private array $bezirk   = [];
    private array $error    = [];

    /**
     * Schreibt einen neuen Datensatz in die Tabelle bezirke.
     * Einfache Überprüfung der übergebenen Werte.
     * Weitergabe an createTransaktion().
     *
     * @param string $name
     * @param int $orte_id
     * @return bool
     *
     * @see createTransaction()
     */
    public function create(string $name = '', int $orte_id = 0):bool{

        # prüfe ob name gesetzt
        if ($name == ''){
            $this->error[] = 'Kein Name angegeben!';
            return false;
        }

        # prüfe ob orte_id gesetzt
        if ($orte_id == 0 or !is_int($orte_id)){
            $this->error[] = 'Kein gültiger Ort angegeben!';
            return false;
        }

        # weitergabe an createTransaktion()
        $bezirk = compact('name','orte_id');
        $result = $this->createTransaction($bezirk);

        # prüfe ob insert erfolgreich
        if (empty($result)){
            $this->error[] = 'Leerer Datensatz zurückgegeben!';
            return false;
        }else{
            $this->bezirk = $result[0];
            return true;
        }

    }

    /**
     * Liest einen Datensatz aus der Tabelle bezirke anhand einer übergebenen ID.
     * @param int $id
     * @return array
     * @throws ErrorException
     */
    public function read(int $id = 0):array{

        if ($id == 0 or !is_int($id)){
            $this->error[]= 'Keine gültige ID angegeben!';
            return false;
        }else{
            $sql = "SELECT id, name, orte_id FROM bezirke WHERE id = :id";
            $sqlArgs = array(':id'=>$id);
        }

        return Database::readFromDatabase($sql, $sqlArgs);
    }

    /**
     * Aktualisiert einen Datensatz in der Tabelle 'Bezirke'.
     * @param int $id           = ID des Datensatzes
     * @param string $name      = neuer Name des Datensatzes
     * @param int $orte_id      = ID des verknüpften Ortes
     * @return bool
     */
    public function update(int $id = 0, string $name = '', int $orte_id = 0): bool
    {
        # prüfe ob Parameter gesetzt sind
            if ($id == 0 or !is_int($id)) {
                $this->error[] = 'Keine gültige ID angegeben!';
                return false;
            }
            if ($name == ''){
                $this->error[] = 'Kein gültiger Name angegeben!';
                return false;
            }
            if ($orte_id == 0 or !is_int($orte_id)){
                $this->error[] = 'Kein gültiger Ort angegeben!';
                return false;
            }

        # Daten an Transaktion übergeben und bei erfolgreicher Transaktion Klassenarray füllen
        $result = $this->updateTransaction(compact('id','name','orte_id'));

            if (empty($result)){
                return false;
            }else{
                $this->bezirk = $result[0];
                return true;
            }


    }

    /**
     * Gibt die aktuelle Instanz der Klasse Bezirk als Array aus.
     * @return array
     */
    public function getBezirk(): array
    {
        return $this->bezirk;
    }

    /**
     * Durchsuche die Tabelle bezirke anhand des Suchstrings. Bei leerem
     * String gebe den gesamten Inhalt zurück.
     *
     * @param string $searchString  Suchstring
     * @param string $order         Sortierreihenfolge, default ASC
     * @return array
     * @throws ErrorException
     */
    public function search(string $searchString = '', string $order = 'ASC'):array{

        # prüfe ob Suchstring leer
        if ($searchString == ''){
            $sql = "SELECT id, name, orte_id AS ort FROM bezirke ORDER BY name";
            $sqlArgs = array();
        }else{
            $sql = "SELECT id, name, orte_id AS ort FROM bezirke WHERE UPPER(name) LIKE UPPER(:name) ORDER BY name";
            $sqlArgs = array(':name'=>'%'.$searchString.'%');
        }

        # prüfe ob order leer
        if (strtoupper($order) !== 'ASC') $sql .= ' DESC';

        # Daten lesen
        return Database::readFromDatabase($sql, $sqlArgs);
    }

    /**
     * Schreibt einen neuen Datensatz in die Tabelle 'bezirke'
     * @param array $bezirk
     * @return array|false
     */
    private function createTransaction(array $bezirk = []){
        $data = [];
        $pdo  = null;

        try {

            # prüfe ob Array leer
            if (empty($bezirk)) throw new InvalidArgumentException('Leerer Datensatz!');

            # initialisiere Variablen

            $name = '';
            $nameExists = false;

            extract($bezirk, EXTR_OVERWRITE);

            # mit Datenbank verbinden
            $pdo = Database::connectDB();
            # Transaction starten
            $pdo->beginTransaction();
            # prüfen, ob die Bezeichnung des Bezirks bereits existiert
            $prepStmt = $pdo->prepare('SELECT id FROM bezirke WHERE LOWER(name) = LOWER(:name)');
            $prepStmt->execute(array('name' => $name));
            $resultName = $prepStmt->fetchAll(PDO::FETCH_ASSOC);
            # Name vorhanden ja / nein

            if ($resultName) $nameExists = true;

            # Name existiert
            if($nameExists){
                # werfe Ausnahme
                throw new Exception('Logischer Fehler: Name existiert bereits!');
                # Name existiert
            }else{
                # lösche eventuellen key 'id' aus dem Array
                if (array_key_exists('id', $bezirk)) unset($bezirk['id']);
                $sql = 'INSERT INTO bezirke (name, orte_id) VALUES (:name, :orte_id)';
            }

            # Datenbankaktion ausführen
            $stmt = $pdo->prepare($sql);
            $msg = $stmt->execute($bezirk);


            if (!$msg){
                throw new Exception('Datenbankzugriffsfehler!');
            }else{
                $idx = $pdo->lastInsertId();
                # Daten zur Kontrolle aus Datenbank holen
                $stmt = $pdo->prepare('SELECT id, name, orte_id FROM bezirke WHERE id = :id');
                $stmt->execute(array('id'=>$idx));
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                if (empty($data)) throw new Exception('Datenbankaktion liefert leeren Datensatz!');
            }

            # Transaction senden
            $pdo->commit();

        }catch (InvalidArgumentException | Exception $e){
            $this->error[] = 'Fehler beim Schreiben in die Datenbank! '.$e->getMessage();
            $pdo->rollBack();
            $pdo = null;
        } finally {
            Database::closeDB();
            # Daten senden
            return $data;
        }
    }

    /**
     * Aktualisiert einen Datensatz der Tabelle 'bezirke'
     * @param array $bezirk
     * @return array|false
     */
    private function updateTransaction(array $bezirk = []){

        $data = [];
        $pdo  = null;

        try {

            # prüfe ob Array leer
            if (empty($bezirk)) throw new InvalidArgumentException('Leerer Datensatz!');

            # initialisiere Variablen

            $name = '';
            $id = 0;
            $nameExists = false;
            $idExists = false;

            extract($bezirk, EXTR_OVERWRITE);

            # mit Datenbank verbinden
            $pdo = Database::connectDB();
            # Transaction starten
            $pdo->beginTransaction();
            # prüfen, ob Bezirk bereits existiert
            $prepStmt = $pdo->prepare('SELECT id FROM bezirke WHERE LOWER(name) = LOWER(:name)');
            $prepStmt->execute(array('name' => $name));
            $resultName = $prepStmt->fetchAll(PDO::FETCH_ASSOC);
            # prüfen ob ID existiert
            $prepStmt = $pdo->prepare('SELECT id FROM bezirke WHERE id = :id');
            $prepStmt->execute(array('id' => $id));
            $resultId = $prepStmt->fetchAll(PDO::FETCH_ASSOC);

            if ($resultName) $nameExists = true;
            if ($resultId) $idExists = true;

            if ($idExists) {
                # Name existiert und die ID passt nicht zur übergebenen Firmen-ID
                if ($nameExists && intval($resultName[0]['id']) !== $bezirk['id']) {
                    # werfe Ausnahme
                    throw new Exception('Logischer Fehler: Name existiert bereits in einem anderen Datensatz!');
                    # Name existiert und die ID passt zur übergebenen Firmen-ID
                } else {
                    $sql = 'UPDATE bezirke SET name = :name, orte_id = :orte_id WHERE id = :id';
                }
            }else{
                # ID existiert nicht
                throw new Exception('Logischer Fehler: ID existiert nicht!');
            }

            # Datenbankaktion ausführen
            $stmt = $pdo->prepare($sql);
            $msg = $stmt->execute($bezirk);


            if (!$msg){
                throw new Exception('Datenbankzugriffsfehler!');
            }else{

                # Daten zur Kontrolle aus Datenbank holen
                $stmt = $pdo->prepare('SELECT id, name, orte_id FROM bezirke WHERE id = :id');
                $stmt->execute(array('id'=>$bezirk['id']));
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                if (empty($data)) throw new Exception('Datenbankaktion liefert leeren Datensatz!');
            }

            # Transaction senden
            $pdo->commit();

        }catch (InvalidArgumentException $e){
            $this->error[] = 'Fehler beim Schreiben in die Datenbank! '.$e->getMessage();

        }catch(Exception $e){
            $this->error[] = 'Fehler beim Schreiben in die Datenbank! '.$e->getMessage();
            $pdo->rollBack();
            $pdo = null;
            Database::closeDB();

        } finally {
            # Daten senden
            return $data;
        }
    }
}