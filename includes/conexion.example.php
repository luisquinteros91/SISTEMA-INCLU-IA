<?php
$host="localhost";
$usuario="TU_USUARIO_MYSQL";
$password="TU_PASSWORD_MYSQL";
$base="TU_BASE_DE_DATOS";
$conexion=mysqli_connect($host,$usuario,$password,$base);
if(!$conexion){ die("Error de conexión con la base de datos."); }
mysqli_set_charset($conexion,"utf8mb4");
?>