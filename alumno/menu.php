<?php
session_start();

require_once "../includes/conexion.php";

if (!isset($_SESSION["alumno_id"])) {
    header("Location: login.php");
    exit;
}

$alumnoId = (int)$_SESSION["alumno_id"];

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
        perfil_adaptacion
    FROM alumnos
    WHERE id = ?
    LIMIT 1
");

$stmtAlumno->bind_param("i", $alumnoId);
$stmtAlumno->execute();

$resultadoAlumno = $stmtAlumno->get_result();
$alumno = $resultadoAlumno->fetch_assoc();

$stmtAlumno->close();

if (!$alumno) {
    session_destroy();
    header("Location: login.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| MATERIAS Y PROGRESO
|--------------------------------------------------------------------------
| total_actividades:
| Cantidad de actividades activas de cada materia.
|
| realizadas:
| Cantidad de actividades diferentes respondidas por el alumno.
|--------------------------------------------------------------------------
*/

$stmtMaterias = $conexion->prepare("
    SELECT
        m.id,
        m.nombre,
        m.icono,

        COUNT(DISTINCT act.id) AS total_actividades,

        COUNT(
            DISTINCT CASE
                WHEN r.alumno_id = ? THEN r.actividad_id
                ELSE NULL
            END
        ) AS realizadas

    FROM materias m

    LEFT JOIN actividades act
        ON act.materia_id = m.id
        AND act.activa = 1

    LEFT JOIN respuestas r
        ON r.actividad_id = act.id
        AND r.alumno_id = ?

    GROUP BY
        m.id,
        m.nombre,
        m.icono

    ORDER BY
        m.nombre ASC
");

$stmtMaterias->bind_param("ii", $alumnoId, $alumnoId);
$stmtMaterias->execute();

$resultadoMaterias = $stmtMaterias->get_result();

/*
|--------------------------------------------------------------------------
| RESUMEN GENERAL
|--------------------------------------------------------------------------
*/

$stmtResumen = $conexion->prepare("
    SELECT
        COUNT(DISTINCT r.actividad_id) AS actividades_realizadas,

        COALESCE(
            SUM(
                CASE
                    WHEN r.correcta = 1 THEN 1
                    ELSE 0
                END
            ),
            0
        ) AS correctas,

        COUNT(r.id) AS total_respuestas

    FROM respuestas r

    WHERE r.alumno_id = ?
");

$stmtResumen->bind_param("i", $alumnoId);
$stmtResumen->execute();

$resumen = $stmtResumen->get_result()->fetch_assoc();
$stmtResumen->close();

$actividadesRealizadas = (int)($resumen["actividades_realizadas"] ?? 0);
$respuestasCorrectas = (int)($resumen["correctas"] ?? 0);
$totalRespuestas = (int)($resumen["total_respuestas"] ?? 0);

$porcentajeAciertos = 0;

if ($totalRespuestas > 0) {
    $porcentajeAciertos = round(
        ($respuestasCorrectas / $totalRespuestas) * 100
    );
}

$nombreAlumno = trim($alumno["nombre"]);
$nombreCompleto = trim(
    $alumno["nombre"] . " " . $alumno["apellido"]
);
?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <meta
        name="theme-color"
        content="#1e3a8a"
    >

    <title>Mi espacio | INCLU-IA</title>

    <link
        href="../assets/css/estilo.css"
        rel="stylesheet"
    >

    <style>
        * {
            box-sizing: border-box;
        }

        :root {
            --azul-oscuro: #172554;
            --azul: #2563eb;
            --azul-claro: #dbeafe;
            --celeste: #eff6ff;
            --verde: #16a34a;
            --verde-claro: #dcfce7;
            --amarillo: #f59e0b;
            --fondo: #f4f7fb;
            --texto: #1f2937;
            --texto-suave: #64748b;
            --blanco: #ffffff;
            --borde: #e2e8f0;
            --tamano-base: 16px;
        }

        body {
            margin: 0;
            padding: 0;
            min-height: 100vh;
            font-family: Arial, Helvetica, sans-serif;
            font-size: var(--tamano-base);
            color: var(--texto);
            background:
                linear-gradient(
                    180deg,
                    #dbeafe 0,
                    #f4f7fb 230px
                );
        }

        body.texto-grande {
            --tamano-base: 19px;
        }

        body.alto-contraste {
            --fondo: #000000;
            --texto: #ffffff;
            --texto-suave: #f8fafc;
            --blanco: #111827;
            --borde: #ffffff;
            --azul-claro: #172554;
            --celeste: #111827;

            background: #000000;
        }

        button,
        a {
            font-family: inherit;
        }

        .app-alumno {
            width: 100%;
            max-width: 1100px;
            min-height: 100vh;
            margin: 0 auto;
            padding: 22px 20px 105px;
        }

        .app-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            margin-bottom: 20px;
        }

        .datos-bienvenida h1 {
            margin: 0 0 7px;
            color: var(--azul-oscuro);
            font-size: 2rem;
        }

        .alto-contraste .datos-bienvenida h1 {
            color: white;
        }

        .datos-bienvenida p {
            margin: 0;
            color: var(--texto-suave);
        }

        .curso-alumno {
            display: inline-block;
            margin-top: 9px;
            padding: 6px 12px;
            border-radius: 20px;
            color: #1e3a8a;
            background: #dbeafe;
            font-weight: bold;
            font-size: 0.88rem;
        }

        .avatar-alumno {
            width: 76px;
            height: 76px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 4px solid white;
            border-radius: 24px;
            background: linear-gradient(
                135deg,
                #fde68a,
                #f59e0b
            );
            box-shadow: 0 10px 25px rgba(15, 23, 42, 0.15);
            font-size: 40px;
        }

        .herramientas-accesibilidad {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
        }

        .boton-herramienta {
            min-height: 44px;
            border: 1px solid var(--borde);
            border-radius: 12px;
            padding: 10px 14px;
            color: var(--texto);
            background: var(--blanco);
            box-shadow: 0 5px 15px rgba(15, 23, 42, 0.06);
            font-size: 0.92rem;
            font-weight: bold;
            cursor: pointer;
        }

        .boton-herramienta:focus,
        .materia-app:focus,
        .barra-inferior a:focus {
            outline: 4px solid rgba(37, 99, 235, 0.35);
            outline-offset: 3px;
        }

        .bienvenida {
            display: grid;
            grid-template-columns: 1fr auto;
            align-items: center;
            gap: 20px;
            margin-bottom: 25px;
            padding: 26px;
            color: white;
            border-radius: 24px;
            background: linear-gradient(
                135deg,
                #1e3a8a,
                #2563eb
            );
            box-shadow: 0 14px 30px rgba(30, 58, 138, 0.25);
        }

        .bienvenida h2 {
            margin: 0 0 8px;
            font-size: 1.7rem;
        }

        .bienvenida p {
            margin: 0;
            color: #dbeafe;
            line-height: 1.5;
        }

        .btn-audio {
            min-width: 135px;
            min-height: 50px;
            border: 2px solid rgba(255, 255, 255, 0.65);
            border-radius: 15px;
            padding: 12px 17px;
            color: #1e3a8a;
            background: white;
            font-size: 1rem;
            font-weight: bold;
            cursor: pointer;
        }

        .resumen-progreso {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 28px;
        }

        .dato-progreso {
            padding: 18px;
            text-align: center;
            border: 1px solid var(--borde);
            border-radius: 18px;
            background: var(--blanco);
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.07);
        }

        .dato-progreso .dato-icono {
            display: block;
            margin-bottom: 7px;
            font-size: 28px;
        }

        .dato-progreso strong {
            display: block;
            margin-bottom: 4px;
            color: #1e3a8a;
            font-size: 1.45rem;
        }

        .alto-contraste .dato-progreso strong {
            color: white;
        }

        .dato-progreso span {
            color: var(--texto-suave);
            font-size: 0.83rem;
        }

        .titulo-seccion {
            margin: 0 0 16px;
            color: var(--texto);
            font-size: 1.35rem;
        }

        .subtitulo-seccion {
            margin: -8px 0 18px;
            color: var(--texto-suave);
        }

        .materias-app {
            display: grid;
            grid-template-columns: repeat(
                auto-fit,
                minmax(245px, 1fr)
            );
            gap: 18px;
        }

        .materia-app {
            position: relative;
            min-height: 205px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 21px;
            overflow: hidden;
            text-decoration: none;
            color: var(--texto);
            border: 2px solid transparent;
            border-radius: 22px;
            background: var(--blanco);
            box-shadow: 0 9px 23px rgba(15, 23, 42, 0.09);
            transition:
                transform 0.2s ease,
                box-shadow 0.2s ease,
                border-color 0.2s ease;
        }

        .materia-app:hover {
            transform: translateY(-4px);
            border-color: #93c5fd;
            box-shadow: 0 15px 28px rgba(15, 23, 42, 0.13);
        }

        .parte-superior-materia {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .icono-materia {
            width: 62px;
            height: 62px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 18px;
            background: var(--azul-claro);
            font-size: 32px;
        }

        .nombre-materia {
            font-size: 1.15rem;
            line-height: 1.25;
        }

        .datos-materia {
            margin-top: 17px;
        }

        .estado-materia {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 8px;
            color: var(--texto-suave);
            font-size: 0.83rem;
        }

        .barra-progreso {
            width: 100%;
            height: 12px;
            overflow: hidden;
            border-radius: 20px;
            background: #e2e8f0;
        }

        .barra-progreso span {
            display: block;
            height: 100%;
            border-radius: 20px;
            background: linear-gradient(
                90deg,
                #2563eb,
                #22c55e
            );
        }

        .boton-comenzar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 16px;
            padding: 11px 13px;
            color: #1e3a8a;
            border-radius: 12px;
            background: #eff6ff;
            font-weight: bold;
        }

        .sin-actividades {
            color: #92400e;
            background: #fef3c7;
        }

        .sin-materias {
            grid-column: 1 / -1;
            padding: 40px 20px;
            text-align: center;
            color: var(--texto-suave);
            border-radius: 20px;
            background: var(--blanco);
        }

        .barra-inferior {
            position: fixed;
            z-index: 100;
            right: 0;
            bottom: 0;
            left: 0;
            min-height: 76px;
            display: flex;
            justify-content: center;
            gap: 8px;
            padding:
                8px
                max(10px, calc((100vw - 650px) / 2));
            border-top: 1px solid var(--borde);
            background: rgba(255, 255, 255, 0.96);
            box-shadow: 0 -7px 22px rgba(15, 23, 42, 0.1);
            backdrop-filter: blur(10px);
        }

        .alto-contraste .barra-inferior {
            background: #111827;
        }

        .barra-inferior a {
            min-width: 85px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 4px;
            padding: 7px 12px;
            text-decoration: none;
            color: #64748b;
            border-radius: 13px;
            font-size: 1.25rem;
        }

        .barra-inferior a span {
            font-size: 0.72rem;
            font-weight: bold;
        }

        .barra-inferior a.activo {
            color: #1d4ed8;
            background: #dbeafe;
        }

        .alto-contraste .barra-inferior a {
            color: white;
        }

        @media (max-width: 700px) {
            .app-alumno {
                padding: 18px 15px 100px;
            }

            .datos-bienvenida h1 {
                font-size: 1.55rem;
            }

            .avatar-alumno {
                width: 62px;
                height: 62px;
                font-size: 32px;
            }

            .bienvenida {
                grid-template-columns: 1fr;
                padding: 22px;
            }

            .btn-audio {
                width: 100%;
            }

            .resumen-progreso {
                grid-template-columns: 1fr;
            }

            .materias-app {
                grid-template-columns: 1fr;
            }

            .materia-app {
                min-height: 185px;
            }

            .barra-inferior {
                justify-content: space-around;
                padding: 7px 4px;
            }

            .barra-inferior a {
                min-width: 67px;
                padding: 6px;
            }
        }
    </style>

</head>

<body>

<div class="app-alumno">

    <header class="app-header">

        <div class="datos-bienvenida">

            <h1>
                Hola,
                <?= htmlspecialchars($nombreAlumno) ?>
                👋
            </h1>

            <p>
                Hoy vamos a aprender juntos.
            </p>

            <span class="curso-alumno">
                🎒 Curso:
                <?= htmlspecialchars($alumno["curso"]) ?>
            </span>

        </div>

        <div
            class="avatar-alumno"
            aria-label="Avatar del alumno"
        >
            😊
        </div>

    </header>

    <section
        class="herramientas-accesibilidad"
        aria-label="Herramientas de accesibilidad"
    >

        <button
            type="button"
            class="boton-herramienta"
            onclick="cambiarTamanoTexto()"
        >
            🔠 Letra grande
        </button>

        <button
            type="button"
            class="boton-herramienta"
            onclick="cambiarContraste()"
        >
            ◐ Alto contraste
        </button>

        <button
            type="button"
            class="boton-herramienta"
            onclick="detenerVoz()"
        >
            🔇 Detener audio
        </button>

    </section>

    <section class="bienvenida">

        <div>

            <h2>🌟 Bienvenido a INCLU-IA</h2>

            <p>
                Elegí una materia. Podés escuchar las instrucciones
                todas las veces que necesites.
            </p>

        </div>

        <button
            type="button"
            onclick="hablarBienvenida()"
            class="btn-audio"
        >
            🔊 Escuchar
        </button>

    </section>

    <section class="resumen-progreso">

        <div class="dato-progreso">

            <span class="dato-icono">📝</span>

            <strong>
                <?= $actividadesRealizadas ?>
            </strong>

            <span>
                Actividades realizadas
            </span>

        </div>

        <div class="dato-progreso">

            <span class="dato-icono">✅</span>

            <strong>
                <?= $respuestasCorrectas ?>
            </strong>

            <span>
                Respuestas correctas
            </span>

        </div>

        <div class="dato-progreso">

            <span class="dato-icono">🎯</span>

            <strong>
                <?= $porcentajeAciertos ?>%
            </strong>

            <span>
                Porcentaje de aciertos
            </span>

        </div>

    </section>

    <h2 class="titulo-seccion">
        📚 Mis materias
    </h2>

    <p class="subtitulo-seccion">
        Tocá una tarjeta para ver sus actividades.
    </p>

    <section class="materias-app">

        <?php if ($resultadoMaterias->num_rows > 0): ?>

            <?php while ($materia = $resultadoMaterias->fetch_assoc()): ?>

                <?php
                $totalActividades =
                    (int)$materia["total_actividades"];

                $realizadas =
                    (int)$materia["realizadas"];

                $porcentajeMateria = 0;

                if ($totalActividades > 0) {
                    $porcentajeMateria = round(
                        ($realizadas / $totalActividades) * 100
                    );

                    if ($porcentajeMateria > 100) {
                        $porcentajeMateria = 100;
                    }
                }

                $iconoMateria = trim(
                    $materia["icono"] ?? ""
                );

                if ($iconoMateria === "") {
                    $iconoMateria = "📘";
                }
                ?>

                <a
                    href="actividades.php?materia_id=<?= (int)$materia["id"] ?>"
                    class="materia-app"
                    aria-label="Abrir actividades de <?= htmlspecialchars(
                        $materia["nombre"]
                    ) ?>"
                >

                    <div>

                        <div class="parte-superior-materia">

                            <div class="icono-materia">
                                <?= htmlspecialchars($iconoMateria) ?>
                            </div>

                            <strong class="nombre-materia">
                                <?= htmlspecialchars(
                                    $materia["nombre"]
                                ) ?>
                            </strong>

                        </div>

                        <div class="datos-materia">

                            <?php if ($totalActividades > 0): ?>

                                <div class="estado-materia">

                                    <span>
                                        <?= $realizadas ?>
                                        de
                                        <?= $totalActividades ?>
                                        realizadas
                                    </span>

                                    <strong>
                                        <?= $porcentajeMateria ?>%
                                    </strong>

                                </div>

                                <div
                                    class="barra-progreso"
                                    aria-label="Progreso <?= $porcentajeMateria ?> por ciento"
                                >
                                    <span
                                        style="width: <?= $porcentajeMateria ?>%;"
                                    ></span>
                                </div>

                            <?php else: ?>

                                <div class="estado-materia">
                                    <span>
                                        Todavía no hay actividades.
                                    </span>
                                </div>

                            <?php endif; ?>

                        </div>

                    </div>

                    <?php if ($totalActividades > 0): ?>

                        <div class="boton-comenzar">
                            <span>Ver actividades</span>
                            <span>→</span>
                        </div>

                    <?php else: ?>

                        <div class="boton-comenzar sin-actividades">
                            <span>Próximamente</span>
                            <span>⏳</span>
                        </div>

                    <?php endif; ?>

                </a>

            <?php endwhile; ?>

        <?php else: ?>

            <div class="sin-materias">

                <div style="font-size: 45px; margin-bottom: 12px;">
                    📚
                </div>

                Todavía no hay materias disponibles.

            </div>

        <?php endif; ?>

    </section>

</div>

<nav
    class="barra-inferior"
    aria-label="Menú principal"
>

    <a
        href="menu.php"
        class="activo"
    >
        🏠
        <span>Inicio</span>
    </a>

    <a href="menu.php#materias">
        📚
        <span>Materias</span>
    </a>

    <a href="progreso.php">
        📊
        <span>Progreso</span>
    </a>

    <a href="salir.php">
        🚪
        <span>Salir</span>
    </a>

</nav>

<script>
const nombreAlumno = <?= json_encode(
    $nombreAlumno,
    JSON_UNESCAPED_UNICODE
) ?>;

function hablar(texto) {
    if (!("speechSynthesis" in window)) {
        alert(
            "Este navegador no permite reproducir voz."
        );
        return;
    }

    window.speechSynthesis.cancel();

    const voz = new SpeechSynthesisUtterance(texto);

    voz.lang = "es-AR";
    voz.rate = 0.82;
    voz.pitch = 1;
    voz.volume = 1;

    window.speechSynthesis.speak(voz);
}

function hablarBienvenida() {
    const texto =
        "Hola " +
        nombreAlumno +
        ". Bienvenido a Inclu IA. " +
        "Elegí una materia para comenzar. " +
        "Podés escuchar las instrucciones todas las veces que necesites.";

    hablar(texto);
}

function detenerVoz() {
    if ("speechSynthesis" in window) {
        window.speechSynthesis.cancel();
    }
}

function cambiarTamanoTexto() {
    document.body.classList.toggle("texto-grande");

    const activo =
        document.body.classList.contains("texto-grande");

    localStorage.setItem(
        "incluiaTextoGrande",
        activo ? "1" : "0"
    );
}

function cambiarContraste() {
    document.body.classList.toggle("alto-contraste");

    const activo =
        document.body.classList.contains("alto-contraste");

    localStorage.setItem(
        "incluiaAltoContraste",
        activo ? "1" : "0"
    );
}

document.addEventListener("DOMContentLoaded", function () {
    if (
        localStorage.getItem("incluiaTextoGrande") === "1"
    ) {
        document.body.classList.add("texto-grande");
    }

    if (
        localStorage.getItem("incluiaAltoContraste") === "1"
    ) {
        document.body.classList.add("alto-contraste");
    }
});
</script>

</body>
</html>