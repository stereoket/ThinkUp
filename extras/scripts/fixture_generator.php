<?php
if (!isset($argv[1])) {
    echo "You must specify an SQL query as the first parameter.\n";
    exit;
} else {
    if (strpos($argv[1], 'SELECT') !== 0) {
        echo "Parameter must be a SELECT query. Please try again.\n";
        exit;
    }
    $query = strval($argv[1]);
}

require_once dirname(__FILE__) . '/../../webapp/init.php';

$config = Config::getInstance();
$pdo = new PDO(PDODAO::getConnectString($config), $config->getValue('db_user'), $config->getValue('db_password'));

$results = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);

$matches = array();
preg_match('/FROM '.$config->getValue('table_prefix').'(.+?) /', $query, $matches);
$table_name = $matches[1];

foreach ($results as $result) {
    echo "\$builders[] = FixtureBuilder::build('$table_name', array(\n";
    $fixture_data = '';
    foreach ($result as $column => $value) {
        $fixture_data .= "    '$column' => ";
        switch (gettype($value)) {
            case 'boolean': $fixture_data .= $value ? 'true' : 'false'; break;
            case 'string': $fixture_data .= "'" . $value . "'"; break;
            case 'NULL': $fixture_data .= 'null';
            default: $fixture_data .= $value; break;
        }
        $fixture_data .= ",\n";
    }

    echo rtrim($fixture_data, ",\n") . "\n);\n";
}