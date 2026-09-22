<?php
session_start();

if (!isset($_SESSION["alumno_id"])) {
    header("Location: login.php");
    exit;
}

$correcta = isset($_GET["correcta"]) ? intval($_GET["correcta"]) : 0;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Resultado</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="../assets/css/estilo.css" rel="stylesheet">
</head>
<body class="body-app-alumno">

<div class="app-alumno">

    <div class="app-card" style="margin-top:40px; text-align:center;">

        <?php if ($correcta == 1) { ?>

            <h1>🎉 ¡Muy bien!</h1>
            <p>Respuesta correcta.</p>
            <div style="font-size:42px;">⭐⭐⭐⭐⭐</div>

            <script>
                let voz = new SpeechSynthesisUtterance("Muy bien. Respuesta correcta.");
                voz.lang = "es-AR";
                voz.rate = 0.85;
                speechSynthesis.speak(voz);
            </script>

        <?php } else { ?>

            <h1>💪 Seguimos practicando</h1>
            <p>No pasa nada. Intentemos otra vez.</p>
            <div style="font-size:42px;">⭐⭐</div>

            <script>
                let voz = new SpeechSynthesisUtterance("No pasa nada. Seguimos practicando.");
                voz.lang = "es-AR";
                voz.rate = 0.85;
                speechSynthesis.speak(voz);
            </script>

        <?php } ?>

        <a href="menu.php" class="boton">Volver a materias</a>

    </div>

</div>

</body>
</html>