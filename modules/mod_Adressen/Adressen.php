<?php
/**
 * Die Klasse erlaubt den Zugriff auf die Datentabelle adressen.
 * @author Lars Münchhagen <lars.muenchhagen@outlook.de>
 * @version 0.0.1
 *
 */


namespace modules\mod_Adressen;


/*--------------------TODO AND FIX----------*/

/*--------------------REQUIREMENTS----------*/

use ErrorException;
use system\Database;

class Adressen
{
    private int     $id         = 0;
    private string  $name       = '';
    private string  $nummer     = '';
    private string  $lat        = '';
    private string  $lng        = '';
    private array   $error      = [];
    private array   $adresse    = [];


    /*--------------------PUBLIC----------------*/
    /**
     * Setzt eine neue Adresse. Die ID der neuen Adresse ist bis zum Eintrag in die
     * Datenbank = 0.
     *
     * @param string    $name   Der neue Straßenname, darf nicht NULL oder leer sein.
     * @param string    $nummer Die neue Hausnummer.
     * @param string    $lat    Der neue Breitengrad.
     * @param string    $lng    Der neue Längengrad.
     *
     * @return void
     */
    public function setNewAdresse(string $name = '', string $nummer = '', string $lat = '', string $lng = ''):void{
        # prüfen ob der name leer ist
        if ($name == ''){
            $this->error [] = 'Der Straßenname darf nicht leer sein';
        }else{
            # instanz als neu kennzeichnen -1
            $this->id = -1;
            # array $adresse vorsorglich leeren
            $this->adresse = array();
            $this->adresse = compact('name', 'nummer', 'lat', 'lng');
        }
    }
    /**
     * Erzeuge einen Datenbankeintrag.
     *
     * return bool  Rückgabe des Ergebnisses des Datenbankeintrags.
     *
     * @throws ErrorException
     */
    public function create():bool{
        # prüfe ob bereits eine ID vorhanden ist
        if ($this->id !== -1){
            $this->error[] = 'Die Adresse ist nicht neu!';
            return false;
        }else{
            $sql = "INSERT INTO adressen (name, nummer, lat, lng) VALUES (:name, :nummer, :lat,:lng)";
            $sqlArgs = $this->adresse;
            $result = $this->createNewEntry($sql, $sqlArgs);
            if (!$result){
                $this->error[] = ' Fehler bei der Erzeugung des Datenbankeintrags!';
                return false;
            }else {
                $this->setId($this->id);
                return true;
            }
        }
    }
    /**
     * Update eines Datenbankeintrags, die ID muss gesetzt sein.
     * @return bool
     * @throws ErrorException
     */
    public function update():bool{
        if ($this->id == 0 or $this->id == -1){
            $this->error[] = 'Keine gültige ID angegeben!';
            return false;
        }else{
            $sql = "UPDATE adressen SET name = :name, nummer = :nummer, lat = :lat, lng = :lng WHERE id = :id";
            $sqlArgs = array(   'name'  => $this->name,
                                'nummer'=> $this->nummer,
                                'lat'   => $this->lat,
                                'lng'   => $this->lng,
                                'id'    => $this->id
                            );
            $this->adresse = $sqlArgs;
            $result = $this->aktualisiereAdresse($sql, $sqlArgs);
            if (!$result){
                return false;
            }else{
                return true;
            }
        }
    }
    public function read():bool{}
    /*--------------------SETTER----------------*/
    /**
     * setze die ID der aktuellen Instanz
     * @param int $id           Die ID als Ganzzahl größer 0.
     * @throws ErrorException
     */
    public function setId(int $id = 0): void
    {
        $sql = "SELECT id, name, nummer, lat, lng FROM adressen WHERE id = :id";
        $sqlArgs = array('id'=>$id);

        if ($id == 0 or !is_numeric($id)){
            $this->error[] = 'Keine oder ungültige ID angegeben! Die ID muss eine Ganzzahl größer 0 sein.';
        }else{

            $result = $this->readFromDatabase($sql, $sqlArgs);

            if($result){
                $this->setAdressenInstanzvariablen($this->adresse);
            }
        }
    }
    /**
     * setze den Namen der aktuellen Instanz
     * @param string $name
     */
    public function setName(string $name = ''): void
    {
        if ($name == ''){
            $this->error[] = "Der angegebene Name darf nicht leer sein!";
        }else {
            $this->name = $name;
        }
    }
    /**
     * setze die Hausnummer der aktuellen Instanz
     * @param string $nummer
     */
    public function setNummer(string $nummer = ''): void
    {
        $this->nummer = $nummer;
    }
    /**
     * setze die Breitengrade der aktuellen Instanz
     * @param string $lat
     */
    public function setLat(string $lat = ''): void
    {
        $this->lat = $lat;
    }
    /**
     * setze die Längengrade der aktuellen Instanz
     * @param string $lng
     */
    public function setLng(string $lng = ''): void
    {
        $this->lng = $lng;
    }

    /*--------------------GETTER----------------*/
    /**
     * gebe die Daten der aktuellen Instanz als Array zurück
     * @return array
     */
    public function getAdresse():array{
        return $this->adresse;
    }
    /**
     * gibt die ID der aktuellen Instanz aus
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }
    /**
     * gibt den Namen der aktuellen Instanz aus
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }
    /**
     * gibt die Hausnummer der aktuellen Instanz aus
     * @return string
     */
    public function getNummer(): string
    {
        return $this->nummer;
    }
    /**
     * gibt den Breitengrad der aktuellen Instanz aus
     * @return string
     */
    public function getLat(): string
    {
        return $this->lat;
    }
    /**
     * gibt den Längengrad der aktuellen Instanz aus
     * @return string
     */
    public function getLng(): string
    {
        return $this->lng;
    }
    /**
     * Gibt die aktuellen Fehler als array zurück
     *
     * @return array
     */
    public function getError(): array
    {
        return $this->error;
    }

    /*--------------------PRIVATE---------------*/
    /**
     * Schreibe einen neuen Datensatz in die Datenbanktabelle adressen.
     * Bei Erfolg setze die Instanz-ID auf die erzeugt ID.
     *
     *@param    string  $sql        Der SQL-Befehl.
     *@param    array   $sqlArgs    Die Argumente für die prepared statements.
     *
     * @return bool
     */
    private function createNewEntry(string $sql = '', array $sqlArgs = []):bool{
        $pdo = Database::connectDB();
        $stmt = $pdo->prepare($sql);
        $result = $stmt ->execute($sqlArgs);
        $last_id = $pdo->lastInsertId();
        Database::closeDB();
        if (!$result){
            return false;
        }else{
            $this->id = $last_id;
            return true;
        }
    }
    /**
     * Lese den aktuellen Datensatz aus der Datenbanktabelle 'adressen'.
     * Es werden der entsprechende Query-String, sowie die Parameter für prepared-statements übergeben.
     * Der Rückgabewert ist abhängig vom Erfolg der Query-Ausführung. Bei erfolgreicher Ausführung wird
     * das Ergebnis als Array in die Instanz geschrieben und zur weiteren Verarbeitung bereitgestellt.
     * Wird kein Datensatz ermittelt, so ist die Instanzvariable $adresse leer.
     *
     * @param array $sqlArgs Die übergebenen Parameter für die Datenbankabfrage.
     * @param string $sql Der übergebene SQL-String für die Datenbankabfrage.
     *
     * @return bool                 Der Rückgabewert ist true oder false, je nach Ergebnis der Datenbankabfrage.
     *
     * @throws ErrorException       Fehler der Datenbankabfrage
     * @see                         setAdressenInstanzvariablen()
     */
    private function readFromDatabase (string $sql = '', array $sqlArgs = []): bool{
        if($sql == '' OR $sqlArgs == []){
            return false;
        }else{
            $result = Database::readFromDatabase($sql, $sqlArgs);
            if(!$result){
                $this->error[] = "Fehler bei der Datenbankabfrage";
                return false;
            }else{
                $this->adresse = $result;
                return true;
            }
        }
    }

    /**
     * Aktualisiere einen Datenbankeintrag
     * @throws ErrorException
     */
    private function aktualisiereAdresse(string $sql, array $sqlArgs):bool{
        $result = Database::updateDatabase($sql, $sqlArgs);
        if (!$result){
            $this->error[] = 'Fehler beim Aktualisieren der Adresse!';
            return false;
        }else{
            return true;
        }
    }
    /**
     * Diese Methode generiert aus dem Array $adresse die Instanzvariablen.
     * Voraussetzung ist, dass das Array nicht leer ist.
     * Diese Methode ist allen Methoden mit Datenbankzugriff nachgeordnet.
     * Diese Methode hat keinen Rückgabewert.
     *
     * @param array     $adressen   Ein Array, das alle Einträge der Datenbanktabelle zu dieser Instanz enthält.
     *
     * @return void
     */
    private function setAdressenInstanzvariablen(array $adressen = []): void{

        if (!empty($adressen)){
            $this->adresse  = $adressen[0];
            $this->lat      = $adressen[0]['lat'];
            $this->id       = $adressen[0]['id'];
            $this->name     = $adressen[0]['name'];
            $this->lng      = $adressen[0]['lng'];
        }
    }
}