<?php
/**
 * Capa visual compartida de los dashboards por fase (2 a 5):
 * estilos, helpers PHP (hero, KPI), animaciones (contadores, anillos, barras) y utilidades Chart.js.
 */

if (!function_exists('fd_h')) {
    function fd_h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
    function fd_json($v): string {
        return json_encode($v, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
    }
    function fd_money($v, int $dec = 0): string { return 'S/ ' . number_format((float)$v, $dec); }

    /** Número que "corre" desde 0 hasta su valor al cargar la página */
    function fd_count($to, int $dec = 0, string $prefix = '', string $suffix = ''): string {
        return '<span class="fd-count" data-to="' . fd_h((float)$to) . '" data-dec="' . $dec . '" data-prefix="' . fd_h($prefix) . '" data-suffix="' . fd_h($suffix) . '">'
             . fd_h($prefix . number_format((float)$to, $dec) . $suffix) . '</span>';
    }

    /**
     * Tarjeta KPI. $o: to (número), dec, prefix, suffix, sub (texto), subHtml (HTML ya seguro), bar (0-100), barClass, tono (ok|warn|danger)
     */
    function fd_kpi(string $icono, string $bg, string $fg, string $label, array $o, int $i = 0): string {
        $valor = isset($o['text'])
            ? fd_h($o['text'])
            : fd_count($o['to'] ?? 0, $o['dec'] ?? 0, $o['prefix'] ?? '', $o['suffix'] ?? '');
        $color = ['ok' => '#15803d', 'warn' => '#b45309', 'danger' => '#b91c1c'][$o['tono'] ?? ''] ?? '#0f172a';
        $html = '<div class="fd-kpi fd-rise" style="--d:' . (0.05 * $i) . 's">'
              . '<div class="fd-kpi-top"><span class="fd-kpi-label">' . fd_h($label) . '</span>'
              . '<span class="fd-kpi-icon" style="background:' . $bg . '; color:' . $fg . ';"><i class="fas ' . $icono . '"></i></span></div>'
              . '<div class="fd-kpi-val" style="color:' . $color . ';">' . $valor . '</div>';
        if (isset($o['bar'])) {
            $html .= '<div class="fd-bar" style="margin-top:8px;"><div class="' . fd_h($o['barClass'] ?? 'ok') . '" data-w="' . max(0, min(100, (float)$o['bar'])) . '"></div></div>';
        }
        if (!empty($o['subHtml'])) { $html .= '<div class="fd-kpi-sub">' . $o['subHtml'] . '</div>'; }
        elseif (!empty($o['sub'])) { $html .= '<div class="fd-kpi-sub">' . fd_h($o['sub']) . '</div>'; }
        return $html . '</div>';
    }

    /** Banner principal con cifra destacada y anillo de progreso animado */
    function fd_hero(array $c): string {
        $r = 52; $circ = round(2 * M_PI * $r, 2);
        $html = '<div class="fd-hero fd-rise" style="--g1:' . $c['g1'] . '; --g2:' . $c['g2'] . ';">'
              . '<span class="fd-orb o1"></span><span class="fd-orb o2"></span>'
              . '<div class="fd-hero-main">'
              . '<span class="fd-hero-badge"><i class="fas ' . fd_h($c['icono']) . '"></i> ' . fd_h($c['badge']) . '</span>'
              . '<h2 class="fd-hero-title">' . fd_h($c['titulo']) . '</h2>'
              . '<p class="fd-hero-sub">' . fd_h($c['subtitulo']) . '</p>'
              . '<div class="fd-hero-actions">' . ($c['acciones'] ?? '') . '</div></div>'
              . '<div class="fd-hero-side">'
              . '<div class="fd-hero-figure"><div class="fd-hero-figure-label">' . fd_h($c['cifra_label']) . '</div>'
              . '<div class="fd-hero-figure-val">' . fd_count($c['cifra'], $c['cifra_dec'] ?? 0, $c['cifra_prefix'] ?? 'S/ ', $c['cifra_suffix'] ?? '') . '</div>'
              . (!empty($c['cifra_sub']) ? '<div class="fd-hero-figure-sub">' . fd_h($c['cifra_sub']) . '</div>' : '') . '</div>'
              . '<div class="fd-ring"><svg viewBox="0 0 120 120" width="124" height="124">'
              . '<circle cx="60" cy="60" r="' . $r . '" fill="none" stroke="rgba(255,255,255,.22)" stroke-width="10"/>'
              . '<circle class="fd-ring-fg" cx="60" cy="60" r="' . $r . '" fill="none" stroke="#fff" stroke-width="10" stroke-linecap="round" '
              . 'transform="rotate(-90 60 60)" stroke-dasharray="' . $circ . '" stroke-dashoffset="' . $circ . '" data-pct="' . max(0, min(100, (float)$c['anillo'])) . '" data-circ="' . $circ . '"/></svg>'
              . '<div class="fd-ring-txt"><strong>' . fd_count($c['anillo'], 1, '', '%') . '</strong><span>' . fd_h($c['anillo_label']) . '</span></div></div>'
              . '</div></div>';
        return $html;
    }

    /** Badge de estado (ok | warn | danger | info | neutral) */
    function fd_badge(string $tono, string $texto): string {
        return '<span class="fd-b ' . fd_h($tono) . '">' . fd_h($texto) . '</span>';
    }
}
?>
<style>
.fd-wrap { --ink:#0f172a; --mute:#64748b; padding:4px 0 40px; }
.fd-rise { opacity:0; transform:translateY(16px); animation:fdRise .6s cubic-bezier(.22,.8,.3,1) forwards; animation-delay:var(--d,0s); }
@keyframes fdRise { to { opacity:1; transform:none; } }

/* Hero */
.fd-hero { position:relative; overflow:hidden; border-radius:22px; padding:30px 34px; margin-bottom:22px; color:#fff;
    background:linear-gradient(120deg,var(--g1),var(--g2)); box-shadow:0 14px 34px -12px var(--g1); display:flex; justify-content:space-between; align-items:center; gap:28px; flex-wrap:wrap; }
.fd-orb { position:absolute; border-radius:50%; background:rgba(255,255,255,.10); pointer-events:none; animation:fdFloat 9s ease-in-out infinite; }
.fd-orb.o1 { width:260px; height:260px; right:-60px; top:-90px; }
.fd-orb.o2 { width:170px; height:170px; left:38%; bottom:-80px; animation-delay:-4s; background:rgba(255,255,255,.07); }
@keyframes fdFloat { 0%,100% { transform:translate(0,0) scale(1); } 50% { transform:translate(-18px,14px) scale(1.06); } }
.fd-hero-main { position:relative; flex:1 1 380px; min-width:280px; }
.fd-hero-badge { display:inline-flex; align-items:center; gap:7px; background:rgba(255,255,255,.18); border:1px solid rgba(255,255,255,.3); padding:5px 14px; border-radius:999px; font-size:11px; font-weight:800; letter-spacing:.6px; text-transform:uppercase; backdrop-filter:blur(4px); }
.fd-hero-title { margin:14px 0 8px; font-size:28px; line-height:1.15; font-weight:800; letter-spacing:-.4px; }
.fd-hero-sub { margin:0; font-size:14px; opacity:.88; max-width:560px; line-height:1.5; }
.fd-hero-actions { display:flex; gap:10px; flex-wrap:wrap; margin-top:18px; }
.fd-hero-actions a { background:rgba(255,255,255,.16); border:1px solid rgba(255,255,255,.35); color:#fff; text-decoration:none; padding:8px 15px; border-radius:11px; font-size:12.5px; font-weight:700; display:inline-flex; align-items:center; gap:7px; transition:background .2s, transform .2s; }
.fd-hero-actions a:hover { background:rgba(255,255,255,.3); transform:translateY(-2px); }
.fd-hero-side { position:relative; display:flex; align-items:center; gap:26px; flex-wrap:wrap; }
.fd-hero-figure { text-align:right; }
.fd-hero-figure-label { font-size:12px; opacity:.85; font-weight:600; text-transform:uppercase; letter-spacing:.5px; }
.fd-hero-figure-val { font-size:38px; font-weight:800; letter-spacing:-1px; line-height:1.1; margin-top:4px; }
.fd-hero-figure-sub { font-size:12px; opacity:.8; margin-top:2px; }
.fd-ring { position:relative; width:124px; height:124px; flex-shrink:0; }
.fd-ring-fg { transition:stroke-dashoffset 1.6s cubic-bezier(.22,.8,.3,1); }
.fd-ring-txt { position:absolute; inset:0; display:flex; flex-direction:column; align-items:center; justify-content:center; text-align:center; }
.fd-ring-txt strong { font-size:21px; font-weight:800; line-height:1; }
.fd-ring-txt span { font-size:9.5px; opacity:.85; text-transform:uppercase; letter-spacing:.4px; margin-top:3px; max-width:78px; line-height:1.15; }

/* KPIs */
.fd-grid-kpi { display:grid; grid-template-columns:repeat(auto-fit,minmax(215px,1fr)); gap:14px; margin-bottom:22px; }
.fd-grid-kpi.n6 { grid-template-columns:repeat(3,minmax(0,1fr)); }
.fd-grid-kpi.n8 { grid-template-columns:repeat(4,minmax(0,1fr)); }
@media (max-width:1100px) { .fd-grid-kpi.n8 { grid-template-columns:repeat(2,minmax(0,1fr)); } }
@media (max-width:760px) { .fd-grid-kpi.n6, .fd-grid-kpi.n8 { grid-template-columns:repeat(2,minmax(0,1fr)); } }
@media (max-width:480px) { .fd-grid-kpi.n6, .fd-grid-kpi.n8 { grid-template-columns:1fr; } }
.fd-kpi { background:#fff; border:1px solid #e2e8f0; border-radius:16px; padding:16px 18px; transition:transform .25s, box-shadow .25s; }
.fd-kpi:hover { transform:translateY(-4px); box-shadow:0 14px 28px -10px rgba(15,23,42,.18); }
.fd-kpi-top { display:flex; justify-content:space-between; align-items:center; gap:8px; }
.fd-kpi-label { font-size:12.5px; font-weight:600; color:var(--mute); }
.fd-kpi-icon { width:36px; height:36px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:15px; flex-shrink:0; }
.fd-kpi-val { font-size:27px; font-weight:800; letter-spacing:-.5px; margin-top:8px; line-height:1.1; }
.fd-kpi-sub { font-size:11.5px; color:#94a3b8; margin-top:6px; }

/* Tarjetas / gráficos */
.fd-grid-2 { display:grid; grid-template-columns:repeat(auto-fit,minmax(380px,1fr)); gap:16px; margin-bottom:22px; }
.fd-card { background:#fff; border:1px solid #e2e8f0; border-radius:18px; padding:20px 22px; min-width:0; box-shadow:0 2px 10px rgba(15,23,42,.03); }
.fd-grid-2.w { grid-template-columns:repeat(auto-fit,minmax(470px,1fr)); }
.fd-card.span2 { grid-column:span 2; }
@media (max-width:900px) { .fd-card.span2 { grid-column:auto; } }
.fd-card-head { display:flex; justify-content:space-between; align-items:flex-start; gap:10px; margin-bottom:12px; flex-wrap:wrap; }
.fd-card-title { margin:0; font-size:15px; font-weight:800; color:var(--ink); display:flex; align-items:center; gap:8px; }
.fd-card-sub { font-size:12px; color:#94a3b8; margin:3px 0 0; }
.fd-chart { position:relative; height:280px; }
.fd-chart.alto { height:330px; }
.fd-empty { position:absolute; inset:0; display:none; flex-direction:column; align-items:center; justify-content:center; text-align:center; color:#94a3b8; font-size:12.5px; gap:8px; padding:20px; }
.fd-empty i { font-size:30px; color:#cbd5e1; }
.fd-section { display:flex; justify-content:space-between; align-items:flex-end; margin:6px 0 12px; gap:10px; flex-wrap:wrap; }
.fd-section h3 { margin:0; font-size:18px; font-weight:800; color:var(--ink); }
.fd-section p { margin:3px 0 0; font-size:12.5px; color:var(--mute); }

/* Tablas */
.fd-table { width:100%; border-collapse:collapse; font-size:13px; }
.fd-table th { text-align:left; padding:9px 10px; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.4px; color:#64748b; border-bottom:1px solid #e2e8f0; }
.fd-table td { padding:11px 10px; border-bottom:1px solid #f1f5f9; vertical-align:middle; }
.fd-table tbody tr { transition:background .15s; }
.fd-table tbody tr:hover { background:#f8fafc; }
.fd-table .num { text-align:right; }
.fd-nowrap { white-space:nowrap; }
.fd-link { color:inherit; text-decoration:none; font-weight:700; }
.fd-link:hover { text-decoration:underline; }
.fd-b { display:inline-flex; align-items:center; gap:4px; padding:3px 9px; border-radius:999px; font-size:11px; font-weight:700; white-space:nowrap; }
.fd-b.ok { background:#dcfce7; color:#15803d; } .fd-b.warn { background:#fef3c7; color:#b45309; } .fd-b.danger { background:#fee2e2; color:#b91c1c; }
.fd-b.info { background:#dbeafe; color:#1d4ed8; } .fd-b.neutral { background:#f1f5f9; color:#64748b; }
.fd-ok-box { padding:34px 20px; text-align:center; color:#16a34a; font-weight:600; font-size:13px; }
.fd-ok-box i { font-size:32px; display:block; margin-bottom:8px; }
.fd-ok-box.vacio { color:#94a3b8; font-weight:500; } .fd-ok-box.vacio i { color:#cbd5e1; }

/* Barras animadas */
.fd-bar { width:100%; height:8px; background:#e2e8f0; border-radius:999px; overflow:hidden; }
.fd-bar > div { height:100%; width:0; border-radius:999px; transition:width 1.4s cubic-bezier(.22,.8,.3,1);
    background-image:linear-gradient(45deg,rgba(255,255,255,.28) 25%,transparent 25%,transparent 50%,rgba(255,255,255,.28) 50%,rgba(255,255,255,.28) 75%,transparent 75%,transparent);
    background-size:1rem 1rem; animation:fdStripes 1s linear infinite; }
.fd-bar > div.ok { background-color:#16a34a; } .fd-bar > div.warn { background-color:#f59e0b; } .fd-bar > div.danger { background-color:#dc2626; } .fd-bar > div.info { background-color:#3b82f6; }
@keyframes fdStripes { from { background-position:1rem 0; } to { background-position:0 0; } }
.fd-nota { background:#f8fafc; border:1px solid #e2e8f0; border-radius:14px; padding:14px 18px; font-size:12.5px; color:#475569; line-height:1.65; }
@media (prefers-reduced-motion: reduce) { .fd-rise { animation:none; opacity:1; transform:none; } .fd-orb, .fd-bar > div { animation:none; } }
</style>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
const fdFmt = (n, d = 2) => Number(n || 0).toLocaleString('es-PE', {minimumFractionDigits: d, maximumFractionDigits: d});
const fdMoney = (n, d = 0) => 'S/ ' + fdFmt(n, d);
const fdEsc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const fdReduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

/* Contadores animados */
function fdContar(el) {
    const to = parseFloat(el.dataset.to) || 0, dec = parseInt(el.dataset.dec) || 0, pre = el.dataset.prefix || '', suf = el.dataset.suffix || '';
    const pintar = v => { el.textContent = pre + v.toLocaleString('es-PE', {minimumFractionDigits: dec, maximumFractionDigits: dec}) + suf; };
    if (fdReduce) { pintar(to); return; }
    const t0 = performance.now(), dur = 1400;
    (function paso(t) {
        const p = Math.min(1, (t - t0) / dur);
        pintar(to * (1 - Math.pow(1 - p, 3)));
        if (p < 1) requestAnimationFrame(paso);
    })(t0);
}

/* Arranque: contadores, anillos y barras */
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.fd-count').forEach(fdContar);
    requestAnimationFrame(() => {
        document.querySelectorAll('.fd-ring-fg').forEach(c => { c.style.strokeDashoffset = (parseFloat(c.dataset.circ) * (1 - parseFloat(c.dataset.pct) / 100)); });
        document.querySelectorAll('.fd-bar > div[data-w]').forEach(b => { b.style.width = b.dataset.w + '%'; });
    });
});

/* Chart.js: apariencia y animación comunes */
if (typeof Chart !== 'undefined') {
    Chart.defaults.font.family = "'Inter', 'Segoe UI', sans-serif";
    Chart.defaults.color = '#64748b';
    Chart.defaults.animation.duration = 1400;
    Chart.defaults.animation.easing = 'easeOutQuart';
    Chart.defaults.plugins.legend.position = 'bottom';
    Chart.defaults.plugins.legend.labels.usePointStyle = true;
    Chart.defaults.plugins.legend.labels.boxWidth = 8;
    Chart.defaults.plugins.tooltip.backgroundColor = 'rgba(15,23,42,.92)';
    Chart.defaults.plugins.tooltip.padding = 11;
    Chart.defaults.plugins.tooltip.cornerRadius = 10;
    Chart.defaults.scale.grid.color = 'rgba(148,163,184,.18)';
    // Texto en el centro de las donas: options.plugins.centerText = {text, sub}
    Chart.register({
        id: 'centerText',
        afterDraw(chart) {
            const o = chart.options.plugins.centerText;
            if (!o || o.text === undefined) return;   // Chart.js entrega {} a los gráficos sin esta opción
            const a = chart.chartArea, x = (a.left + a.right) / 2, y = (a.top + a.bottom) / 2;
            const c = chart.ctx; c.save(); c.textAlign = 'center'; c.textBaseline = 'middle';
            c.fillStyle = '#0f172a'; c.font = "800 19px 'Inter', sans-serif"; c.fillText(o.text, x, y - 6);
            c.fillStyle = '#94a3b8'; c.font = "600 11px 'Inter', sans-serif"; c.fillText(o.sub || '', x, y + 14);
            c.restore();
        }
    });
}

/** Muestra un mensaje en lugar del gráfico cuando no hay datos */
function fdVacio(canvasId) {
    const cv = document.getElementById(canvasId);
    if (!cv) return;
    cv.style.display = 'none';
    const e = cv.parentElement.querySelector('.fd-empty');
    if (e) e.style.display = 'flex';
}
/** Etiqueta corta de dinero para ejes: S/ 4.5 M · S/ 500 mil · S/ 80 */
const fdCorto = v => { const n = Math.abs(Number(v) || 0), s = v < 0 ? '− ' : ''; return s + 'S/ ' + (n >= 1e6 ? fdFmt(n / 1e6, n % 1e6 ? 1 : 0) + ' M' : (n >= 1e3 ? fdFmt(n / 1e3, n % 1e3 ? 1 : 0) + ' mil' : fdFmt(n, 0))); };
const fdSuma = a => a.reduce((x, y) => x + (Number(y) || 0), 0);
</script>
