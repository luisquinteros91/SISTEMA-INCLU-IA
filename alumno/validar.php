<?php
session_start();
include "../includes/conexion.php";

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: login.php");
    exit;
}

$dni = isset($_POST["dni"]) ? trim($_POST["dni"]) : "";

if ($dni == "") {
    header("Location: login.php?error=vacio");
    exit;
}

$dni = mysqli_real_escape_string($conexion, $dni);

$sql = "SELECT id, dni, apellido, nombre, curso 
        FROM alumnos 
        WHERE dni = '$dni' 
        LIMIT 1";

$resultado = mysqli_query($conexion, $sql);

if (!$resultado) {
    die("Error SQL: " . mysqli_error($conexion) . "<br>Consulta: " . $sql);
}

if (mysqli_num_rows($resultado) == 0) {
    header("Location: login.php?error=noexiste");
    exit;
}

$alumno = mysqli_fetch_assoc($resultado);

$_SESSION["alumno_id"] = $alumno["id"];
$_SESSION["alumno_dni"] = $alumno["dni"];
$_SESSION["alumno_nombre"] = $alumno["nombre"];
$_SESSION["alumno_apellido"] = $alumno["apellido"];
$_SESSION["alumno_curso"] = $alumno["curso"];

header("Location: menu.php");
exit;
?>