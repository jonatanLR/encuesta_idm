<?php

namespace App\Services;

use App\Models\SurveyVersion;

/**
 * Offline v1 — Bloque 2 (D-08).
 *
 * Una versión publicada (published_at no nulo) es la definición que usan
 * las capturas en curso, incluidas las descargadas a dispositivos offline.
 * En ella solo se permiten correcciones de texto y de orden; los cambios
 * estructurales (crear, activar o desactivar elementos, cambiar códigos,
 * tipos, obligatoriedad, valores de opciones o condiciones) requieren
 * crear una nueva versión.
 */
class PublishedVersionGuard
{
    public const STRUCTURE_LOCKED_MESSAGE = 'Esta versión está publicada: solo se pueden corregir textos y el orden. '
        .'Para crear, activar o desactivar elementos, o cambiar códigos, tipos, obligatoriedad, valores o '
        .'condiciones, cree una nueva versión.';

    public function isLocked(SurveyVersion $version): bool
    {
        return $version->published_at !== null;
    }

    /**
     * Devuelve los campos estructurales cuyo valor enviado difiere del actual.
     * Los valores deben llegar ya normalizados al mismo tipo.
     *
     * @param  array<string, mixed>  $current
     * @param  array<string, mixed>  $submitted
     * @return array<int, string>
     */
    public function changedFields(array $current, array $submitted): array
    {
        return array_values(array_filter(
            array_keys($current),
            fn (string $field): bool => array_key_exists($field, $submitted)
                && $current[$field] !== $submitted[$field]
        ));
    }
}
