<?php
/**
 * Die Klasse erlaubt den Zugriff auf die Datentabelle strassen.
 * @author Lars Münchhagen <lars.muenchhagen@outlook.de>
 * @version 0.0.3
 *
 */


namespace modules\mod_Strasse;


/*--------------------TODO AND FIX----------*/
# TODO refactor
# TODO überprüfen ob alle Getter und Setter erforderlich sind
# TODO nach Umbenennung Funktionen nochmals überprüfen
/*--------------------REQUIREMENTS----------*/

use ErrorException;
use system\Database;

class Strasse
{

    private array   $error      = [];
    private array   $strasse    = [];


    /*--------------------PUBLIC----------------*/
    # create
    # read
    # update
    /*--------------------PUBLIC / STATIC-------*/
    /**
     * Gibt eine sortierte Liste aller Straßennamen aus.
     *
     * @param string $sort      Sortierreihenfolge nach Name; ASC (default) = aufsteigend, DESC = absteigend
     * @return array
     * @throws ErrorException
     */
    public static function getAllStrassen(string $sort = 'ASC'):array{
        # Sortierreihenfolge festlegen
        if (strtoupper($sort !== 'ASC')){
            $sql = 'SELECT id, name FROM strassen ORDER BY name DESC';
        }else{
            $sql = 'SELECT id, name FROM strassen';
        }
        # Datenbank abfragen
        return Database::readFromDatabase($sql);
    }

    /**
     * Durchsucht die Tabelle strassen nach $name mit Wildcards vor und nach dem String.
     * @param string $name      Der Suchstring.
     * @return array
     * @throws ErrorException
     */
    public static function search(string $name = ''):array{

        # kein Suchstring vorhanden
        if ($name == ''){
            return self::getAllStrassen();
        }

        # Suchstring vorhanden
        $sql = 'SELECT id, name FROM strassen WHERE UPPER(name) LIKE UPPER(:name)';
        $sqlArgs = array(':name'=> '%'.$name.'%');
        return Database::readFromDatabase($sql, $sqlArgs);
    }


    /*--------------------GETTER----------------*/
    /**
     * Gibt den Namen der Strasse zurück.
     * @return string
     */
    public function getStrasseName():string{
        return $this->strasse['name'];
    }

    /**
     * Gibt die Id der Strasse zurück.
     * @return int
     */
    public function getId():int{
        return $this->strasse['id'];
    }

    /**
     * Gibt Id und Name der Strasse als Array zurück.
     * @return array
     */
    public function getStrasse(): array
    {
        return $this->strasse;
    }

    /**
     * Gibt die Fehlermeldungen als Array zurück.
     * @return array
     */
    public function getError():array{
        return $this->error;
    }

    /*--------------------PRIVATE---------------*/
    # prüfe ID
    # prüfe Name
}