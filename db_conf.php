<?php
require 'vendor/autoload.php'; // Asegurate de que este camino sea correcto

// Configuración base de datos
$server = "xxx.168.0.xxx";
// $database = "xxLOxx";
// $username = "sa";
// $password = "*******";

// Conexión SQL Server
// $connectionOptions = [
//     "Database" => $database,
//     "Uid" => $username,
//     "PWD" => $password
// ];

// Conexión SQL Server
$connectionOptions = [
    "Database" => "xxLOx",
    "Uid" => "sa",
    "PWD" => "*******"
];


$conn = sqlsrv_connect($server, $connectionOptions);

if (!$conn) {
    die(print_r(sqlsrv_errors(), true));
}
