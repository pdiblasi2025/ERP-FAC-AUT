<?php
require 'vendor/autoload.php'; // Asegurate de que este camino sea correcto

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$server = "192.168.0.119";

$connectionOptions = [
    "Database" => "FLETCO", // Cambia esto por el nombre de tu base de datos
    "Uid" => "sa", // Cambia esto por tu usuario de SQL Server
    "PWD" => "hlIcWGTZo5" // Cambia esto por tu contraseña de SQL Server
];

$conn = sqlsrv_connect($server, $connectionOptions);

if (!$conn) {
    die(print_r(sqlsrv_errors(), true));
}

// Limpia la tabla USR_PROSS_FACAUT antes de insertar nuevos registros
$TruncateSql = "DELETE USR_PROSS_FACAUT"; 

    
$TruncateStmt = sqlsrv_query($conn, $TruncateSql);
                        if ($TruncateStmt) {
                            echo "BORRADO EJECUTADO CON EXITO EJECUTADO:\n";
                        } else {
                            echo "Error al ejecutar consulta:\n";
                            print_r(sqlsrv_errors());
                        }                    
                   

// Buscar registros
$sql = "SELECT 
        H.SAR_FCRMVH_IDENTI,
        H.SAR_FCRMVH_NROCTA,
        V.VTMCLH_NRODOC,  
        SUM((I.SAR_FCRMVI_CANTID * I.SAR_FCRMVI_PRECIO) * 1.21) AS TOTAL
        FROM SAR_FCRMVH AS H
        JOIN SAR_FCRMVI AS I ON I.SAR_FCRMVI_IDENTI = H.SAR_FCRMVH_IDENTI
        JOIN VTMCLH AS V ON H.SAR_FCRMVH_NROCTA = V.VTMCLH_NROCTA
        WHERE H.SAR_FCRMVH_STATUS IN ('W', 'X')
        GROUP BY 
            H.SAR_FCRMVH_IDENTI, 
            H.SAR_FCRMVH_NROCTA, 
            V.VTMCLH_NRODOC
        
        ORDER BY 
            H.SAR_FCRMVH_NROCTA";


$stmt = sqlsrv_query($conn, $sql);

        // Verifica si la consulta se ejecutó correctamente
        if (!$stmt) {
                //die(print_r(sqlsrv_errors(), true));
                $errores = print_r(sqlsrv_errors(), true);
                
                // Ruta del archivo de log
                $logPath = __DIR__ . '/errores_sqlsrv.log';
                
                // Texto del log con fecha
                $mensajeLog = "[" . date('Y-m-d H:i:s') . "] ERROR SQLSRV:\n" . $errores . "\n";
                
                // Escribir en el archivo de log
                file_put_contents($logPath, $mensajeLog, FILE_APPEND | LOCK_EX);
                
                die("Error en la consulta SQL. Ver log para más detalles.");
            } else {
                // Procesar los resultados
                while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                    $identificacion = $row['SAR_FCRMVH_IDENTI'];
                    $nrocta = $row['SAR_FCRMVH_NROCTA'];
                    $nrodoc = $row['VTMCLH_NRODOC'];
                    $total = $row['TOTAL'];
                    
                    $registros[] = [
                        'SAR_FCRMVH_IDENTI' => $identificacion,
                        'SAR_FCRMVH_NROCTA' => $nrocta,
                        'VTMCLH_NRODOC' => $nrodoc,
                        'TOTAL' => $total
                    ];
                    
                    
                }
                
                foreach ($registros as $r) {
                    $columns = array_keys($r);
                    $values = array_values($r);
                    $columnList = implode(", ", $columns);
                    $placeholders = implode(", ", array_fill(0, count($columns), "?"));
                    
                    $insertSql = "INSERT INTO USR_PROSS_FACAUT ($columnList) VALUES ($placeholders)";    

                    //var_dump($r);

                    echo "SQL: $insertSql\n";
                    print_r($values);

                    $insertStmt = sqlsrv_query($conn, $insertSql, $values);

                    if ($insertStmt) {
                        $insertados++;
                    } else {
                        echo "Error al ejecutar consulta:\n";
                        print_r(sqlsrv_errors());
                    }

                    }

                

       
            }


// Hasta aca tengo todos las facturas que tengo que procesar y evaluar.
// Marco las facturas que no superan los monto maxino declarado por ARCA.

$updateSql = "UPDATE USR_PROSS_FACAUT
                 SET USR_PADRON = 'N'
               WHERE TOTAL <= 1357480";

$updateStmt = sqlsrv_query($conn, $updateSql);

                    if ($updateStmt) {
                        echo "UPDATE N EJECUTADO:\n";
                    } else {
                        echo "Error al ejecutar consulta:\n";
                        print_r(sqlsrv_errors());
                    }

// Verifico si los cuit con el campo USR_PADRON en NULL estan en la tabla SAR_VTTFC1

$update2Sql = "UPDATE USR_PROSS_FACAUT
                SET USR_PADRON = 'N'
                WHERE VTMCLH_NRODOC NOT IN (SELECT SAR_VTTFC1_VTTCUI FROM SAR_VTTFC1)
                AND USR_PADRON IS NULL"; //no estan el el padron

$update2Stmt = sqlsrv_query($conn, $update2Sql);

                    if ($update2Stmt) {
                        echo "UPDATE V EJECUTADO:\n";
                    } else {
                        echo "Error al ejecutar consulta:\n";
                        print_r(sqlsrv_errors());
                    }



// Dados de baja en el padron.

$update3Sql = "UPDATE USR_PROSS_FACAUT
                SET USR_PADRON = 'N'
                WHERE VTMCLH_NRODOC in (SELECT SAR_VTTFC1_VTTCUI FROM SAR_VTTFC1 WHERE SAR_VT_DEBAJA = 'S') 
                AND USR_PADRON IS NULL"; // Estan en el padron pero dados de baja.

$update3Stmt = sqlsrv_query($conn, $update3Sql);

                    if ($update3Stmt) {
                        echo "UPDATE B EJECUTADO:\n";
                    } else {
                        echo "Error al ejecutar consulta:\n";
                        print_r(sqlsrv_errors());
                    }

// Activos en el padron.
    
$update4Sql = "UPDATE USR_PROSS_FACAUT
                SET USR_PADRON = 'P'
                WHERE VTMCLH_NRODOC in (SELECT SAR_VTTFC1_VTTCUI FROM SAR_VTTFC1 WHERE SAR_VT_DEBAJA = 'N') 
                AND USR_PADRON IS NULL";

$update4Stmt = sqlsrv_query($conn, $update4Sql);

                    if ($update4Stmt) {
                        echo "UPDATE A EJECUTADO:\n";
                    } else {
                        echo "Error al ejecutar consulta:\n";
                        print_r(sqlsrv_errors());
                    }

// Aca debo iniciar los cambios en la tabla SAR_FCRMVH_STATUS para marcar las facturas como listas a procesar procesadas.

/**
UPDATE SAR_FCRMVH
SET SAR_FCRMVH_STATUS = 'N'
FROM SAR_FCRMVH FCRMVH
INNER JOIN USR_PROSS_FACAUT FACAUT 
    ON FCRMVH.SAR_FCRMVH_IDENTI = FACAUT.SAR_FCRMVH_IDENTI
    AND FCRMVH.SAR_FCRMVH_NROCTA = FACAUT.SAR_FCRMVH_NROCTA
WHERE FACAUT.USR_PADRON = 'N';
 */
$update5Sql = "UPDATE SAR_FCRMVH
                SET SAR_FCRMVH_STATUS = 'N'
                FROM SAR_FCRMVH FCRMVH
                INNER JOIN USR_PROSS_FACAUT FACAUT 
                    ON FCRMVH.SAR_FCRMVH_IDENTI = FACAUT.SAR_FCRMVH_IDENTI
                    AND FCRMVH.SAR_FCRMVH_NROCTA = FACAUT.SAR_FCRMVH_NROCTA
                WHERE FACAUT.USR_PADRON = 'N'";
$update5Stmt = sqlsrv_query($conn, $update5Sql);

                    if ($update5Stmt) {
                        echo "UPDATE SAR_FCRMVH CUIT NO ALCANZADOS EJECUTADO:\n";
                    } else {
                        echo "Error al ejecutar consulta:\n";
                        print_r(sqlsrv_errors());
                    }

/*
UPDATE SAR_FCRMVH
SET SAR_FCRMVH_CIRCOM = 0410,
    SAR_FCRMVH_CIRAPL = 0410,
    USR_SAR_VIRT_TIPDOP = 27,
    USR_SAR_VIRT_VALDOP = SCA,
    SAR_FCRMVH_STATUS = "N"
FROM SAR_FCRMVH FCRMVH
INNER JOIN USR_PROSS_FACAUT FACAUT 
    ON FCRMVH.SAR_FCRMVH_IDENTI = FACAUT.SAR_FCRMVH_IDENTI
    AND FCRMVH.SAR_FCRMVH_NROCTA = FACAUT.SAR_FCRMVH_NROCTA
WHERE FACAUT.USR_PADRON = 'N';
*/
$update6Sql = "UPDATE SAR_FCRMVH
                SET SAR_FCRMVH_CIRCOM = 0410,
                    SAR_FCRMVH_CIRAPL = 0410,
                    USR_SAR_VIRT_TIPDOP = 27,
                    USR_SAR_VIRT_VALDOP = 'SCA',
                    SAR_FCRMVH_STATUS = 'N'
                FROM SAR_FCRMVH FCRMVH
                INNER JOIN USR_PROSS_FACAUT FACAUT 
                    ON FCRMVH.SAR_FCRMVH_IDENTI = FACAUT.SAR_FCRMVH_IDENTI
                    AND FCRMVH.SAR_FCRMVH_NROCTA = FACAUT.SAR_FCRMVH_NROCTA
                WHERE FACAUT.USR_PADRON = 'P'";
$update6Stmt = sqlsrv_query($conn, $update6Sql);

                    if ($update6Stmt) {
                        echo "UPDATE SAR_FCRMVH CUIT ALCANZADOS EJECUTADO:\n";
                    } else {
                        echo "Error al ejecutar consulta:\n";
                        print_r(sqlsrv_errors());
                    }



sqlsrv_close($conn);


die();





