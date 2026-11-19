<?php

namespace App\Support;

use App\Workflow\Documents\WorkflowDocument;

/**
 * Filling a transaction in on its printed page: the PDF templates mark each printed blank with
 * data-f="<field>" (resources/views/pdf/*). This reads which fields a document's page prints, so the
 * fill-in page knows from the start which editors open from the page and which stay in the list under it.
 * The browser checks again against the drawn page (resources/js/pdf-fill.js).
 */
final class PdfFill
{
    /** @return list<string> field names (and "__"-prefixed groups such as __schedule) printed as blanks */
    public static function printed(WorkflowDocument $document): array
    {
        static $cache = [];

        return $cache[$document->pdfView()] ??= array_values(array_unique(self::scan($document->pdfView(), 0)));
    }

    /**
     * Field => the field that must be answered first for it to show (its 'show_when'), so tapping a blank that
     * is not open yet (e.g. "please specify" before "others" is ticked) opens the tick box instead.
     *
     * @return array<string, string>
     */
    public static function deps(WorkflowDocument $document): array
    {
        $deps = [];
        foreach ($document->fields() as $name => $f) {
            if (! empty($f['show_when']) && is_array($f['show_when'])) {
                $deps[$name] = (string) array_key_first($f['show_when']);
            }
        }

        return $deps;
    }

    private static function scan(string $view, int $depth): array
    {
        if ($depth > 3) {
            return [];
        }
        try {
            $src = file_get_contents(view()->getFinder()->find($view));
        } catch (\Throwable) {
            return [];
        }

        $names = [];
        preg_match_all('/data-f="([^"]+)"/', $src, $m);
        foreach ($m[1] as $list) {
            array_push($names, ...preg_split('/\s+/', trim($list)));
        }
        // A template may be another template under a different title (@include('pdf.…')).
        preg_match_all("/@include\\(\\s*'(pdf\\.[a-z0-9._-]+)'/i", $src, $inc);
        foreach ($inc[1] as $child) {
            array_push($names, ...self::scan($child, $depth + 1));
        }

        return $names;
    }
}
