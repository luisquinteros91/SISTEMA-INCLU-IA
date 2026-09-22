<?php
session_start();
include "../includes/conexion.php";

if (!isset($_SESSION["alumno_id"])) {
    header("Location: login.php");
    exit;
}

if (!isset($_GET["materia_id"])) {
    header("Location: menu.php");
    exit;
}

$materia_id = intval($_GET["materia_id"]);

$sql_materia = "SELECT * FROM materias WHERE id = $materia_id LIMIT 1";
$res_materia = mysqli_query($conexion, $sql_materia);
$materia = mysqli_fetch_assoc($res_materia);

$sql = "SELECT * FROM actividades 
        WHERE materia_id = $materia_id 
        AND activa = 1 
        ORDER BY id ASC";

$resultado = mysqli_query($conexion, $sql);

if (!$resultado) {
    die("Error SQL: " . mysqli_error($conexion));
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Actividades | INCLU-IA</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="../assets/css/estilo.css" rel="stylesheet">
</head>

<body class="body-app-alumno">

<div class="app-alumno">

    <div class="app-header">
        <div>
            <h1><?php echo $materia["nombre"]; ?></h1>
            <p>Elegí una actividad</p>
        </div>

        <div class="avatar-alumno">
            <?php echo $materia["icono"]; ?>
        </div>
    </div>

    <div class="lista-actividades">

        <?php if (mysqli_num_rows($resultado) == 0) { ?>
            <div class="app-card">
                <h2>Sin actividades</h2>
                <p>Todavía no hay actividades cargadas para esta materia.</p>
            </div>
        <?php } ?>

        <?php while ($actividad = mysqli_fetch_assoc($resultado)) { ?>
            <a href="resolver.php?id=<?php echo $actividad["id"]; ?>" class="actividad-app">
                <div>
                    <h2><?php echo $actividad["titulo"]; ?></h2>
                    <p>Nivel: <?php echo $actividad["nivel"]; ?></p>
                </div>
                <span>▶</span>
            </a>
        <?php } ?>

    </div>

    <div class="barra-inferior">
        <a href="menu.php">🏠<span>Inicio</span></a>
        <a href="#">⭐<span>Logros</span></a>
        <a href="#">📊<span>Progreso</span></a>
        <a href="salir.php">🚪<span>Salir</span></a>
    </div>

</div>

</body>
</html>