<?php
session_start();
chdir(dirname(__FILE__));
include_once '../../reglas_negocio/Facultad.php';

$facultad = $_SESSION['facultad'];
$s = serialize($facultad);
$u = file_put_contents("../../horarios_guardados/facultad", $s);

if(!$u){
    $respuesta = "fallo";
    echo json_encode($respuesta);
}else{
    $respuesta = "exito";
    echo json_encode($respuesta);
}