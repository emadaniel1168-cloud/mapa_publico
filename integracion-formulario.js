/*
 * Este archivo conecta el formulario HTML con api.php.
 * IDs esperados del formulario:
 * label-profesor, label-grado, label-salon, label-hora,
 * label-dia, label-id-horario, label-titulo, label-descripcion,
 * label-pitch, label-yaw y btn-add-label.
 */

const API_URL = 'api.php';
const profesorInput = document.getElementById('label-profesor');
const gradoInput = document.getElementById('label-grado');
const salonInput = document.getElementById('label-salon');
const diaInput = document.getElementById('label-dia');
const horarioSelect = document.getElementById('label-id-horario');
const sugerencias = document.getElementById('sugerencias-profesores');

let profesorSeleccionado = '';
let horarioSeleccionado = null;

function escapeHtml(value) {
  return String(value ?? '').replace(/[&<>'"]/g, char => ({
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    "'": '&#039;',
    '"': '&quot;'
  }[char]));
}

async function obtenerProfesores(texto) {
  if (!sugerencias) return;

  const query = (texto ?? '').trim();
  if (query.length > 0 && query.length < 2) {
    // Se muestran coincidencias breves para no romper la búsqueda rápida.
  }

  const response = await fetch(`${API_URL}?action=profesores&q=${encodeURIComponent(query)}`);
  const data = await response.json();

  sugerencias.innerHTML = '';

  if (!data.ok) return;

  data.items.forEach(profesor => {
    const opcion = document.createElement('button');
    opcion.type = 'button';
    opcion.className = 'sugerencia-profesor';
    opcion.textContent = `${profesor.id_profesor} — ${profesor.especialidad}`;

    opcion.addEventListener('click', () => {
      profesorSeleccionado = profesor.id_profesor;
      profesorInput.value = profesor.id_profesor;
      sugerencias.innerHTML = '';
      cargarHorarios();
    });

    sugerencias.appendChild(opcion);
  });
}

async function cargarHorarios() {
  if (!profesorSeleccionado) return;

  const params = new URLSearchParams({
    action: 'horarios',
    profesor: profesorSeleccionado,
    grado: gradoInput.value.trim(),
    dia: diaInput ? diaInput.value : ''
  });

  const response = await fetch(`${API_URL}?${params}`);
  const data = await response.json();

  horarioSelect.innerHTML = '<option value="">Selecciona un horario válido</option>';

  if (!data.ok) return;

  data.items.forEach(horario => {
    const option = document.createElement('option');
    option.value = horario.id_horario;
    option.textContent = [
      horario.id_grado,
      horario.dia_semana,
      `${horario.hora_inicio} - ${horario.hora_fin}`,
      horario.materia,
      horario.salon
    ].join(' | ');
    option.dataset.horario = JSON.stringify(horario);
    horarioSelect.appendChild(option);
  });
}

function completarDesdeHorario() {
  const option = horarioSelect.options[horarioSelect.selectedIndex];
  if (!option || !option.dataset.horario) return;

  horarioSeleccionado = JSON.parse(option.dataset.horario);
  gradoInput.value = horarioSeleccionado.id_grado;
  salonInput.value = horarioSeleccionado.salon;

  const horaInicioInput = document.getElementById('label-hora-inicio');
  const horaFinInput = document.getElementById('label-hora-fin');
  const horaInput = document.getElementById('label-hora');

  if (horaInicioInput) {
    horaInicioInput.value = horarioSeleccionado.hora_inicio ? horarioSeleccionado.hora_inicio.slice(0, 5) : '';
  }

  if (horaFinInput) {
    horaFinInput.value = horarioSeleccionado.hora_fin ? horarioSeleccionado.hora_fin.slice(0, 5) : '';
  }

  if (horaInput) {
    horaInput.value = horarioSeleccionado.hora_inicio ? horarioSeleccionado.hora_inicio.slice(0, 5) : '';
  }
}

async function guardarLugarEnBaseDeDatos() {
  const titulo = document.getElementById('label-titulo').value.trim();
  const descripcion = document.getElementById('label-descripcion').value.trim();
  const pitch = Number(document.getElementById('label-pitch').value || 0);
  const yaw = Number(document.getElementById('label-yaw').value || 0);
  const panoramaId = window.escenaActualId || 'imagen1';

  if (!profesorSeleccionado || profesorInput.value !== profesorSeleccionado) {
    alert('Selecciona un profesor de la lista de sugerencias.');
    profesorInput.focus();
    return;
  }

  if (!horarioSelect.value) {
    alert('Selecciona un horario válido de la base de datos.');
    horarioSelect.focus();
    return;
  }

  const response = await fetch(`${API_URL}?action=guardar_lugar`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      panorama_id: panoramaId,
      titulo,
      descripcion,
      id_horario: Number(horarioSelect.value),
      pitch,
      yaw
    })
  });

  const data = await response.json();

  if (!response.ok || !data.ok) {
    alert(data.error || 'No se pudo guardar la información.');
    return;
  }

  alert('Información guardada correctamente. ID: ' + data.id_lugar);
}

profesorInput.addEventListener('focus', () => {
  profesorSeleccionado = '';
  obtenerProfesores(profesorInput.value).catch(console.error);
});

profesorInput.addEventListener('input', event => {
  profesorSeleccionado = '';
  obtenerProfesores(event.target.value).catch(console.error);
});

gradoInput.addEventListener('change', cargarHorarios);
if (diaInput) diaInput.addEventListener('change', cargarHorarios);
horarioSelect.addEventListener('change', completarDesdeHorario);
document.getElementById('btn-add-label').addEventListener('click', guardarLugarEnBaseDeDatos);

obtenerProfesores('').catch(console.error);
