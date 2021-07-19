<?php
/**
 * Diese Klasse bietet Zugriff auf die Datenbanktabelle funkarten.
 *
 * @author Lars Münchhagen <lars.muenchhagen@outlook.de>
 * @version 0.0.1
 *
 */


namespace modules\mod_Funkart;


/*--------------------TODO AND FIX----------*/

/*--------------------REQUIREMENTS----------*/

use ErrorException;
use system\Database;
use TypeError;

class Funkart
{

    private array   $funkart    = array('id'=>0,'name'=>'');
    private array   $error      = [];

    /*--------------------PUBLIC----------------*/
    /**
     * Schreibe einen neuen Datensatz in die Datenbank.
     * @param string $newName
     * @return bool
     * @throws ErrorException
     */
    public function create(string $newName):bool{

        if (!$this->setNewName($newName)){
            return false;
        }else {
            $sql = 'INSERT INTO funkarten SET name = :name';

            $pdo = Database::connectDB();
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute(array('name' => $this->funkart['name']));
            $last_id = $pdo->lastInsertId();

            Database::closeDB();
            if (!$result) {
                $this->error[] = 'Fehler beim Eintrag in die Datenbank!';
                return false;
            } else {
                $this->funkart['id'] = $last_id;
                return true;
            }
        }
    }

    /**
     * Lese Datensatz aus Tabelle funkarten.
     * @param null $content     Der Wert des zu lesenden Feldes; id als int, name als string
     * @return array
     * @throws ErrorException
     */
    public function read($content = null): array
    {

        if (is_int($content) && $content!== 0) {
            # id ist nicht 0
            if (!$this->setId($content)) {
                # rückgabewert setId ist false
                $this->error[] = 'Keine gültige ID angegeben!';
                return array();
            } else {
                return $this->funkart;
            }
        }else{
            # id ist 0
            if (!is_int($content) && $content !== null){
                # name ist nicht leer
                if (!$this->setName($content)){
                    # rückgabewert setName ist false
                    return array();
                }else{
                    return $this->funkart;
                }
            }
            return array();
        }
    }

    /**
     * Aktualisiert einen Datensatz anhand der ID.
     * Überprüft ob ID und Name gesetzt sind.
     *
     * @param int $id
     * @param string $newName
     * @return bool
     * @throws ErrorException
     */
    public function update(int $id = 0, string $newName = ''):bool{
        if (!is_int($id) || $id == 0){
            $this->error[] = 'Keine gültige ID angegeben!';
            return false;
        }
        if ($newName !== ''){
            $sql = 'UPDATE funkarten SET name = :name WHERE id = :id';
            $sqlArgs = array(':id'=>$id, ':name'=>$newName);
            if(Database::updateDatabase($sql, $sqlArgs)){
                $this->funkart['id'] = $id;
                $this->funkart['name'] = $newName;
                return true;
            }
        }
        return false;
    }

    /**
     * Sucht anhand der Bezeichnung nach der Funkart.
     * @param string $name  Suchstring
     * @return array
     * @throws ErrorException
     */
    public function search(string $name = ''):array{
        if ($name == ''){
            return $this->getAllFunkarten();
        }else{
            $sql = 'SELECT id, name FROM funkarten WHERE UPPER(name) LIKE UPPER(:name)';
            $sqlArgs = array(':name'=>'%'.$name.'%');
            return Database::readFromDatabase($sql, $sqlArgs);
        }
    }

    /**
     * Gibt eine sortierte Liste der Funkarten aus.
     * @param string $sort  Sortierreihenfolge ASC = aufsteigend, DESC = absteigend
     * @return array
     * @throws ErrorException
     */
    public function getAllFunkarten(string $sort = 'ASC'):array{
        if($sort == strtoupper('ASC')){
            $sql = 'SELECT id, name FROM funkarten ORDER BY name';
        }else{
            $sql = 'SELECT id, name FROM funkarten ORDER BY name DESC';
        }
        return Database::readFromDatabase($sql);
}

    /*--------------------GETTER----------------*/
    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->funkart['id'];
    }
    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->funkart['name'];
    }
    /**
     * @return array
     */
    public function getFunkart(): array
    {
        return $this->funkart;
    }
    /**
     * @return array
     */
    public function getError(): array
    {
        return $this->error;
    }

    /*--------------------PRIVATE---------------*/
    /**
     * Dies Methode nimmt eine neue Funkart entgegen.
     * Sie prüft, ob die angegebene Funkart bereits vorhanden ist. Ist sie bereits vorhanden,
     * wird eine Fehlernachricht erzeugt, die Daten aus der Datenbank in die Instanz geschrieben
     * und der Rückgabewert auf false gesetzt.
     * Ist die Funkart noch nicht in der Datenbank vorhanden, wird name an die Instanzvariable name
     * weitergegeben und der Rückgabewert auf true gesetzt.
     *
     * @param string $name      Die neue Bezeichnung.
     * @return bool
     * @throws ErrorException
     */
    private function setNewName(string $name = ''):bool{
        if ($name == ''){
            $this->error[] = 'Keine Bezeichnung angegeben!';
            return false;
        }else{
            $sql = "SELECT COUNT(*) as anzahl, id, name FROM funkarten WHERE UPPER(name) = UPPER(:name)";
            $sqlArgs = array(':name'=>$name);
            $result = Database::readFromDatabase($sql, $sqlArgs);
            if ($result[0]['anzahl'] > 0){
                $this->error[] = 'Funkart existiert bereits!';
                $this->funkart = $result[0];
                return false;
            }else{
                $this->funkart['name'] = $name;
                return true;
            }

        }
    }
    /**
     * Nimmt eine ID entgegen und prüft vor der Weitergabe ob der Typ Integer ist.
     * Ist die ID gültig, wird der Tabelleninhalt in die Variable funkarten geschrieben
     * und der Rückgabewert ist true.
     *
     * @param int               $id
     * @return bool             ID vorhanden = true, nicht vorhanden = false
     * @throws ErrorException
     */
    private function setId(int $id = 0): bool
    {
        try {
            if ($id == 0) {
                $this->error[] = 'Keine gültige ID angegeben!';
                return false;
            } else {
                $sql = 'SELECT COUNT(*) AS anzahl, id, name FROM funkarten WHERE id = :id';
                $result = Database::readFromDatabase($sql, array('id' => $id));
                if ($result[0]['anzahl'] == 0) {
                    $this->error[] = 'Die ID existiert nicht!';
                    return false;
                } else {
                    $this->funkart['id'] = $result[0]['id'];
                    $this->funkart['name'] = $result[0]['name'];
                    return true;
                }
            }
        } catch (TypeError $e) {
            $this->error[] = 'Die ID muss eine ganze Zahl > 0 sein!';
            return false;
        }
    }
    /**
     * Nimmt einen Namen entgegen und prüft vor Weitergabe ob dieser bereits vorhanden ist.
     * Ist der Name bereits vorhanden, ist der Rückgabewert true.
     * Ist der Name noch nicht in der Tabelle enthalten,
     * wird dieser in die Instanzvariable geschrieben und true zurückgegeben.
     *
     * @param string            $name   Bezeichnung der Funkart
     * @return bool                     Name der Funkart vorhanden = true, nicht vorhanden = false
     * @throws ErrorException
     */
    private function setName(string $name=''): bool
    {
        if ($name == ''){
            $this->error[] = 'Kein Name angegeben!';
            return false;
        }else{
            $sql = 'SELECT id, name FROM funkarten WHERE UPPER(name) = UPPER(:name)';
            $result = Database::readFromDatabase($sql, array('name'=>$name));

            if (!$result){
                $this->error[] = 'Funkart nicht gefunden!';
                return false;
            }else {
                $this->funkart['id'] = $result[0]['id'];
                $this->funkart['name'] = $result[0]['name'];
                return true;
            }
        }
    }
}