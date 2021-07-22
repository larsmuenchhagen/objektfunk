<?php
/**
 * Klasse zur Verwaltung der Kontakte zu Fremdfirmen
 * @author Lars Münchhagen <lars.muenchhagen@outlook.de>
 * @version 0.0.2
 *
 */


namespace modules\mod_Firma;


/*--------------------TODO AND FIX----------*/

# FIX http prefix web
/*--------------------REQUIREMENTS----------*/

use ErrorException;
use Exception;
use InvalidArgumentException;
use PDO;
use system\Database;

class Firma
{


    private array   $firma      = array('id'=>0,
                                        'name'=>'',
                                        'mail'=>'',
                                        'telefon'=>'',
                                        'web'=>''
                                        );
    private array   $error      = [];

    /*--------------------PUBLIC----------------*/
    /**
     * Schreibt einen neuen Datensatz in die Tabelle firmen.
     * Die Variable $name darf nicht leer und noch nicht vorhanden sein.
     * Im Erfolgsfall wird true zurückgegeben und die Instanzvariablen gefüllt.
     *
     * @param string $name      Die Bezeichnung der Firma, darf nicht leer sein.
     * @param string $mail      Die Mailadresse der Firma.
     * @param string $telefon   Die Haupttelefonnummer der Firma.
     * @param string $web       Die Webadresse der Firma.
     * @return bool
     * @throws Exception
     */
    public function create(string $name = '', string $mail = '', string $telefon = '', string $web = ''): bool
    {
        # prüfe ob relevante Variablen gesetzt
        if ($name == '') {
            $this->error[] = 'Die Bezeichnung der Firma darf nicht leer sein!';
            return false;
        }
        $result = $this->createDatabaseTransaction(compact('name','mail','telefon','web'));
        if (empty($result)){
            return false;
        }else{
            $this->firma = $result[0];
            return true;
        }
    }

    /**
     * Liest den angegebenen Datensatz aus der Tabelle firmen und füllt die Instanzvariable.
     * Die ID darf nicht leer sein.
     *
     * @param int $id           Die ID des gewünschten Datensatzes (default = 0).
     * @return bool
     * @throws ErrorException
     */
    public function read(int $id = 0): bool
    {
        if ($id == 0){
            $this->error[] = 'Keine ID angegeben!';
            return false;
        }
        $sql = 'SELECT id, name, mail, telefon, web FROM firmen WHERE id = :id';
        $sqlArgs = array(':id'=>$id);
        $result = Database::readFromDatabase($sql, $sqlArgs);
        if(empty($result)){
            $this->error[] = 'ID existiert nicht!';
            return false;
        }else {
            $this->firma = $result[0];
            return true;
        }
    }

    /**
     * Aktualisiert einen Datensatz in der Tabelle firmen anhand der ID.
     * Prüft ob der Name der Firma bereits in der Tabelle vorhanden ist, um Doppelungen zu vermeiden.
     *
     * @param int $id
     * @param string $name
     * @param string $mail
     * @param string $telefon
     * @param string $web
     * @return bool
     */
    public function update(int $id = 0, string $name = '', string $mail = '', string $telefon = '', string $web = ''): bool
    {
        # keine id angegeben
        if ($id == 0){
            $this->error[] = 'Keine gültige ID angegeben!';
            return false;
        }

        $result = $this->updateDatabaseTransaction(compact('id','name','mail','telefon','web'));
        if (empty($result)){
            return false;
        }else{
            $this->firma = $result[0];
            return true;
        }

    }

    /*--------------------SETTER----------------*/

    /*--------------------GETTER----------------*/
    /**
     * Gibt einen Array mit dem Inhalt der Instanzvariablen zurück.
     * @return array
     */
    public function getFirma(): array
    {
        return $this->firma;
    }

    /**
     * Gibt einen Array mit Fehlermeldungen zurück.
     * @return array
     */
    public function getError(): array
    {
        return $this->error;
    }

    /*--------------------PRIVATE---------------*/
    /**
     * Führt den Insert in die Datenbank als Transaktion aus.
     * Es wird geprüft, ob das übergebene Array leer ist. In diesem Fall wird eine Exception geworfen.
     * Anschließend wird überprüft, ob der übergebene Name bereits vorhanden ist. In diesem Fall wird
     * ebenfalls eine Exception geworfen.
     * Sind alle Überprüfungen erfolgreich, wird der Datensatz in die Tabelle geschrieben und zur Kontrolle
     * wieder gelesen. Der gelesene Datensatz wird zurückgegeben.
     *
     * @param array $firma  Die Firmendaten. (name => string, mail => string, telefon => string, web => string)
     * @return array
     */
    private function createDatabaseTransaction(array $firma = []): array
    {

        $data = [];
        $pdo  = null;

        try {

            # prüfe ob Array leer
            if (empty($firma)) throw new InvalidArgumentException('Leerer Datensatz!');

            # initialisiere Variablen

            $name = '';
            $nameExists = false;

            extract($firma, EXTR_OVERWRITE);

            # mit Datenbank verbinden
            $pdo = Database::connectDB();
            # Transaction starten
            $pdo->beginTransaction();
            # prüfen ob Firmenname bereits existiert
            $prepStmt = $pdo->prepare('SELECT id FROM firmen WHERE LOWER(name) = LOWER(:name)');
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
                if (array_key_exists('id', $firma)) unset($firma['id']);
                $sql = 'INSERT INTO firmen (name, mail, telefon, web) VALUES (:name, :mail, :telefon, :web)';
            }

            # Datenbankaktion ausführen
            $stmt = $pdo->prepare($sql);
            $msg = $stmt->execute($firma);


            if (!$msg){
                throw new Exception('Datenbankzugriffsfehler!');
            }else{
                $idx = $pdo->lastInsertId();
                # Daten zur Kontrolle aus Datenbank holen
                $stmt = $pdo->prepare('SELECT id, name, mail, telefon, web FROM firmen WHERE id = :id');
                $stmt->execute(array('id'=>$idx));
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

    /**
     * Führt das Update in die Datenbank als Transaktion aus.
     * Es wird geprüft, ob das übergebene Array leer ist. In diesem Fall wird eine Exception geworfen.
     * Es wird überprüft, ob der übergebene Name bereits vorhanden ist. In diesem Fall wird
     * ebenfalls eine Exception geworfen.
     * Es wird überprüft, ob die übergebene ID vorhanden ist, wenn nein wird
     * ebenfalls eine Exception geworfen.
     * Sind alle Überprüfungen erfolgreich, wird der Datensatz in die Tabelle geschrieben und zur Kontrolle
     * wieder gelesen. Der gelesene Datensatz wird zurückgegeben.
     * @param array $firma
     * @return array
     */
    private function updateDatabaseTransaction(array $firma = []): array
    {

        $data = [];
        $pdo  = null;

        try {

            # prüfe ob Array leer
            if (empty($firma)) throw new InvalidArgumentException('Leerer Datensatz!');

            # initialisiere Variablen

            $name = '';
            $id = 0;
            $nameExists = false;
            $idExists = false;

            extract($firma, EXTR_OVERWRITE);

            # mit Datenbank verbinden
            $pdo = Database::connectDB();
            # Transaction starten
            $pdo->beginTransaction();
            # prüfen ob Firmenname bereits existiert
            $prepStmt = $pdo->prepare('SELECT id FROM firmen WHERE LOWER(name) = LOWER(:name)');
            $prepStmt->execute(array('name' => $name));
            $resultName = $prepStmt->fetchAll(PDO::FETCH_ASSOC);
            # prüfen ob ID existiert
            $prepStmt = $pdo->prepare('SELECT id FROM firmen WHERE id = :id');
            $prepStmt->execute(array('id' => $id));
            $resultId = $prepStmt->fetchAll(PDO::FETCH_ASSOC);

            if ($resultName) $nameExists = true;
            if ($resultId) $idExists = true;

            if ($idExists) {
                # Name existiert und die ID passt nicht zur übergebenen Firmen-ID
                if ($nameExists && intval($resultName[0]['id']) !== $firma['id']) {
                    # werfe Ausnahme
                    throw new Exception('Logischer Fehler: Name existiert bereits in einem anderen Datensatz!');
                    # Name existiert und die ID passt zur übergebenen Firmen-ID
                } else {
                    $sql = 'UPDATE firmen SET name = :name, mail = :mail, telefon = :telefon, web = :web WHERE id = :id';
                }
            }else{
                # ID existiert nicht
                    throw new Exception('Logischer Fehler: ID existiert nicht!');
            }

            # Datenbankaktion ausführen
            $stmt = $pdo->prepare($sql);
            $msg = $stmt->execute($firma);


            if (!$msg){
                throw new Exception('Datenbankzugriffsfehler!');
            }else{

                # Daten zur Kontrolle aus Datenbank holen
                $stmt = $pdo->prepare('SELECT id, name, mail, telefon, web FROM firmen WHERE id = :id');
                $stmt->execute(array('id'=>$firma['id']));
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