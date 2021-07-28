<?php
/**
 * Diese Klasse bietet Zugriff auf die Datenbanktabelle funkarten.
 *
 * @author Lars Münchhagen <lars.muenchhagen@outlook.de>
 * @version 0.0.2
 *
 */


namespace modules\mod_Funkart;


/*--------------------TODO AND FIX----------*/

/*--------------------REQUIREMENTS----------*/

use ErrorException;
use Exception;
use InvalidArgumentException;
use PDO;
use system\Database;

class Funkart
{
    private array $funkart  = [];
    private array $error    = [];

    /**
     * Nimmt einen neuen Namen für einen neuen Datenbankeintrag entgegen.
     * Ist der übergebene String leer wird false zurückgegeben, ansonsten wird die Methode
     * createTransaction() aufgerufen und anschließend deren Rückgabewert weitergegeben.
     *
     * @param string $name
     * @return bool
     *
     * @see createTransaction()
     */
    public function create(string $name = ''): bool
    {
        if ($name == '') {
            $this->error[] = 'Name darf nicht leer sein!';
            return false;
        }

        $result = $this->createTransaction(array('name'=>$name));

        if (empty($result)){
            return false;
        }else{
            $this->funkart = $result[0];
            return true;
        }

    }

    /**
     * Liest einen Datensatz anhand der ID aus der Tabelle funkarten.
     * Prüft ob eine ID im Format Integer und ungleich 0 übergeben wurde.
     * Bei erfolgreicher Ausführung wird das Ergebnis der Abfrage in die Instanzvariable geschrieben.
     *
     * @param int $id
     * @return bool
     */
    public function read(int $id = 0): bool
    {

       try {
            # prüfe ob eine ID als Integer übergeben wurde
            if ($id == 0 or !is_int($id)) throw new Exception('Keine gültige ID angegeben!');

            $sql = "SELECT id, name FROM funkarten WHERE id = :id";
            $result = Database::readFromDatabase($sql, array(':id'=>$id));

            if (empty($result)) throw new Exception('Datenbankaktion liefert leeren Datensatz!');

            $this->funkart = $result[0];
            return true;

        }catch(Exception $e){
            $this->error[] = 'Fehler bei der Datenbankabfrage: '.$e->getMessage();
            return false;
        }
    }

    /**
     * Nimmt die Daten für eine Aktualisierung einer Funkart entgegen.
     * Prüft die einfache Plausibilität der übergebenen Daten.
     * Leitet die Daten an updateTransaction weiter.
     *
     * @param int $id
     * @param string $name
     * @return bool
     *
     * @see updateTransaction()
     */
    public function update(int $id = 0, string $name = ''): bool
    {

        # prüfen ob Daten übergeben
        # prüfe ID
        if ($id == 0 or !is_int($id)){
            $this->error[] = 'Keine gültige ID angegeben!';
            return false;
        }

        # prüfe Name
        if ($name == ''){
            $this->error[] = 'Kein Name angegeben!';
            return false;
        }

        # Übergabe an update-Methode
        $funkart = compact('id','name');
        $result = $this->updateTransaction($funkart);

        # prüfe Rückgabe
        if (empty($result)){
            return false;
        }else{
            $this->funkart = $result[0];
            return true;
        }
    }

    /**
     * Durchsucht in der Tabelle funkarten die Spalte name nach dem angegebenen String.
     * Wildcards werden von der Methode vor und nach dem Suchstring gesetzt.
     * Wird kein Suchstring angegeben, wird der Tabelleninhalt zurückgegeben.
     *
     * @param string $searchString      String nach dem gesucht wird.
     * @param string $order             Sortierung der Ausgabe (default ASC)
     * @return array
     * @throws ErrorException
     */
    public function search(string $searchString = '', string $order = 'ASC'):array{

        # Prüfe ob Suchstring angegeben
        if ($searchString == ''){
            $sql = "SELECT id, name FROM funkarten ORDER BY name";
            $sqlArgs = [];
        }else{
            $sql = "SELECT id, name FROM funkarten WHERE UPPER(name) LIKE UPPER(:name) ORDER BY name";
            $sqlArgs = array(':name'=>'%'.$searchString.'%');
        }

        # Setze Sortierreihenfolge
        if (strtoupper($order) == 'DESC') $sql .= ' DESC';

        return Database::readFromDatabase($sql, $sqlArgs);
    }

    /**
     * Gibt die aktuelle Instanz der Klasse Funkart als Array zurück.
     * @return array
     */
    public function getFunkart():array{
        return $this->funkart;
    }

    /**
     * Gibt eventuelle Fehlermeldungen zurück.
     * @return array
     */
    public function getError():array{
        return $this->error;
    }

    /**
     * Ausführung der Datenbanktransaktion.
     * @param array $funkart
     * @return array
     */
    private function createTransaction(array $funkart = []):array{

        $pdo    = null;
        $data   = [];

        try {
            # prüfen ob Array Daten enthält
            if (empty($funkart)) throw new InvalidArgumentException('Leerer Datensatz!');

            # Datenbankverbindung
            $pdo = Database::connectDB();

            # Beginn der Transaktion
            $pdo->beginTransaction();

            # Prüfe ob Name bereits vorhanden
            $stmt = $pdo->prepare('SELECT COUNT(*) AS anzahl FROM funkarten WHERE UPPER(name) = UPPER(:name)');
            $stmt->execute(array(':name'=>$funkart['name']));
            $count = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($count[0]['anzahl'] > 0) throw new Exception('Die Bezeichnung der Funkart ist bereits vorhanden!');

            # Name noch nicht vorhanden
            $stmt = $pdo->prepare('INSERT INTO funkarten SET name = :name');
            $msg = $stmt->execute(array(':name'=>$funkart['name']));

            # Insert erfolgreich?
            if (!$msg){
                throw new Exception('Datenbankzugriffsfehler!');
            }else{

                # ID des eingefügten Datensatzes holen
                $idx = $pdo->lastInsertId();

                # Daten zur Kontrolle aus Datenbank holen
                $stmt = $pdo->prepare('SELECT id, name FROM funkarten WHERE id = :id');
                $stmt->execute(array('id'=>$idx));
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

                # Datensatz erfolgreich gelesen?
                if (empty($data)) throw new Exception('Datenbankaktion liefert leeren Datensatz!');
            }

            # Transaktion senden
            $pdo->commit();

        }catch (InvalidArgumentException | Exception $e){

            $pdo->rollBack();
            $this->error[] = 'Fehler beim Schreiben in die Datenbank: '.$e->getMessage();
            $pdo = null;

        } finally {
            # Datenbankverbindung trennen
            Database::closeDB();

            return $data;
        }


    }

    /**
     * Ausführung der Update-Transaktion.
     * @param array $funkart
     * @return array
     */
    private function updateTransaction(array $funkart = []):array{

        $pdo    = null;
        $data   = [];

        try {
            # prüfen ob Array Daten enthält
            if (empty($funkart)) throw new InvalidArgumentException('Leerer Datensatz!');

            # Datenbankverbindung
            $pdo = Database::connectDB();

            # Beginn der Transaktion
            $pdo->beginTransaction();

            # Prüfe ob Name bereits vorhanden
            $stmt = $pdo->prepare('SELECT id, name FROM funkarten WHERE UPPER(name) = UPPER(:name)');
            $stmt->execute(array(':name'=>$funkart['name']));
            $count = $stmt->fetchAll(PDO::FETCH_ASSOC);

            # prüfe ob der vorhandene Name mit der ID übereinstimmt
            if (!empty($count) && $count[0]['id'] !== $funkart['id']) throw new Exception('Die Bezeichnung der Funkart ist bereits vorhanden!');

            # Name noch nicht vorhanden
            # prüfe ob der neue Eintrag identisch zum vorhandenen Eintrag ist
            if (!empty($count) && $count[0] == $funkart) throw new Exception('Kein Update erforderlich!');

            # Einträge sind nicht identisch
            $stmt = $pdo->prepare('UPDATE funkarten SET name = :name WHERE id = :id');
            $msg = $stmt->execute($funkart);

            # Update erfolgreich?
            if (!$msg){
                throw new Exception('Datenbankzugriffsfehler!');
            }else{

                # Daten zur Kontrolle aus Datenbank holen
                $stmt = $pdo->prepare('SELECT id, name FROM funkarten WHERE id = :id');
                $stmt->execute(array('id'=>$funkart['id']));
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

                # Datensatz erfolgreich gelesen?
                if (empty($data)) throw new Exception('Datenbankaktion liefert leeren Datensatz!');
            }

            # Transaktion senden
            $pdo->commit();

        }catch (InvalidArgumentException | Exception $e){

            $pdo->rollBack();
            $this->error[] = 'Fehler beim Schreiben in die Datenbank: '.$e->getMessage();
            $pdo = null;

        } finally {
            # Datenbankverbindung trennen
            Database::closeDB();

            return $data;
        }
    }
}