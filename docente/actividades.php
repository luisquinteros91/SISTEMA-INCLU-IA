<?php
session_start();
include "../includes/conexion.php";

if (!isset($_SESSION["docente_id"])) {
    header("Location: login.php");
    exit;
}

$mensaje = "";

/* Guardar actividad */
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $materia_id = intval($_POST["materia_id"]);
    $titulo = trim($_POST["titulo"]);
    $consigna = trim($_POST["consigna"]);
    $nivel = trim($_POST["nivel"]);

    if ($materia_id > 0 && $titulo != "" && $consigna != "") {

        $titulo = mysqli_real_escape_string($conexion, $titulo);
        $consigna = mysqli_real_escape_string($conexion, $consigna);
        $nivel = mysqli_real_escape_string($conexion, $nivel);

        $sql = "INSERT INTO actividades 
                (materia_id, titulo, consigna, nivel, activa)
                VALUES 
                ($materia_id, '$titulo', '$consigna', '$nivel', 1)";

        if (mysqli_query($conexion, $sql)) {
            $mensaje = "Actividad creada correctamente.";
        } else {
            $mensaje = "Error al crear actividad: " . mysqli_error($conexion);
        }

    } else {
        $mensaje = "Completá materia, título y consigna.";
    }
}

/* Materias */
$materias = mysqli_query($conexion, "SELECT * FROM materias ORDER BY nombre ASC");

/* Actividades */
$sql_actividades = "SELECT actividades.*, materias.nombre AS materia
                    FROM actividades
                    LEFT JOIN materias ON actividades.materia_id = materias.id
                    ORDER BY actividades.id DESC";

$actividades = mysqli_query($conexion, $sql_actividades);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Actividades | INCLU-IA</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="../assets/css/estilo.css" rel="stylesheet">
</head>
<body class="body-dashboard">

<div class="app-layout">

    <aside class="sidebar">
        <div class="logo">
            <div class="logo-icon">🌈</div>
            <div>
                <h2>INCLU-IA</h2>
                <small>Inclusión inteligente</small>
            </div>
        </div>

        <nav class="menu-lateral">
            <a href="dashboard.php">🏠 Inicio</a>
            <a href="alumnos.php">👨‍🎓 Alumnos</a>
            <a href="materias.php">📚 Materias</a>
            <a href="actividades.php" class="activo">📝 Actividades</a>
            <a href="progreso.php">📊 Progreso</a>
            <a href="#">🤖 IA</a>
            <a href="#">⚙ Configuración</a>
        </nav>

        <a href="salir.php" class="logout">Cerrar sesión</a>
    </aside>

    <main class="main-panel">

        <section class="topbar">
            <div>
                <h1>Actividades adaptadas</h1>
                <p>Creá consignas simples, visuales y accesibles.</p>
            </div>
        </section>

        <?php if ($mensaje != "") { ?>
            <div class="alerta-panel"><?php echo $mensaje; ?></div>
        <?php } ?>

        <section class="panel-card">
            <h2>Nueva actividad</h2>

            <form method="POST" class="form-panel">

                <label>Materia</label>
                <select name="materia_id" required>
                    <option value="">Seleccionar materia</option>
                    <?php while ($m = mysqli_fetch_assoc($materias)) { ?>
                        <option value="<?php echo $m["id"]; ?>">
                            <?php echo $m["nombre"]; ?>
                        </option>
                    <?php } ?>
                </select>

                <label>Título</label>
                <input type="text" name="titulo" placeholder="Ej: Suma con imágenes" required>

                <label>Consigna adaptada</label>
                <textarea name="consigna" placeholder="Ej: Tocá el resultado correcto de 2 + 3" required></textarea>

                <label>Nivel</label>
                <select name="nivel">
                    <option value="Inicial">Inicial</option>
                    <option value="Intermedio">Intermedio</option>
                    <option value="Avanzado">Avanzado</option>
                </select>

                <button type="submit" class="boton-panel">Guardar actividad</button>
            </form>
        </section>

        <section class="panel-card">
            <h2>Actividades cargadas</h2>

            <div class="tabla-responsive">
                <table>
                    <tr>
                        <th>ID</th>
                        <th>Materia</th>
                        <th>Título</th>
                        <th>Nivel</th>
                        <th>Estado</th>
                    </tr>

                    <?php while ($a = mysqli_fetch_assoc($actividades)) { ?>
                        <tr>
                            <td><?php echo $a["id"]; ?></td>
                            <td><?php echo $a["materia"]; ?></td>
                            <td><?php echo $a["titulo"]; ?></td>
                            <td><?php echo $a["nivel"]; ?></td>
                            <td><?php echo $a["activa"] == 1 ? "Activa" : "Inactiva"; ?></td>
                        </tr>
                    <?php } ?>
                </table>
            </div>
        </section>

    </main>

</div>

</body>
</html>