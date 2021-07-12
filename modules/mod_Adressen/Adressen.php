<?php
/**
 * Die Klasse erlaubt den Zugriff auf die Datentabelle adressen.
 * @author Lars Münchhagen <lars.muenchhagen@outlook.de>
 * @version 0.0.2
 *
 */


namespace modules\mod_Adressen;


/*--------------------TODO AND FIX----------*/
# TODO refactor
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
     * Erzeuge einen Datenbankeintrag.
     *
     * return bool  Rückgabe des Ergebnisses des Datenbankeintrags.
     * @param string $name
     * @param string $nummer
     * @param string $lat
     * @param string $lng
     * @return bool
     * @throws ErrorException
     */
    public function create(string $name = '', string $nummer = '', string $lat = '', string $lng = ''):bool{
        # prüfe ob ID innerhalb der Instanz auf neue Adresse gesetzt wurde.
        if ($this->id !== 0){
            $this->error[] = 'Die Adresse ist nicht neu!';
            return false;
        }else{
            # prüfe ob name nicht leer ist
            if ($name == ''){
                $this->error [] = 'Der Straßenname darf nicht leer sein';
                return false;
            }else {
                # schreibe in die Datenbanktabelle
                $sql = "INSERT INTO adressen (name, nummer, lat, lng) VALUES (:name, :nummer, :lat,:lng)";
                $this->adresse = compact('name','nummer','lat','lng');
                $sqlArgs = $this->adresse;
                $result = $this->createNewAdresse($sql, $sqlArgs);
                # Prüfe das Ergebnis
                if (!$result) {
                    $this->error[] = ' Fehler bei der Erzeugung des Datenbankeintrags!';
                    return false;
                } else {
                    $id = $this->id;
                    $this->adresse = compact('id','name','nummer','lat','lng');
                    if($this->adresse == $this->read()[0]){
                        return true;
                    }else{
                        $this->error[] = 'Fehler beim Schreiben, Daten stimmen nicht überein!';
                        return false;
                    }
                }
            }
        }
    }
    /**
     * Update eines Datenbankeintrags, die ID muss vorher über Instanz.setId() gesetzt sein.
     *
     * @return bool
     * @throws ErrorException
     */
    public function update():bool{
        if ($this->id == 0 or $this->id == -1){
            $this->error[] = 'Keine gültige ID angegeben!';
            return false;
        }else{
            $sql = "UPDATE adressen SET name = :name, nummer = :nummer, lat = :lat, lng = :lng WHERE id = :id";
            $sqlArgs = $this->adresse;
            $result = $this->aktualisiereAdresse($sql, $sqlArgs);
            if (!$result){
                return false;
            }else{
                return true;
            }
        }
    }
    /**
     * Lese die Adresse aus der Datenbanktabelle adressen anhand der ID.
     * Die Id muss vorher über Instanz.setId() bekanntgegeben werden.
     * Die Adresse wird an das Array adressen gebunden und anschließend
     * über die Methode setAdressenInstanzvariablen() an die jeweiligen
     * Instanzvariablen übergeben.
     *
     * @return array                                Es wird ein Ergebnis als Array oder ein leeres Array zurückgegeben.
     * @throws ErrorException
     * @see setAdressenInstanzvariablen()
     */
    public function read():array{
        # Prüfe ob id gesetzt
        if ($this->id == 0 OR $this->id == -1 OR !is_numeric($this->id)){
            $this->error[] = 'Es wurde keine gültige AdressenID angegeben!';
            return array();
        }else{
            $sql = "SELECT id, name, nummer, lat, lng FROM adressen WHERE id = :id";
            $sqlArgs = array(':id'=>$this->id);
            $result = $this->readFromAdressen($sql, $sqlArgs);
            if (!$result){
                $this->error[] = 'Fehler bei der Abfrage der Daten!';
                return array();
            }else{
                $this->setAdressenInstanzvariablen($result);
                return $result;
            }
        }
    }
    /**
     * Gibt eine komplette Liste der Tabelle adressen aus.
     *
     * @param string    $orderBy    Spalte nach der sortiert werden soll.
     * @param string    $order      Sortierreihenfolge ASC = aufsteigend (default), DESC absteigend
     * @param int       $rows       Anzahl der auszugebenden Spalten
     * @param int       $offset     Beginn der Rückgabe Achtung! 1. Zeile hat Offset 0
     * @return array                Ein assoziatives Array ([0]=>Array [int id, string name, string nummer, string lat, string lng]...)
     * @throws ErrorException
     */
    public function readAdressenListe(string $orderBy = 'name',string $order='ASC', int $rows = 0, int $offset = 0):array{
        $sqlOrder = ' ORDER BY '.$orderBy;
            if (strtoupper($order)== 'ASC'){
                $sqlOrder .= ' ASC';
            }else {
                $sqlOrder .= ' DESC';
            }
        if ($rows == 0 OR !is_numeric($rows)){
            $sqlLimit = '';
        }else {
            $sqlLimit = ' LIMIT '.$rows;
            if ($offset !== 0 AND is_numeric($offset)){
                $sqlLimit .= ' OFFSET '.$offset;
            }
        }
        $sql = "SELECT * FROM adressen".$sqlOrder.$sqlLimit;

        return $this->readFromAdressen($sql);
    }

    /*--------------------SETTER----------------*/
    /**
     * setze die ID der aktuellen Instanz
     * @param int $id           Die ID als Ganzzahl größer 0.
     * @throws ErrorException
     */
    public function setId(int $id = 0): void
    {
        if ($id == 0 or !is_numeric($id)){
            $this->error[] = 'Keine oder ungültige ID angegeben! Die ID muss eine Ganzzahl größer 0 sein.';
        }else{
            $this->id = $id;
            $this->adresse['id'] = $id;
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
            $this->adresse['name'] = $name;
        }
    }
    /**
     * setze die Hausnummer der aktuellen Instanz
     * @param string $nummer
     */
    public function setNummer(string $nummer = ''): void
    {
        $this->nummer = $nummer;
        $this->adresse['nummer'] = $nummer;
    }
    /**
     * setze die Breitengrade der aktuellen Instanz
     * @param string $lat
     */
    public function setLat(string $lat = ''): void
    {
        $this->lat = $lat;
        $this->adresse['lat']= $lat;
    }
    /**
     * setze die Längengrade der aktuellen Instanz
     * @param string $lng
     */
    public function setLng(string $lng = ''): void
    {
        $this->lng = $lng;
        $this->adresse['lng'] = $lng;
    }
    public function setAdresse(int $id = 0, string $name='', string $nummer='', string $lat='', string $lng=''){
        # Prüfe ob eine gültige ID vorhanden
        if ($id == 0 or $id == -1 or !is_numeric($id)) {
            $this->error[] = 'Keine gültige ID angegeben!';
        }
        # Prüfe ob name gesetzt
        if ($name == ''){
            $this->error[] = 'Kein Straßenname angegeben!';
        }
        # Prüfe ob bis hierher Fehler erzeugt wurden, wenn nein fortfahren
        if (count($this->error) == 0){
            $this->id = $id;
            $this->name = $name;
            $this->nummer = $nummer;
            $this->lat = $lat;
            $this->lng = $lng;
            $this->adresse = compact('id', 'name', 'nummer', 'lat', 'lng');
        }
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
    private function createNewAdresse(string $sql = '', array $sqlArgs = []):bool{
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
     * @return array                 Der Rückgabewert ist ein Array mit dem Inhalt der Datenbankabfrage.
     *
     * @throws ErrorException       Fehler der Datenbankabfrage
     * @see                         setAdressenInstanzvariablen()
     */
    private function readFromAdressen (string $sql = '', array $sqlArgs = []): array
    {
        if($sql == ''){
            return array();
        }else{
            $result = Database::readFromDatabase($sql, $sqlArgs);
            if(!$result){
                $this->error[] = "Fehler bei der Datenbankabfrage";
                return array();
            }else{
                return $result;
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
            $this->id       = $adressen[0]['id'];
            $this->name     = $adressen[0]['name'];
            $this->nummer   = (is_null($adressen[0]['nummer'])) ? '' : $adressen[0]['nummer'];
            $this->lat      = (is_null($adressen[0]['lat'])) ? '' : $adressen[0]['lat'];
            $this->lng      = (is_null($adressen[0]['lng'])) ? '' : $adressen[0]['lng'];
        }
    }
}