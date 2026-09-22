<?php
session_start();
include "../includes/conexion.php";

if (!isset($_SESSION["alumno_id"])) {
    header("Location: login.php");
    exit;
}

if (!isset($_GET["id"])) {
    header("Location: menu.php");
    exit;
}

$actividad_id = intval($_GET["id"]);

$sql = "SELECT actividades.*, materias.nombre AS materia
        FROM actividades
        LEFT JOIN materias ON actividades.materia_id = materias.id
        WHERE actividades.id = $actividad_id
        LIMIT 1";

$res = mysqli_query($conexion, $sql);

if (!$res || mysqli_num_rows($res) == 0) {
    die("Actividad no encontrada.");
}

$actividad = mysqli_fetch_assoc($res);

$sql_opciones = "SELECT * FROM opciones WHERE actividad_id = $actividad_id ORDER BY id ASC";
$opciones = mysqli_query($conexion, $sql_opciones);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Resolver actividad</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="../assets/css/estilo.css" rel="stylesheet">
</head>
<body class="body-app-alumno">

<div class="app-alumno">

    <div class="app-header">
        <div>
            <h1><?php echo $actividad["materia"]; ?></h1>
            <p><?php echo $actividad["titulo"]; ?></p>
        </div>
        <div class="avatar-alumno">📝</div>
    </div>

    <div class="app-card">
        <h2>Consigna</h2>
        <p id="consigna"><?php echo $actividad["consigna"]; ?></p>

        <button class="btn-audio" onclick="leerConsigna()">🔊 Escuchar</button>
    </div>

    <form method="POST" action="guardar_respuesta.php">

        <input type="hidden" name="actividad_id" value="<?php echo $actividad_id; ?>">

        <?php while ($op = mysqli_fetch_assoc($opciones)) { ?>
            <button type="submit" name="opcion_id" value="<?php echo $op["id"]; ?>" class="boton">
                <?php echo $op["texto"]; ?>
            </button>
        <?php } ?>

    </form>

    <div class="barra-inferior">
        <a href="menu.php">🏠<span>Inicio</span></a>
        <a href="#">⭐<span>Logros</span></a>
        <a href="#">📊<span>Progreso</span></a>
        <a href="salir.php">🚪<span>Salir</span></a>
    </div>

</div>

<script>
function leerConsigna() {
    let texto = document.getElementById("consigna").innerText;
    let voz = new SpeechSynthesisUtterance(texto);
    voz.lang = "es-AR";
    voz.rate = 0.85;
    speechSynthesis.speak(voz);
}
</script>

</body>
</html>