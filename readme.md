# Procesos de la regularizacion de Facturas provinientes desde MILOGA. 


* 1ro busco los registros que estan en ('E','N','X') en la tabla **SAR_FCRMVH**  

            ```SQL
            SELECT 
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
            ORDER BY 
                H.SAR_FCRMVH_NROCTA;
            


* 2do busco los en mi tabla de usuario **(USR_PROSS_FACAUT)** los registros cuyo valor Total se menores o igual a  3958316
    y marco el campo USR_PADRON en "N"

* 3ero Busco los **VTMCLH_NRODOC** de la tabla **USR_PROSS_FACAUT QUE NO TENGA UNA 'N'** EN EL CAMPO **USR_PADRON** en la tabla  **SAR_VTTFC1** Y verifico que el campo **SAR_VT_DEBAJA** sea igual a "N" , en ese caso marco el campo **USR_PADRON** en "N" en la tabla **USR_PROSS_FACAUT**

* 4to Busco los **VTMCLH_NRODOC** de la tabla **USR_PROSS_FACAUT** QUE NO TENGA UNA 'N' EN EL CAMPO USR_PADRON en la tabla  **SAR_VTTFC1** Y verifico que 
el campo **SAR_VT_DEBAJA** sea igual a "S", en ese caso marco el campo **USR_PADRON** en "S" en la tabla **USR_PROSS_FACAUT**.

* 5to Recorro la tabla **USR_PROSS_FACAUT** y busco los registros que contengan el campo **USR_PADRON** en "N" y actualizo esos registros en la tabla
SAR_FCRMVH colocando el campo **SAR_FCRMVH_STATUS** en "N" para que las facturas sean tomadas por el proceso de softland.

* 6to Recorro la tabla **USR_PROSS_FACAUT** y busco los registros que contengan el campo **USR_PADRON** en "S"  y actualizo esos registros en la tabla

### SAR_FCRMVH colocando el campo :

    SAR_FCRMVH_CIRCOM = 0410
    SAR_FCRMVH_CIRAPL = 0410
    USR_SAR_VIRT_TIPDOP = 27
    USR_SAR_VIRT_VALDOP = SCA
    SAR_FCRMVH_STATUS = "N"

* 7mo Por ultimo recorro la tabla USR_PROSS_FACAUT wn informo por  EMAIL los  VTMCLH_NRODOC Y SAR_FCRMVH_IDENTI que fueron procesados y estan listos para que el proceso  AUTOMATICO DE SOFTLAND se ejecute.

* 8vo Si todo fue ejecutado sin errores hago un DROP de la tabla  USR_PROSS_FACAUT para que quede limpia para la proxima corrida del proceso.


* link acrca : 
https://servicioscf.afip.gob.ar/FCEServicioConsulta/api/fceconsulta.aspx/getGrandesEmpresas












