<?php
session_start();

require_once "../includes/conexion.php";

/*
|--------------------------------------------------------------------------
| CONTROL DE SESIÓN
|--------------------------------------------------------------------------
| Si tu sesión docente usa otro nombre, reemplazá 'docente_id'
| por el nombre que tengas en validar.php.
*/

if (!isset($_SESSION["docente_id"])) {
    header("Location: login.php");
    exit;
}

$mensaje = "";
$tipoMensaje = "";

/*
|--------------------------------------------------------------------------
| AGREGAR MATERIA
|--------------------------------------------------------------------------
*/

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["guardar_materia"])
) {
    $nombre = trim($_POST["nombre"] ?? "");
    $icono = trim($_POST["icono"] ?? "");

    if ($nombre === "") {
        $mensaje = "Debe escribir el nombre de la materia.";
        $tipoMensaje = "error";
    } else {
        /*
        Verificamos que no exista otra materia con el mismo nombre.
        */

        $verificar = $conexion->prepare("
            SELECT id
            FROM materias
            WHERE nombre = ?
            LIMIT 1
        ");

        $verificar->bind_param("s", $nombre);
        $verificar->execute();

        $resultadoVerificacion = $verificar->get_result();

        if ($resultadoVerificacion->num_rows > 0) {
            $mensaje = "Ya existe una materia con ese nombre.";
            $tipoMensaje = "error";
        } else {
            $stmt = $conexion->prepare("
                INSERT INTO materias (nombre, icono)
                VALUES (?, ?)
            ");

            $stmt->bind_param("ss", $nombre, $icono);

            if ($stmt->execute()) {
                $mensaje = "Materia agregada correctamente.";
                $tipoMensaje = "exito";
            } else {
                $mensaje = "No se pudo agregar la materia.";
                $tipoMensaje = "error";
            }

            $stmt->close();
        }

        $verificar->close();
    }
}

/*
|--------------------------------------------------------------------------
| EDITAR MATERIA
|--------------------------------------------------------------------------
*/

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["actualizar_materia"])
) {
    $id = intval($_POST["id"] ?? 0);
    $nombre = trim($_POST["nombre"] ?? "");
    $icono = trim($_POST["icono"] ?? "");

    if ($id <= 0 || $nombre === "") {
        $mensaje = "Los datos de la materia no son válidos.";
        $tipoMensaje = "error";
    } else {
        /*
        Verificamos que no exista otra materia con el mismo nombre.
        */

        $verificar = $conexion->prepare("
            SELECT id
            FROM materias
            WHERE nombre = ?
            AND id <> ?
            LIMIT 1
        ");

        $verificar->bind_param("si", $nombre, $id);
        $verificar->execute();

        $resultadoVerificacion = $verificar->get_result();

        if ($resultadoVerificacion->num_rows > 0) {
            $mensaje = "Ya existe otra materia con ese nombre.";
            $tipoMensaje = "error";
        } else {
            $stmt = $conexion->prepare("
                UPDATE materias
                SET nombre = ?,
                    icono = ?
                WHERE id = ?
            ");

            $stmt->bind_param("ssi", $nombre, $icono, $id);

            if ($stmt->execute()) {
                $mensaje = "Materia actualizada correctamente.";
                $tipoMensaje = "exito";
            } else {
                $mensaje = "No se pudo actualizar la materia.";
                $tipoMensaje = "error";
            }

            $stmt->close();
        }

        $verificar->close();
    }
}

/*
|--------------------------------------------------------------------------
| ELIMINAR MATERIA
|--------------------------------------------------------------------------
*/

if (isset($_GET["eliminar"])) {
    $idEliminar = intval($_GET["eliminar"]);

    if ($idEliminar > 0) {
        /*
        Intentamos verificar si la materia tiene actividades.
        Esto supone que actividades tiene una columna materia_id.
        */

        $puedeEliminar = true;

        $consultaColumna = $conexion->query("
            SHOW COLUMNS FROM actividades LIKE 'materia_id'
        ");

        if ($consultaColumna && $consultaColumna->num_rows > 0) {
            $verificarActividades = $conexion->prepare("
                SELECT COUNT(*) AS total
                FROM actividades
                WHERE materia_id = ?
            ");

            $verificarActividades->bind_param("i", $idEliminar);
            $verificarActividades->execute();

            $resultadoActividades =
                $verificarActividades->get_result()->fetch_assoc();

            if ((int)$resultadoActividades["total"] > 0) {
                $puedeEliminar = false;
            }

            $verificarActividades->close();
        }

        if (!$puedeEliminar) {
            $mensaje = "No se puede eliminar la materia porque tiene actividades asociadas.";
            $tipoMensaje = "error";
        } else {
            $stmt = $conexion->prepare("
                DELETE FROM materias
                WHERE id = ?
            ");

            $stmt->bind_param("i", $idEliminar);

            if ($stmt->execute()) {
                $mensaje = "Materia eliminada correctamente.";
                $tipoMensaje = "exito";
            } else {
                $mensaje = "No se pudo eliminar la materia.";
                $tipoMensaje = "error";
            }

            $stmt->close();
        }
    }
}

/*
|--------------------------------------------------------------------------
| CARGAR MATERIA PARA EDITAR
|--------------------------------------------------------------------------
*/

$materiaEditar = null;

if (isset($_GET["editar"])) {
    $idEditar = intval($_GET["editar"]);

    if ($idEditar > 0) {
        $stmt = $conexion->prepare("
            SELECT id, nombre, icono
            FROM materias
            WHERE id = ?
        ");

        $stmt->bind_param("i", $idEditar);
        $stmt->execute();

        $resultadoEditar = $stmt->get_result();
        $materiaEditar = $resultadoEditar->fetch_assoc();

        $stmt->close();
    }
}

/*
|--------------------------------------------------------------------------
| LISTAR MATERIAS
|--------------------------------------------------------------------------
*/

$materias = $conexion->query("
    SELECT id, nombre, icono
    FROM materias
    ORDER BY nombre ASC
");

if (!$materias) {
    die("Error al consultar las materias: " . $conexion->error);
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Materias | INCLU-IA</title>

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
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .encabezado h1 {
            margin: 0 0 7px;
            font-size: 30px;
        }

        .encabezado p {
            margin: 0;
            color: #6b7280;
        }

        .grilla {
            display: grid;
            grid-template-columns: 350px 1fr;
            gap: 25px;
            align-items: start;
        }

        .tarjeta {
            background: white;
            border-radius: 18px;
            padding: 25px;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
        }

        .tarjeta h2 {
            margin-top: 0;
            margin-bottom: 20px;
            font-size: 21px;
        }

        .campo {
            margin-bottom: 18px;
        }

        .campo label {
            display: block;
            margin-bottom: 7px;
            font-weight: bold;
        }

        .campo input {
            width: 100%;
            padding: 13px;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            font-size: 15px;
            outline: none;
        }

        .campo input:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
        }

        .ayuda {
            color: #6b7280;
            font-size: 13px;
            margin-top: 6px;
        }

        .boton {
            display: inline-block;
            border: none;
            border-radius: 10px;
            padding: 11px 15px;
            font-weight: bold;
            text-decoration: none;
            cursor: pointer;
            font-size: 14px;
        }

        .boton-principal {
            width: 100%;
            background: #2563eb;
            color: white;
        }

        .boton-principal:hover {
            background: #1d4ed8;
        }

        .boton-cancelar {
            width: 100%;
            margin-top: 9px;
            text-align: center;
            background: #e5e7eb;
            color: #374151;
        }

        .boton-editar {
            background: #f59e0b;
            color: white;
        }

        .boton-eliminar {
            background: #dc2626;
            color: white;
        }

        .mensaje {
            padding: 14px 16px;
            border-radius: 11px;
            margin-bottom: 20px;
            font-weight: bold;
        }

        .mensaje.exito {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #86efac;
        }

        .mensaje.error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }

        .tabla-contenedor {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 14px;
            border-bottom: 1px solid #e5e7eb;
            text-align: left;
        }

        th {
            background: #eff6ff;
            color: #1e3a8a;
            font-size: 14px;
        }

        tr:hover td {
            background: #f9fafb;
        }

        .materia {
            display: flex;
            align-items: center;
            gap: 13px;
        }

        .icono-materia {
            width: 48px;
            height: 48px;
            border-radius: 13px;
            background: #dbeafe;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 25px;
        }

        .nombre-materia {
            font-weight: bold;
            color: #1f2937;
        }

        .acciones {
            display: flex;
            gap: 7px;
            flex-wrap: wrap;
        }

        .sin-materias {
            text-align: center;
            color: #6b7280;
            padding: 35px;
        }

        @media (max-width: 900px) {
            .pagina {
                display: block;
            }

            .menu-lateral {
                width: 100%;
            }

            .contenido {
                padding: 20px;
            }

            .grilla {
                grid-template-columns: 1fr;
            }

            .encabezado {
                display: block;
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
        <a href="materias.php" class="activo">📚 Materias</a>
        <a href="actividades.php">📝 Actividades</a>
    	<a href="progreso.php">📊 Progreso</a>
        <a href="#">📊 Estadísticas</a>
        <a href="#">⚙️ Configuración</a>
        <a href="salir.php">🚪 Cerrar sesión</a>

    </aside>

    <main class="contenido">

        <div class="encabezado">

            <div>
                <h1>Gestión de materias</h1>

                <p>
                    Agregá, editá y eliminá las materias disponibles
                    en INCLU-IA.
                </p>
            </div>

        </div>

        <?php if ($mensaje !== ""): ?>

            <div class="mensaje <?= htmlspecialchars($tipoMensaje) ?>">
                <?= htmlspecialchars($mensaje) ?>
            </div>

        <?php endif; ?>

        <div class="grilla">

            <section class="tarjeta">

                <?php if ($materiaEditar): ?>

                    <h2>✏️ Editar materia</h2>

                    <form method="POST">

                        <input
                            type="hidden"
                            name="id"
                            value="<?= intval($materiaEditar["id"]) ?>"
                        >

                        <div class="campo">

                            <label for="nombre">
                                Nombre de la materia
                            </label>

                            <input
                                type="text"
                                id="nombre"
                                name="nombre"
                                value="<?= htmlspecialchars(
                                    $materiaEditar["nombre"]
                                ) ?>"
                                required
                            >

                        </div>

                        <div class="campo">

                            <label for="icono">
                                Ícono o emoji
                            </label>

                            <input
                                type="text"
                                id="icono"
                                name="icono"
                                maxlength="50"
                                value="<?= htmlspecialchars(
                                    $materiaEditar["icono"] ?? ""
                                ) ?>"
                                placeholder="Ejemplo: 📐"
                            >

                            <div class="ayuda">
                                Podés usar un emoji como 📖, 🔢, 🌱 o 🤖.
                            </div>

                        </div>

                        <button
                            type="submit"
                            name="actualizar_materia"
                            class="boton boton-principal"
                        >
                            Guardar cambios
                        </button>

                        <a
                            href="materias.php"
                            class="boton boton-cancelar"
                        >
                            Cancelar edición
                        </a>

                    </form>

                <?php else: ?>

                    <h2>➕ Agregar materia</h2>

                    <form method="POST">

                        <div class="campo">

                            <label for="nombre">
                                Nombre de la materia
                            </label>

                            <input
                                type="text"
                                id="nombre"
                                name="nombre"
                                placeholder="Ejemplo: Matemática"
                                required
                            >

                        </div>

                        <div class="campo">

                            <label for="icono">
                                Ícono o emoji
                            </label>

                            <input
                                type="text"
                                id="icono"
                                name="icono"
                                maxlength="50"
                                placeholder="Ejemplo: 🔢"
                            >

                            <div class="ayuda">
                                Podés escribir o copiar un emoji.
                            </div>

                        </div>

                        <button
                            type="submit"
                            name="guardar_materia"
                            class="boton boton-principal"
                        >
                            Guardar materia
                        </button>

                    </form>

                <?php endif; ?>

            </section>

            <section class="tarjeta">

                <h2>📚 Materias registradas</h2>

                <div class="tabla-contenedor">

                    <?php if ($materias->num_rows > 0): ?>

                        <table>

                            <thead>
                                <tr>
                                    <th>Materia</th>
                                    <th>Ícono</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>

                            <tbody>

                            <?php while ($materia = $materias->fetch_assoc()): ?>

                                <?php
                                $iconoMostrar = trim(
                                    $materia["icono"] ?? ""
                                );

                                if ($iconoMostrar === "") {
                                    $iconoMostrar = "📘";
                                }
                                ?>

                                <tr>

                                    <td>

                                        <div class="materia">

                                            <div class="icono-materia">
                                                <?= htmlspecialchars(
                                                    $iconoMostrar
                                                ) ?>
                                            </div>

                                            <span class="nombre-materia">
                                                <?= htmlspecialchars(
                                                    $materia["nombre"]
                                                ) ?>
                                            </span>

                                        </div>

                                    </td>

                                    <td>
                                        <?= htmlspecialchars($iconoMostrar) ?>
                                    </td>

                                    <td>

                                        <div class="acciones">

                                            <a
                                                href="materias.php?editar=<?= intval(
                                                    $materia["id"]
                                                ) ?>"
                                                class="boton boton-editar"
                                            >
                                                ✏️ Editar
                                            </a>

                                            <a
                                                href="materias.php?eliminar=<?= intval(
                                                    $materia["id"]
                                                ) ?>"
                                                class="boton boton-eliminar"
                                                onclick="
                                                    return confirm(
                                                        '¿Está seguro de eliminar esta materia?'
                                                    );
                                                "
                                            >
                                                🗑️ Eliminar
                                            </a>

                                        </div>

                                    </td>

                                </tr>

                            <?php endwhile; ?>

                            </tbody>

                        </table>

                    <?php else: ?>

                        <div class="sin-materias">
                            Todavía no hay materias registradas.
                        </div>

                    <?php endif; ?>

                </div>

            </section>

        </div>

    </main>

</div>

</body>
</html>