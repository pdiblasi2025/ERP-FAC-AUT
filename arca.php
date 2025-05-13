<?php

require 'db_conf.php';
require 'vendor/autoload.php'; 

class Arca {

    public function GetCuit($cuit) {
    $cuit = preg_replace('/[^0-9]/', '', $cuit); // Eliminar caracteres no numéricos
    return $cuit;
        }



    public function DeleteArcaPadron() {
        global $conn;
        // Eliminar registros de la tabla USR_PADRON_ARCA
        $sql = "DELETE FROM USR_PADRON_ARCA";
        $stmt = sqlsrv_query($conn, $sql);
        if ($stmt === false) {
            echo "Error al eliminar datos:\n";
            print_r(sqlsrv_errors());
        } else {
            echo "Datos eliminados correctamente.\n";
        }

       // sqlsrv_close($conn);
    }



        public function GetArcaPadron() {
                global $conn;  
               

                    if ($conn === false) {
                        die(print_r(sqlsrv_errors(), true));
                    }

                    // Llamada al servicio AFIP
                    $url = "https://servicioscf.afip.gob.ar/FCEServicioConsulta/api/fceconsulta.aspx/getGrandesEmpresas";

                    $curl = curl_init($url);
                    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($curl, CURLOPT_POST, true);
                    curl_setopt($curl, CURLOPT_POSTFIELDS, '{}');
                    curl_setopt($curl, CURLOPT_HTTPHEADER, [
                        "Content-Type: application/json",
                        "Content-Length: " . strlen('{}')
                    ]);

                    $response = curl_exec($curl);
                    curl_close($curl);

                    if (!$response) {
                        die("Error en la solicitud al servicio.");
                    }

                    // Decodificar JSON
                    $data = json_decode($response, true);



                    if (!$response) {
                        die("Error en la solicitud al servicio.");
                    }

                    // Decodificar primer nivel
                    $data = json_decode($response, true);
                    if (!isset($data['d'])) {
                        die("No se encontró la clave 'd' en la respuesta.");
                    }

                    // Decodificar el string JSON contenido en "d"
                    $empresas = json_decode($data['d'], true);
                    if (!is_array($empresas)) {
                        die("La clave 'd' no contiene un JSON válido.");
                    }

                    $insertados = 0;

                    // Insertar en SQL Server
                    foreach ($empresas as $empresa) {
                        $cuit = $empresa['Cuit'] ?? null;
                        $denominacion = $empresa['Denominacion'] ?? null;
                        $actividad = $empresa['Actividad_Principal'] ?? null;
                        $fechaInicio = $empresa['Fecha_Inicio'] ?? null;

                        // Convertir fecha de dd/mm/yyyy a yyyy-mm-dd
                        $fechaSQL = DateTime::createFromFormat('d/m/Y', $fechaInicio);
                        $fechaSQL = $fechaSQL ? $fechaSQL->format('Y-m-d') : null;

                        $sql = "INSERT INTO USR_PADRON_ARCA (ARCA_CUIT, ARCA_DENOMINACION, ARCA_ACT_PRINCIPAL, ARCA_VIGENCIA_DESDE)
                                VALUES (?, ?, ?, ?)";

                        $params = [$cuit, $denominacion, $actividad, $fechaSQL];

                        $stmt = sqlsrv_query($conn, $sql, $params);
                        if ($stmt === false) {
                            echo "❌ Error al insertar CUIT $cuit:\n";
                            print_r(sqlsrv_errors());
                        } else {
                            $insertados++;
                        }
                    }

                    sqlsrv_close($conn);

                    echo "Empresas insertadas: $insertados\n"; 
                            
                            }
        // Cerrar la conexión
    
}

?>