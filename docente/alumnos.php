<?php
session_start();
include "../includes/conexion.php";

if (!isset($_SESSION["docente_id"])) {
    header("Location: login.php");
    exit;
}

$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $dni = trim($_POST["dni"]);
    $apellido = trim($_POST["apellido"]);
    $nombre = trim($_POST["nombre"]);
    $curso = trim($_POST["curso"]);
    $perfil = trim($_POST["perfil_adaptacion"]);
    $observaciones = trim($_POST["observaciones"]);

    if ($dni != "" && $apellido != "" && $nombre != "" && $curso != "") {

        $dni = mysqli_real_escape_string($conexion, $dni);
        $apellido = mysqli_real_escape_string($conexion, $apellido);
        $nombre = mysqli_real_escape_string($conexion, $nombre);
        $curso = mysqli_real_escape_string($conexion, $curso);
        $perfil = mysqli_real_escape_string($conexion, $perfil);
        $observaciones = mysqli_real_escape_string($conexion, $observaciones);

        $sql_insert = "INSERT INTO alumnos 
        (dni, apellido, nombre, curso, perfil_adaptacion, observaciones)
        VALUES
        ('$dni', '$apellido', '$nombre', '$curso', '$perfil', '$observaciones')";

        if (mysqli_query($conexion, $sql_insert)) {
            $mensaje = "Alumno agregado correctamente.";
        } else {
            $mensaje = "Error al agregar alumno: " . mysqli_error($conexion);
        }
    } else {
        $mensaje = "Completá DNI, apellido, nombre y curso.";
    }
}

$sql = "SELECT * FROM alumnos ORDER BY apellido ASC, nombre ASC";
$resultado = mysqli_query($conexion, $sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Alumnos</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="../assets/css/estilo.css" rel="stylesheet">
</head>
<body>

<div class="contenedor">
    <div class="tarjeta grande">

        <h1>Gestión de Alumnos</h1>
        <p>Agregar y visualizar alumnos inclusivos registrados.</p>

        <?php if ($mensaje != "") { ?>
            <div class="error"><?php echo $mensaje; ?></div>
        <?php } ?>

        <form method="POST">
            <input type="text" name="dni" placeholder="DNI" required>
            <input type="text" name="apellido" placeholder="Apellido" required>
            <input type="text" name="nombre" placeholder="Nombre" required>
            <input type="text" name="curso" placeholder="Curso" required>
            <input type="text" name="perfil_adaptacion" placeholder="Perfil de adaptación">
            <input type="text" name="observaciones" placeholder="Observaciones">

            <button type="submit" class="boton">Agregar alumno</button>
        </form>

        <h2>Alumnos registrados</h2>

        <div class="tabla-responsive">
            <table>
                <tr>
                    <th>DNI</th>
                    <th>Alumno</th>
                    <th>Curso</th>
                    <th>Perfil</th>
                </tr>

                <?php while ($alumno = mysqli_fetch_assoc($resultado)) { ?>
                    <tr>
                        <td><?php echo $alumno["dni"]; ?></td>
                        <td><?php echo $alumno["apellido"] . ", " . $alumno["nombre"]; ?></td>
                        <td><?php echo $alumno["curso"]; ?></td>
                        <td><?php echo $alumno["perfil_adaptacion"]; ?></td>
                    </tr>
                <?php } ?>
            </table>
        </div>

        <a href="dashboard.php" class="link">Volver al dashboard</a>

    </div>
</div>

</body>
</html>