@php
    $helpRouteName = $contextHelpKey ?? optional(request()->route())->getName();
    $helpRoutes = config('context_help.routes', []);
    $helpEntry = $helpRoutes[$helpRouteName] ?? null;
    $standalone = $standalone ?? false;
@endphp

@if ($helpEntry)
    <button
        type="button"
        class="context-help-trigger {{ $standalone ? 'context-help-trigger--standalone' : 'context-help-trigger--topbar' }}"
        data-context-help-open
        aria-controls="contextHelpDrawer"
        aria-expanded="false"
        title="Abrir manual de esta pantalla"
    >
        <span class="context-help-trigger-icon" aria-hidden="true">?</span>
        <span class="context-help-trigger-label">Ayuda</span>
    </button>
@endif
