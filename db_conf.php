<?php
require 'vendor/autoload.php'; // Asegurate de que este camino sea correcto

// Configuración base de datos
$server = "192.168.0.119";
// Conexión SQL Server
$connectionOptions = [
    "Database" => "IFLOW2",
    "Uid" => "sa",
    "PWD" => "hlIcWGTZo5"
];


$conn = sqlsrv_connect($server, $connectionOptions);

if (!$conn) {
    die(print_r(sqlsrv_errors(), true));
}
