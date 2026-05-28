<?php

namespace App\Support;

use Throwable;
use Illuminate\Support\Facades\Cache;
use App\Services\Translation\TranslationService;

class ApiTranslationCatalog
{
    public function __construct(
        private readonly TranslationService $translationService
    ) {
    }

    public function get(string $locale): array
    {
        $locale = trim(strtolower($locale));

        $englishCatalog = $this->loadCatalog('en');

        if (empty($englishCatalog)) {
            return [];
        }

        if ($locale === '' || $locale === 'en') {
            return $englishCatalog;
        }

        $catalog = $this->loadCatalog($locale);

        if (empty($catalog)) {
            return $englishCatalog;
        }

        if (! $this->isSameCatalog($catalog, $englishCatalog)) {
            return $catalog;
        }

        $cacheKey = "api_translation_catalog_runtime_v3_{$locale}";

        return Cache::rememberForever($cacheKey, function () use ($englishCatalog, $locale) {
            return $this->translateCatalog($englishCatalog, $locale);
        });
    }

    private function loadCatalog(string $locale): array
    {
        $path = base_path("lang/{$locale}/api/index.php");

        if (! file_exists($path)) {
            return [];
        }

        $catalog = require $path;

        return is_array($catalog) ? $catalog : [];
    }

    private function isSameCatalog(array $left, array $right): bool
    {
        return md5(json_encode($left)) === md5(json_encode($right));
    }

    private function translateCatalog(array $catalog, string $targetLocale): array
    {
        $flat = [];
        $this->flatten($catalog, '', $flat);

        if (empty($flat)) {
            return $catalog;
        }

        $keys = array_keys($flat);
        $values = array_values($flat);
        $translatedValues = $this->translateBatchValues($values, $targetLocale);

        $rebuiltFlat = [];
        foreach ($keys as $index => $key) {
            $rebuiltFlat[$key] = $translatedValues[$index] ?? $flat[$key];
        }

        return $this->unflatten($rebuiltFlat);
    }

    private function translateBatchValues(array $values, string $targetLocale): array
    {
        $translatedValues = [];
        $chunks = array_chunk($values, 25);

        foreach ($chunks as $chunk) {
            $preparedChunk = [];
            $placeholderMaps = [];

            foreach ($chunk as $item) {
                [$preparedText, $placeholderMap] = $this->protectPlaceholders((string) $item);
                $preparedChunk[] = $preparedText;
                $placeholderMaps[] = $placeholderMap;
            }

            $delimiter = "\n[[[__SEG__]]]\n";
            $joined = implode($delimiter, $preparedChunk);

            try {
                $translatedJoined = $this->translationService
                    ->from('en')
                    ->to($targetLocale)
                    ->translate($joined);

                $translatedChunk = explode($delimiter, (string) $translatedJoined);
            }
            catch (Throwable) {
                $translatedChunk = [];
            }

            if (count($translatedChunk) !== count($preparedChunk)) {
                foreach ($preparedChunk as $singleText) {
                    try {
                        $translatedChunk[] = $this->translationService
                            ->from('en')
                            ->to($targetLocale)
                            ->translate($singleText);
                    }
                    catch (Throwable) {
                        $translatedChunk[] = $singleText;
                    }
                }
            }

            foreach ($translatedChunk as $index => $translatedText) {
                $translatedValues[] = $this->restorePlaceholders(
                    (string) $translatedText,
                    $placeholderMaps[$index] ?? []
                );
            }
        }

        return $translatedValues;
    }

    private function protectPlaceholders(string $text): array
    {
        $map = [];
        $counter = 0;

        $protected = preg_replace_callback('/\{[^}]+\}/', function ($matches) use (&$map, &$counter) {
            $token = "__PH_{$counter}__";
            $map[$token] = $matches[0];
            $counter++;

            return $token;
        }, $text);

        return [$protected, $map];
    }

    private function restorePlaceholders(string $text, array $map): string
    {
        foreach ($map as $token => $placeholder) {
            $text = str_replace($token, $placeholder, $text);
        }

        return $text;
    }

    private function flatten(array $array, string $prefix, array &$flat): void
    {
        foreach ($array as $key => $value) {
            $path = $prefix === '' ? (string) $key : "{$prefix}.{$key}";

            if (is_array($value)) {
                $this->flatten($value, $path, $flat);
                continue;
            }

            $flat[$path] = (string) $value;
        }
    }

    private function unflatten(array $flat): array
    {
        $result = [];

        foreach ($flat as $path => $value) {
            $segments = explode('.', $path);
            $ref =& $result;

            foreach ($segments as $segment) {
                if (! isset($ref[$segment]) || ! is_array($ref[$segment])) {
                    $ref[$segment] = [];
                }

                $ref =& $ref[$segment];
            }

            $ref = $value;
        }

        return $result;
    }
}
