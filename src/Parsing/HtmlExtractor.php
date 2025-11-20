<?php

namespace Balintpethe\LaravelUniversalScraper\Parsing;

use Symfony\Component\DomCrawler\Crawler;
use Illuminate\Support\Collection;

class HtmlExtractor
{
    /**
     * Konfiguráció alapján listát nyer ki a HTML-ből.
     *
     * @param  string  $html
     * @param  string  $baseUrl
     * @param  array   $definition  ['item' => '...', 'fields' => [...]]
     */
    public function extractList(string $html, string $baseUrl, array $definition): Collection
    {
        $crawler = new Crawler($html, $baseUrl);

        $itemSelector = $definition['item'] ?? null;

        if (!$itemSelector) {
            throw new \InvalidArgumentException('List definition must have an [item] selector.');
        }

        $fields = $definition['fields'] ?? [];

        $items = $crawler->filter($itemSelector)->each(function (Crawler $node) use ($fields, $baseUrl) {
            $data = [];

            foreach ($fields as $name => $field) {
                $selector = $field['selector'] ?? null;
                $attr     = $field['attr'] ?? 'text';

                if (!$selector) {
                    continue;
                }

                $fieldNode = $node->filter($selector);

                if (!$fieldNode->count()) {
                    $data[$name] = null;
                    continue;
                }

                if ($attr === 'text') {
                    $value = trim($fieldNode->text());
                } else {
                    $value = $fieldNode->attr($attr);
                }

                if (($field['absolute_url'] ?? false) && is_string($value)) {
                    if (!str_starts_with($value, 'http')) {
                        $value = rtrim($baseUrl, '/') . '/' . ltrim($value, '/');
                    }
                }

                $cast = $field['cast'] ?? null;

                if ($cast && is_string($value)) {
                    $value = $this->cast($value, $cast);
                }

                $data[$name] = $value;
            }

            return $data;
        });

        return collect($items);
    }

    /**
     * Alap típuskonverzió (árakhoz stb.).
     */
    protected function cast(string $value, string $cast): mixed
    {
        $normalized = preg_replace('/[^\d\.,-]/', '', $value) ?? '';

        switch ($cast) {
            case 'int':
                return (int) filter_var($normalized, FILTER_SANITIZE_NUMBER_INT);

            case 'float':
                $normalized = str_replace([' ', ','], ['', '.'], $normalized);

                return (float) $normalized;

            case 'string':
            default:
                return $value;
        }
    }
}

