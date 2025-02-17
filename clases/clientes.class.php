<?php
require_once "conexion/conexion.php";
require_once "respuestas.class.php";

class clientes extends conexion
{
    private $table = "clientes";
    private $nombre = "";
    private $fechaNacimiento = "0000-00-00";
    private $telefono = "";
    private $horario_entrenamiento = "00:00:00";
    private $productos_adquiridos = "";
    private $asesor = "";
    private $marca = "";
    private $consumo_vitaminas_suplementos_medicamentos = "";
    private $presentacion = "";
    private $ciclo = false;
    private float $peso = 0.00;
    private float $altura = 0.00;
    private $genero = "";
    private $actividad = "";
    private $naf = 0.00;
    private $horas_ejercicio = 0.00;
    private $objetivo = "";
    private $alergia_lactosa = false;
    private $alergia_semillas = false;
    private $imc = 0.00;
    private $peso_ideal = 0.00;
    private $tmb = 0.00;
    private $get_total = 0.00;
    private $agua_litros = 0.00;
    private $pdf_plan = "";

    public function listaClientes($pagina = 1)
    {
        $inicio  = 0;
        $cantidad = 100;
        if ($pagina > 1) {
            $inicio = ($cantidad * ($pagina - 1)) + 1;
            $cantidad = $cantidad * $pagina;
        }
        $query = "SELECT id_cliente,nombre,fecha_nacimiento,telefono,horario_entrenamiento,productos_adquiridos,asesor,marca,consumo_vitaminas_suplementos_medicamentos,presentacion_producto,primera_vez_ciclo,peso,altura,genero,naf,horas_ejercicio,objetivo,alergia_lactosa,alergia_semillas,imc,peso_ideal,tmb,get_total,agua_litros,pdf_plan,created_at FROM " . $this->table . " limit $inicio,$cantidad";
        $datos = parent::obtenerDatos($query);
        return ($datos);
    }

    public function obtenerCliente($id)
    {
        $query = "SELECT * FROM " . $this->table . " WHERE id_cliente = ?";
        $stmt = $this->conexion->prepare($query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $this->convertirUTF8($result->fetch_all(MYSQLI_ASSOC));
    }

    public function post($json)
    {
        $_respuestas = new respuestas;
        $datos = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return $_respuestas->error_400("Formato JSON incorrecto.");
        }

        $requiredFields = [
            'nombre',
            'fecha_nacimiento',
            'telefono',
            'horario_entrenamiento',
            'productos_adquiridos',
            'asesor',
            'marca',
            'consumo_vitaminas_suplementos_medicamentos',
            'presentacion_producto',
            'primera_vez_ciclo',
            'peso',
            'altura',
            'genero',
            'actividad',
            'objetivo',
            'alergia_lactosa',
            'alergia_semillas'
        ];

        foreach ($requiredFields as $field) {
            if (!isset($datos[$field])) {
                return $_respuestas->error_400("Falta el campo $field.");
            }
        }

        // Sanitización de entrada
        $this->nombre = htmlspecialchars(strip_tags($datos['nombre']));
        $this->fechaNacimiento = htmlspecialchars(strip_tags($datos['fecha_nacimiento']));
        $this->telefono = htmlspecialchars(strip_tags($datos['telefono']));
        $this->horario_entrenamiento = htmlspecialchars(strip_tags($datos['horario_entrenamiento']));
        $this->productos_adquiridos = htmlspecialchars(strip_tags($datos['productos_adquiridos']));
        $this->asesor = htmlspecialchars(strip_tags($datos['asesor']));
        $this->marca = htmlspecialchars(strip_tags($datos['marca']));
        $this->consumo_vitaminas_suplementos_medicamentos = htmlspecialchars(strip_tags($datos['consumo_vitaminas_suplementos_medicamentos']));
        $this->presentacion = htmlspecialchars(strip_tags($datos['presentacion_producto']));
        $this->ciclo = filter_var($datos['primera_vez_ciclo'], FILTER_VALIDATE_BOOLEAN);
        $this->peso = filter_var($datos['peso'], FILTER_VALIDATE_FLOAT);
        $this->altura = filter_var($datos['altura'], FILTER_VALIDATE_FLOAT);
        $this->genero = htmlspecialchars(strip_tags($datos['genero']));
        $this->actividad = htmlspecialchars(strip_tags($datos['actividad']));
        $this->objetivo = htmlspecialchars(strip_tags($datos['objetivo']));
        $this->alergia_lactosa = filter_var($datos['alergia_lactosa'], FILTER_VALIDATE_BOOLEAN);
        $this->alergia_semillas = filter_var($datos['alergia_semillas'], FILTER_VALIDATE_BOOLEAN);

        // Calcular la edad
        $fecha_actual = new DateTime();
        $fecha_nac = new DateTime($this->fechaNacimiento);
        $edad = $fecha_actual->diff($fecha_nac)->y;

        // Definir el valor de NAF según la actividad seleccionada
        $actividadValores = [
            "Sedentario" => 1.2,
            "Ligero" => 1.375,
            "Moderado" => 1.55,
            "Activo" => 1.725,
            "Muy Activo" => 1.9
        ];

        if (!isset($actividadValores[$this->actividad])) {
            return $_respuestas->error_400("Actividad inválida.");
        }

        $this->naf = $actividadValores[$this->actividad];

        // Calcular el IMC
        $this->imc = $this->peso / ($this->altura * $this->altura);

        // Determinar el IMC deseado según género
        $imc_buscado = ($this->genero === 'hombre') ? 24 : 22;
        $this->peso_ideal = $imc_buscado * ($this->altura * $this->altura);

        // Calcular TMB (Ecuación de Mifflin-St Jeor)
        if ($this->genero === 'hombre') {
            $this->tmb = (10 * $this->peso) + (6.25 * ($this->altura * 100)) - (5 * $edad) + 5;
        } else {
            $this->tmb = (10 * $this->peso) + (6.25 * ($this->altura * 100)) - (5 * $edad) - 161;
        }

        // GET antes del ajuste por objetivo
        $this->get_total = $this->tmb * $this->naf;

        // Ajuste del GET según el objetivo
        $ajustesObjetivo = [
            "bajar de peso" => 0.8,    // -20%
            "recomposicion" => 0.9,    // -10%
            "definicion" => 0.8,       // -20%
            "aumento de volumen" => 1.2 // +20%
        ];

        if (!isset($ajustesObjetivo[$this->objetivo])) {
            return $_respuestas->error_400("Objetivo inválido.");
        }

        $this->get_total *= $ajustesObjetivo[$this->objetivo];

        // Extraer la parte entera y decimal
        $entero = floor($this->get_total / 100) * 100;
        $decimal = $this->get_total - $entero;

        // Si el decimal es mayor o igual a 50, subir al siguiente múltiplo de 100
        if ($decimal >= 50) {
            $this->get_total = $entero + 100;
        } else {
            $this->get_total = $entero;
        }

        $objetivos_alias = [
            "bajar de peso" => "recomposicion",
            "aumento de volumen" => "volumen"
        ];
        if (isset($objetivos_alias[$this->objetivo])) {
            $this->objetivo = $objetivos_alias[$this->objetivo];
        }

        // Calcular consumo de agua
        $this->agua_litros = (($this->peso + 40) * 24 + ($this->peso * 6) * $this->horas_ejercicio) / 1000;

        // Generar la ruta del PDF
        $this->pdf_plan = $this->buscarPDFPlan();

        // Insertar en la base de datos
        $resp = $this->insertarCliente();
        if ($resp) {
            return [
                "status" => "ok",
                "result" => ["clienteId" => $resp]
            ];
        } else {
            return $_respuestas->error_500();
        }
    }

    private function buscarPDFPlan()
    {
        $ruta_base = __DIR__ . "/../planes/";
        $carpetas = ["BARBARIAN", "EUROLAB", "MESO10", "MESOFRANCE", "SBELLA", "VASSAL"];
        $marca = strtolower($this->marca);
        $objetivo = strtolower($this->objetivo);
        $calorias = $this->get_total;

        // 1️⃣ PRIMERO, BUSCAR ARCHIVO EXACTO (ej: eurolabvolumen2675.pdf)
        $archivo_exacto = "{$marca}{$objetivo}{$calorias}.pdf";

        foreach ($carpetas as $carpeta) {
            $ruta_carpeta = $ruta_base . $carpeta;
            if (is_dir($ruta_carpeta)) {
                $archivos = scandir($ruta_carpeta);

                foreach ($archivos as $archivo) {
                    if (strcasecmp($archivo, $archivo_exacto) === 0) {
                        return "planes/$carpeta/$archivo";
                    }
                }

                // 2️⃣ SI NO ENCUENTRA, BUSCAR UN ARCHIVO CON RANGO DE KCAL (ej: eurolabvolumen2650-2700.pdf)
                foreach ($archivos as $archivo) {
                    if (preg_match("/^{$marca}{$objetivo}(\d{4})-(\d{4})\.pdf$/i", $archivo, $matches)) {
                        $kcal_min = intval($matches[1]);
                        $kcal_max = intval($matches[2]);

                        // Verificar si las calorías están dentro del rango
                        if ($calorias >= $kcal_min && $calorias <= $kcal_max) {
                            return "planes/$carpeta/$archivo";
                        }
                    }
                }
            }
        }

        // 3️⃣ SI NO ENCUENTRA NINGÚN ARCHIVO
        return "archivo no encontrado";
    }

    public function exportarClientesCSV()
    {
        $query = "SELECT * FROM " . $this->table;
        $datos = parent::obtenerDatos($query);

        if (empty($datos)) {
            return ["status" => "error", "message" => "No hay clientes disponibles."];
        }

        $archivo = __DIR__ . "/../export/clientes.csv";
        $carpeta_export = __DIR__ . "/../export";

        // Verificar si la carpeta export existe, si no, crearla
        if (!is_dir($carpeta_export)) {
            mkdir($carpeta_export, 0777, true);
        }

        // Tabla de equivalencias NAF (usaremos la comparación más cercana)
        $actividadValores = [
            1.2 => "Sedentario",
            1.375 => "Ligero",
            1.55 => "Moderado",
            1.725 => "Activo",
            1.9 => "Muy Activo"
        ];

        // Función para encontrar el valor más cercano
        function obtenerActividadNAF($naf, $actividadValores)
        {
            $valores = array_keys($actividadValores);
            $cercano = $valores[0];

            foreach ($valores as $valor) {
                if (abs($naf - $valor) < abs($naf - $cercano)) {
                    $cercano = $valor;
                }
            }

            return $actividadValores[$cercano] ?? "Desconocido";
        }

        // Abrir el archivo CSV para sobrescribirlo
        $csvFile = fopen($archivo, 'w');

        // Agregar BOM para evitar problemas de codificación en Excel
        fprintf($csvFile, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // Encabezados
        $encabezados = array_keys($datos[0]);
        fputcsv($csvFile, $encabezados);

        // Escribir los datos
        foreach ($datos as $row) {
            // Convertir valores de alergias y ciclo a 'Sí' o 'No'
            $row['alergia_lactosa'] = ($row['alergia_lactosa'] == 1) ? 'Sí' : 'No';
            $row['alergia_semillas'] = ($row['alergia_semillas'] == 1) ? 'Sí' : 'No';
            $row['primera_vez_ciclo'] = ($row['primera_vez_ciclo'] == 1) ? 'Sí' : 'No';

            // Convertir NAF al valor más cercano de la tabla
            $row['naf'] = obtenerActividadNAF(floatval($row['naf']), $actividadValores);

            // Convertir cada valor a UTF-8 para evitar caracteres extraños
            foreach ($row as $key => $value) {
                $row[$key] = mb_convert_encoding($value, 'UTF-8', 'auto');
            }

            fputcsv($csvFile, $row);
        }

        fclose($csvFile);

        return ["status" => "ok", "message" => "Archivo CSV generado exitosamente.", "archivo" => "export/clientes.csv"];
    }

    public function mostrarPDF($id_cliente)
    {
        // Obtener la ruta del PDF desde la base de datos
        $query = "SELECT pdf_plan FROM " . $this->table . " WHERE id_cliente = ?";
        $stmt = $this->conexion->prepare($query);
        $stmt->bind_param("i", $id_cliente);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();

        if (!$data || empty($data['pdf_plan']) || $data['pdf_plan'] === "archivo no encontrado") {
            header("HTTP/1.1 404 Not Found");
            echo json_encode(["error" => "Archivo no encontrado"]);
            exit();
        }

        $ruta_pdf = __DIR__ . "/../" . $data['pdf_plan']; // Convertir a ruta absoluta

        if (!file_exists($ruta_pdf)) {
            header("HTTP/1.1 404 Not Found");
            echo json_encode(["error" => "El archivo PDF no existe en el servidor"]);
            exit();
        }

        // Configurar encabezados para mostrar el PDF
        header("Content-Type: application/pdf");
        header("Content-Disposition: inline; filename=\"" . basename($ruta_pdf) . "\"");
        readfile($ruta_pdf);
        exit();
    }


    private function insertarCliente()
    {
        $query = "INSERT INTO " . $this->table . " (nombre,fecha_nacimiento,telefono,horario_entrenamiento,productos_adquiridos,asesor,marca,consumo_vitaminas_suplementos_medicamentos,presentacion_producto,primera_vez_ciclo,peso,altura,genero,naf,horas_ejercicio,objetivo,alergia_lactosa,alergia_semillas,imc,peso_ideal,tmb,get_total,agua_litros,pdf_plan)
        values
        ('" . $this->nombre . "','" . $this->fechaNacimiento . "','" . $this->telefono . "','" . $this->horario_entrenamiento . "','" . $this->productos_adquiridos . "','" . $this->asesor . "','" . $this->marca . "','" . $this->consumo_vitaminas_suplementos_medicamentos . "','" . $this->presentacion . "','" . $this->ciclo . "','" . $this->peso . "','" . $this->altura . "','" . $this->genero . "','" . $this->naf . "','" . $this->horas_ejercicio . "','" . $this->objetivo . "','" . $this->alergia_lactosa . "','" . $this->alergia_semillas . "','" . $this->imc . "','" . $this->peso_ideal . "','" . $this->tmb . "','" . $this->get_total . "','" . $this->agua_litros . "','" . $this->pdf_plan . "')";
        $resp = parent::nonQueryId($query);
        if ($resp) {
            return $resp;
        } else {
            return 0;
        }
    }
}
