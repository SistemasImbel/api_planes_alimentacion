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
    private $naf = 0.0;
    private $naf_texto = "";
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


    private const ACTIVIDAD_VALORES = [
        "Sedentario" => 1.2,
        "Ligero" => 1.375,
        "Moderado" => 1.55,
        "Activo" => 1.725,
        "Muy Activo" => 1.9
    ];

    private const AJUSTES_OBJETIVO = [
        "bajar de peso" => 0.8,
        "recomposicion" => 0.9,
        "definicion" => 0.8,
        "aumento de volumen" => 1.2,
        "aumento de gluteo" => 0.8
    ];


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

    // Dentro de la clase clientes
    public function contarClientes()
    {
        $query = "SELECT COUNT(id_cliente) as total FROM " . $this->table;
        $result = parent::obtenerDatos($query);
        return $result[0]['total'] ?? 0;
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
        $this->ciclo = isset($datos['primera_vez_ciclo'])
            ? filter_var($datos['primera_vez_ciclo'], FILTER_VALIDATE_BOOLEAN)
            : null;
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
        if ($fecha_nac > $fecha_actual) {
            return $_respuestas->error_400("La fecha de nacimiento no puede ser en el futuro.");
        }
        $edad = $fecha_actual->diff($fecha_nac)->y;

        if (!isset(self::ACTIVIDAD_VALORES[$this->actividad])) {
            return $_respuestas->error_400("Actividad inválida.");
        }

        $this->naf = self::ACTIVIDAD_VALORES[$this->actividad]; // Valor numérico para cálculos
        $this->naf_texto = $this->actividad; // Valor en texto para la BD

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

        if (!isset(self::AJUSTES_OBJETIVO[$this->objetivo])) {
            return $_respuestas->error_400("Objetivo inválido.");
        }

        $this->get_total *= self::AJUSTES_OBJETIVO[$this->objetivo];

        // Extraer la parte entera y decimal
        $entero = floor($this->get_total / 100) * 100;
        $decimal = $this->get_total - $entero;

        // Si el decimal es mayor o igual a 50, subir al siguiente múltiplo de 100
        if ($decimal >= 50) {
            $this->get_total = $entero + 100;
        } else {
            $this->get_total = $entero;
        }

        // al ver los planes de alimentacion considerar cambiar el arreglo

        $objetivos_alias = [
            "bajar de peso" => "bajar",
            "recomposicion" => "recomposicion",
            "aumento de volumen" => "volumen",
            "aumento de gluteo" => "gluteo"
        ];

        if (isset($objetivos_alias[$this->objetivo])) {
            $this->objetivo = $objetivos_alias[$this->objetivo];
        }

        // Si la actividad es diferente de "Sedentario", asignar las horas de entrenamiento
        $this->horas_ejercicio = ($this->actividad !== "Sedentario") ? filter_var($datos['horas_ejercicio'], FILTER_VALIDATE_FLOAT) : 0.00;

        // Calcular consumo de agua
        $this->agua_litros = (($this->peso + 40) * 24 + ($this->peso * 6) * $this->horas_ejercicio) / 1000;

        // Verificar si la actividad es diferente de "Sedentario" y si horas_ejercicio está presente
        if ($this->actividad !== "Sedentario" && (!isset($datos['horas_ejercicio']) || !is_numeric($datos['horas_ejercicio']) || $datos['horas_ejercicio'] < 0)) {
            return $_respuestas->error_400("Si la actividad no es 'Sedentario', debes enviar un valor válido para horas_ejercicio.");
        }

        // Generar la ruta del PDF
        $pdf_resultado = $this->buscarPDFPlan(
            $this->marca,
            $this->objetivo,
            $this->get_total,
            $this->alergia_lactosa,
            $this->alergia_semillas
        );
        
        $mensaje_adicional = null;
        
        if (str_starts_with($pdf_resultado, "No se encontró") || str_starts_with($pdf_resultado, "No se encontraron")) {
            $this->pdf_plan = null;
            $mensaje_adicional = $pdf_resultado;
        } else {
            $this->pdf_plan = $pdf_resultado;
        }
        
        // Insertar en la base de datos
        $resp = $this->insertarCliente();
        if ($resp) {
            $respuesta = [
                "status" => "ok",
                "result" => ["clienteId" => $resp]
            ];
        
            if ($mensaje_adicional !== null) {
                $respuesta["mensaje"] = $mensaje_adicional;
            }
        
            return $respuesta;
        } else {
            return $_respuestas->error_500();
        }        
    }

    private function buscarPDFPlan($marca, $objetivo, $get_total, $alergia_lactosa, $alergia_semillas)
    {
        $basePath = "planes/";
        $folderPath = $basePath . strtolower($marca);

        // Paso 1: Obtener calorías disponibles dinámicamente desde carpetas
        $calorias_disponibles = [];
        if (is_dir($folderPath)) {
            $folders = scandir($folderPath);
            foreach ($folders as $folder) {
                $folder_lower = strtolower($folder);
                if (preg_match("/" . strtolower($marca) . strtolower($objetivo) . "(\d{3,4})/", $folder_lower, $matches)) {
                    $calorias_disponibles[] = intval($matches[1]);
                }
            }
        }

        // Si no hay calorías detectadas, no hay planes disponibles
        if (empty($calorias_disponibles)) {
            return "No se encontraron planes disponibles para la marca y objetivo especificados.";
        }

        // Paso 2: Ordenar y limpiar calorías
        $calorias_disponibles = array_unique($calorias_disponibles);
        sort($calorias_disponibles);

        // Paso 3: Elegir la caloría más adecuada
        $calorias_asignadas = $calorias_disponibles[0];
        foreach ($calorias_disponibles as $cal) {
            if ($get_total <= $cal) {
                $calorias_asignadas = $cal;
                break;
            }
        }
        if ($get_total > max($calorias_disponibles)) {
            $calorias_asignadas = max($calorias_disponibles);
        }

        // Paso 4: Construir rutas en orden de prioridad
        $ruta_base = $basePath . strtolower($marca) . strtolower($objetivo) . $calorias_asignadas;
        $rutas_posibles = [];

        if ($alergia_lactosa) {
            $rutas_posibles[] = $ruta_base . "sinlacteos";
        }
        if ($alergia_semillas) {
            $rutas_posibles[] = $ruta_base . "sinsemillas";
        }
        $rutas_posibles[] = $ruta_base;

        // Paso 5: Buscar PDFs en rutas
        foreach ($rutas_posibles as $ruta) {
            if (is_dir($ruta)) {
                $archivos = array_values(array_filter(scandir($ruta), function ($file) {
                    return !in_array($file, ['.', '..']) && pathinfo($file, PATHINFO_EXTENSION) === 'pdf';
                }));
                if (!empty($archivos)) {
                    return $ruta . "/" . $archivos[array_rand($archivos)];
                }
            }
        }

        // Si llega aquí, no hay PDFs
        return "No se encontró ningún plan PDF compatible con las restricciones y calorías calculadas.";
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

        // Abrir el archivo CSV para sobrescribirlo
        $csvFile = fopen($archivo, 'w');

        // Agregar BOM para evitar problemas de codificación en Excel
        fprintf($csvFile, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // Encabezados
        $encabezados = array_keys($datos[0]);
        fputcsv($csvFile, $encabezados);

        // Escribir los datos
        foreach ($datos as $row) {
            // Convertir los valores de las alergias y el ciclo a 'Sí' o 'No'
            $row['alergia_lactosa'] = ($row['alergia_lactosa'] == 1) ? 'Sí' : 'No';
            $row['alergia_semillas'] = ($row['alergia_semillas'] == 1) ? 'Sí' : 'No';
            $row['primera_vez_ciclo'] = ($row['primera_vez_ciclo'] == 1) ? 'Sí' : 'No';

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
        $query = "INSERT INTO " . $this->table . " (
        nombre, fecha_nacimiento, telefono, horario_entrenamiento, productos_adquiridos,
        asesor, marca, consumo_vitaminas_suplementos_medicamentos, presentacion_producto,
        primera_vez_ciclo, peso, altura, genero, naf, horas_ejercicio, objetivo,
        alergia_lactosa, alergia_semillas, imc, peso_ideal, tmb, get_total, agua_litros, pdf_plan
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->conexion->prepare($query);

        if (!$stmt) {
            return 0; // o log de error
        }

        $cicloValue = is_null($this->ciclo) ? null : ($this->ciclo ? 1 : 0);

        $stmt->bind_param(
            "sssssssssidddssdiiiddds",
            $this->nombre,
            $this->fechaNacimiento,
            $this->telefono,
            $this->horario_entrenamiento,
            $this->productos_adquiridos,
            $this->asesor,
            $this->marca,
            $this->consumo_vitaminas_suplementos_medicamentos,
            $this->presentacion,
            $cicloValue,
            $this->peso,
            $this->altura,
            $this->genero,
            $this->naf_texto,
            $this->horas_ejercicio,
            $this->objetivo,
            $this->alergia_lactosa,
            $this->alergia_semillas,
            $this->imc,
            $this->peso_ideal,
            $this->tmb,
            $this->get_total,
            $this->agua_litros,
            $this->pdf_plan
        );

        if ($stmt->execute()) {
            return $stmt->insert_id;
        }

        return 0;
    }
}
