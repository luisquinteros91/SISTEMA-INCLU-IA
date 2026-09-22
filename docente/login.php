<?php
session_start();

$error = "";

if (isset($_GET["error"])) {
    if ($_GET["error"] == "vacio") {
        $error = "Debe completar usuario y contraseña.";
    }

    if ($_GET["error"] == "incorrecto") {
        $error = "Usuario o contraseña incorrectos.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ingreso Docente</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="../assets/css/estilo.css" rel="stylesheet">
</head>
<body>

<div class="contenedor">
    <div class="tarjeta">
        <h1>Panel Docente</h1>
        <p>Ingrese sus datos para administrar la plataforma.</p>

        <?php if ($error != "") { ?>
            <div class="error"><?php echo $error; ?></div>
        <?php } ?>

        <form method="POST" action="validar.php">
            <input type="text" name="usuario" placeholder="Usuario" required autocomplete="off">
            <input type="password" name="password" placeholder="Contraseña" required>

            <button type="submit" class="boton">Ingresar</button>
        </form>

        <a href="../index.php" class="link">Volver</a>
    </div>
</div>

</body>
</html>