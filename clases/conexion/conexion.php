<?php
require_once __DIR__ . '/../../config.php';


class conexion {

    protected $server;
    protected $user;
    protected $password;
    protected $database;
    protected $port;
    protected $conexion;

    function __construct(){
        $this->server = getenv('DB_SERVER');
        $this->user = getenv('DB_USER');
        $this->password = getenv('DB_PASSWORD');
        $this->database = getenv('DB_DATABASE');
        $this->port = getenv('DB_PORT');

        $this->conexion = new mysqli($this->server, $this->user, $this->password, $this->database, $this->port);

        if($this->conexion->connect_errno){
            error_log("Error de conexión: " . $this->conexion->connect_error);
            die("Error interno del servidor.");
        }
    }


    protected function convertirUTF8($array){
        array_walk_recursive($array,function(&$item,$key){
            if(!mb_detect_encoding($item,'utf-8',true)){
                $item = utf8_encode($item);
            }
        });
        return $array;
    }


    public function obtenerDatos($sqlstr){
        $results = $this->conexion->query($sqlstr);
        $resultArray = array();
        foreach ($results as $key) {
            $resultArray[] = $key;
        }
        return $this->convertirUTF8($resultArray);

    }



    public function nonQuery($sqlstr){
        $results = $this->conexion->query($sqlstr);
        return $this->conexion->affected_rows;
    }


    //INSERT 
    public function nonQueryId($sqlstr){
        $results = $this->conexion->query($sqlstr);
         $filas = $this->conexion->affected_rows;
         if($filas >= 1){
            return $this->conexion->insert_id;
         }else{
             return 0;
         }
    }

}



?>