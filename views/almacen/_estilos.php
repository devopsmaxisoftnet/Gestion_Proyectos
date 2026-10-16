<?php
/** Estilos compartidos del módulo Almacén (mismo lenguaje visual que Compras y WBS). */
?>
<style>
.alm-card { background:#fff; border:1px solid #e2e8f0; border-radius:14px; }
.alm-kpi { padding:14px 18px; display:flex; align-items:center; gap:14px; transition:transform .2s, box-shadow .2s; }
.alm-kpi:hover { transform:translateY(-2px); box-shadow:0 8px 20px rgba(15,23,42,.06); }
.alm-kpi-icon { width:42px; height:42px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:18px; flex-shrink:0; }
.alm-kpi-val { font-size:19px; font-weight:800; line-height:1; }
.alm-kpi-lbl { font-size:11px; color:#64748b; font-weight:600; text-transform:uppercase; margin-top:4px; }
.alm-sub { font-size:11px; color:#94a3b8; margin-top:3px; }
.alm-tabs { display:flex; gap:4px; flex-wrap:wrap; padding:0 16px; background:#fafafa; border-bottom:1px solid #e2e8f0; }
.alm-tab { padding:13px 16px; font-size:13px; font-weight:700; color:#64748b; border:none; background:none; cursor:pointer; border-bottom:2.5px solid transparent; display:inline-flex; align-items:center; gap:7px; }
.alm-tab:hover { color:#0f766e; }
.alm-tab.activo { color:#0d9488; border-bottom-color:#0d9488; }
.alm-tab .cnt { background:#f1f5f9; color:#475569; font-size:10.5px; padding:1px 7px; border-radius:999px; }
.alm-tab.activo .cnt { background:#ccfbf1; color:#0f766e; }
.alm-panel { display:none; }
.alm-panel.activo { display:block; }
.alm-tbl { width:100%; border-collapse:collapse; text-align:left; font-size:13px; }
.alm-tbl thead tr { background:#f8fafc; border-bottom:1px solid #e2e8f0; color:#475569; font-size:11.5px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; }
.alm-tbl th { padding:12px 14px; }
.alm-tbl td { padding:12px 14px; border-bottom:1px solid #f1f5f9; vertical-align:middle; }
.alm-tbl tbody tr:hover { background:#fbfcfd; }
.alm-tbl tr.anulado td { opacity:.55; }
.alm-badge { display:inline-flex; align-items:center; gap:4px; padding:3px 9px; border-radius:999px; font-size:11px; font-weight:700; }
.alm-badge.ok { background:#dcfce7; color:#15803d; }
.alm-badge.warn { background:#fef3c7; color:#b45309; }
.alm-badge.danger { background:#fee2e2; color:#b91c1c; }
.alm-badge.neutral { background:#f1f5f9; color:#64748b; }
.alm-badge.info { background:#dbeafe; color:#1d4ed8; }
.alm-btn { width:30px; height:30px; border-radius:8px; display:inline-flex; align-items:center; justify-content:center; text-decoration:none; font-size:12px; cursor:pointer; border:1px solid #cbd5e1; background:#f1f5f9; color:#334155; }
.alm-btn.teal { background:#f0fdfa; border-color:#99f6e4; color:#0f766e; }
.alm-btn.rojo { background:#fef2f2; border-color:#fee2e2; color:#dc2626; }
.alm-btn.azul { background:#eff6ff; border-color:#bfdbfe; color:#1d4ed8; }
.alm-cta { color:#fff; text-decoration:none; padding:9px 18px; border-radius:10px; font-size:13px; font-weight:700; display:inline-flex; align-items:center; gap:7px; border:none; cursor:pointer; }
.alm-cta.teal { background:linear-gradient(135deg,#14b8a6 0%,#0d9488 100%); box-shadow:0 4px 12px rgba(13,148,136,.25); }
.alm-cta.naranja { background:linear-gradient(135deg,#f97316 0%,#ea580c 100%); box-shadow:0 4px 12px rgba(249,115,22,.25); }
.alm-overlay { display:none; position:fixed; inset:0; background:rgba(15,23,42,.6); backdrop-filter:blur(4px); z-index:9999; align-items:center; justify-content:center; }
.alm-modal { background:#fff; border-radius:18px; width:95%; max-width:520px; box-shadow:0 20px 40px rgba(0,0,0,.2); overflow:hidden; max-height:92vh; display:flex; flex-direction:column; animation:almIn .3s ease-out; }
@keyframes almIn { from { opacity:0; transform:translateY(20px); } to { opacity:1; transform:none; } }
.alm-field label { display:block; font-size:12px; font-weight:700; color:#334155; margin-bottom:5px; }
.alm-field input, .alm-field select, .alm-field textarea { width:100%; padding:9px 12px; border:1.5px solid #cbd5e1; border-radius:9px; font-size:13px; box-sizing:border-box; background:#fff; color:#1e293b; }
.alm-search { width:100%; padding:8px 12px 8px 34px; border:1.5px solid #cbd5e1; border-radius:10px; font-size:13px; outline:none; box-sizing:border-box; }
.alm-bar { width:100%; height:8px; background:#e2e8f0; border-radius:999px; overflow:hidden; }
.alm-bar > div { height:100%; border-radius:999px; transition:width 1s cubic-bezier(.22,.8,.3,1); width:0;
    background-image:linear-gradient(45deg, rgba(255,255,255,.28) 25%, transparent 25%, transparent 50%, rgba(255,255,255,.28) 50%, rgba(255,255,255,.28) 75%, transparent 75%, transparent);
    background-size:1rem 1rem; animation:almStripes 1s linear infinite; }
@keyframes almStripes { from { background-position:1rem 0; } to { background-position:0 0; } }
.alm-toggle { padding:5px 12px; border-radius:8px; border:1px solid #cbd5e1; background:#fff; font-size:11.5px; font-weight:700; color:#475569; cursor:pointer; }
.alm-toggle.activo { background:#0d9488; border-color:#0d9488; color:#fff; }
.alm-form-cell input, .alm-form-cell select { width:100%; padding:8px 10px; border:1.5px solid #cbd5e1; border-radius:8px; font-size:12.5px; box-sizing:border-box; background:#fff; color:#334155; }
.alm-form-cell input.num { text-align:right; font-weight:700; }
</style>
