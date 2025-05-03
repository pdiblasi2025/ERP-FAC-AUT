<?php
require 'vendor/autoload.php'; // Asegurate de que este camino sea correcto

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Configuración base de datos
$server = "vSOFTLAND";
$database = "IFLOW2";
$username = "sa";
$password = "hllcWGTZo5";

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
$sql = "SELECT SAR_FCRMVH_IDENTI,
               SAR_FCRMVH_NROCTA ,
               VTMCLH_NRODOC, 
               SUM ((SAR_FCRMVI_CANTID * SAR_FCRMVI_PRECIO)1.21) AS USR_IMP_TOTAL
        FROM SAR_FCRMVH AS H
        JOIN SAR_FCRMVI AS I ON i.SAR_FCRMVI_IDENTI = h.SAR_FCRMVH_IDENTI
        JOIN VTMCLH AS v ON h.SAR_FCRMVH_NROCTA = v.VTMCLH_NROCTA
        WHERE h.SAR_FCRMVH_STATUS = 'X'
        GROUP BY SAR_FCRMVH_IDENTI, SAR_FCRMVH_NROCTA, vtmclh_nrodoc
        HAVING  SUM ((SAR_FCRMVI_CANTIDSAR_FCRMVI_PRECIO)*1.21) >= 1357480
        ORDER BY 2";
$stmt = sqlsrv_query($conn, $sql);

if (!$stmt) {
    die(print_r(sqlsrv_errors(), true));
}

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

    // ESTA TABLA LA TENGO QUE CREAR EN LA BASE DE DATOS DE CADA EMPRESA A PROCESAR.

    $insertSql = "INSERT INTO USR_PROSS_FACAUT ($columnList) VALUES ($placeholders)";
    $insertStmt = sqlsrv_query($conn, $insertSql, $values);

    if ($insertStmt) {
        $insertados++;
    }
}

sqlsrv_close($conn);

// Preparar correo con PHPMailer
$mail = new PHPMailer(true);

try {
    // Configuración SMTP
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = $correoOrigen;
    $mail->Password   = $contrasenaCorreo;
    $mail->SMTPSecure = 'tls';
    $mail->Port       = 587;

    // Emisor y destinatario
    $mail->setFrom($correoOrigen, $nombreRemitente);
    $mail->addAddress($correoDestino);

    // Contenido del correo
    $mail->isHTML(true);
    $mail->Subject = 'Reporte de proceso automático';

    $fecha = date("Y-m-d H:i:s");
    $mail->Body = "
        <h3>Proceso ejecutado</h3>
        <p><strong>Fecha:</strong> $fecha</p>
        <p><strong>Registros encontrados:</strong> " . count($registros) . "</p>
        <p><strong>Registros insertados:</strong> $insertados</p>
    ";

    $mail->send();
    echo "Correo enviado correctamente.\n";
} catch (Exception $e) {
    echo "Error al enviar correo: {$mail->ErrorInfo}\n";
}
