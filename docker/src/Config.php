<?php
namespace Biblhertz\Manifest_Server;

use Biblhertz\Manifest_Server\utilities\PDODatabase;

/********************************************************************/
/*		CONFIG SETUP SIMPLE MANIFEST SERVER						    */
/*																	*/
/*	Author 	: 	Chris Tomlinson								        */
/********************************************************************/


class Config {

static private $data;

    /**
     * Load config file
     *
     * @param string config file path
     */
    public static function load(string $configFile) {
        self::$data = parse_ini_file($configFile);
        //print_r(self::$data);
    }

    /**
     * get config setting
     *
     * @param string the key
     *
     * @return mixed the value
     */
    public static function get($key) {
        return self::$data[$key] ?? null;
    }

/********************************************************************/
/*		STATIC VARIABLES : ESSENTIALLY CONSTANTS					*/
/*  These are loaded from config.ini file                           */
/********************************************************************/

    /********************************************************************/
    /*		LOCAL API SERVER CREDENTIALS					            */
    /*  simple authentication used to access the api                    */
    /*  set credentials here                                            */
    /********************************************************************/
    public static $API_USERNAME;     //user name
    public static $API_PASSWORD;     //password
    public static $API_KEY;          //API key for X-API-Key header auth


    /********************************************************************/
    /*		STATIC VARIABLES    					                    */
    /* these are set in the setup() method                              */
    /********************************************************************/
    public static $FILE_STORE_PATH;
    public static $FILE_STORE_URL;
    public static $INTERNAL_FILE_STORE_URL;
    public static $IIIF_VALIDATOR;
    public static $PUT_MANIFEST;
    public static $REMOVE_MANIFEST;

    //valid series that files can be uploaded to
    public static $validSeries = [];
    public static $flexibleSeries = [];

    /********************************************************************/
    /*		SET UP ENVIRONMENTS IN DOCKER CONTEXT                         */
    /********************************************************************/
    const LOCALHOST = 0;             //run on local host on bare metal
    const LOCALDOCKER = 1;           //run on local host but in docker containers
    const REMOTEDOCKER = 2;          //run on remote host in docker containers

    public static $ENVIRONMENT;


    public static function isRemoteDocker(){if(Config::$ENVIRONMENT==self::REMOTEDOCKER)return true;return false;}
    public static function isLocalDocker(){if(Config::$ENVIRONMENT==self::LOCALDOCKER)return true;return false;}
    public static function isBareMetal(){if(Config::$ENVIRONMENT==self::LOCALHOST)return true;return false;}

    /********************************************************************/
    /*		SETUP ENVIRONMENT				                            */
    /*   this depends on your deployment envirionment                   */
    /*   variables set below depending on deployment environment        */
    /* this is called from the base page class so will always be called */
    /* when a page is rendered                                          */
    /********************************************************************/
    public static function setup(){
        self::load(dirname(__DIR__) . '/config.ini');

        self::$ENVIRONMENT=Config::get("environment");
        self::$validSeries=Config::get("valid_series");
        self::$flexibleSeries=Config::get("flexible_series") ?? [];
        self::$INTERNAL_FILE_STORE_URL=Config::get("internal_file_store_url");
        self::$FILE_STORE_PATH=Config::get("file_store_path");
        self::$FILE_STORE_URL=Config::get("file_store_url");
        self::$IIIF_VALIDATOR=Config::get("iiif_validator");
        self::$PUT_MANIFEST=Config::get("put_manifest");
        self::$REMOVE_MANIFEST=Config::get("remove_manifest");
        self::$API_USERNAME=Config::get("username");
        self::$API_PASSWORD=Config::get("password");
        self::$API_KEY=Config::get("api_key") ?? '';

        PDODatabase::setHost(Config::get("db_host"));
        PDODatabase::setDatabaseName(Config::get("db_name"));
        PDODatabase::setUser(Config::get("db_user"));
        PDODatabase::setPassword(Config::get("db_password") ?? "");
    }

    public static function getDB(): PDODatabase {
        return new PDODatabase();
    }

}
?>
