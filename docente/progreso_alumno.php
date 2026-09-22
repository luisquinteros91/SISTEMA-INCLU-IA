<?php
session_start();

require_once "../includes/conexion.php";

if (!isset($_SESSION["docente_id"])) {
    header("Location: login.php");
    exit;
}

$alumnoId = intval($_GET["id"] ?? 0);

if ($alumnoId <= 0) {
    header("Location: progreso.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| DATOS DEL ALUMNO
|--------------------------------------------------------------------------
*/

$stmtAlumno = $conexion->prepare("
    SELECT
        id,
        dni,
        apellido,
        nombre,
        curso,
        perfil_adaptacion,
        observaciones,
        activo
    FROM alumnos
    WHERE id = ?
");

$stmtAlumno->bind_param("i", $alumnoId);
$stmtAlumno->execute();

$resultadoAlumno = $stmtAlumno->get_result();
$alumno = $resultadoAlumno->fetch_assoc();

$stmtAlumno->close();

if (!$alumno) {
    die("El alumno no existe.");
}

/*
|--------------------------------------------------------------------------
| PROGRESO POR MATERIA
|--------------------------------------------------------------------------
*/

$stmtMaterias = $conexion->prepare("
    SELECT
        m.id,
        m.nombre,
        m.icono,

        COUNT(r.id) AS total_respuestas,

        COUNT(DISTINCT r.actividad_id) AS actividades_realizadas,

        COALESCE(SUM(
            CASE
                WHEN r.correcta = 1 THEN 1
                ELSE 0
            END
        ), 0) AS correctas,

        COALESCE(SUM(
            CASE
                WHEN r.correcta = 0 THEN 1
                ELSE 0
            END
        ), 0) AS incorrectas

    FROM materias m

    LEFT JOIN actividades act
        ON act.materia_id = m.id

    LEFT JOIN respuestas r
        ON r.actividad_id = act.id
        AND r.alumno_id = ?

    GROUP BY
        m.id,
        m.nombre,
        m.icono

    ORDER BY m.nombre ASC
");

$stmtMaterias->bind_param("i", $alumnoId);
$stmtMaterias->execute();

$resultadoMaterias = $stmtMaterias->get_result();

/*
|--------------------------------------------------------------------------
| HISTORIAL DE RESPUESTAS
|--------------------------------------------------------------------------
*/

$stmtHistorial = $conexion->prepare("
    SELECT
        r.id,
        r.correcta,
        r.fecha,
        act.titulo,
        act.nivel,
        m.nombre AS materia,
        m.icono

    FROM respuestas r

    INNER JOIN actividades act
        ON act.id = r.actividad_id

    INNER JOIN materias m
        ON m.id = act.materia_id

    WHERE r.alumno_id = ?

    ORDER BY r.fecha DESC

    LIMIT 30
");

$stmtHistorial->bind_param("i", $alumnoId);
$stmtHistorial->execute();

$resultadoHistorial = $stmtHistorial->get_result();
?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Progreso del alumno | INCLU-IA</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: #f4f7fb;
            color: #1f2937;
        }

        .contenedor {
            max-width: 1150px;
            margin: auto;
            padding: 30px 20px;
        }

        .volver {
            display: inline-block;
            margin-bottom: 20px;
            color: #1d4ed8;
            text-decoration: none;
            font-weight: bold;
        }

        .perfil {
            background: linear-gradient(135deg, #1e3a8a, #2563eb);
            color: white;
            padding: 28px;
            border-radius: 20px;
            margin-bottom: 25px;
        }

        .perfil h1 {
            margin: 0 0 8px;
        }

        .perfil p {
            margin: 5px 0;
            color: #dbeafe;
        }

        .titulo-seccion {
            margin: 28px 0 15px;
        }

        .materias {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
        }

        .tarjeta {
            background: white;
            border-radius: 17px;
            padding: 20px;
            box-shadow: 0 8px 22px rgba(15, 23, 42, 0.07);
        }

        .materia-titulo {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
        }

        .icono {
            width: 47px;
            height: 47px;
            border-radius: 13px;
            background: #dbeafe;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .estadisticas {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 8px;
            text-align: center;
        }

        .dato {
            background: #f8fafc;
            border-radius: 10px;
            padding: 10px;
        }

        .dato strong {
            display: block;
            font-size: 19px;
        }

        .dato span {
            font-size: 11px;
            color: #6b7280;
        }

        .barra-fondo {
            height: 12px;
            background: #e5e7eb;
            border-radius: 20px;
            overflow: hidden;
            margin-top: 15px;
        }

        .barra {
            height: 100%;
            background: linear-gradient(90deg, #2563eb, #22c55e);
        }

        .porcentaje {
            margin-top: 7px;
            text-align: right;
            font-weight: bold;
            color: #1e3a8a;
        }

        .tabla-contenedor {
            background: white;
            border-radius: 17px;
            padding: 20px;
            box-shadow: 0 8px 22px rgba(15, 23, 42, 0.07);
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 13px;
            border-bottom: 1px solid #e5e7eb;
            text-align: left;
        }

        th {
            background: #eff6ff;
            color: #1e3a8a;
        }

        .correcta {
            color: #15803d;
            font-weight: bold;
        }

        .incorrecta {
            color: #b91c1c;
            font-weight: bold;
        }

        .sin-datos {
            color: #6b7280;
            text-align: center;
            padding: 25px;
        }

        @media (max-width: 900px) {
            .materias {
                grid-template-columns: 1fr;
            }
        }
    </style>

</head>

<body>

<div class="contenedor">

    <a href="progreso.php" class="volver">
        ← Volver al progreso general
    </a>

    <section class="perfil">

        <h1>
            <?= htmlspecialchars(
                $alumno["apellido"] . ", " . $alumno["nombre"]
            ) ?>
        </h1>

        <p>
            Curso:
            <?= htmlspecialchars($alumno["curso"]) ?>
            · DNI:
            <?= htmlspecialchars($alumno["dni"]) ?>
        </p>

        <p>
            Perfil de adaptación:
            <?= htmlspecialchars(
                $alumno["perfil_adaptacion"] ?: "Sin especificar"
            ) ?>
        </p>

    </section>

    <h2 class="titulo-seccion">
        📚 Progreso por materia
    </h2>

    <section class="materias">

        <?php while ($materia = $resultadoMaterias->fetch_assoc()): ?>

            <?php
            $total = (int)$materia["total_respuestas"];
            $correctas = (int)$materia["correctas"];
            $incorrectas = (int)$materia["incorrectas"];
            $realizadas = (int)$materia["actividades_realizadas"];

            $porcentaje = 0;

            if ($total > 0) {
                $porcentaje = round(
                    ($correctas / $total) * 100
                );
            }

            $icono = trim($materia["icono"] ?? "");

            if ($icono === "") {
                $icono = "📘";
            }
            ?>

            <article class="tarjeta">

                <div class="materia-titulo">

                    <div class="icono">
                        <?= htmlspecialchars($icono) ?>
                    </div>

                    <strong>
                        <?= htmlspecialchars($materia["nombre"]) ?>
                    </strong>

                </div>

                <div class="estadisticas">

                    <div class="dato">
                        <strong><?= $realizadas ?></strong>
                        <span>Actividades</span>
                    </div>

                    <div class="dato">
                        <strong><?= $correctas ?></strong>
                        <span>Correctas</span>
                    </div>

                    <div class="dato">
                        <strong><?= $incorrectas ?></strong>
                        <span>Incorrectas</span>
                    </div>

                </div>

                <div class="barra-fondo">

                    <div
                        class="barra"
                        style="width: <?= $porcentaje ?>%;"
                    ></div>

                </div>

                <div class="porcentaje">
                    <?= $porcentaje ?>% de aciertos
                </div>

            </article>

        <?php endwhile; ?>

    </section>

    <h2 class="titulo-seccion">
        🕒 Historial de actividades
    </h2>

    <section class="tabla-contenedor">

        <?php if ($resultadoHistorial->num_rows > 0): ?>

            <table>

                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Materia</th>
                        <th>Actividad</th>
                        <th>Nivel</th>
                        <th>Resultado</th>
                    </tr>
                </thead>

                <tbody>

                <?php while ($respuesta = $resultadoHistorial->fetch_assoc()): ?>

                    <tr>

                        <td>
                            <?= date(
                                "d/m/Y H:i",
                                strtotime($respuesta["fecha"])
                            ) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars(
                                ($respuesta["icono"] ?: "📘") .
                                " " .
                                $respuesta["materia"]
                            ) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($respuesta["titulo"]) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars(
                                $respuesta["nivel"] ?: "Sin nivel"
                            ) ?>
                        </td>

                        <td>

                            <?php if ((int)$respuesta["correcta"] === 1): ?>

                                <span class="correcta">
                                    ✅ Correcta
                                </span>

                            <?php else: ?>

                                <span class="incorrecta">
                                    ❌ Incorrecta
                                </span>

                            <?php endif; ?>

                        </td>

                    </tr>

                <?php endwhile; ?>

                </tbody>

            </table>

        <?php else: ?>

            <div class="sin-datos">
                Este alumno todavía no realizó actividades.
            </div>

        <?php endif; ?>

    </section>

</div>

</body>
</html>