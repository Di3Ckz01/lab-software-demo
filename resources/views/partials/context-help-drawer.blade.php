@php
    $helpRouteName = $contextHelpKey ?? optional(request()->route())->getName();
    $helpRoutes = config('context_help.routes', []);
    $helpEntry = $helpRoutes[$helpRouteName] ?? null;
    $manualConfig = config('context_help.manual', []);

    $manualPath = $helpEntry['manual_path'] ?? ($manualConfig['path'] ?? 'manuales/manual-demo.pdf');
    $manualLabel = $helpEntry['manual_label'] ?? ($manualConfig['label'] ?? 'Ayuda de LAB DEMO');
    $manualBaseUrl = asset($manualPath);
    // La edición pública no incluye manuales institucionales.
    $manualAvailable = is_file(public_path($manualPath));

    $helpTitle = $helpEntry['title'] ?? 'Ayuda de esta pantalla';
    $helpPage = max(1, (int) ($helpEntry['page'] ?? 1));
    $helpPageEnd = max($helpPage, (int) ($helpEntry['page_end'] ?? $helpPage));
    $helpPageLabel = $helpPage === $helpPageEnd
        ? 'Página ' . $helpPage
        : 'Páginas ' . $helpPage . ' - ' . $helpPageEnd;

    // Chrome/Edge/Firefox reconocen #page=N en el visor PDF integrado.
    $manualSectionUrl = $manualBaseUrl . '#page=' . $helpPage . '&zoom=page-width';
@endphp

@if ($helpEntry)
<style>
    .context-help-trigger{display:inline-flex;align-items:center;justify-content:center;gap:7px;min-height:36px;padding:8px 13px;border-radius:11px;font-family:inherit;font-size:11px;font-weight:850;line-height:1;border:1px solid #c8daf4;cursor:pointer;transition:.16s ease;white-space:nowrap}.context-help-trigger:hover{transform:translateY(-1px)}.context-help-trigger-icon{width:20px;height:20px;border:1.5px solid currentColor;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:12px;font-weight:950}.context-help-trigger--topbar{color:#fff;background:rgba(255,255,255,.08);border-color:rgba(255,255,255,.22)}.context-help-trigger--topbar:hover{background:rgba(255,255,255,.15);border-color:rgba(255,255,255,.38)}.context-help-trigger--standalone{position:fixed;z-index:3100;right:18px;top:18px;min-width:92px;color:#0b63df;background:rgba(255,255,255,.98);box-shadow:0 8px 24px rgba(15,35,70,.16)}.context-help-trigger--standalone:hover{background:#f5f9ff}.context-help-overlay{position:fixed;inset:0;z-index:3200;background:rgba(6,27,58,.28);opacity:0;visibility:hidden;transition:opacity .22s ease,visibility .22s ease}.context-help-overlay.is-open{opacity:1;visibility:visible}.context-help-drawer{position:absolute;right:0;top:0;width:min(720px,96vw);height:100%;background:#f8fbff;border-left:1px solid #dce7f5;box-shadow:-22px 0 55px rgba(14,38,78,.22);display:flex;flex-direction:column;transform:translateX(100%);transition:transform .24s ease}.context-help-overlay.is-open .context-help-drawer{transform:translateX(0)}.context-help-header{padding:16px 18px;background:#fff;border-bottom:1px solid #e3eaf4;display:flex;align-items:center;justify-content:space-between;gap:16px}.context-help-heading{min-width:0}.context-help-eyebrow{display:flex;align-items:center;gap:7px;color:#1468f3;font-size:10px;font-weight:900;text-transform:uppercase;letter-spacing:.05em;margin-bottom:4px}.context-help-heading h2{margin:0;color:#10294f;font-size:20px;line-height:1.2;font-weight:900}.context-help-heading p{margin:5px 0 0;color:#687b96;font-size:11px;line-height:1.4}.context-help-close{width:36px;height:36px;flex:0 0 auto;border:1px solid #dce6f3;border-radius:50%;background:#fff;color:#506582;font-size:22px;line-height:1;cursor:pointer}.context-help-close:hover{background:#f2f7ff;color:#0b63df}.context-help-body{flex:1;min-height:0;padding:12px;background:#eef4fb}.context-help-pdf-shell{height:100%;min-height:420px;background:#fff;border:1px solid #d8e4f2;border-radius:14px;overflow:hidden;box-shadow:0 7px 20px rgba(19,49,89,.08);position:relative}.context-help-pdf-frame{display:block;width:100%;height:100%;min-height:420px;border:0;background:#fff}.context-help-pdf-fallback{position:absolute;inset:0;z-index:-1;display:flex;align-items:center;justify-content:center;padding:30px;text-align:center;color:#60728c;font-size:13px}.context-help-footer{padding:12px 16px;border-top:1px solid #e1e9f3;background:#fff;display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap}.context-help-footer-actions{display:flex;align-items:center;gap:9px;flex-wrap:wrap}.context-help-link,.context-help-understood{min-height:40px;padding:0 14px;border-radius:11px;font-family:inherit;font-size:11px;font-weight:850;display:inline-flex;align-items:center;justify-content:center;text-decoration:none;cursor:pointer}.context-help-link{border:1px solid #c8daf4;background:#fff;color:#1468f3}.context-help-link:hover{background:#f2f7ff}.context-help-understood{min-width:105px;border:0;background:#1468f3;color:#fff;box-shadow:0 8px 18px rgba(20,104,243,.2)}.context-help-understood:hover{background:#0d5bd4}.context-help-open .app-shell{filter:saturate(.92)}body.context-help-no-scroll{overflow:hidden}@media(max-width:720px){.context-help-trigger-label{display:none}.context-help-trigger{padding:8px}.context-help-trigger--standalone{min-width:40px;width:40px;height:40px;border-radius:50%;padding:0}.context-help-drawer{width:100vw}.context-help-header{padding:14px}.context-help-heading h2{font-size:18px}.context-help-body{padding:8px}.context-help-footer{align-items:stretch}.context-help-footer-actions{width:100%}.context-help-link,.context-help-understood{flex:1}.context-help-pdf-shell,.context-help-pdf-frame{min-height:360px}}
</style>

<div class="context-help-overlay" id="contextHelpOverlay" aria-hidden="true">
    <aside class="context-help-drawer" id="contextHelpDrawer" role="dialog" aria-modal="true" aria-labelledby="contextHelpTitle">
        <header class="context-help-header">
            <div class="context-help-heading">
                <div class="context-help-eyebrow">
                    <span aria-hidden="true">?</span>
                    <span>{{ $manualLabel }}</span>
                </div>
                <h2 id="contextHelpTitle">{{ $helpTitle }}</h2>
                <p>{{ $manualAvailable ? $helpPageLabel . ' de la guía disponible.' : 'La guía institucional no está incluida en esta demostración.' }}</p>
            </div>
            <button type="button" class="context-help-close" data-context-help-close aria-label="Cerrar ayuda">×</button>
        </header>

        <div class="context-help-body">
            @if ($manualAvailable)
            <div class="context-help-pdf-shell">
                <div class="context-help-pdf-fallback">
                    Si el visor PDF de tu navegador no se muestra, utiliza “Abrir esta sección” en la parte inferior.
                </div>
                <iframe
                    class="context-help-pdf-frame"
                    src="{{ $manualSectionUrl }}"
                    title="{{ $manualLabel }} - {{ $helpTitle }}"
                    loading="lazy"
                ></iframe>
            </div>
            @else
            <div role="status" style="padding:24px;background:#fff;border:1px solid #d8e4f2;border-radius:14px;line-height:1.7;color:#526981;">
                Esta es una versión de portafolio con datos ficticios. El manual institucional fue excluido de esta edición.
            </div>
            @endif
        </div>

        <footer class="context-help-footer">
            <div class="context-help-footer-actions">
                @if ($manualAvailable)
                <a class="context-help-link" href="{{ $manualSectionUrl }}" target="_blank" rel="noopener">
                    Abrir esta sección
                </a>
                <a class="context-help-link" href="{{ $manualBaseUrl }}" target="_blank" rel="noopener">
                    Manual completo
                </a>
                @else
                <span style="font-size:11px;color:#687b96;">Guía no incluida en el portafolio</span>
                @endif
            </div>
            <button type="button" class="context-help-understood" data-context-help-close>Entendido</button>
        </footer>
    </aside>
</div>

<script>
(function () {
    const overlay = document.getElementById('contextHelpOverlay');
    if (!overlay || overlay.dataset.ready === '1') return;
    overlay.dataset.ready = '1';

    const drawer = document.getElementById('contextHelpDrawer');
    const openButtons = Array.from(document.querySelectorAll('[data-context-help-open]'));
    const closeButtons = Array.from(document.querySelectorAll('[data-context-help-close]'));
    let lastTrigger = null;

    function openHelp(trigger) {
        lastTrigger = trigger || document.activeElement;
        overlay.classList.add('is-open');
        overlay.setAttribute('aria-hidden', 'false');
        document.body.classList.add('context-help-no-scroll');
        openButtons.forEach(button => button.setAttribute('aria-expanded', 'true'));
        window.setTimeout(() => overlay.querySelector('[data-context-help-close]')?.focus(), 40);
    }

    function closeHelp() {
        overlay.classList.remove('is-open');
        overlay.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('context-help-no-scroll');
        openButtons.forEach(button => button.setAttribute('aria-expanded', 'false'));
        if (lastTrigger && typeof lastTrigger.focus === 'function') lastTrigger.focus();
    }

    openButtons.forEach(button => button.addEventListener('click', () => openHelp(button)));
    closeButtons.forEach(button => button.addEventListener('click', closeHelp));

    overlay.addEventListener('click', event => {
        if (event.target === overlay) closeHelp();
    });

    drawer?.addEventListener('click', event => event.stopPropagation());

    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && overlay.classList.contains('is-open')) closeHelp();
    });
})();
</script>
@endif
