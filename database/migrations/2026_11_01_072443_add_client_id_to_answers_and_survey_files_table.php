<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Offline v1 — Bloque 1 (D-03, D-34, D-36).
 *
 * Agrega un identificador estable generado por el cliente (ULID de 26
 * caracteres, igual formato que los public_id existentes) a respuestas y
 * archivos. Las filas existentes reciben un ULID propio antes de imponer
 * la unicidad. No se modifican claves primarias ni foráneas.
 */
return new class extends Migration
{
    /**
     * @var array<int, string>
     */
    private array $tables = [
        'answers',
        'survey_files',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $blueprint): void {
                $blueprint->char('client_id', 26)
                    ->nullable()
                    ->after('id');
            });

            $this->backfill($table);

            Schema::table($table, function (Blueprint $blueprint): void {
                $blueprint->unique('client_id');
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $blueprint): void {
                $blueprint->dropUnique(['client_id']);
                $blueprint->dropColumn('client_id');
            });
        }
    }

    /**
     * Asigna un ULID a cada fila sin client_id, por lotes y sin alterar
     * ninguna otra columna (tampoco updated_at).
     */
    private function backfill(string $table): void
    {
        DB::table($table)
            ->whereNull('client_id')
            ->select('id')
            ->chunkById(500, function ($rows) use ($table): void {
                foreach ($rows as $row) {
                    DB::table($table)
                        ->where('id', $row->id)
                        ->update(['client_id' => (string) Str::ulid()]);
                }
            });
    }
};
