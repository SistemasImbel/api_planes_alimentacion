<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

// Manejo de solicitudes OPTIONS (Preflight)
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'clases/respuestas.class.php';
require_once 'clases/clientes.class.php';

$_respuestas = new respuestas;
$_clientes = new clientes;

if ($_SERVER['REQUEST_METHOD'] == "GET") {

    if (isset($_GET["page"])) {
        $pagina = $_GET["page"];
        $listaClientes = $_clientes->listaClientes($pagina);
        header('Content-Type: application/json');
        echo json_encode($listaClientes);
        http_response_code(200);
    } elseif (isset($_GET['id_cliente'])) {
        $clienteid = $_GET['id_cliente'];
        $datosCliente = $_clientes->obtenerCliente($clienteid);
        header('Content-Type: application/json');
        echo json_encode($datosCliente);
        http_response_code(200);
    } elseif (isset($_GET["count"]) && $_GET["count"] == "true") {
        $total = $_clientes->contarClientes();
        echo json_encode(["total" => $total]);
        exit();
    } elseif (isset($_GET['pdf'])) {
        // Nuevo endpoint para mostrar el PDF
        $id_cliente = intval($_GET['pdf']);
        $_clientes->mostrarPDF($id_cliente);
    } elseif (isset($_GET['export']) && $_GET['export'] == "csv") {
        $resultado = $_clientes->exportarClientesCSV();
        header('Content-Type: application/json');
        echo json_encode($resultado);
        http_response_code(200);
    }
} elseif ($_SERVER['REQUEST_METHOD'] == "POST") {
    //recibimos los datos enviados
    $postBody = file_get_contents("php://input");
    //enviamos los datos al manejador
    $datosArray = $_clientes->post($postBody);
    //delvovemos una respuesta 
    header('Content-Type: application/json');
    if (isset($datosArray["result"]["error_id"])) {
        $responseCode = $datosArray["result"]["error_id"];
        http_response_code($responseCode);
    } else {
        http_response_code(200);
    }
    echo json_encode($datosArray);
}
