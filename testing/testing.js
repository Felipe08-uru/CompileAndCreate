'use strict';

// ================== IMPORTS (los mismos módulos que usa el frontend) ==================
import {
  iniciarSesion, registrarUsuario, getUsuarios, getUsuarioPorCi,
  crearUsuario, actualizarUsuario, eliminarUsuario,
} from '../frontend/js/APIUsuarios.js';

import {
  getCamiones, getCamionPorMatricula, crearCamion, eliminarCamion,
} from '../frontend/js/APICamiones.js';

import {
  getContenedores, getContenedorPorId, crearContenedor, eliminarContenedor,
} from '../frontend/js/APIContenedores.js';

import { getCentros, crearCentro, eliminarCentro } from '../frontend/js/APICentros.js';

import {
  getMaquinaria, crearMaquinaria, actualizarMaquinaria, eliminarMaquinaria,
} from '../frontend/js/APIMaquinaria.js';

import {
  getRegistros, crearRegistro, actualizarRegistro, eliminarRegistro,
} from '../frontend/js/APIRegistroCamion.js';

import { getPoligonos, crearPoligono, eliminarPoligono } from '../frontend/js/APIPoligonos.js';

import {
  getIncidencias, getIncidenciaPorId, crearIncidencia, eliminarIncidencia,
} from '../frontend/js/APIIncidencias.js';

import {
  getRutas, getRutaActiva, getRuta, crearRuta, asignarCamionARuta, registrarPeso, eliminarRuta,
} from '../frontend/js/APIRutas.js';

import {
  getAsignaciones, getMiAsignacion, asignarCamionACuadrilla, eliminarAsignacion,
} from '../frontend/js/APIAsignaciones.js';

import {
  getDesbordados, getPromedio, getZonasLlenado, getLimpieza, registrarLectura,
} from '../frontend/js/APIEstadisticas.js';

// ================== UI / LOG helpers ==================
const out = document.getElementById('output');
const resumenEl = document.getElementById('resumen');
const contador = { ok: 0, fail: 0, info: 0 };

function fmt(x) {
  try { if (typeof x === 'string') return x; return JSON.stringify(x, null, 2); }
  catch (e) { try { return String(x); } catch (_) { return '[unserializable]'; } }
}
function recortar(texto, max) {
  texto = fmt(texto);
  return texto.length > max ? texto.slice(0, max) + '...' : texto;
}
function crear(tag, clase, texto) {
  const el = document.createElement(tag);
  if (clase) el.className = clase;
  if (texto !== undefined) el.textContent = texto; // textContent: lo que devuelve el servidor nunca se interpreta como HTML
  return el;
}
function actualizarResumen() {
  resumenEl.textContent = '';
  [['ok', 'Correctas', contador.ok], ['fail', 'Fallas', contador.fail], ['info', 'Avisos', contador.info]].forEach(function (d) {
    const span = crear('span', d[0]);
    span.appendChild(crear('strong', '', String(d[2])));
    span.appendChild(document.createTextNode(' ' + d[1] + '   '));
    resumenEl.appendChild(span);
  });
}
function fila(clase, estadoTexto, nombre, detalle) {
  const f = crear('div', 'fila ' + clase);
  f.appendChild(crear('div', 'estado', estadoTexto));
  const cuerpo = crear('div');
  cuerpo.appendChild(crear('div', 'nombre', nombre));
  if (detalle) cuerpo.appendChild(crear('div', 'detalle', detalle));
  f.appendChild(cuerpo);
  out.appendChild(f);
  const linea = estadoTexto + ': ' + nombre + (detalle ? '\n' + detalle : '');
  if (clase === 'fail') console.error(linea); else console.log(linea);
}
function log(texto) { out.appendChild(crear('p', 'aviso', texto)); console.log(texto); }
function pass(nombre, payload) { contador.ok++; actualizarResumen(); fila('ok', 'OK', nombre, payload === '' ? '' : recortar(payload, 300)); }
function fail(nombre, err)     { contador.fail++; actualizarResumen(); fila('fail', 'FALLA', nombre, err && err.message ? err.message : fmt(err)); }
function info(nombre, msg)     { contador.info++; actualizarResumen(); fila('info', 'AVISO', nombre, fmt(msg || '')); }
function seccion(titulo)       { out.appendChild(crear('h2', '', titulo)); }

// ================== Aserciones ==================
function resumenRes(res) {
  return res ? '\nHTTP ' + res.status + ' ' + recortar(res.data, 300) : '';
}
function afirmar(cond, msg, res) {
  if (!cond) throw new Error(msg + resumenRes(res));
}
function estado(res, esperado) {
  const lista = Array.isArray(esperado) ? esperado : [esperado];
  afirmar(lista.includes(res.status), 'esperaba HTTP ' + lista.join(' o ') + ' y llegó ' + res.status, res);
}
function hayError(res) {
  afirmar(!res.ok && res.data && res.data.error, 'debía devolver un error', res);
}
function hayErrorEnCuerpo(res) {
  // Algunas validaciones responden 200 con { error } en el cuerpo
  afirmar(res.data && res.data.error, 'debía devolver un error', res);
}
function listaContiene(data, valor) {
  return Array.isArray(data) && data.some(function (fila) {
    return fila && Object.values(fila).some(function (v) { return String(v) === String(valor); });
  });
}
function contieneValor(obj, valor) {
  if (obj === null || obj === undefined) return false;
  if (typeof obj !== 'object') return String(obj) === String(valor);
  return Object.values(obj).some(function (v) { return contieneValor(v, valor); });
}
function buscarFila(lista, campo, valor) {
  if (!Array.isArray(lista)) return null;
  return lista.find(function (f) { return f && String(f[campo]) === String(valor); }) || null;
}

async function prueba(nombre, fn) {
  try {
    const payload = await fn();
    pass(nombre, payload === undefined ? '' : payload);
    return true;
  } catch (e) {
    fail(nombre, e);
    return false;
  }
}

// ================== Sesiones (token en localStorage, igual que la aplicación) ==================
const CREDENCIALES = {
  Administrador: { correo_e: 'admin@gmail.com',      contrasena: '1234', rol: 'Administrador' },
  Vecino:        { correo_e: 'vecino@gmail.com',     contrasena: '1234', rol: 'Vecino' },
  Operario:      { correo_e: 'operario@gmail.com',   contrasena: '1234', rol: 'Operario' },
  Cuadrilla:     { correo_e: 'cuadrilla@gmail.com',  contrasena: '1234', rol: 'Cuadrilla' },
  Cuadrilla2:    { correo_e: 'cuadrilla2@gmail.com', contrasena: '1234', rol: 'Cuadrilla' },
};
const CI_CUADRILLA_2 = '77777777';
const CAMION_CUADRILLA_2 = 'SBJ5678';
const tokens = {};

async function como(nombre) {
  if (!tokens[nombre]) {
    const c = CREDENCIALES[nombre];
    const r = await iniciarSesion({ correo_e: c.correo_e, contrasena: c.contrasena });
    afirmar(r.ok && r.data.token, 'no se pudo iniciar sesión como ' + nombre, r);
    tokens[nombre] = r.data.token;
  }
  localStorage.setItem('token', tokens[nombre]);
}
function sinSesion() { localStorage.removeItem('token'); }

// ================== Datos de prueba ==================
const sufijo = String(Date.now()).slice(-6);
const digito = function () { return Math.floor(Math.random() * 10); };
const matriculaCrud = 'TST' + sufijo;        // camión que se crea y borra en la sección de camiones
const matriculaRuta = 'TSR' + sufijo;        // camión que se usa en las rutas
const ciVecino = '9' + sufijo + digito();    // 8 dígitos
const ciOperario = '9' + sufijo + ((digito() + 1) % 10);
const correoVecino = 'test.' + sufijo + '@example.com';
const correoOperario = 'test.op.' + sufijo + '@example.com';

// PNG de 1x1 píxel: el servidor revisa el tipo real del archivo (mime_content_type)
const PNG_BASE64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';
function archivoPng() {
  const bin = atob(PNG_BASE64);
  const bytes = new Uint8Array(bin.length);
  for (let i = 0; i < bin.length; i++) bytes[i] = bin.charCodeAt(i);
  return new File([bytes], 'TEST_incidencia.png', { type: 'image/png' });
}
function archivoTexto() {
  return new File(['esto no es una imagen'], 'TEST_falso.png', { type: 'image/png' });
}

// Pila de limpieza: lo que se crea se registra y se deshace al final, en orden inverso
const pilaLimpieza = [];
function deshacer(nombre, fn) { pilaLimpieza.push({ nombre: nombre, fn: fn }); }

// ================== RUNNER ==================
(async function run() {
  const tokenOriginal = localStorage.getItem('token');
  const rolOriginal = localStorage.getItem('rol');

  log('Se usan los usuarios de sigeru.sql. Los datos que se crean llevan el prefijo TST, TSR o TEST y se borran al final.');

  let idContenedorRuta = null;
  let idZonaTest = null;
  let idRutaTest = null;
  let idCentro = null;

  try {

  // ====== (1) Sesión ======
  seccion('1. Sesión');

  for (const nombre of ['Administrador', 'Vecino', 'Operario', 'Cuadrilla', 'Cuadrilla2']) {
    await prueba('Login ' + nombre, async function () {
      const c = CREDENCIALES[nombre];
      const r = await iniciarSesion({ correo_e: c.correo_e, contrasena: c.contrasena });
      estado(r, 200);
      afirmar(r.data.token && r.data.token.length >= 32, 'no devolvió un token válido', r);
      afirmar(r.data.rol === c.rol, 'rol esperado ' + c.rol, r);
      tokens[nombre] = r.data.token;
      return { rol: r.data.rol };
    });
  }

  await prueba('Login con contraseña incorrecta devuelve 401', async function () {
    const r = await iniciarSesion({ correo_e: 'admin@gmail.com', contrasena: 'mal' });
    estado(r, 401);
    hayError(r);
  });

  await prueba('Login con correo inexistente da el mismo error (no revela usuarios)', async function () {
    const a = await iniciarSesion({ correo_e: 'admin@gmail.com', contrasena: 'mal' });
    const b = await iniciarSesion({ correo_e: 'nadie@gmail.com', contrasena: 'mal' });
    estado(b, 401);
    afirmar(a.data.error === b.data.error, 'los mensajes deberían ser iguales', b);
  });

  await prueba('Login con datos incompletos devuelve error', async function () {
    const r = await iniciarSesion({ correo_e: 'admin@gmail.com' });
    hayError(r);
  });

  if (!tokens.Administrador) {
    fail('Runner', 'Sin sesión de administrador no se puede seguir: revisá que sigeru.sql esté cargado y la URL del backend.');
    return;
  }

  // ====== (2) Autorización ======
  seccion('2. Autorización (401 sin sesión, 403 sin el rol necesario)');

  await prueba('Sin token: GET /camiones devuelve 401', async function () {
    sinSesion();
    estado(await getCamiones(), 401);
  });

  await prueba('Token falso: GET /camiones devuelve 401', async function () {
    localStorage.setItem('token', 'token-inventado');
    estado(await getCamiones(), 401);
  });

  await prueba('Vecino no puede crear camiones (403)', async function () {
    await como('Vecino');
    estado(await crearCamion({ matricula: 'TSTV' + sufijo, tipo: 'Reciclables' }), 403);
  });

  await prueba('Vecino no puede listar usuarios (403)', async function () {
    await como('Vecino');
    estado(await getUsuarios(), 403);
  });

  await prueba('Vecino no puede crear centros ni zonas (403)', async function () {
    await como('Vecino');
    estado(await crearCentro({ servicio: 'TEST' }), 403);
    estado(await crearPoligono([]), 403);
  });

  await prueba('Vecino no puede crear maquinaria ni registros de camión (403)', async function () {
    await como('Vecino');
    estado(await crearMaquinaria({ id_centro: 1, nombre: 'TEST' }), 403);
    estado(await crearRegistro({ matricula: 'SBI1234', id_centro: 1, tipo_carga: 'TEST' }), 403);
  });

  await prueba('Cuadrilla no puede crear camiones ni ver estadísticas (403)', async function () {
    await como('Cuadrilla');
    estado(await crearCamion({ matricula: 'TSTC' + sufijo, tipo: 'Reciclables' }), 403);
    estado(await getLimpieza(), 403);
  });

  await prueba('Operario no puede ver estadísticas ni crear rutas (403)', async function () {
    await como('Operario');
    estado(await getDesbordados(), 403);
    estado(await crearRuta(2, 'Reciclables'), 403);
  });

  // ====== (3) Camiones ======
  seccion('3. Camiones (CRUD)');
  await como('Administrador');

  await prueba('Crear camión', async function () {
    await como('Administrador');
    const r = await crearCamion({ matricula: matriculaCrud, tipo: 'Reciclables' });
    afirmar(r.data.mensaje, 'no devolvió mensaje de éxito', r);
    return r.data;
  });

  await prueba('Rechaza matrícula repetida', async function () {
    hayErrorEnCuerpo(await crearCamion({ matricula: matriculaCrud, tipo: 'Reciclables' }));
  });

  await prueba('Rechaza tipo fuera de la lista (Orgánico)', async function () {
    hayErrorEnCuerpo(await crearCamion({ matricula: 'TSTX' + sufijo, tipo: 'Orgánico' }));
  });

  await prueba('Rechaza estado fuera de la lista (Roto)', async function () {
    hayErrorEnCuerpo(await crearCamion({ matricula: 'TSTY' + sufijo, tipo: 'Reciclables', estado: 'Roto' }));
  });

  await prueba('Rechaza matrícula de 11 caracteres', async function () {
    hayErrorEnCuerpo(await crearCamion({ matricula: 'ABCDEFGHIJK', tipo: 'Reciclables' }));
  });

  await prueba('Acepta matrícula de exactamente 10 caracteres (límite)', async function () {
    const mat = 'T' + sufijo + 'ABC'.slice(0, 3); // 10 caracteres
    afirmar(mat.length === 10, 'la matrícula de prueba debe tener 10 caracteres');
    const r = await crearCamion({ matricula: mat, tipo: 'Reciclables' });
    afirmar(r.data.mensaje, 'debía aceptarla', r);
    await eliminarCamion(mat);
  });

  await prueba('Listar y consultar camión por matrícula', async function () {
    const lista = await getCamiones();
    estado(lista, 200);
    afirmar(listaContiene(lista.data, matriculaCrud), 'no aparece ' + matriculaCrud, lista);
    const uno = await getCamionPorMatricula(matriculaCrud);
    afirmar(uno.data.Tipo === 'Reciclables', 'Tipo incorrecto', uno);
    afirmar(uno.data.Estado === 'Disponible', 'Estado por defecto debería ser Disponible', uno);
  });

  await prueba('Eliminar camión', async function () {
    const r = await eliminarCamion(matriculaCrud);
    afirmar(r.data.mensaje, 'no se eliminó', r);
    const lista = await getCamiones();
    afirmar(!listaContiene(lista.data, matriculaCrud), 'sigue apareciendo en la lista', lista);
  });

  await prueba('Eliminar camión inexistente devuelve error', async function () {
    hayErrorEnCuerpo(await eliminarCamion('NOEXISTE1'));
  });

  // Camión que se usará más adelante en rutas y registros
  await prueba('Crear camión para las pruebas de rutas', async function () {
    const r = await crearCamion({ matricula: matriculaRuta, tipo: 'Reciclables' });
    afirmar(r.data.mensaje, 'no se creó', r);
    deshacer('Eliminar camión ' + matriculaRuta, async function () { return eliminarCamion(matriculaRuta); });
  });

  // ====== (4) Contenedores ======
  seccion('4. Contenedores (CRUD y relación con la zona)');

  await prueba('Crear contenedor dentro de una zona existente lo relaciona con esa zona', async function () {
    const base = await getContenedorPorId(1);
    afirmar(base.data && base.data.Latitud !== undefined, 'el contenedor 1 de la semilla no existe', base);
    const r = await crearContenedor({ tipo: 'Reciclables', latitud: Number(base.data.Latitud), longitud: Number(base.data.Longitud) });
    afirmar(r.data.id_contenedor, 'no devolvió id_contenedor', r);
    afirmar(String(r.data.id_poligono) === String(base.data.Id_Poligono), 'debía quedar en la zona ' + base.data.Id_Poligono, r);
    const lista = await getContenedores();
    afirmar(listaContiene(lista.data, r.data.id_contenedor), 'no aparece en la lista', lista);
    const uno = await getContenedorPorId(r.data.id_contenedor);
    afirmar(uno.data.Tipo === 'Reciclables', 'Tipo incorrecto', uno);
    const del = await eliminarContenedor(r.data.id_contenedor);
    afirmar(del.data.mensaje, 'no se eliminó', del);
    return { id: r.data.id_contenedor, zona: r.data.id_poligono };
  });

  await prueba('Crear contenedor fuera de toda zona lo deja sin zona', async function () {
    const r = await crearContenedor({ tipo: 'Residuos mezclados', latitud: 10, longitud: 10 });
    afirmar(r.data.id_contenedor, 'no devolvió id_contenedor', r);
    afirmar(r.data.id_poligono === null, 'id_poligono debía ser null', r);
    await eliminarContenedor(r.data.id_contenedor);
  });

  await prueba('Rechaza tipo inválido, ubicación fuera de rango y datos faltantes', async function () {
    hayErrorEnCuerpo(await crearContenedor({ tipo: 'Orgánico', latitud: 1, longitud: 1 }));
    hayErrorEnCuerpo(await crearContenedor({ tipo: 'Reciclables', latitud: 95, longitud: 0 }));
    hayErrorEnCuerpo(await crearContenedor({ tipo: 'Reciclables', latitud: 0, longitud: 181 }));
    hayErrorEnCuerpo(await crearContenedor({ tipo: 'Reciclables' }));
  });

  await prueba('Eliminar contenedor inexistente devuelve error', async function () {
    hayErrorEnCuerpo(await eliminarContenedor(999999));
  });

  // ====== (5) Zonas ======
  seccion('5. Zonas (polígonos)');

  await prueba('Rechaza zona con menos de 3 vértices', async function () {
    hayErrorEnCuerpo(await crearPoligono([{ latitud: -10, longitud: -50 }, { latitud: -10, longitud: -49.9 }]));
  });

  await prueba('Crear zona de prueba y listarla', async function () {
    const r = await crearPoligono([
      { latitud: -10.0, longitud: -50.0 },
      { latitud: -10.0, longitud: -49.9 },
      { latitud: -9.9,  longitud: -49.9 },
      { latitud: -9.9,  longitud: -50.0 },
    ]);
    afirmar(r.data.id_poligono, 'no devolvió id_poligono', r);
    idZonaTest = r.data.id_poligono;
    deshacer('Eliminar zona ' + idZonaTest, async function () { return eliminarPoligono(idZonaTest); });
    const lista = await getPoligonos();
    estado(lista, 200);
    afirmar(listaContiene(lista.data, idZonaTest) || contieneValor(lista.data, idZonaTest), 'no aparece la zona', lista);
    return { id_poligono: idZonaTest };
  });

  await prueba('Contenedor creado dentro de la zona de prueba queda en esa zona', async function () {
    afirmar(idZonaTest, 'no hay zona de prueba');
    const r = await crearContenedor({ tipo: 'Reciclables', latitud: -9.95, longitud: -49.95 });
    afirmar(r.data.id_contenedor, 'no devolvió id_contenedor', r);
    idContenedorRuta = r.data.id_contenedor;
    deshacer('Eliminar contenedor ' + idContenedorRuta, async function () { return eliminarContenedor(idContenedorRuta); });
    afirmar(String(r.data.id_poligono) === String(idZonaTest), 'debía quedar en la zona ' + idZonaTest, r);
    return r.data;
  });

  // ====== (6) Estadísticas ======
  seccion('6. Estadísticas (lecturas, desbordes, promedios, zonas y limpieza)');

  if (!idContenedorRuta) {
    info('Estadísticas', 'No hay contenedor de prueba; se omite la sección.');
  } else {
    await prueba('Operario registra una lectura desbordada (97): responde 201 y desbordado = true', async function () {
      await como('Operario');
      const r = await registrarLectura(idContenedorRuta, 97);
      estado(r, 201);
      afirmar(r.data.desbordado === true, 'debía marcar desbordado', r);
      afirmar(r.data.id_lectura, 'no devolvió id_lectura', r);
      return r.data;
    });

    await prueba('Límites del nivel: 0 y 100 se aceptan', async function () {
      await como('Administrador');
      estado(await registrarLectura(idContenedorRuta, 0), 201);
      estado(await registrarLectura(idContenedorRuta, 100), 201);
    });

    await prueba('Cuadrilla puede registrar una lectura (50)', async function () {
      await como('Cuadrilla');
      estado(await registrarLectura(idContenedorRuta, 50), 201);
    });

    await prueba('Rechaza nivel 140, -1 y contenedor sin indicar (400)', async function () {
      await como('Operario');
      estado(await registrarLectura(idContenedorRuta, 140), 400);
      estado(await registrarLectura(idContenedorRuta, -1), 400);
      estado(await registrarLectura(null, 50), 400);
    });

    await prueba('Rechaza fecha mal formada (400) y contenedor inexistente (404)', async function () {
      await como('Operario');
      estado(await registrarLectura(idContenedorRuta, 50, 'ayer'), 400);
      estado(await registrarLectura(999999, 50), 404);
    });

    await prueba('Vecino no puede registrar lecturas (403)', async function () {
      await como('Vecino');
      estado(await registrarLectura(idContenedorRuta, 50), 403);
    });

    await prueba('Desbordados del mes incluye el contenedor con 2 desbordes (97 y 100)', async function () {
      await como('Administrador');
      const r = await getDesbordados('', 500);
      estado(r, 200);
      const fila = buscarFila(r.data.contenedores, 'Id_Contenedor', idContenedorRuta);
      afirmar(fila, 'el contenedor no aparece entre los desbordados', r);
      afirmar(Number(fila.Veces) === 2, 'esperaba Veces = 2 y llegó ' + fila.Veces, r);
      afirmar(r.data.umbral === 90, 'el umbral debía ser 90', r);
      return fila;
    });

    await prueba('Mes inválido devuelve 400', async function () {
      await como('Administrador');
      estado(await getDesbordados('abc'), 400);
    });

    await prueba('Promedio por contenedor: (97+0+100+50)/4 = 61,75', async function () {
      const r = await getPromedio(30, 500);
      estado(r, 200);
      const fila = buscarFila(r.data.contenedores, 'Id_Contenedor', idContenedorRuta);
      afirmar(fila, 'el contenedor no aparece en el promedio', r);
      afirmar(Math.abs(Number(fila.Promedio) - 61.75) <= 0.06, 'esperaba ~61,75 y llegó ' + fila.Promedio, r);
      afirmar(typeof r.data.promedio_general === 'number', 'falta promedio_general', r);
      return fila;
    });

    await prueba('Llenado por zona: la zona de prueba tiene ~61,75', async function () {
      const r = await getZonasLlenado(30);
      estado(r, 200);
      const fila = buscarFila(r.data.zonas, 'Id_Poligono', idZonaTest);
      afirmar(fila, 'la zona no aparece', r);
      afirmar(Math.abs(Number(fila.Promedio) - 61.75) <= 0.06, 'esperaba ~61,75 y llegó ' + fila.Promedio, r);
      afirmar(fila.Categoria === 'medio', 'categoría esperada: medio', r);
      return fila;
    });

    await prueba('Limpieza: contenedor nuevo figura como "nunca" (sin recolección)', async function () {
      const r = await getLimpieza();
      estado(r, 200);
      const fila = buscarFila(r.data.contenedores, 'Id_Contenedor', idContenedorRuta);
      afirmar(fila, 'el contenedor no aparece', r);
      afirmar(fila.Categoria === 'nunca' && fila.Dias === null, 'esperaba nunca / null', r);
      return fila;
    });

    await prueba('Con la semilla: hay contenedores con días de limpieza registrados', async function () {
      const r = await getLimpieza();
      const conDias = r.data.contenedores.filter(function (c) { return typeof c.Dias === 'number'; });
      afirmar(conDias.length >= 10, 'esperaba al menos 10 contenedores con fecha de limpieza (¿se cargó sigeru.sql?) y hay ' + conDias.length, r);
      const mayor = Math.max.apply(null, conDias.map(function (c) { return c.Dias; }));
      afirmar(mayor >= 1, 'ningún contenedor tiene días sin limpiar', r);
      return { contenedores_con_fecha: conDias.length, mayor_cantidad_de_dias: mayor };
    });
  }

  // ====== (7) Usuarios ======
  seccion('7. Usuarios (registro, alta por administrador, edición y baja)');

  await prueba('Registro de vecino y login con ese usuario', async function () {
    sinSesion();
    const r = await registrarUsuario({ ci: ciVecino, nombre1: 'Test', apellido1: 'Api', correo_e: correoVecino, contrasena: 'clave123' });
    afirmar(r.data.success, 'no se registró', r);
    deshacer('Eliminar usuario ' + ciVecino, async function () { await como('Administrador'); return eliminarUsuario(ciVecino); });
    const login = await iniciarSesion({ correo_e: correoVecino, contrasena: 'clave123' });
    estado(login, 200);
    afirmar(login.data.rol === 'Vecino', 'el rol debía ser Vecino', login);
  });

  await prueba('Registro repetido, cédula inválida y correo inválido devuelven error', async function () {
    sinSesion();
    hayError(await registrarUsuario({ ci: ciVecino, nombre1: 'Test', apellido1: 'Api', correo_e: 'otro.' + correoVecino, contrasena: 'clave123' }));
    hayError(await registrarUsuario({ ci: '12', nombre1: 'T', apellido1: 'A', correo_e: 'x@x.com', contrasena: 'c' }));
    hayError(await registrarUsuario({ ci: '1234567a', nombre1: 'T', apellido1: 'A', correo_e: 'x@x.com', contrasena: 'c' }));
    hayError(await registrarUsuario({ ci: '9' + sufijo + '9', nombre1: 'T', apellido1: 'A', correo_e: 'no-es-correo', contrasena: 'c' }));
  });

  await prueba('El registro público siempre crea Vecino (aunque pida otro rol)', async function () {
    sinSesion();
    const ci = '9' + sufijo + ((digito() + 2) % 10);
    const correo = 'rol.' + sufijo + '@example.com';
    const r = await registrarUsuario({ ci: ci, nombre1: 'Test', apellido1: 'Rol', correo_e: correo, contrasena: 'clave123', rol: 'Administrador' });
    if (r.data.success) {
      deshacer('Eliminar usuario ' + ci, async function () { await como('Administrador'); return eliminarUsuario(ci); });
      const login = await iniciarSesion({ correo_e: correo, contrasena: 'clave123' });
      afirmar(login.data.rol === 'Vecino', 'se pudo crear un usuario con rol ' + login.data.rol + ' desde el registro público', login);
    }
  });

  await prueba('Administrador lista usuarios sin exponer contraseñas', async function () {
    await como('Administrador');
    const r = await getUsuarios();
    estado(r, 200);
    afirmar(listaContiene(r.data, ciVecino), 'no aparece el vecino registrado', r);
    afirmar(r.data.every(function (u) { return !('contrasena' in u); }), 'la lista incluye la contraseña', r);
  });

  await prueba('Administrador crea un operario, lo edita y lo consulta', async function () {
    await como('Administrador');
    const alta = await crearUsuario({ ci: ciOperario, nombre1: 'TestOp', apellido1: 'Api', correo_e: correoOperario, contrasena: 'clave123', rol: 'Operario' });
    afirmar(alta.data.success, 'no se creó', alta);
    deshacer('Eliminar usuario ' + ciOperario, async function () { await como('Administrador'); return eliminarUsuario(ciOperario); });
    const edit = await actualizarUsuario({ ci: ciOperario, nombre1: 'Editado', nombre2: '', apellido1: 'Api', apellido2: '', correo_e: correoOperario, rol: 'Cuadrilla' });
    afirmar(edit.data.mensaje, 'no se editó', edit);
    const uno = await getUsuarioPorCi(ciOperario);
    afirmar(uno.data.nombre1 === 'Editado', 'el nombre no cambió', uno);
    afirmar(uno.data.rol === 'Cuadrilla', 'el rol no cambió', uno);
  });

  await prueba('Edición con texto de inyección SQL solo afecta a ese usuario', async function () {
    await como('Administrador');
    const malo = "x' WHERE 1=1 -- ";
    await actualizarUsuario({ ci: ciOperario, nombre1: malo, nombre2: '', apellido1: 'Api', apellido2: '', correo_e: correoOperario, rol: 'Cuadrilla' });
    const lista = await getUsuarios();
    const afectados = lista.data.filter(function (u) { return u.nombre1 === malo; });
    afirmar(afectados.length <= 1, 'la edición modificó ' + afectados.length + ' usuarios', lista);
    const admin = buscarFila(lista.data, 'ci', '12341234');
    afirmar(admin && admin.nombre1 === 'Administrador', 'el administrador de la semilla fue modificado', lista);
  });

  await prueba('Edición con correo o rol inválido devuelve error', async function () {
    await como('Administrador');
    hayError(await actualizarUsuario({ ci: ciOperario, nombre1: 'A', apellido1: 'B', correo_e: 'mal', rol: 'Cuadrilla' }));
    hayError(await actualizarUsuario({ ci: ciOperario, nombre1: 'A', apellido1: 'B', correo_e: correoOperario, rol: 'Dios' }));
  });

  await prueba('Eliminar usuario y verificar que ya no inicia sesión', async function () {
    await como('Administrador');
    const r = await eliminarUsuario(ciOperario);
    afirmar(r.data.success, 'no se eliminó', r);
    const login = await iniciarSesion({ correo_e: correoOperario, contrasena: 'clave123' });
    estado(login, 401);
  });

  // ====== (8) Centros, maquinaria y registros de camiones ======
  seccion('8. Centros de acopio, maquinaria y registros de camiones');
  await como('Administrador');

  await prueba('Crear centro y listarlo', async function () {
    await como('Administrador');
    const r = await crearCentro({ servicio: 'TEST Centro ' + sufijo, capacidad: 10 });
    afirmar(r.data.id_centro, 'no devolvió id_centro', r);
    idCentro = r.data.id_centro;
    deshacer('Eliminar centro ' + idCentro, async function () { return eliminarCentro(idCentro); });
    const lista = await getCentros();
    afirmar(listaContiene(lista.data, idCentro), 'no aparece en la lista', lista);
  });

  await prueba('Crear centro sin servicio devuelve error', async function () {
    hayErrorEnCuerpo(await crearCentro({ capacidad: 5 }));
  });

  if (idCentro) {
    await prueba('Operario ve el centro en el listado de centros', async function () {
      await como('Operario');
      const r = await getCentros();
      estado(r, 200);
      afirmar(listaContiene(r.data, idCentro), 'el centro no aparece', r);
    });

    let idMaquina = null;
    await prueba('Operario crea maquinaria con valores por defecto', async function () {
      await como('Operario');
      const r = await crearMaquinaria({ id_centro: idCentro, nombre: 'TEST Grúa' });
      afirmar(r.data.id_maquinaria, 'no devolvió id_maquinaria', r);
      idMaquina = r.data.id_maquinaria;
      deshacer('Eliminar maquinaria ' + idMaquina, async function () { await como('Administrador'); return eliminarMaquinaria(idMaquina); });
    });

    await prueba('Maquinaria sin nombre devuelve error', async function () {
      await como('Operario');
      hayErrorEnCuerpo(await crearMaquinaria({ id_centro: idCentro, nombre: '  ' }));
    });

    if (idMaquina) {
      await prueba('Editar maquinaria y verificar en el listado del centro', async function () {
        await como('Operario');
        const e = await actualizarMaquinaria({ id_maquinaria: idMaquina, nombre: 'TEST Grúa 2', cantidad: 3, estado: 'En reparación' });
        afirmar(e.data.mensaje, 'no se editó', e);
        const l = await getMaquinaria(idCentro);
        estado(l, 200);
        afirmar(listaContiene(l.data, 'TEST Grúa 2'), 'no aparece el nombre editado', l);
      });
    }

    let idRegistro = null;
    await prueba('Operario registra la llegada de un camión', async function () {
      await como('Operario');
      const r = await crearRegistro({ matricula: matriculaRuta, id_centro: idCentro, tipo_carga: 'Reciclables', cantidad_carga: 120.5 });
      afirmar(r.data.id_registro, 'no devolvió id_registro', r);
      idRegistro = r.data.id_registro;
      deshacer('Eliminar registro ' + idRegistro, async function () { await como('Operario'); return eliminarRegistro(idRegistro); });
    });

    await prueba('Registro con camión inexistente o sin tipo de carga devuelve error', async function () {
      await como('Operario');
      hayErrorEnCuerpo(await crearRegistro({ matricula: 'NOEXISTE1', id_centro: idCentro, tipo_carga: 'X' }));
      hayErrorEnCuerpo(await crearRegistro({ matricula: matriculaRuta, id_centro: idCentro }));
    });

    if (idRegistro) {
      await prueba('Editar registro y verlo como operario y como administrador', async function () {
        await como('Operario');
        const e = await actualizarRegistro({ id_registro: idRegistro, tipo_carga: 'Residuos mezclados', cantidad_carga: 99 });
        afirmar(e.data.mensaje, 'no se editó', e);
        const propios = await getRegistros();
        estado(propios, 200);
        afirmar(listaContiene(propios.data, idRegistro), 'el operario no ve su registro', propios);
        await como('Administrador');
        const todos = await getRegistros();
        estado(todos, 200);
        afirmar(listaContiene(todos.data, idRegistro), 'el administrador no ve el registro', todos);
      });

      await prueba('Vecino no puede ver los registros (no es 200)', async function () {
        await como('Vecino');
        const r = await getRegistros();
        afirmar(!r.ok, 'el vecino pudo listar registros', r);
      });
    }
  } else {
    info('Maquinaria y registros', 'No se creó el centro de prueba; se omiten estas pruebas.');
  }

  // ====== (9) Incidencias ======
  seccion('9. Incidencias (con foto)');

  if (!idContenedorRuta) {
    info('Incidencias', 'No hay contenedor de prueba; se omite la sección.');
  } else {
    let idIncidencia = null;

    await prueba('Vecino reporta una incidencia con foto PNG', async function () {
      await como('Vecino');
      const r = await crearIncidencia(idContenedorRuta, 'Contenedor lleno', archivoPng());
      afirmar(r.data.success && r.data.Id_Incidencia, 'no se registró', r);
      idIncidencia = r.data.Id_Incidencia;
      deshacer('Eliminar incidencia ' + idIncidencia, async function () { await como('Administrador'); return eliminarIncidencia(idIncidencia); });
      return r.data;
    });

    await prueba('Un archivo que no es imagen se rechaza (400)', async function () {
      await como('Vecino');
      estado(await crearIncidencia(idContenedorRuta, 'Contenedor lleno', archivoTexto()), 400);
    });

    await prueba('Incidencia sin tipo se rechaza (400)', async function () {
      await como('Vecino');
      estado(await crearIncidencia(idContenedorRuta, '', archivoPng()), 400);
    });

    if (idIncidencia) {
      await prueba('Administrador lista y consulta la incidencia', async function () {
        await como('Administrador');
        const l = await getIncidencias();
        estado(l, 200);
        afirmar(listaContiene(l.data, idIncidencia), 'no aparece en la lista', l);
        const u = await getIncidenciaPorId(idIncidencia);
        estado(u, 200);
        afirmar(contieneValor(u.data, 'Contenedor lleno'), 'tipo incorrecto', u);
      });

      await prueba('Vecino no puede eliminar incidencias (403) y el administrador sí', async function () {
        await como('Vecino');
        estado(await eliminarIncidencia(idIncidencia), 403);
        await como('Administrador');
        const r = await eliminarIncidencia(idIncidencia);
        afirmar(r.data.success, 'no se eliminó', r);
      });
    }
  }

  // ====== (10) Rutas, asignaciones y registro de peso ======
  seccion('10. Rutas y asignaciones (flujo completo con una cuadrilla)');

  if (!idContenedorRuta || !idZonaTest) {
    info('Rutas', 'Faltan la zona o el contenedor de prueba; se omite la sección.');
  } else {
    await como('Administrador');

    await prueba('Rechaza ruta de zona inexistente (404), tipo inválido y zona sin contenedores de ese tipo', async function () {
      estado(await crearRuta(999999, 'Reciclables'), 404);
      estado(await crearRuta(idZonaTest, 'Inventado'), 400);
      estado(await crearRuta(idZonaTest, 'Residuos mezclados'), 400);
    });

    await prueba('Rechaza ruta Reciclables con un camión de Residuos mezclados (SBI1234)', async function () {
      estado(await crearRuta(idZonaTest, 'Reciclables', 'SBI1234'), 400);
    });

    await prueba('Crear ruta sin camión: una parada (el contenedor de prueba)', async function () {
      const r = await crearRuta(idZonaTest, 'Reciclables');
      estado(r, 201);
      afirmar(r.data.id_ruta, 'no devolvió id_ruta', r);
      afirmar(r.data.total_paradas === 1, 'esperaba 1 parada y llegó ' + r.data.total_paradas, r);
      idRutaTest = r.data.id_ruta;
      deshacer('Eliminar ruta ' + idRutaTest, async function () { await como('Administrador'); return eliminarRuta(idRutaTest); });
      return r.data;
    });

    if (idRutaTest) {
      await prueba('Consultar la ruta y verla en el listado del administrador', async function () {
        const u = await getRuta(idRutaTest);
        estado(u, 200);
        const l = await getRutas();
        estado(l, 200);
        afirmar(contieneValor(l.data, idRutaTest), 'la ruta no aparece en la lista', l);
      });

      await prueba('Una cuadrilla no puede registrar peso en una ruta sin su camión (403)', async function () {
        await como('Cuadrilla2');
        estado(await registrarPeso(idRutaTest, 1, 10), 403);
      });

      await prueba('Asignar el camión de prueba a la ruta y a la cuadrilla 2', async function () {
        await como('Administrador');
        const a = await asignarCamionARuta(idRutaTest, matriculaRuta);
        afirmar(a.data.mensaje, 'no se asignó el camión a la ruta', a);
        deshacer('Quitar asignación del camión ' + matriculaRuta, async function () { await como('Administrador'); return eliminarAsignacion(matriculaRuta); });
        // Se devuelve la cuadrilla 2 a su camión original al final
        deshacer('Restaurar camión de la cuadrilla 2', async function () { await como('Administrador'); return asignarCamionACuadrilla(CI_CUADRILLA_2, CAMION_CUADRILLA_2); });
        const c = await asignarCamionACuadrilla(CI_CUADRILLA_2, matriculaRuta);
        afirmar(c.data.mensaje, 'no se asignó el camión a la cuadrilla', c);
        const lista = await getAsignaciones();
        estado(lista, 200);
        afirmar(contieneValor(lista.data, matriculaRuta), 'la asignación no aparece', lista);
      });

      await prueba('La cuadrilla 2 ve su asignación y su ruta activa', async function () {
        await como('Cuadrilla2');
        const mia = await getMiAsignacion();
        estado(mia, 200);
        afirmar(contieneValor(mia.data, matriculaRuta), 'su asignación no muestra el camión de prueba', mia);
        const activa = await getRutaActiva();
        estado(activa, 200);
        afirmar(contieneValor(activa.data, idRutaTest), 'su ruta activa no es la de prueba', activa);
      });

      await prueba('Peso inválido (-5, texto) y parada que no es la siguiente (orden 5)', async function () {
        await como('Cuadrilla2');
        estado(await registrarPeso(idRutaTest, 1, -5), 400);
        estado(await registrarPeso(idRutaTest, 1, 'abc'), 400);
        estado(await registrarPeso(idRutaTest, 5, 10), 409);
      });

      await prueba('Vecino no puede registrar peso (403)', async function () {
        await como('Vecino');
        estado(await registrarPeso(idRutaTest, 1, 10), 403);
      });

      await prueba('Registrar el peso de la parada 1 (12,5 kg) finaliza la ruta', async function () {
        await como('Cuadrilla2');
        const r = await registrarPeso(idRutaTest, 1, 12.5);
        estado(r, 200);
        afirmar(r.data.finalizada === true, 'la ruta debía quedar finalizada (una sola parada)', r);
        return r.data;
      });

      await prueba('Después de recoger, el contenedor figura como limpiado hoy (Dias = 0, reciente)', async function () {
        await como('Administrador');
        const r = await getLimpieza();
        estado(r, 200);
        const fila = buscarFila(r.data.contenedores, 'Id_Contenedor', idContenedorRuta);
        afirmar(fila, 'el contenedor no aparece', r);
        afirmar(fila.Dias === 0 && fila.Categoria === 'reciente', 'esperaba 0 días / reciente y llegó ' + fila.Dias + ' / ' + fila.Categoria, r);
      });

      await prueba('Registrar peso en una ruta finalizada devuelve 409', async function () {
        await como('Cuadrilla2');
        estado(await registrarPeso(idRutaTest, 1, 5), 409);
      });
    }
  }

  } catch (e) {
    fail('Runner', e);
  } finally {

  // ====== (11) Limpieza ======
  seccion('11. Limpieza de los datos de prueba');
  while (pilaLimpieza.length) {
    const paso = pilaLimpieza.pop();
    try {
      const r = await paso.fn();
      const detalle = r && r.data ? (r.data.mensaje || r.data.success || r.data.error || r.status) : 'listo';
      info(paso.nombre, detalle);
    } catch (e) {
      info(paso.nombre + ' (falló)', e && e.message ? e.message : e);
    }
  }

  // Se deja la sesión del navegador como estaba
  if (tokenOriginal) localStorage.setItem('token', tokenOriginal); else localStorage.removeItem('token');
  if (rolOriginal) localStorage.setItem('rol', rolOriginal);

  log('Pruebas terminadas. Correctas: ' + contador.ok + ', fallas: ' + contador.fail + ', avisos: ' + contador.info + '.');
  }
})();
