<?php
/**
 * This is project's console commands configuration for Robo task runner.
 *
 * @see http://robo.li/
 */
class RoboFile extends \Robo\Tasks
{
    private function includeCientConfig($client){
        include dirname(__FILE__)."/../config.base.php";
        include ALL_CLIENT_BASE_PATH. $client . "/config.php";

        include (dirname(__FILE__)."/../include.common.php");

        include(dirname(__FILE__)."/../server.includes.inc.php");
    }

    function hello(array $world)
    {
        $this->say("Hello, " . implode(', ', $world));
    }

    function migrate($client, $action){
        $this->includeCientConfig($client);
        $this->say("DB Migrating, " . $action . " for ". $client. "-" . APP_BASE_PATH);
        $migrationManager = new MigrationManager();
        $res = $migrationManager->runMigration($action);
        $this->say("DB Migrating Result : " . print_r($res, true));
    }
}