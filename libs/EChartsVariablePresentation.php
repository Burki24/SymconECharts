<?php

declare(strict_types=1);

namespace SymconECharts;

use RuntimeException;
use Throwable;

/**
 * Resolves the numeric display metadata shared by ECharts chart families.
 */
final class EChartsVariablePresentation
{
    /**
     * Strict access rejects unreadable or malformed unit metadata instead of
     * silently falling back. Existing callers retain lenient resolution.
     *
     * @return array{minimum: float, maximum: float, unit: string, decimals: int}
     */
    public static function Resolve(
        int $variableID,
        float $minimum,
        float $maximum,
        string $unit,
        int $decimals,
        bool $useVariablePresentation,
        bool $strict = false
    ): array {
        $configuration = [
            'minimum'  => $minimum,
            'maximum'  => $maximum,
            'unit'     => trim($unit),
            'decimals' => $decimals
        ];
        if (!$useVariablePresentation || $variableID <= 0 || !IPS_VariableExists($variableID)) {
            if ($strict && $useVariablePresentation) {
                throw new RuntimeException('Variable presentation is unavailable.');
            }
            return $configuration;
        }

        try {
            $presentation = IPS_GetVariablePresentation($variableID);
            if (!is_array($presentation)) {
                if ($strict) {
                    throw new RuntimeException('Variable presentation is invalid.');
                }
                return $configuration;
            }

            $profileName = $presentation['PROFILE'] ?? null;
            if ($strict && (($profileName !== null && !is_string($profileName))
                || (array_key_exists('SUFFIX', $presentation) && !is_string($presentation['SUFFIX'])))) {
                throw new RuntimeException('Variable unit metadata is invalid.');
            }
            if (is_string($profileName) && $profileName !== '') {
                $profile = IPS_GetVariableProfile($profileName);
                if ($strict && (!is_array($profile) || (array_key_exists('Suffix', $profile) && !is_string($profile['Suffix'])))) {
                    throw new RuntimeException('Variable profile unit metadata is invalid.');
                }
                if (is_array($profile)) {
                    $configuration = self::ApplyValues($configuration, [
                        'MIN'    => $profile['MinValue'] ?? null,
                        'MAX'    => $profile['MaxValue'] ?? null,
                        'SUFFIX' => $profile['Suffix'] ?? null,
                        'DIGITS' => $profile['Digits'] ?? null
                    ]);
                }
            }

            return self::ApplyValues($configuration, $presentation);
        } catch (Throwable $exception) {
            if ($strict) {
                throw new RuntimeException('Variable presentation could not be resolved.', 0, $exception);
            }
            return $configuration;
        }
    }

    /**
     * @param array{minimum: float, maximum: float, unit: string, decimals: int} $configuration
     * @param array<string, mixed> $presentation
     * @return array{minimum: float, maximum: float, unit: string, decimals: int}
     */
    private static function ApplyValues(array $configuration, array $presentation): array
    {
        $presentationMinimum = $presentation['MIN'] ?? null;
        $presentationMaximum = $presentation['MAX'] ?? null;
        if ((is_int($presentationMinimum) || is_float($presentationMinimum))
            && (is_int($presentationMaximum) || is_float($presentationMaximum))
            && is_finite((float) $presentationMinimum)
            && is_finite((float) $presentationMaximum)
            && (float) $presentationMinimum < (float) $presentationMaximum
        ) {
            $configuration['minimum'] = (float) $presentationMinimum;
            $configuration['maximum'] = (float) $presentationMaximum;
        }
        if (array_key_exists('SUFFIX', $presentation) && is_string($presentation['SUFFIX'])) {
            $configuration['unit'] = trim($presentation['SUFFIX']);
        }
        $presentationDigits = $presentation['DIGITS'] ?? null;
        if (is_int($presentationDigits) && $presentationDigits >= 0 && $presentationDigits <= 6) {
            $configuration['decimals'] = $presentationDigits;
        }

        return $configuration;
    }
}
