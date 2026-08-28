<?php

namespace App\Console\Commands;

use App\Models\Community;
use App\Models\Municipality;
use App\Support\CommunitySearch;
use Illuminate\Console\Command;
use RuntimeException;

class ImportCommunities extends Command
{
    protected $signature = 'communities:import';

    protected $description = 'Importa las comunidades del Distrito Central';

    public function handle(): int
    {
        $municipality = Municipality::where(
            'name',
            'Distrito Central'
        )->first();

        if (!$municipality) {
            $this->error(
                'No existe el municipio Distrito Central.'
            );

            return self::FAILURE;
        }

        $path = database_path(
            'data/distrito_central_communities.csv'
        );

        if (!file_exists($path)) {
            $this->error(
                "No se encontró el archivo: {$path}"
            );

            return self::FAILURE;
        }

        $handle = fopen($path, 'r');

        if ($handle === false) {
            $this->error(
                'No fue posible abrir el archivo CSV.'
            );

            return self::FAILURE;
        }

        $headers = fgetcsv($handle);

        if ($headers === false) {
            fclose($handle);

            $this->error('El archivo CSV está vacío.');

            return self::FAILURE;
        }

        $headers = array_map(
            fn ($header) => trim($header),
            $headers
        );

        $requiredHeaders = [
            'source_code',
            'name',
            'type',
            'area',
        ];

        foreach ($requiredHeaders as $header) {
            if (!in_array($header, $headers, true)) {
                fclose($handle);

                throw new RuntimeException(
                    "Falta la columna requerida: {$header}"
                );
            }
        }

        $imported = 0;
        $updated = 0;
        $skipped = 0;

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) !== count($headers)) {
                $skipped++;

                continue;
            }

            $data = array_combine($headers, $row);

            $name = trim($data['name'] ?? '');

            if ($name === '') {
                $skipped++;

                continue;
            }

            $community = Community::updateOrCreate(
                [
                    'municipality_id' => $municipality->id,
                    'name' => $name,
                ],
                [
                    'source_code' => $this->nullableValue(
                        $data['source_code'] ?? null
                    ),
                    'search_name' => CommunitySearch::normalize(
                        $name
                    ),
                    'type' => $this->normalizeType(
                        $data['type'] ?? null
                    ),
                    'area' => $this->nullableValue(
                        $data['area'] ?? null
                    ),
                    'active' => true,
                ]
            );

            if ($community->wasRecentlyCreated) {
                $imported++;
            } else {
                $updated++;
            }
        }

        fclose($handle);

        $this->info(
            "Comunidades nuevas: {$imported}"
        );

        $this->info(
            "Comunidades actualizadas: {$updated}"
        );

        $this->info(
            "Registros omitidos: {$skipped}"
        );

        return self::SUCCESS;
    }

    private function nullableValue(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function normalizeType(?string $type): string
    {
        $allowed = [
            'colony',
            'neighborhood',
            'village',
            'hamlet',
            'residential',
            'other',
        ];

        return in_array($type, $allowed, true)
            ? $type
            : 'other';
    }
}