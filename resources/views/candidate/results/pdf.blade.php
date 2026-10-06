<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Rapport TestDaF — Tentative #{{ $attempt->id }}</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; color: #1e293b; margin: 32px; font-size: 13px; }
        h1 { font-size: 22px; margin: 0 0 4px; }
        h2 { font-size: 15px; margin: 20px 0 8px; border-bottom: 1px solid #e2e8f0; padding-bottom: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #cbd5e1; padding: 6px 8px; text-align: left; }
        th { background: #f1f5f9; }
        .muted { color: #64748b; font-size: 12px; }
        .grade { font-size: 18px; font-weight: bold; }
        .no-print { margin: 16px 0; }
        @media print { .no-print { display: none; } body { margin: 0; } }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()" style="background:#2563eb;color:#fff;border:0;border-radius:8px;padding:10px 18px;font-size:14px;cursor:pointer;">Imprimer / Exporter en PDF</button>
        <a href="{{ route('results.report', $attempt) }}" style="margin-left:12px;color:#2563eb;">Retour au rapport</a>
    </div>

    <h1>Rapport TestDaF digital — {{ $attempt->modellTest?->title ?? 'Tentative #'.$attempt->id }}</h1>
    <p class="muted">
        Candidat : {{ $user->name }} ({{ $user->email }}) ·
        Date : {{ $attempt->completed_at?->format('d/m/Y H:i') ?? '—' }} ·
        Généré le {{ $generatedAt->format('d/m/Y H:i') }}
    </p>
    @if ($grade20 !== null)
        <p class="grade">Note indicative : {{ number_format($grade20, 1, ',', ' ') }} / 20</p>
        <p class="muted">Moyenne des parties corrigées (échelle 0–20). Estimation pédagogique — jamais une note officielle TestDaF. Le TestDaF évalue chaque partie séparément.</p>
    @endif

    <h2>Résultats par compétence</h2>
    <table>
        <tr><th>Compétence</th><th>Points</th><th>%</th><th>/20</th><th>TDN estimé</th></tr>
        @foreach (\App\Enums\Skill::sequence() as $skill)
            @php $r = $results->get($skill->value); @endphp
            <tr>
                <td>{{ $skill->label() }}</td>
                <td>{{ $r ? number_format($r->points, 1, ',', ' ').' / '.number_format($r->max_points, 1, ',', ' ') : '—' }}</td>
                <td>{{ $r ? number_format($r->percentage, 0).' %' : '—' }}</td>
                <td>{{ $r ? number_format($r->points20, 1, ',', ' ') : '—' }}</td>
                <td>{{ $r ? $r->tdn : '—' }}</td>
            </tr>
        @endforeach
    </table>

    <h2>Détail par partie</h2>
    <table>
        <tr><th>Partie</th><th>Compétence</th><th>Score</th><th>%</th></tr>
        @foreach ($partSummary as $part)
            <tr>
                <td>{{ $part['title'] }}</td>
                <td>{{ $part['skill_label'] }}</td>
                <td>{{ number_format($part['score'], 1, ',', ' ').' / '.number_format($part['max'], 1, ',', ' ') }}</td>
                <td>{{ $part['percentage'] !== null ? number_format($part['percentage'], 1).' %' : '—' }}</td>
            </tr>
        @endforeach
    </table>

    <h2>Erreurs fréquentes</h2>
    @if (! empty($weakTypes))
        <ul>
            @foreach ($weakTypes as $row)
                <li>{{ $row['type'] }} — {{ $row['error_rate'] }} % d'erreurs ({{ $row['errors'] }}/{{ $row['total'] }})</li>
            @endforeach
        </ul>
    @else
        <p>Aucune erreur récurrente détectée.</p>
    @endif

    <p class="muted" style="margin-top:24px;">Échelle 0–20 : 0–4 sous TDN 3 · 5–9 TDN 3 · 10–15 TDN 4 · 16–20 TDN 5. Document d'entraînement, sans valeur certificative.</p>
</body>
</html>
