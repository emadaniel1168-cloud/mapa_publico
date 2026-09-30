# Integración PHP + MariaDB para el mapa 360

Esta carpeta contiene una base inicial para conectar el formulario del mapa 360 con la base de datos `colegio_santander`.

## Archivos

- `01_lugares_360.sql`: crea la tabla adicional que relaciona un panorama con un horario.
- `db.php`: conexión PDO a MariaDB.
- `api.php`: endpoints para profesores, horarios, grados, lugares y guardado.
- `integracion-formulario.js`: autocompletado y envío del formulario.

## Instalación en XAMPP

Copia la carpeta dentro de:

```text
C:\xampp\htdocs\recorrido-360\api\
```

En phpMyAdmin importa primero el archivo original `colegio_santander.sql`. Después importa `01_lugares_360.sql` para crear la tabla adicional.

La conexión lee estas variables de entorno:

```text
MYSQLHOST
MYSQLPORT
MYSQLDATABASE
MYSQLUSER
MYSQLPASSWORD
```

Configúralas en el entorno donde se ejecuta PHP. En Railway, agrega allí los valores de conexión de Railway; no los guardes en el repositorio. Si no están definidas, `db.php` usa los valores locales de XAMPP.

Inicia Apache y MySQL desde XAMPP. Luego prueba:

```text
http://localhost/recorrido-360/api/api.php?action=profesores&q=RA
```

La respuesta debe ser JSON con profesores cuyo nombre empiece por `RA`.

## Integración con el HTML actual

El archivo `integracion-formulario.js` necesita que el formulario tenga estos IDs:

```html
<input id="label-titulo" type="text">
<textarea id="label-descripcion"></textarea>
<input id="label-profesor" type="text">
<div id="sugerencias-profesores"></div>
<input id="label-grado" type="text">
<input id="label-salon" type="text">
<input id="label-dia" type="text">
<select id="label-id-horario"></select>
<input id="label-hora" type="time">
<input id="label-pitch" type="number" value="0">
<input id="label-yaw" type="number" value="0">
<button id="btn-add-label" type="button">Guardar información</button>
```

Antes de cargar el script define el panorama activo:

```html
<script>
  window.escenaActualId = 'imagen1';
</script>
<script src="api/integracion-formulario.js"></script>
```

Cuando Pannellum cambie de escena, actualiza ese valor:

```javascript
visor.on('scenechange', function (idEscena) {
  window.escenaActualId = idEscena;
});
```

El formulario no acepta un profesor escrito manualmente. El usuario debe elegir una sugerencia proveniente de `profesores`. Además, al guardar, PHP comprueba que el `id_horario` exista en `horarios`.

## Nota sobre hora, profesor, grado y salón

En la base de datos, la hora no está guardada directamente en `horarios`. Se obtiene relacionando `horarios.num_bloque_clase` con `bloques_horarios.num_bloque` y la jornada del grado. Por eso el formulario debe permitir seleccionar un horario válido; al seleccionar ese horario se completan profesor, grado, salón, materia y hora.

## Seguridad

No pongas las credenciales de MariaDB dentro del JavaScript. El archivo `db.php` debe permanecer en el servidor. El navegador solamente debe llamar a `api.php`.
