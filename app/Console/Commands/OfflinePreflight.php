<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Diagnóstico de solo lectura previo a Offline v1 (Bloque 1, D-36).
 *
 * No modifica la base de datos: únicamente ejecuta consultas SELECT y
 * reporta situaciones que conviene revisar antes de habilitar la
 * sincronización offline.
 */
class OfflinePreflight extends Command
{
    protected $signature = 'offline:preflight
                            {--detalle : Muestra las filas encontradas en cada revisión}';

    protected $description = 'Diagnóstico de solo lectura de los datos antes de habilitar Offline v1';

    /**
     * Preguntas cuyos datos se guardan en household_members y no en answers.
     *
     * @var array<int, string>
     */
    private const MEMBER_ENTITY_QUESTION_CODES = [
        'MEMBER_001',
        'MEMBER_002',
        'MEMBER_003',
        'MEMBER_004',
        'MEMBER_005',
    ];

    public function handle(): int
    {
        $this->info('Diagnóstico Offline v1 (solo lectura)');
        $this->newLine();

        /** @var array<int, array{0: string, 1: string, 2: int, 3: string}> $results */
        $results = [];

        $checks = [
            [
                'Respuestas duplicadas (encuesta + pregunta + miembro)',
                'duplicateAnswers',
                'Varias filas para la misma respuesta. Revisar antes de sincronizar.',
            ],
            [
                'Respuestas de MEMBER_001 a MEMBER_005 en answers',
                'memberEntityAnswers',
                'Esos datos viven en household_members; estas filas son históricas.',
            ],
            [
                'Respuestas sin client_id',
                'answersWithoutClientId',
                'Debe ser 0 después de la migración del Bloque 1.',
            ],
            [
                'Archivos sin client_id',
                'filesWithoutClientId',
                'Debe ser 0 después de la migración del Bloque 1.',
            ],
            [
                'Archivos en el disco público',
                'publicFiles',
                'Se moverán al disco privado en el Bloque 3 (D-07).',
            ],
            [
                'Preguntas de prueba activas en versiones publicadas',
                'activeTestQuestions',
                'Desactivarlas antes de congelar la versión (C-09).',
            ],
            [
                'Comunidades de prueba activas',
                'activeTestCommunities',
                'Desactivarlas antes del bootstrap offline (C-09).',
            ],
        ];

        foreach ($checks as [$label, $method, $note]) {
            $rows = $this->{$method}();

            $results[] = [
                $label,
                $rows === null ? 'N/A' : (string) count($rows),
                $rows === null ? 'Columna client_id aún no existe (migración pendiente).' : $note,
            ];

            if ($this->option('detalle') && is_array($rows) && $rows !== []) {
                $this->line("<comment>{$label}</comment>");
                $this->table(
                    array_keys((array) $rows[0]),
                    array_map(fn (object $row): array => (array) $row, $rows)
                );
            }
        }

        $this->table(['Revisión', 'Encontrados', 'Nota'], $results);

        $warnings = collect($results)
            ->filter(fn (array $result): bool => $result[1] !== '0')
            ->count();

        $this->newLine();
        $this->line("Revisiones con hallazgos: {$warnings}");
        $this->line('No se modificó ningún dato.');

        return self::SUCCESS;
    }

    /**
     * @return array<int, object>
     */
    private function duplicateAnswers(): array
    {
        return DB::table('answers')
            ->selectRaw('survey_response_id, question_id, household_member_id, COUNT(*) as total')
            ->groupBy('survey_response_id', 'question_id', 'household_member_id')
            ->havingRaw('COUNT(*) > 1')
            ->orderBy('survey_response_id')
            ->get()
            ->all();
    }

    /**
     * @return array<int, object>
     */
    private function memberEntityAnswers(): array
    {
        return DB::table('answers')
            ->join('questions', 'questions.id', '=', 'answers.question_id')
            ->join('sections', 'sections.id', '=', 'questions.section_id')
            ->where('sections.code', 'MEMBER')
            ->whereIn('questions.code', self::MEMBER_ENTITY_QUESTION_CODES)
            ->select('answers.id', 'answers.survey_response_id', 'questions.code', 'answers.household_member_id')
            ->orderBy('answers.id')
            ->get()
            ->all();
    }

    /**
     * @return array<int, object>|null
     */
    private function answersWithoutClientId(): ?array
    {
        return $this->rowsWithoutClientId('answers');
    }

    /**
     * @return array<int, object>|null
     */
    private function filesWithoutClientId(): ?array
    {
        return $this->rowsWithoutClientId('survey_files');
    }

    /**
     * @return array<int, object>|null
     */
    private function rowsWithoutClientId(string $table): ?array
    {
        if (! Schema::hasColumn($table, 'client_id')) {
            return null;
        }

        return DB::table($table)
            ->whereNull('client_id')
            ->select('id')
            ->orderBy('id')
            ->get()
            ->all();
    }

    /**
     * @return array<int, object>
     */
    private function publicFiles(): array
    {
        return DB::table('survey_files')
            ->where('disk', 'public')
            ->select('id', 'survey_response_id', 'file_type')
            ->orderBy('id')
            ->get()
            ->all();
    }

    /**
     * @return array<int, object>
     */
    private function activeTestQuestions(): array
    {
        return DB::table('questions')
            ->join('sections', 'sections.id', '=', 'questions.section_id')
            ->join('survey_versions', 'survey_versions.id', '=', 'sections.survey_version_id')
            ->whereNotNull('survey_versions.published_at')
            ->where('questions.active', true)
            ->where('questions.code', 'like', '%TEST%')
            ->select('questions.id', 'questions.code', 'survey_versions.version')
            ->orderBy('questions.id')
            ->get()
            ->all();
    }

    /**
     * @return array<int, object>
     */
    private function activeTestCommunities(): array
    {
        return DB::table('communities')
            ->where('active', true)
            ->where('source_code', 'like', 'TEST%')
            ->select('id', 'source_code', 'name')
            ->orderBy('id')
            ->get()
            ->all();
    }
}
