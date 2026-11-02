/*
 * Diálogos con la estética de PHYLLO OS. Reemplazan alert() y confirm().
 *
 *   Dialogo.aviso('Texto', { titulo: 'Título' });
 *   Dialogo.confirmar('¿Seguro?', { aceptar: 'Eliminar' }, function () { ... });
 *
 * Para formularios no hace falta JavaScript: basta con
 *   <form data-confirmar="¿Seguro?" data-confirmar-ok="Eliminar"> ... </form>
 * y al enviarlo se muestra el cuadro; el formulario sale solo si se acepta.
 */
(function () {
  'use strict';

  var actual = null; // diálogo abierto (solo hay uno a la vez)

  function cerrar() {
    if (!actual) return;
    document.removeEventListener('keydown', actual.teclas, true);
    actual.fondo.remove();
    var previo = actual.previo;
    actual = null;
    if (previo && previo.focus) previo.focus();
  }

  function boton(texto, clase, alClick) {
    var b = document.createElement('button');
    b.type = 'button';
    b.textContent = texto;
    if (clase) b.className = clase;
    b.addEventListener('click', alClick);
    return b;
  }

  // botones: [{ texto, alClick, principal }]. alCancelar: qué hace Esc / la ×.
  function mostrar(titulo, texto, icono, botones, alCancelar) {
    cerrar();
    var previo = document.activeElement;

    var fondo = document.createElement('div');
    fondo.className = 'dlg-fondo';

    var dlg = document.createElement('div');
    dlg.className = 'dlg';
    dlg.setAttribute('role', 'dialog');
    dlg.setAttribute('aria-modal', 'true');
    dlg.setAttribute('aria-labelledby', 'dlgTitulo');
    dlg.setAttribute('aria-describedby', 'dlgTexto');

    var barra = document.createElement('div');
    barra.className = 'dlg-barra';
    var t = document.createElement('span');
    t.id = 'dlgTitulo';
    t.textContent = titulo;
    var x = boton('×', 'dlg-cerrar', function () { cerrar(); alCancelar(); });
    x.setAttribute('aria-label', 'Cerrar');
    x.tabIndex = -1;
    barra.appendChild(t);
    barra.appendChild(x);

    var cuerpo = document.createElement('div');
    cuerpo.className = 'dlg-cuerpo';
    var ic = document.createElement('div');
    ic.className = 'dlg-icono';
    ic.textContent = icono;
    ic.setAttribute('aria-hidden', 'true');
    var tx = document.createElement('div');
    tx.className = 'dlg-texto';
    tx.id = 'dlgTexto';
    tx.textContent = texto; // textContent: el mensaje nunca se interpreta como HTML
    cuerpo.appendChild(ic);
    cuerpo.appendChild(tx);

    var fila = document.createElement('div');
    fila.className = 'dlg-botones';
    var hechos = botones.map(function (def) {
      var b = boton(def.texto, def.principal ? 'dlg-ok' : '', function () {
        cerrar();
        def.alClick();
      });
      fila.appendChild(b);
      return b;
    });

    dlg.appendChild(barra);
    dlg.appendChild(cuerpo);
    dlg.appendChild(fila);
    fondo.appendChild(dlg);
    document.body.appendChild(fondo);

    // Esc cancela; Tab da vueltas entre los botones del cuadro.
    function teclas(e) {
      if (e.key === 'Escape') {
        e.preventDefault();
        cerrar();
        alCancelar();
      } else if (e.key === 'Tab') {
        e.preventDefault();
        var i = hechos.indexOf(document.activeElement);
        i = e.shiftKey ? (i <= 0 ? hechos.length - 1 : i - 1) : (i + 1) % hechos.length;
        hechos[i].focus();
      }
    }
    document.addEventListener('keydown', teclas, true);
    actual = { fondo: fondo, previo: previo, teclas: teclas };

    // El foco empieza en el botón principal, así Enter acepta.
    var inicial = hechos[botones.length - 1];
    botones.forEach(function (def, i) { if (def.principal) inicial = hechos[i]; });
    inicial.focus();
  }

  var Dialogo = {
    aviso: function (texto, opciones) {
      opciones = opciones || {};
      var fin = opciones.alCerrar || function () {};
      mostrar(opciones.titulo || 'Aviso', texto, '!', [
        { texto: opciones.boton || 'Aceptar', principal: true, alClick: fin }
      ], fin);
    },

    confirmar: function (texto, opciones, alAceptar) {
      opciones = opciones || {};
      mostrar(opciones.titulo || 'Confirmar acción', texto, '?', [
        { texto: opciones.aceptar || 'Aceptar', principal: true, alClick: alAceptar },
        { texto: opciones.cancelar || 'Cancelar', alClick: function () {} }
      ], function () {});
    }
  };

  window.Dialogo = Dialogo;

  // Formularios con data-confirmar: piden confirmación antes de enviarse.
  document.addEventListener('submit', function (e) {
    var form = e.target;
    var msg = form.getAttribute && form.getAttribute('data-confirmar');
    if (!msg) return;
    e.preventDefault();
    Dialogo.confirmar(msg, { aceptar: form.getAttribute('data-confirmar-ok') || 'Aceptar' }, function () {
      form.submit(); // submit() no vuelve a disparar este evento
    });
  });
})();
