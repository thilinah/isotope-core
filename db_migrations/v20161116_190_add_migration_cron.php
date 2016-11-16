<?php
class v20161116_190_add_migration_cron extends AbstractMigration{
    
    public function up(){

        $sql = <<<'SQL'
        Alter table Crons add unique key `KEY_Crons_name` (`name`);
SQL;


        return $this->db()->Execute($sql);
    }

    public function down(){

        $sql = <<<'SQL'
        Alter table Crons drop key `KEY_Crons_name`;
SQL;

        return $this->db()->Execute($sql);
    }
    
}