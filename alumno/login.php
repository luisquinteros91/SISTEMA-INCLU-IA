<?php
session_start();

$error = "";

if (isset($_GET["error"])) {
    if ($_GET["error"] == "vacio") {
        $error = "Debe ingresar un DNI.";
    }

    if ($_GET["error"] == "noexiste") {
        $error = "No existe un alumno con ese DNI.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ingreso Alumno</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="../assets/css/estilo.css" rel="stylesheet">
</head>
<body>

<div class="contenedor">
    <div class="tarjeta">
        <h1>Ingreso Alumno</h1>
        <p>Escribí tu DNI para comenzar.</p>

        <?php if ($error != "") { ?>
            <div class="error"><?php echo $error; ?></div>
        <?php } ?>

        <form method="POST" action="validar.php">
            <input type="text" name="dni" placeholder="DNI del alumno" required autocomplete="off">
            <button type="submit" class="boton">Entrar</button>
        </form>

        <a href="../index.php" class="link">Volver</a>
    </div>
</div>

</body>
</html>