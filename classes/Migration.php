<?php


abstract class AbstractMigration
{
    protected $file;

    public function __construct($file)
    {
        $this->file = $file;
    }

    public function getMigrationEntity(){
        $migration = new Migration();
        $migration->Load("file = ?",$this->file);
        if(!empty($migration->id) && $migration->file == $this->file){
            return $migration;
        }
        return null;
    }
}


class MigrationManager{
    public function runAllPendingMigrations(){

        $adminModulesTemp = array();
        $ams = scandir(CLIENT_PATH.'/admin/');
        $currentLocation = 0;
        foreach($ams as $am) {
            if (is_dir(CLIENT_PATH . '/admin/' . $am) && $am != '.' && $am != '..') {

            }
        }
            
    }
}