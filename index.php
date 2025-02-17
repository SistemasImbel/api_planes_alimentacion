<!DOCTYPE html>
<html lang="en">

<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>API - Planes de Alimentación</title>
   <link rel="stylesheet" href="assets/estilo.css" type="text/css">
</head>

<body>

   <div class="container">
      <h1>Api de Planes de Alimentación</h1>
      <div class="divbody">
         <h3>clientes</h3>
         <code>
            GET /clientes?page=$numeroPagina
            <br>
            GET /clientes?id_cliente=$idCliente
         </code>
         <code>
            POST /clientes
            <br>
            {
            <br>
            "nombre" : "", -> REQUERIDO
            <br>
            "fecha_nacimiento" : "", -> REQUERIDO
            <br>
            "telefono" : "",
            <br>
            "horario_entrenamiento" : "",
            <br>
            "productos_adquiridos" : "", -> REQUERIDO
            <br>
            "asesor" : "", -> REQUERIDO
            <br>
            "marca" : "", -> REQUERIDO
            <br>
            "consumo_vitaminas_suplementos_medicamentos" : "",
            <br>
            "presentacion_producto" : "", -> REQUERIDO
            <br>
            "primera_vez_ciclo" : false, -> REQUERIDO
            <br>
            "peso" : 0.00, -> REQUERIDO
            <br>
            "altura" : 0.00, -> REQUERIDO
            <br>
            "genero" : "", -> REQUERIDO
            <br>
            "naf" : 0.00, -> REQUERIDO
            <br>
            "horas_ejercicio" : 0.00,
            <br>
            "objetivo" : "", -> REQUERIDO
            <br>
            "alergia_lactosa" : false,
            <br>
            "alergia_semillas" : false,
            <br>
            "imc" : 0.00,
            <br>
            "peso_ideal" : 0.00,
            <br>
            "tmb" : 0.00,
            <br>
            "get_total" : 0.00,
            <br>
            "agua_litros" : 0.00,
            <br>
            "pdf_plan" : ""
            <br>
            }
         </code>
      </div>
   </div>

</body>

</html>