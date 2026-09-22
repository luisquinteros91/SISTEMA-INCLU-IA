<?php
session_start();
include "../includes/conexion.php";

if (!isset($_SESSION["alumno_id"])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: menu.php");
    exit;
}

$alumno_id = intval($_SESSION["alumno_id"]);
$actividad_id = intval($_POST["actividad_id"]);
$opcion_id = intval($_POST["opcion_id"]);

$sql = "SELECT es_correcta FROM opciones WHERE id = $opcion_id LIMIT 1";
$res = mysqli_query($conexion, $sql);

if (!$res || mysqli_num_rows($res) == 0) {
    die("Opción no encontrada.");
}

$opcion = mysqli_fetch_assoc($res);
$correcta = intval($opcion["es_correcta"]);

$sql_insert = "INSERT INTO respuestas 
(alumno_id, actividad_id, opcion_id, correcta)
VALUES
($alumno_id, $actividad_id, $opcion_id, $correcta)";

mysqli_query($conexion, $sql_insert);

header("Location: resultado.php?correcta=$correcta");
exit;
?>