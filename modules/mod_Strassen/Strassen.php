<?php
/**
 * Die Klasse erlaubt den Zugriff auf die Datentabelle strassen.
 * @author Lars Münchhagen <lars.muenchhagen@outlook.de>
 * @version 0.0.2
 *
 */


namespace modules\mod_Strassen;


/*--------------------TODO AND FIX----------*/
# TODO refactor
# TODO überprüfen ob alle Getter und Setter erforderlich sind
# TODO nach Umbenennung Funktionen nochmals überprüfen
/*--------------------REQUIREMENTS----------*/

use ErrorException;
use system\Database;

class Strassen
{
    private int     $id         = 0;
    private string  $name       = '';
    private string  $nummer     = '';
    private string  $lat        = '';
    private string  $lng        = '';
    private array   $error      = [];
    private array   $strasse    = [];


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
        # prüfe ob ID innerhalb der Instanz auf neue Strasse gesetzt wurde.
        if ($this->id !== 0){
            $this->error[] = 'Die Strasse ist nicht neu!';
            return false;
        }else{
            # prüfe ob name nicht leer ist
            if ($name == ''){
                $this->error [] = 'Der Straßenname darf nicht leer sein';
                return false;
            }else {
                # schreibe in die Datenbanktabelle
                $sql = "INSERT INTO strassen (name, nummer, lat, lng) VALUES (:name, :nummer, :lat,:lng)";
                $this->strasse = compact('name','nummer','lat','lng');
                $sqlArgs = $this->strasse;
                $result = $this->createNewStrasse($sql, $sqlArgs);
                # Prüfe das Ergebnis
                if (!$result) {
                    $this->error[] = ' Fehler bei der Erzeugung des Datenbankeintrags!';
                    return false;
                } else {
                    $id = $this->id;
                    $this->strasse = compact('id','name','nummer','lat','lng');
                    if($this->strasse == $this->read()[0]){
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
            $sql = "UPDATE strassen SET name = :name, nummer = :nummer, lat = :lat, lng = :lng WHERE id = :id";
            $sqlArgs = $this->strasse;
            $result = $this->aktualisiereStrasse($sql, $sqlArgs);
            if (!$result){
                return false;
            }else{
                return true;
            }
        }
    }
    /**
     * Lese die Adresse aus der Datenbanktabelle strassen anhand der ID.
     * Die Id muss vorher über Instanz.setId() bekanntgegeben werden.
     * Die Adresse wird an das Array strassen gebunden und anschließend
     * über die Methode setAdressenInstanzvariablen() an die jeweiligen
     * Instanzvariablen übergeben.
     *
     * @return array                                Es wird ein Ergebnis als Array oder ein leeres Array zurückgegeben.
     * @throws ErrorException
     * @see setStrassenInstanzvariablen()
     */
    public function read():array{
        # Prüfe ob id gesetzt
        if ($this->id == 0 OR $this->id == -1 OR !is_numeric($this->id)){
            $this->error[] = 'Es wurde keine gültige AdressenID angegeben!';
            return array();
        }else{
            $sql = "SELECT id, name, nummer, lat, lng FROM strassen WHERE id = :id";
            $sqlArgs = array(':id'=>$this->id);
            $result = $this->readFromStrassen($sql, $sqlArgs);
            if (!$result){
                $this->error[] = 'Fehler bei der Abfrage der Daten!';
                return array();
            }else{
                $this->setStrassenInstanzvariablen($result);
                return $result;
            }
        }
    }

    /*--------------------PUBLIC / STATIC-------*/
    /**
     * Gibt eine komplette Liste der Tabelle strassen aus.
     * Statische Methode der Klasse Strassen, zur Abfrage wird eine temporäre Instanz genutzt.
     *
     * @param string    $orderBy    Spalte nach der sortiert werden soll.
     * @param string    $order      Sortierreihenfolge ASC = aufsteigend (default), DESC absteigend
     * @param int       $rows       Anzahl der auszugebenden Spalten
     * @param int       $offset     Beginn der Rückgabe Achtung! 1. Zeile hat Offset 0
     * @return array                Ein assoziatives Array ([0]=>Array [int id, string name, string nummer, string lat, string lng]...)
     * @throws ErrorException
     */
    public static function getStrassenListe(string $orderBy = 'name', string $order='ASC', int $rows = 0, int $offset = 0):array{
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
        $sql = "SELECT * FROM strassen".$sqlOrder.$sqlLimit;

        $tmp_Adr = new Strassen();
        return $tmp_Adr->readFromStrassen($sql);
    }
    /**
     * Eine Volltextsuche, die in allen Spalten nach Übereinstimmungen mit dem Suchstring sucht.
     * Die Datenbankabfrage erfolgt mit LIKE, Wildcards vor und nach dem gesuchten String.
     *
     * Statische Methode der Klasse Strassen, zur Abfrage wird eine temporäre Instanz erzeugt.
     *
     * @param string            $suche  Der entsprechende Suchstring.
     * @return array                    Das Ergebnis der Datenbankabfrage.
     * @throws ErrorException
     */
    public static function search(string $suche = ''): array
    {
        if ($suche == '') return array();
        $sql = "SELECT id, name, nummer, lat, lng FROM strassen ";
        $sql .= "WHERE UPPER(name) LIKE UPPER(:name) OR ";
        $sql .= "UPPER(nummer) LIKE UPPER(:nummer) OR ";
        $sql .= "UPPER(lat) LIKE UPPER(:lat) OR ";
        $sql .= "UPPER(lng) LIKE UPPER(:lng)";
        $sqlArgs = array(   ':name'     =>'%'.$suche.'%',
                            ':nummer'   =>'%'.$suche.'%',
                            ':lat'      =>'%'.$suche.'%',
                            ':lng'      =>'%'.$suche.'%'
                    );
        $tmp_Adr = new Strassen();
        return $tmp_Adr->readFromStrassen($sql, $sqlArgs);
    }

    /*--------------------SETTER----------------*/
    /**
     * setze die ID der aktuellen Instanz
     * @param int $id           Die ID als Ganzzahl größer 0.
     */
    public function setId(int $id = 0): void
    {
        if ($id == 0 or !is_numeric($id)){
            $this->error[] = 'Keine oder ungültige ID angegeben! Die ID muss eine Ganzzahl größer 0 sein.';
        }else{
            $this->id = $id;
            $this->strasse['id'] = $id;
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
            $this->strasse['name'] = $name;
        }
    }
    /**
     * setze die Hausnummer der aktuellen Instanz
     * @param string $nummer
     */
    public function setNummer(string $nummer = ''): void
    {
        $this->nummer = $nummer;
        $this->strasse['nummer'] = $nummer;
    }
    /**
     * setze die Breitengrade der aktuellen Instanz
     * @param string $lat
     */
    public function setLat(string $lat = ''): void
    {
        $this->lat = $lat;
        $this->strasse['lat']= $lat;
    }
    /**
     * setze die Längengrade der aktuellen Instanz
     * @param string $lng
     */
    public function setLng(string $lng = ''): void
    {
        $this->lng = $lng;
        $this->strasse['lng'] = $lng;
    }
    public function setStrasse(int $id = 0, string $name='', string $nummer='', string $lat='', string $lng=''){
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
            $this->strasse = compact('id', 'name', 'nummer', 'lat', 'lng');
        }
    }

    /*--------------------GETTER----------------*/
    /**
     * gebe die Daten der aktuellen Instanz als Array zurück
     * @return array
     */
    public function getStrasse():array{
        return $this->strasse;
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
     * Schreibe einen neuen Datensatz in die Datenbanktabelle strassen.
     * Bei Erfolg setze die Instanz-ID auf die erzeugt ID.
     *
     *@param    string  $sql        Der SQL-Befehl.
     *@param    array   $sqlArgs    Die Argumente für die prepared statements.
     *
     * @return bool
     */
    private function createNewStrasse(string $sql = '', array $sqlArgs = []):bool{
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
     * Lese den aktuellen Datensatz aus der Datenbanktabelle 'strassen'.
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
     * @see                         setStrassenInstanzvariablen()
     */
    private function readFromStrassen (string $sql = '', array $sqlArgs = []): array
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
    private function aktualisiereStrasse(string $sql, array $sqlArgs):bool{
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
     * @param array     $strassen   Ein Array, das alle Einträge der Datenbanktabelle zu dieser Instanz enthält.
     *
     * @return void
     */
    private function setStrassenInstanzvariablen(array $strassen = []): void{

        if (!empty($strassen)){
            $this->strasse  = $strassen[0];
            $this->id       = $strassen[0]['id'];
            $this->name     = $strassen[0]['name'];
            $this->nummer   = (is_null($strassen[0]['nummer'])) ? '' : $strassen[0]['nummer'];
            $this->lat      = (is_null($strassen[0]['lat'])) ? '' : $strassen[0]['lat'];
            $this->lng      = (is_null($strassen[0]['lng'])) ? '' : $strassen[0]['lng'];
        }
    }
}