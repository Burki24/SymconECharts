<?php

declare(strict_types=1);

namespace SymconECharts;

final class EChartsJSLiveLocale
{
    public static function Dictionary(string $language): array
    {
        static $catalog = null;
        $catalog ??= json_decode(file_get_contents(__DIR__ . '/jslive/locale.json'), true, 32, JSON_THROW_ON_ERROR);
        return $catalog['translations'][strtolower(substr($language, 0, 2))] ?? [];
    }
}

/** Shared imported-chart strings; helper-owned IPSView wording still comes from its own catalog. */
trait EChartsJSLiveTranslations
{
    protected function JSLiveText(string $text): string
    {
        $translated = $this->Translate($text);
        if ($translated !== $text) {
            return $translated;
        }
        $language = method_exists($this, 'ResolveHelperTranslationLanguage') ? $this->ResolveHelperTranslationLanguage() : 'en';
        return EChartsJSLiveLocale::Dictionary($language)[$text] ?? $text;
    }
}
