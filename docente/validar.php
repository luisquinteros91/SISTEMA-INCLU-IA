<?php
session_start();
include "../includes/conexion.php";

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: login.php");
    exit;
}

$usuario = isset($_POST["usuario"]) ? trim($_POST["usuario"]) : "";
$password = isset($_POST["password"]) ? trim($_POST["password"]) : "";

if ($usuario == "" || $password == "") {
    header("Location: login.php?error=vacio");
    exit;
}

$usuario = mysqli_real_escape_string($conexion, $usuario);
$password = mysqli_real_escape_string($conexion, $password);

$sql = "SELECT id, usuario, password, nombre, rol 
        FROM docentes 
        WHERE usuario = '$usuario' 
        AND password = '$password'
        LIMIT 1";

$resultado = mysqli_query($conexion, $sql);

if (!$resultado) {
    die("Error SQL: " . mysqli_error($conexion));
}

if (mysqli_num_rows($resultado) == 0) {
    header("Location: login.php?error=incorrecto");
    exit;
}

$docente = mysqli_fetch_assoc($resultado);

$_SESSION["docente_id"] = $docente["id"];
$_SESSION["docente_usuario"] = $docente["usuario"];
$_SESSION["docente_nombre"] = $docente["nombre"];
$_SESSION["docente_rol"] = $docente["rol"];

header("Location: dashboard.php");
exit;
?>