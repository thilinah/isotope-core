<?php


abstract class AbstractMigration
{
    protected $file;

    private $db;

    protected $lastError;

    public function __construct($file)
    {
        $this->file = $file;
    }

    public function up(){
        return true;
    }

    public function down(){
        return true;
    }

    protected function db(){
        if($this->db == null){
            $this->db = NewADOConnection('mysqli');
            $res = $this->db->Connect(APP_HOST, APP_USERNAME, APP_PASSWORD, APP_DB);
        }
        return $this->db;
    }

    public function getLastError(){
        return $this->lastError;
    }

    /*
    public function up()
    {
        $sql = <<<'SQL'
        create table `Migrations` (
            `id` bigint(20) NOT NULL AUTO_INCREMENT,
            `file` varchar(300) NOT NULL,
            `version` int(11) NOT NULL,
            `created` DATETIME default '0000-00-00 00:00:00',
            `updated` DATETIME default '0000-00-00 00:00:00',
            `status` enum('Pending','Up','Down','UpError','DownError') default 'Pending',
            `last_error` varchar(500) NULL,
            primary key  (`id`),
            unique key `KEY_Migrations_file` (`file`),
            index `KEY_Migrations_status` (`status`),
            index `KEY_Migrations_status` (`version`)
        ) engine=innodb default charset=utf8;
SQL;
        return $this->db()->Execute($sql);
    }

    public function down()
    {
        return $this->db()->Execute('DROP TABLE Migrations');
    }
    */
}


class MigrationManager{

    private static $MIGRATION_PATH = APP_BASE_PATH.'/db_migrations/';
    
    protected $db = null;

    public function getMigrationById($id){
        $migration = new Migration();
        $migration->Load("id = ?",array($id));
        return $migration;
    }

    public function getCurrentMigrations(){
        $migration = new Migration();
        return $migration->Find("1 = 1");
    }

    public function getPendingMigrations(){
        $migration = new Migration();
        return $migration->Find("status = ?",array('Pending'));
    }

    public function getLastRunMigration($statuses){
        $migration = new Migration();
        return $migration->Find("status in (?) order by updated desc limit 1",array(implode(",",$statuses)));
    }

    public function queueMigrations(){

        $migrations = array();
        $ams = scandir(self::$MIGRATION_PATH);
        foreach($ams as $am) {
            if (is_file(self::$MIGRATION_PATH . $am)) {
                $migrations[$am] = self::$MIGRATION_PATH . $am;
            }
        }

        ksort($migrations);

        if(!empty($migrations)){
            $migrationsInDB = $this->getCurrentMigrations();
            $migrationsInDBKeyVal = array();
            foreach ($migrationsInDB as $migration){
                $migrationsInDBKeyVal[$migration->file] = $migration;
            }

            foreach($migrations as $file => $path){
                if(!isset($migrationsInDBKeyVal[$file])){
                    $migration = new Migration();
                    $migration->file = $file;
                    $parts = explode("_",$file);
                    $migration->version = intval($parts[1]);
                    $migration->created = date("Y-m-d H:i:s");
                    $migration->updated = date("Y-m-d H:i:s");
                    $migration->status = 'Pending';
                    $migration->Save();
                }
            }
        }
    }

    public function runPendingMigrations(){
        $migrations = $this->getPendingMigrations();
        foreach ($migrations as $migration){
            $this->runMigrationUp($migration);
        }
    }

    public function runMigration($action){
        $method = 'runMigration'.ucfirst($action);
        $statuses = array();
        if($action == 'up'){
            $statuses = array("Pending","Down");
        }else if($action == 'down'){
            $statuses = array("Up");
        }else{
            return false;
        }
        $migrations = $this->getLastRunMigration($statuses);
        if(count($migrations) > 0){
            $this->$method($migrations[0]);
            return $this->getMigrationById($migrations[0]->id);
        }else{
            $this->queueMigrations();
            $migrations = $this->getPendingMigrations();
            if(count($migrations) > 0){
                $this->$method($migrations[0]);
                return $this->getMigrationById($migrations[0]->id);
            }
        }

        return false;
    }


    protected function runMigrationUp($migration){
        if($migration->status != 'Pending' && $migration->status != 'UpError' && $migration->status != 'Down'){
            return false;    
        }
        
        $path = self::$MIGRATION_PATH . $migration->file;
        if(!file_exists($path)){
            return false;
        }
        
        include $path;
        $migrationName = str_replace('.php','',$migration->file);
        $migClass = new $migrationName;
        $res = $migClass->up();
        if(!$res){
            $migration->last_error = $migClass->getLastError();
            $migration->status = "UpError";
            $migration->updated = date("Y-m-d H:i:s");
            $migration->Save();
        }

        $migration->status = "Up";
        $migration->updated = date("Y-m-d H:i:s");
        $migration->Save();
        return $res;

    }

    protected function runMigrationDown($migration){
        if($migration->status != 'Up' && $migration->status != 'UpError'){
            return false;
        }

        $path = self::$MIGRATION_PATH . $migration->file;
        if(!file_exists($path)){
            return false;
        }

        include $path;
        $migrationName = str_replace('.php','',$migration->file);
        $migClass = new $migrationName;
        $res = $migClass->down();
        if(!$res){
            $migration->last_error = $migClass->getLastError();
            $migration->status = "DownError";
            $migration->updated = date("Y-m-d H:i:s");
            $migration->Save();
        }

        $migration->status = "Down";
        $migration->updated = date("Y-m-d H:i:s");
        $migration->Save();
        return $res;
    }

    
}