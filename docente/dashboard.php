<?php
session_start();
include "../includes/conexion.php";

if (!isset($_SESSION["docente_id"])) {
    header("Location: login.php");
    exit;
}

$total_alumnos = 0;
$total_materias = 0;
$total_actividades = 0;

$res = mysqli_query($conexion, "SELECT COUNT(*) AS total FROM alumnos");
if ($res) {
    $fila = mysqli_fetch_assoc($res);
    $total_alumnos = $fila["total"];
}

$res = mysqli_query($conexion, "SELECT COUNT(*) AS total FROM materias");
if ($res) {
    $fila = mysqli_fetch_assoc($res);
    $total_materias = $fila["total"];
}

$res = mysqli_query($conexion, "SELECT COUNT(*) AS total FROM actividades");
if ($res) {
    $fila = mysqli_fetch_assoc($res);
    $total_actividades = $fila["total"];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Docente | INCLU-IA</title>
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
            <a href="dashboard.php" class="activo">🏠 Inicio</a>
            <a href="alumnos.php">👨‍🎓 Alumnos</a>
            <a href="materia.php">📚 Materias</a>
            <a href="actividades.php">📝 Actividades</a>
            <a href="progreso.php">📊 Progreso</a>
            <a href="#">🤖 IA</a>
            <a href="#">⚙ Configuración</a>
        </nav>

        <a href="salir.php" class="logout">Cerrar sesión</a>
    </aside>

    <main class="main-panel">

        <section class="topbar">
            <div>
                <h1>Dashboard Docente</h1>
                <p>Bienvenido/a, <?php echo $_SESSION["docente_nombre"]; ?></p>
            </div>

            <div class="perfil-docente">
                <span>👤</span>
                <strong><?php echo $_SESSION["docente_rol"]; ?></strong>
            </div>
        </section>

        <section class="hero-dashboard">
            <div>
                <h2>Plataforma inteligente para alumnos inclusivos</h2>
                <p>
                    Gestioná alumnos, materias, actividades adaptadas y seguimiento pedagógico
                    desde un solo lugar.
                </p>
            </div>
            <div class="hero-badge">Feria de Ciencias 2026</div>
        </section>

        <section class="stats-grid">
            <div class="stat-card">
                <span>👨‍🎓</span>
                <h3><?php echo $total_alumnos; ?></h3>
                <p>Alumnos</p>
            </div>

            <div class="stat-card">
                <span>📚</span>
                <h3><?php echo $total_materias; ?></h3>
                <p>Materias</p>
            </div>

            <div class="stat-card">
                <span>📝</span>
                <h3><?php echo $total_actividades; ?></h3>
                <p>Actividades</p>
            </div>

            <div class="stat-card">
                <span>🤖</span>
                <h3>IA</h3>
                <p>Próximamente</p>
            </div>
        </section>

        <section class="modulos-grid">
            <a href="alumnos.php" class="modulo-card">
                <div class="modulo-icon">👨‍🎓</div>
                <div>
                    <h3>Gestión de alumnos</h3>
                    <p>Registrar perfiles, cursos y adaptaciones.</p>
                </div>
            </a>

            <a href="materias.php" class="modulo-card">
                <div class="modulo-icon">📚</div>
                <div>
                    <h3>Materias</h3>
                    <p>Organizar áreas de aprendizaje.</p>
                </div>
            </a>

            <a href="actividades.php" class="modulo-card">
                <div class="modulo-icon">📝</div>
                <div>
                    <h3>Actividades adaptadas</h3>
                    <p>Crear consignas simples, visuales y accesibles.</p>
                </div>
            </a>

            <a href="progreso.php" class="modulo-card">
                <div class="modulo-icon">📊</div>
                <div>
                    <h3>Seguimiento</h3>
                    <p>Ver avances, respuestas y evolución.</p>
                </div>
            </a>

            <a href="#" class="modulo-card">
                <div class="modulo-icon">🧩</div>
                <div>
                    <h3>Recursos inclusivos</h3>
                    <p>Pictogramas, imágenes y audios.</p>
                </div>
            </a>

            <a href="#" class="modulo-card">
                <div class="modulo-icon">🤖</div>
                <div>
                    <h3>Asistente IA</h3>
                    <p>Generar actividades adaptadas automáticamente.</p>
                </div>
            </a>
        </section>

    </main>

</div>

</body>
</html>