Integración con ApiMarketEndpoints para la consulta de NSS y Trayectoria Laboral a través de ApiMarket.
Obtener NSS por CURPConsulta el Número de Seguridad Social (NSS) de un candidato.
URL: /api/v1/apimarket/nss
Método: POSTBody:{
  "curp": "BAMD990126HTSNRJ03"
}
Respuesta Exitosa (200 OK):{
    "status": true,
    "message": "NSS obtenido correctamente.",
    "data": {
        "servicio": "obtener-nss",
        "resultado": {
            "success": true,
            "codigoValidacion": "gWh4ObDWYsRgP84Rhv2B",
            "message": "Obtenido con éxito",
            "status": 200,
            "data": {
                "curp": "BAMD990126HTSNRJ03",
                "nss": "84169950577",
                "nombre": "DJILLALI ADAIR",
                "apaterno": "BANDA",
                "amaterno": "MIRANDA",
                "fecNacimiento": "1999-01-26",
                "sexo": "H"
            }
        }
    }
}
Consultar Trayectoria Laboral
Consulta el historial laboral usando un NSS o CURP.
URL: /api/v1/apimarket/trayectoria
Método: POSTBody (Opción 1: por NSS):{
  "nss": "84169950577"
}
Body (Opción 2: por CURP):{
  "curp": "BAMD990126HTSNRJ03"
}
Respuesta Exitosa (200 OK):{
    "status": true,
    "message": "Trayectoria laboral obtenida correctamente.",
    "data": {
        "servicio": "consultar-historial-laboral-lite",
        "resultado": {
            "success": true,
            "codigoValidacion": "375E9798-3452-4EB8-9950-C9500DCAF9C6",
            "message": "Reporte obtenido con exito",
            "status": 200,
            "data": {
                "fecha_emision": "15 de octubre 2025, 15:45:05",
                "folio": "375E9798-3452-4EB8-9950-C9500DCAF9C6",
                "nombre": "DJILLALI ADAIR BANDA MIRANDA",
                "curp": "BAMD990126HTSNRJ03",
                "f_nacimiento": "26/01/99",
                "edad": 26,
                "nss": "84169950577",
                "rfc": "BAMD990126BI5",
                "dias_desempleado": 0,
                "total_empleos": 2,
                "ultimo_sdi": 292.54,
                "score": {
                    "resultado": 24.1,
                    "casificacion": "No apto",
                    "nivel": "Baja estabilidad",
                    "recomendacion": "Se observa alta frecuencia de cambios laborales...",
                    "alerta": "Se identificaron 1 empleos con duracion menor a 6 meses, lo cual eleva el riesgo."
                },
                "historialLaboral": [
                    {
                        "nrp": "E8730974109",
                        "empresa": "NEXTGEN TECHNOLOGIES SA DE CV",
                        "alta": "16/09/2022",
                        "baja": "01/10/2025",
                        "sdi": 479.95,
                        "duracion": 1111
                    },
                    {
                        "nrp": "F0363064109",
                        "empresa": "RRHH INGENIA SOLUCIONES EN RECURSOS HUMA NOS DE MEX",
                        "alta": "06/10/2025",
                        "baja": "VIGENTE",
                        "sdi": 292.54,
                        "duracion": 9
                    }
                ]
            }
        }
    }
}
