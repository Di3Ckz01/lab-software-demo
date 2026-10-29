<?php

namespace App\Http\Controllers;

use App\Services\GoApiService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExecutiveSummaryController extends Controller
{
    /**
     * Muestra el tablero macro sin bloquear la carga inicial con la consulta
     * pesada del backend. JavaScript solicita el resumen al terminar de pintar
     * la interfaz y cada vez que el usuario cambia el rango.
     */
    public function index(): View
    {
        $today = now();

        return view('dashboards.sample-collection-analytics', [
            'defaultFrom' => $today->copy()->startOfMonth()->format('Y-m-d'),
            'defaultTo' => $today->format('Y-m-d'),
            'defaultGraceMinutes' => 15,
            'defaultMultiOrderThreshold' => 2,
        ]);
    }

    /**
     * Proxy autenticado hacia GET /executive-summary/{from}-{to}.
     */
    public function data(Request $request, GoApiService $goApiService): JsonResponse
    {
        $fromRaw = trim((string) $request->query('from', ''));
        $toRaw = trim((string) $request->query('to', ''));
        $graceRaw = $request->query('graceMinutes', 15);
        $thresholdRaw = $request->query('n', 2);

        $from = $this->parseDate($fromRaw);
        $to = $this->parseDate($toRaw);

        if ($from === null || $to === null) {
            return response()->json([
                'ok' => false,
                'message' => 'Selecciona una fecha inicial y final válidas.',
            ], 422);
        }

        if ($from->greaterThan($to)) {
            return response()->json([
                'ok' => false,
                'message' => 'La fecha inicial no puede ser posterior a la fecha final.',
            ], 422);
        }

        // El contrato permite hasta 366 fechas inclusivas; una diferencia de
        // 365 días representa precisamente 366 días del calendario.
        if ($from->diffInDays($to) > 365) {
            return response()->json([
                'ok' => false,
                'message' => 'El rango no puede exceder 366 días. Intenta con otro rango de fechas.',
            ], 422);
        }

        if (filter_var($graceRaw, FILTER_VALIDATE_INT) === false) {
            return response()->json([
                'ok' => false,
                'message' => 'La tolerancia debe ser un número entero.',
            ], 422);
        }

        if (filter_var($thresholdRaw, FILTER_VALIDATE_INT) === false) {
            return response()->json([
                'ok' => false,
                'message' => 'El umbral de órdenes debe ser un número entero.',
            ], 422);
        }

        $graceMinutes = (int) $graceRaw;
        $multiOrderThreshold = (int) $thresholdRaw;

        if ($graceMinutes < 0 || $graceMinutes > 240) {
            return response()->json([
                'ok' => false,
                'message' => 'La tolerancia debe estar entre 0 y 240 minutos.',
            ], 422);
        }

        if ($multiOrderThreshold < 1 || $multiOrderThreshold > 50) {
            return response()->json([
                'ok' => false,
                'message' => 'El umbral de órdenes debe estar entre 1 y 50.',
            ], 422);
        }

        $summary = $goApiService->getExecutiveSummary(
            $from->format('Ymd'),
            $to->format('Ymd'),
            $graceMinutes,
            $multiOrderThreshold
        );

        if (session('api_session_expired')) {
            return response()->json([
                'ok' => false,
                'session_expired' => true,
                'message' => session('api_session_message', 'Tu sesión expiró. Inicia sesión nuevamente.'),
                'login_url' => route('login'),
            ], 401);
        }

        if (!is_array($summary)) {
            return response()->json([
                'ok' => false,
                'message' => $goApiService->lastErrorMessage()
                    ?: 'No se pudo obtener la analítica del periodo.',
            ], 502);
        }

        return response()->json([
            'ok' => true,
            'data' => $summary,
            'updated_at' => now()->format('d/m/Y H:i'),
        ]);
    }

    private function parseDate(string $value): ?Carbon
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        try {
            $date = Carbon::createFromFormat('Y-m-d', $value)->startOfDay();
            return $date->format('Y-m-d') === $value ? $date : null;
        } catch (\Throwable) {
            return null;
        }
    }
}
