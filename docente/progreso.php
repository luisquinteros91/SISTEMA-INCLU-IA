<?php
session_start();

require_once "../includes/conexion.php";

/*
|--------------------------------------------------------------------------
| CONTROL DE SESIÓN
|--------------------------------------------------------------------------
| Si tu sesión utiliza otro nombre, reemplazá docente_id.
*/

if (!isset($_SESSION["docente_id"])) {
    header("Location: login.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| FILTROS
|--------------------------------------------------------------------------
*/

$buscar = trim($_GET["buscar"] ?? "");
$curso = trim($_GET["curso"] ?? "");

/*
|--------------------------------------------------------------------------
| TOTAL DE ACTIVIDADES ACTIVAS
|--------------------------------------------------------------------------
*/

$consultaTotalActividades = $conexion->query("
    SELECT COUNT(*) AS total
    FROM actividades
    WHERE activa = 1
");

$totalActividadesSistema = 0;

if ($consultaTotalActividades) {
    $filaTotal = $consultaTotalActividades->fetch_assoc();
    $totalActividadesSistema = (int)$filaTotal["total"];
}

/*
|--------------------------------------------------------------------------
| CONSULTAR CURSOS
|--------------------------------------------------------------------------
*/

$cursos = $conexion->query("
    SELECT DISTINCT curso
    FROM alumnos
    WHERE curso IS NOT NULL
    AND curso <> ''
    ORDER BY curso ASC
");

/*
|--------------------------------------------------------------------------
| CONSULTAR PROGRESO DE LOS ALUMNOS
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        a.id,
        a.dni,
        a.apellido,
        a.nombre,
        a.curso,
        a.perfil_adaptacion,
        a.activo,

        COUNT(r.id) AS total_respuestas,

        COUNT(DISTINCT r.actividad_id) AS actividades_realizadas,

        COALESCE(SUM(
            CASE
                WHEN r.correcta = 1 THEN 1
                ELSE 0
            END
        ), 0) AS respuestas_correctas,

        COALESCE(SUM(
            CASE
                WHEN r.correcta = 0 THEN 1
                ELSE 0
            END
        ), 0) AS respuestas_incorrectas,

        MAX(r.fecha) AS ultima_actividad

    FROM alumnos a

    LEFT JOIN respuestas r
        ON r.alumno_id = a.id

    WHERE 1 = 1
";

$parametros = [];
$tipos = "";

if ($buscar !== "") {
    $sql .= "
        AND (
            a.nombre LIKE ?
            OR a.apellido LIKE ?
            OR a.dni LIKE ?
        )
    ";

    $textoBuscar = "%" . $buscar . "%";

    $parametros[] = $textoBuscar;
    $parametros[] = $textoBuscar;
    $parametros[] = $textoBuscar;

    $tipos .= "sss";
}

if ($curso !== "") {
    $sql .= " AND a.curso = ? ";

    $parametros[] = $curso;
    $tipos .= "s";
}

$sql .= "
    GROUP BY
        a.id,
        a.dni,
        a.apellido,
        a.nombre,
        a.curso,
        a.perfil_adaptacion,
        a.activo

    ORDER BY
        a.apellido ASC,
        a.nombre ASC
";

$stmt = $conexion->prepare($sql);

if (!$stmt) {
    die("Error al preparar la consulta: " . $conexion->error);
}

if (!empty($parametros)) {
    $stmt->bind_param($tipos, ...$parametros);
}

$stmt->execute();
$resultadoAlumnos = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Progreso | INCLU-IA</title>

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

        .pagina {
            display: flex;
            min-height: 100vh;
        }

        .menu-lateral {
            width: 250px;
            background: linear-gradient(180deg, #172554, #1e3a8a);
            color: white;
            padding: 25px 18px;
        }

        .logo {
            text-align: center;
            font-size: 26px;
            font-weight: bold;
            margin-bottom: 35px;
        }

        .logo span {
            color: #60a5fa;
        }

        .menu-lateral a {
            display: block;
            color: #dbeafe;
            text-decoration: none;
            padding: 13px 15px;
            margin-bottom: 8px;
            border-radius: 11px;
            transition: 0.2s;
        }

        .menu-lateral a:hover,
        .menu-lateral a.activo {
            background: #2563eb;
            color: white;
        }

        .contenido {
            flex: 1;
            padding: 35px;
        }

        .encabezado {
            margin-bottom: 25px;
        }

        .encabezado h1 {
            margin: 0 0 8px;
            font-size: 31px;
        }

        .encabezado p {
            margin: 0;
            color: #6b7280;
        }

        .resumen {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
            margin-bottom: 25px;
        }

        .tarjeta-resumen {
            background: white;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 8px 22px rgba(15, 23, 42, 0.07);
        }

        .tarjeta-resumen .icono {
            font-size: 28px;
            margin-bottom: 10px;
        }

        .tarjeta-resumen .numero {
            font-size: 28px;
            font-weight: bold;
            color: #1e3a8a;
        }

        .tarjeta-resumen .texto {
            color: #6b7280;
            margin-top: 5px;
        }

        .filtros {
            background: white;
            padding: 20px;
            border-radius: 16px;
            box-shadow: 0 8px 22px rgba(15, 23, 42, 0.07);
            margin-bottom: 25px;

            display: grid;
            grid-template-columns: 1fr 220px auto auto;
            gap: 12px;
            align-items: end;
        }

        .campo label {
            display: block;
            font-weight: bold;
            margin-bottom: 7px;
        }

        .campo input,
        .campo select {
            width: 100%;
            padding: 12px;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            font-size: 15px;
            background: white;
        }

        .boton {
            border: none;
            border-radius: 10px;
            padding: 12px 17px;
            text-decoration: none;
            cursor: pointer;
            font-weight: bold;
            display: inline-block;
            text-align: center;
        }

        .boton-buscar {
            background: #2563eb;
            color: white;
        }

        .boton-limpiar {
            background: #e5e7eb;
            color: #374151;
        }

        .lista-alumnos {
            display: grid;
            grid-template-columns: repeat(2, minmax(300px, 1fr));
            gap: 20px;
        }

        .tarjeta-alumno {
            background: white;
            border-radius: 18px;
            padding: 23px;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
        }

        .cabecera-alumno {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            margin-bottom: 20px;
        }

        .datos-alumno {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .avatar {
            width: 55px;
            height: 55px;
            border-radius: 16px;
            background: #dbeafe;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
        }

        .nombre-alumno {
            font-size: 19px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .curso {
            color: #6b7280;
            font-size: 14px;
        }

        .estado {
            height: fit-content;
            padding: 6px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }

        .estado-activo {
            background: #dcfce7;
            color: #166534;
        }

        .estado-inactivo {
            background: #e5e7eb;
            color: #4b5563;
        }

        .estadisticas {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-bottom: 18px;
        }

        .dato {
            background: #f8fafc;
            border-radius: 12px;
            padding: 12px;
            text-align: center;
        }

        .dato strong {
            display: block;
            font-size: 20px;
            margin-bottom: 4px;
        }

        .dato span {
            color: #6b7280;
            font-size: 12px;
        }

        .barra-titulo {
            display: flex;
            justify-content: space-between;
            margin-bottom: 7px;
            font-size: 14px;
            font-weight: bold;
        }

        .barra-fondo {
            width: 100%;
            height: 13px;
            background: #e5e7eb;
            border-radius: 20px;
            overflow: hidden;
            margin-bottom: 15px;
        }

        .barra {
            height: 100%;
            background: linear-gradient(90deg, #2563eb, #22c55e);
            border-radius: 20px;
        }

        .ultima-actividad {
            font-size: 13px;
            color: #6b7280;
            margin-bottom: 16px;
        }

        .boton-detalle {
            width: 100%;
            background: #1e3a8a;
            color: white;
        }

        .sin-datos {
            background: white;
            border-radius: 16px;
            padding: 40px;
            text-align: center;
            color: #6b7280;
            grid-column: 1 / -1;
        }

        @media (max-width: 1000px) {
            .lista-alumnos {
                grid-template-columns: 1fr;
            }

            .filtros {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 760px) {
            .pagina {
                display: block;
            }

            .menu-lateral {
                width: 100%;
            }

            .contenido {
                padding: 20px;
            }

            .resumen {
                grid-template-columns: 1fr;
            }

            .filtros {
                grid-template-columns: 1fr;
            }

            .estadisticas {
                grid-template-columns: 1fr;
            }
        }
    </style>

</head>

<body>

<div class="pagina">

    <aside class="menu-lateral">

        <div class="logo">
            INCLU-<span>IA</span>
        </div>

        <a href="dashboard.php">🏠 Inicio</a>
        <a href="alumnos.php">👨‍🎓 Alumnos</a>
        <a href="materias.php">📚 Materias</a>
        <a href="actividades.php">📝 Actividades</a>
        <a href="progreso.php" class="activo">📊 Progreso</a>
        <a href="#">⚙️ Configuración</a>
        <a href="salir.php">🚪 Cerrar sesión</a>

    </aside>

    <main class="contenido">

        <div class="encabezado">

            <h1>📊 Progreso de los estudiantes</h1>

            <p>
                Consulte las actividades realizadas y el rendimiento
                de cada alumno.
            </p>

        </div>

        <?php
        $cantidadAlumnos = $resultadoAlumnos->num_rows;
        ?>

        <section class="resumen">

            <div class="tarjeta-resumen">

                <div class="icono">👨‍🎓</div>

                <div class="numero">
                    <?= $cantidadAlumnos ?>
                </div>

                <div class="texto">
                    Alumnos encontrados
                </div>

            </div>

            <div class="tarjeta-resumen">

                <div class="icono">📝</div>

                <div class="numero">
                    <?= $totalActividadesSistema ?>
                </div>

                <div class="texto">
                    Actividades activas
                </div>

            </div>

            <div class="tarjeta-resumen">

                <div class="icono">🎯</div>

                <div class="numero">
                    Seguimiento
                </div>

                <div class="texto">
                    Rendimiento individual
                </div>

            </div>

        </section>

        <form method="GET" class="filtros">

            <div class="campo">

                <label for="buscar">
                    Buscar alumno
                </label>

                <input
                    type="text"
                    id="buscar"
                    name="buscar"
                    value="<?= htmlspecialchars($buscar) ?>"
                    placeholder="Nombre, apellido o DNI"
                >

            </div>

            <div class="campo">

                <label for="curso">
                    Curso
                </label>

                <select id="curso" name="curso">

                    <option value="">
                        Todos los cursos
                    </option>

                    <?php if ($cursos): ?>

                        <?php while ($filaCurso = $cursos->fetch_assoc()): ?>

                            <option
                                value="<?= htmlspecialchars($filaCurso["curso"]) ?>"
                                <?= $curso === $filaCurso["curso"] ? "selected" : "" ?>
                            >
                                <?= htmlspecialchars($filaCurso["curso"]) ?>
                            </option>

                        <?php endwhile; ?>

                    <?php endif; ?>

                </select>

            </div>

            <button
                type="submit"
                class="boton boton-buscar"
            >
                🔍 Buscar
            </button>

            <a
                href="progreso.php"
                class="boton boton-limpiar"
            >
                Limpiar
            </a>

        </form>

        <section class="lista-alumnos">

            <?php if ($resultadoAlumnos->num_rows > 0): ?>

                <?php while ($alumno = $resultadoAlumnos->fetch_assoc()): ?>

                    <?php
                    $totalRespuestas =
                        (int)$alumno["total_respuestas"];

                    $correctas =
                        (int)$alumno["respuestas_correctas"];

                    $incorrectas =
                        (int)$alumno["respuestas_incorrectas"];

                    $actividadesRealizadas =
                        (int)$alumno["actividades_realizadas"];

                    $porcentajeAciertos = 0;

                    if ($totalRespuestas > 0) {
                        $porcentajeAciertos = round(
                            ($correctas / $totalRespuestas) * 100
                        );
                    }

                    $porcentajeCompletado = 0;

                    if ($totalActividadesSistema > 0) {
                        $porcentajeCompletado = round(
                            ($actividadesRealizadas /
                            $totalActividadesSistema) * 100
                        );

                        if ($porcentajeCompletado > 100) {
                            $porcentajeCompletado = 100;
                        }
                    }

                    $ultimaActividad = "Todavía no realizó actividades.";

                    if (!empty($alumno["ultima_actividad"])) {
                        $ultimaActividad = date(
                            "d/m/Y H:i",
                            strtotime($alumno["ultima_actividad"])
                        );
                    }
                    ?>

                    <article class="tarjeta-alumno">

                        <div class="cabecera-alumno">

                            <div class="datos-alumno">

                                <div class="avatar">
                                    👤
                                </div>

                                <div>

                                    <div class="nombre-alumno">
                                        <?= htmlspecialchars(
                                            $alumno["apellido"] . ", " .
                                            $alumno["nombre"]
                                        ) ?>
                                    </div>

                                    <div class="curso">
                                        Curso:
                                        <?= htmlspecialchars($alumno["curso"]) ?>

                                        · DNI:
                                        <?= htmlspecialchars($alumno["dni"]) ?>
                                    </div>

                                </div>

                            </div>

                            <?php if ((int)$alumno["activo"] === 1): ?>

                                <span class="estado estado-activo">
                                    Activo
                                </span>

                            <?php else: ?>

                                <span class="estado estado-inactivo">
                                    Inactivo
                                </span>

                            <?php endif; ?>

                        </div>

                        <div class="estadisticas">

                            <div class="dato">

                                <strong>
                                    <?= $actividadesRealizadas ?>
                                </strong>

                                <span>
                                    Actividades
                                </span>

                            </div>

                            <div class="dato">

                                <strong>
                                    <?= $correctas ?>
                                </strong>

                                <span>
                                    Correctas
                                </span>

                            </div>

                            <div class="dato">

                                <strong>
                                    <?= $incorrectas ?>
                                </strong>

                                <span>
                                    Incorrectas
                                </span>

                            </div>

                        </div>

                        <div class="barra-titulo">

                            <span>Porcentaje de aciertos</span>

                            <span>
                                <?= $porcentajeAciertos ?>%
                            </span>

                        </div>

                        <div class="barra-fondo">

                            <div
                                class="barra"
                                style="width:
                                <?= $porcentajeAciertos ?>%;"
                            ></div>

                        </div>

                        <div class="barra-titulo">

                            <span>Actividades completadas</span>

                            <span>
                                <?= $porcentajeCompletado ?>%
                            </span>

                        </div>

                        <div class="barra-fondo">

                            <div
                                class="barra"
                                style="width:
                                <?= $porcentajeCompletado ?>%;"
                            ></div>

                        </div>

                        <div class="ultima-actividad">

                            <strong>Última actividad:</strong>

                            <?= htmlspecialchars($ultimaActividad) ?>

                        </div>

                        <a
                            href="progreso_alumno.php?id=<?= intval(
                                $alumno["id"]
                            ) ?>"
                            class="boton boton-detalle"
                        >
                            Ver progreso detallado
                        </a>

                    </article>

                <?php endwhile; ?>

            <?php else: ?>

                <div class="sin-datos">

                    No se encontraron alumnos con los filtros seleccionados.

                </div>

            <?php endif; ?>

        </section>

    </main>

</div>

</body>
</html>