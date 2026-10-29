@if (
    app()->environment('local')
    && config('vcare_demo.enabled', false) === true
    && session('vcare_demo_session') === true
)
    {{-- Aviso visible únicamente para usuarios del modo demostración. --}}
    <div
        role="status"
        aria-label="Modo demostración con información ficticia"
        style="
            position: fixed;
            right: 16px;
            bottom: 16px;
            z-index: 9999;
            pointer-events: none;
            background: #172c4a;
            color: #ffffff;
            border: 1px solid rgba(255,255,255,.15);
            border-radius: 12px;
            padding: 10px 14px;
            box-shadow: 0 5px 20px rgba(0,0,0,.18);
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.5;
        "
    >
        <div style="font-weight: 700;">
            &#9679; Modo demostración
        </div>
        <div style="font-size: 10px; color: #d2deef;">
            Datos ficticios · Sin información clínica real
        </div>
    </div>
@endif