<?php
require 'vendor/autoload.php'; // Asegurate de que este camino sea correcto

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Configuración base de datos
$server = "192.168.0.119";
$database = "IFLOW2";
$username = "sa";
$password = "hlIcWGTZo5";

// Configuración correo
$correoDestino = "pdiblasi@iflow21.com";
$correoOrigen = "pdiblasi.iflow@gmail.com"; // Debe ser Gmail
$contrasenaCorreo = "P@blo1979"; // Ver más abajo
$nombreRemitente = "Notificaciones Sistema";

// Conexión SQL Server
$connectionOptions = [
    "Database" => $database,
    "Uid" => $username,
    "PWD" => $password
];
$conn = sqlsrv_connect($server, $connectionOptions);
if (!$conn) {
    die(print_r(sqlsrv_errors(), true));
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
        WHERE H.SAR_FCRMVH_STATUS = 'X'
        GROUP BY 
            H.SAR_FCRMVH_IDENTI, 
            H.SAR_FCRMVH_NROCTA, 
            V.VTMCLH_NRODOC
        HAVING 
            SUM((I.SAR_FCRMVI_CANTID * I.SAR_FCRMVI_PRECIO) * 1.21) >= 1357480
        ORDER BY 
            H.SAR_FCRMVH_NROCTA";

$stmt = sqlsrv_query($conn, $sql);



$registros = [];
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $registros[] = $row;
    
}

// Insertar en USR_SAR_FCRMVH
$insertados = 0;
foreach ($registros as $r) {
    $columns = array_keys($r);
    $values = array_values($r);

    $columnList = implode(", ", $columns);
    $placeholders = implode(", ", array_fill(0, count($columns), "?"));

    // ESTA TABLA LA TENGO QUE CREAR EN LA BASE DE DATOS DE CADA EMPRESA A PROCESAR ESTA EN EL ARCHIVO SQL.

    $insertSql = "INSERT INTO USR_PROSS_FACAUT ($columnList) VALUES ($placeholders)";
    $insertStmt = sqlsrv_query($conn, $insertSql, $values);

    if ($insertStmt) {
        $insertados++;
    }
}

sqlsrv_close($conn);



// Preparar correo con PHPMailer
// $mail = new PHPMailer(true);

// try {
//     // Configuración SMTP
//     $mail->isSMTP();
//     $mail->Host       = 'smtp.gmail.com';
//     $mail->SMTPAuth   = true;
//     $mail->Username   = $correoOrigen;
//     $mail->Password   = $contrasenaCorreo;
//     $mail->SMTPSecure = 'tls';
//     $mail->Port       = 587;

//     // Emisor y destinatario
//     $mail->setFrom($correoOrigen, $nombreRemitente);
//     $mail->addAddress($correoDestino);

//     // Contenido del correo
//     $mail->isHTML(true);
//     $mail->Subject = 'Reporte de proceso automático';

//     $fecha = date("Y-m-d H:i:s");
//     $mail->Body = "
//         <h3>Proceso ejecutado</h3>
//         <p><strong>Fecha:</strong> $fecha</p>
//         <p><strong>Registros encontrados:</strong> " . count($registros) . "</p>
//         <p><strong>Registros insertados:</strong> $insertados</p>
//     ";

//     $mail->send();
//     echo "Correo enviado correctamente.\n";
// } catch (Exception $e) {
//     echo "Error al enviar correo: {$mail->ErrorInfo}\n";
// }
