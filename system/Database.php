<?php
/*
 * Copyright (c) 2021.  Lars Münchhagen
 * email: lars.muenchhagen@outlook.de
 */

namespace system;
#TODO: Zugangsdaten auslagern und sichern
/*-----------------REQUIREMENTS--------*/


use ErrorException;
use Exception;
use PDO;

class Database
{
    /*-------------VARIABLES-----------*/
    private static string $database = "objekte";
    private static string $user = "ofAdmin";
    private static string $password = "ich@root";
    private static string $host = "localhost";
    private static array $args = array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4");
    private static $pdo;

    private static string $errFilename = 'db.log';

    private static string $dbConnErr        = " Datenbankverbindungsfehler!\n"." --> ";
    private static string $dbConnSuccess    = " Datenbankverbindung erfolgreich!\n";
    private static string $dbConnClose      = " Datenbankverbindung geschlossen!\n";
    private static string $dbInsErr         = " Fehler beim Einfügen eines Datensatzes!\n";
    private static string $dbUpdErr         = " Fehler beim Schreiben oder Aktualisieren eines oder mehrerer Datensätze!\n";
    private static string $dbReadErr        = " Fehler beim Lesen eines oder mehrerer Datensätze!\n";
    private static string $lineSeparator    = "-";
    private static int    $lineSeparatorMultiplier = 70;




    /*-------------METHODS-------------*/
    public static function connectDB(): PDO
    {
        try {
            self::$pdo = new PDO("mysql:host=".self::$host.";dbname=".self::$database.";charset=utf8mb4",self::$user,self::$password,self::$args);
            return self::$pdo;
        } catch (Exception $e) {
            #$path = $_SERVER['DOCUMENT_ROOT'].DIRECTORY_SEPARATOR.'system'.DIRECTORY_SEPARATOR.'logs'.DIRECTORY_SEPARATOR;
            $errText = date('Y-m-d - H:i:s').self::$dbConnErr.$e->getMessage()."\n";
            error_log($errText,3,LOGS.self::$errFilename);
        }
    }

    public static function closeDB(){
        self::$pdo = null;
    }

    /**
     * öffentliche Methode zum Insert in die Datenbank
     * überprüft die übergebenen Parameter und gibt sie an
     * insertDB weiter
     * nimmt das Ergebnis der Datenbankaktion entgegen und
     * gibt es an den Aufrufer zurück
     *
     * @param string $sqlString
     * @param array $args
     * @return bool
     * @throws Exception
     */
    public static function insertIntoDatabase(string $sqlString ='', array $args=[]): bool
    {
        if ($sqlString == '' OR sizeof($args) == 0) return false;

        return self::writeDatabaseEntry($sqlString,$args);

    }

    /**
     * öffentliche Methode zum Lesen in der Datenbank
     * @param string $sql
     * @param array $args
     * @return array|false
     * @throws ErrorException
     */
    public static function readFromDatabase(string $sql, array $args = []){

        if ($sql == '') return false;
        return self::readDatabaseEntry($sql, $args);
    }

    /**
     * öffentliche Methode zum Aktualisieren von Datenbankeinträgen
     * @param string $sql
     * @param array $args
     * @return bool|false
     * @throws ErrorException
     */
    public static function updateDatabase(string $sql ='', array $args=[]): bool
    {
        # überprüfung
        if ($sql == '' OR sizeof($args) == 0) return false;
        return self::writeDatabaseEntry($sql, $args);
    }

    /*-------------PRIVATES-------------*/

    private static function setLine(): string
    {
        return str_repeat(self::$lineSeparator, self::$lineSeparatorMultiplier)."\n";
    }

    /**
     * Schreibender Datenbankzugriff
     * @param string $sql
     * @param array $args
     * @return bool
     * @throws ErrorException
     */
    private static function writeDatabaseEntry(string $sql, array $args): bool
    {
        # setze Error_Warning auf Exception um ein Abfangen zu ermöglichen
        set_error_handler(function ($severity, $message, $file, $line) {
            throw new ErrorException($message, $severity, $severity, $file, $line);
        });

        $result = false;
        $pdo = self::connectDB();
        try {
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute($args);

        } catch (Exception $e){
            $errText = date('Y-m-d - H:i:s').self::$dbUpdErr.$e->getMessage()."\n";
            $errText .= "File: ".$e->getFile(). "\nRow: ".$e->getLine()."\nTrace:\n".$e->getTraceAsString()."\n".self::setLine();
            error_log($errText,3,LOGS.DIRECTORY_SEPARATOR.self::$errFilename);
        } finally {
            Database::closeDB();
            # setze Exceptions auf default zurück
            restore_error_handler();
            return $result;
        }

    }

    /**
     * ermöglicht lesenden Datenbankzugriff
     * @param string $sql
     * @param array $args
     * @return array
     * @throws ErrorException
     */
    private static function readDatabaseEntry(string $sql, array $args = []): array
    {

        # setze Error_Warning auf Exception um ein Abfangen zu ermöglichen
        set_error_handler(function ($severity, $message, $file, $line) {
            throw new ErrorException($message, $severity, $severity, $file, $line);
        });

        $result = [];
        $pdo = self::connectDB();
        try {
            $stmt = $pdo->prepare($sql);
            if (empty($args)){
                # sql String ohne notwendige Argumente
                $stmt->execute();
            }else{
                # Argumente für sql vorhanden
                $stmt->execute($args);
            }
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e){
            $errText = date('Y-m-d - H:i:s').self::$dbInsErr.$e->getMessage()."\n".self::setLine();
            error_log($errText,3,LOGS.DIRECTORY_SEPARATOR.self::$errFilename);
        } finally {
            Database::closeDB();
            # setze Exceptions auf default zurück
            restore_error_handler();
            return $result;
        }
    }

}