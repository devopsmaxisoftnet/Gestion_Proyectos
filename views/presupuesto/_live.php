<?php
/**
 * Verificación presupuestal en vivo (JS) para formularios que cargan gasto a actividades del WBS.
 * Uso: incluir este archivo, tener un contenedor vacío (<div id="...">), llamar
 *   CtlLive.setDatos(tareas, control)   con la respuesta de tareasProyecto (tareas[].usado / costo_estimado, control)
 *   CtlLive.render(el, CtlLive.evaluar({tarea_id: monto}), {titulo, msgBloquear, msgAdvertir})
 */
?>
<style>
.lv-card { border:1px solid; border-radius:14px; margin:0 0 16px; overflow:hidden; animation:lvIn .35s ease-out; }
@keyframes lvIn { from { opacity:0; transform:translateY(8px); } to { opacity:1; transform:none; } }
.lv-card.ok { background:#f0fdf4; border-color:#bbf7d0; } .lv-card.alerta { background:#fffbeb; border-color:#fde68a; } .lv-card.exceso { background:#fef2f2; border-color:#fecaca; }
.lv-head { display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap; padding:11px 16px; }
.lv-title { font-size:13px; font-weight:800; color:#0f172a; display:flex; align-items:center; gap:8px; }
.lv-pill { display:inline-flex; align-items:center; gap:6px; padding:4px 11px; border-radius:999px; font-size:11.5px; font-weight:800; background:#fff; }
.lv-card.ok .lv-pill { color:#15803d; } .lv-card.alerta .lv-pill { color:#b45309; } .lv-card.exceso .lv-pill { color:#b91c1c; }
.lv-rows { background:rgba(255,255,255,.75); border-top:1px solid rgba(15,23,42,.06); }
.lv-row { display:grid; grid-template-columns:minmax(180px,1.2fr) minmax(160px,1fr) minmax(190px,1fr); gap:14px; align-items:center; padding:9px 16px; border-bottom:1px solid rgba(15,23,42,.05); font-size:12px; }
.lv-row:last-child { border-bottom:none; }
@media (max-width:820px) { .lv-row { grid-template-columns:1fr; gap:6px; } }
.lv-cod { font-family:monospace; font-weight:700; background:#f1f5f9; padding:1px 6px; border-radius:5px; border:1px solid #e2e8f0; font-size:11px; }
.lv-bar { position:relative; display:flex; height:9px; background:#e2e8f0; border-radius:999px; overflow:hidden; }
.lv-bar i { display:block; height:100%; width:0; transition:width .7s cubic-bezier(.22,.8,.3,1);
    background-image:linear-gradient(45deg,rgba(255,255,255,.28) 25%,transparent 25%,transparent 50%,rgba(255,255,255,.28) 50%,rgba(255,255,255,.28) 75%,transparent 75%,transparent);
    background-size:1rem 1rem; animation:lvStripes 1s linear infinite; }
.lv-bar i.a { background-color:#3b82f6; } .lv-bar i.o { background-color:#f59e0b; } .lv-bar i.o.r { background-color:#dc2626; }
.lv-bar b { position:absolute; top:-2px; bottom:-2px; width:2px; background:#0f172a; opacity:.7; }
@keyframes lvStripes { from { background-position:1rem 0; } to { background-position:0 0; } }
.lv-num { text-align:right; color:#475569; }
.lv-neg { color:#b91c1c; font-weight:800; } .lv-pos { color:#15803d; font-weight:800; }
.lv-foot { padding:9px 16px; font-size:12px; font-weight:600; border-top:1px solid rgba(15,23,42,.06); }
.lv-card.exceso .lv-foot { color:#991b1b; } .lv-card.alerta .lv-foot { color:#92400e; }
</style>
<script>
const CtlLive = (() => {
    let tareas = {}, cfg = {modo: 'advertir', tolerancia: 0, umbral: 80};
    const EPS = 0.005;
    const fm = n => 'S/ ' + Number(n || 0).toLocaleString('es-PE', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const MODOS = {informar: 'Solo informar', advertir: 'Advertir y registrar', bloquear: 'Bloquear con autorización'};

    function setDatos(lista, config) {
        tareas = {};
        (lista || []).forEach(t => { tareas[t.id] = {codigo: t.codigo, nombre: t.nombre, usado: +t.usado || 0, presupuesto: +t.costo_estimado || 0}; });
        cfg = Object.assign({modo: 'advertir', tolerancia: 0, umbral: 80}, config || {});
    }

    function nivelDe(p, proy) {
        if (p <= 0) return 'exceso';
        if (proy > p * (1 + cfg.tolerancia / 100) + EPS) return 'exceso';
        if (proy > p + EPS || proy >= p * cfg.umbral / 100) return 'alerta';
        return 'ok';
    }

    /** cargos: {tarea_id: monto adicional}. Devuelve el mismo formato que ControlPresupuestalModel::evaluar. */
    function evaluar(cargos) {
        const items = [];
        Object.keys(cargos).forEach(id => {
            const monto = Math.round((+cargos[id] || 0) * 100) / 100, t = tareas[id];
            if (!t || monto <= 0) return;
            const proy = t.usado + monto, nivel = nivelDe(t.presupuesto, proy);
            items.push({id, codigo: t.codigo, nombre: t.nombre, presupuesto: t.presupuesto, antes: t.usado, monto, proy, nivel,
                        pctAntes: t.presupuesto > 0 ? t.usado / t.presupuesto * 100 : null, pctProy: t.presupuesto > 0 ? proy / t.presupuesto * 100 : null,
                        exceso: nivel === 'exceso' ? proy - Math.max(0, t.presupuesto) : 0});
        });
        items.sort((a, b) => (b.nivel === 'exceso') - (a.nivel === 'exceso') || b.exceso - a.exceso);
        const hayExceso = items.some(i => i.nivel === 'exceso');
        return {items, modo: cfg.modo, hayExceso, hayAlerta: items.some(i => i.nivel === 'alerta'),
                bloquea: hayExceso && cfg.modo === 'bloquear', excesoTotal: items.reduce((s, i) => s + i.exceso, 0)};
    }

    function render(el, ev, o) {
        o = o || {};
        if (!el) return ev;
        if (!ev.items.length || cfg.modo === 'informar') { el.innerHTML = ''; return ev; }
        const nivel = ev.hayExceso ? 'exceso' : (ev.hayAlerta ? 'alerta' : 'ok');
        const nExc = ev.items.filter(i => i.nivel === 'exceso').length;
        const pill = {exceso: ['fa-exclamation-triangle', nExc + (nExc === 1 ? ' actividad excede' : ' actividades exceden') + ' su presupuesto'],
                      alerta: ['fa-exclamation-circle', 'Cerca del límite de presupuesto'], ok: ['fa-check-circle', 'Dentro del presupuesto']}[nivel];
        const filas = ev.items.map(i => {
            const tiene = i.presupuesto > 0;
            const escala = tiene ? Math.max(100, i.pctProy) : 100;
            const wA = tiene ? Math.min(i.pctAntes, escala) / escala * 100 : 0;
            const wO = tiene ? (i.pctProy - i.pctAntes) / escala * 100 : 100;
            const queda = i.presupuesto - i.proy;
            return `<div class="lv-row">
                <div><span class="lv-cod">${esc(i.codigo)}</span> <strong style="color:#1e293b;">${esc(i.nombre)}</strong></div>
                <div><div class="lv-bar"><i class="a" style="width:${wA}%"></i><i class="o ${i.nivel === 'exceso' ? 'r' : ''}" style="width:${Math.max(0, wO)}%"></i>${tiene ? `<b style="left:${100 / escala * 100}%"></b>` : ''}</div></div>
                <div class="lv-num">${!tiene ? '<span class="lv-neg">Sin presupuesto</span> · ' : (i.presupuesto - i.antes < 0 ? '<span class="lv-neg">Ya sobregirada en ' + fm(i.antes - i.presupuesto) + '</span> · ' : 'Disponible ' + fm(i.presupuesto - i.antes) + ' · ')}esta operación ${fm(i.monto)}
                    → <span class="${queda < 0 ? 'lv-neg' : 'lv-pos'}">${queda < 0 ? 'sobregiro ' + fm(-queda) : 'queda ' + fm(queda)}</span></div></div>`;
        }).join('');
        let pie = '';
        if (ev.hayExceso) pie = ev.bloquea ? (o.msgBloquear || 'Para continuar se necesita la autorización de un usuario facultado, con un motivo.')
                                           : (o.msgAdvertir || 'Podrás continuar: el exceso quedará registrado en la bitácora de control presupuestal.');
        el.innerHTML = `<div class="lv-card ${nivel}">
            <div class="lv-head"><div class="lv-title"><i class="fas fa-balance-scale"></i> ${esc(o.titulo || 'Control presupuestal')}
                <small style="font-weight:600; color:#64748b;">· ${esc(MODOS[cfg.modo] || '')}</small></div>
                <span class="lv-pill"><i class="fas ${pill[0]}"></i> ${esc(pill[1])}</span></div>
            <div class="lv-rows">${filas}</div>${pie ? `<div class="lv-foot"><i class="fas fa-info-circle"></i> ${esc(pie)}</div>` : ''}</div>`;
        return ev;
    }

    return {setDatos, evaluar, render, get config() { return cfg; }};
})();
</script>
