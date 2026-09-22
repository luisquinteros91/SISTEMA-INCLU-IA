# INCLU-IA

Plataforma web educativa inclusiva para gestionar actividades adaptadas y realizar el seguimiento del progreso de estudiantes.

## Funcionalidades
### Alumno
- Acceso individual mediante DNI.
- Actividades organizadas por materias.
- Resolución de consignas.
- Registro de respuestas y resultados.

### Docente
- Dashboard docente.
- Gestión de alumnos, materias y actividades.
- Consulta del progreso general.
- Seguimiento individual de cada estudiante.

## Tecnologías
PHP · MySQL · HTML5 · CSS3 · JavaScript · Sesiones PHP

## Instalación local
1. Descargar o clonar el repositorio.
2. Crear la base de datos MySQL requerida.
3. Copiar `includes/conexion.example.php` como `includes/conexion.php`.
4. Completar las credenciales locales.
5. Ejecutar en un servidor PHP/MySQL.

> `includes/conexion.php` está excluido mediante `.gitignore`. No publique credenciales ni datos reales de estudiantes.

## Privacidad
La versión pública no incluye bases de datos reales, DNI reales, contraseñas ni información personal de estudiantes.

## Seguridad
Proyecto de portfolio. Como mejora, la autenticación docente debe migrarse a `password_hash()` / `password_verify()` y las consultas con entradas del usuario deben utilizar sentencias preparadas.

## Autor
**Luis Quinteros**  
Ingeniero en Sistemas · Desarrollador PHP/MySQL · Tecnología Educativa · IA · Arduino/IoT  
LinkedIn: `linkedin.com/in/luis-quinteros-23897427`
