<?php
// https://www.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/schema-representation.html#schema-representation
require __DIR__ . '/vendor/autoload.php';
use Doctrine\DBAL\Schema\Table;

$connectionParams = array(
    'dbname' => 'tgbot',
    'user' => 'tgbot',
    'password' => 'GEBPS$FE3pd,2t[D',
    'host' => 'localhost',
    'driver' => 'pdo_mysql',
);
$conn = \Doctrine\DBAL\DriverManager::getConnection($connectionParams);

$schema = new \Doctrine\DBAL\Schema\Schema();

$myTable2 = $schema->createTable("history");
$myTable2->addColumn("id", "integer", array("unsigned" => true, "autoincrement" => true));
$myTable2->addColumn("user_id", "string", array("length" => 254));
$myTable2->addColumn("inserted_at", "datetime");
$myTable2->addColumn("currency_quantity", "integer", array("length" => 3));
$myTable2->addColumn("currency_name", "string", array("length" => 30));
$myTable2->addColumn("currency_rate", "float", array("precision" => 10, "scale" => 4));
$myTable2->setPrimaryKey(array("id"));

$myTable3 = $schema->createTable("subscriptions");
$myTable3->addColumn("id", "integer", array("unsigned" => true, "autoincrement" => true));
$myTable3->addColumn("user_id", "string", array("length" => 254));
$myTable3->setPrimaryKey(array("id"));

$platform = $conn->getDatabasePlatform();

$queries = $schema->toSql($platform); // get queries to create this schema.
$dropSchema = $schema->toDropSql($platform); // get queries to safely delete this schema.

print_r($queries);
print_r($dropSchema);