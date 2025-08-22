<?php

declare(strict_types=1);

/**
 * HTML Helper
 * Provides utility methods for HTML generation and manipulation
 */

class Html
{
    /**
     * Escape HTML special characters
     */
    public static function escape(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Generate HTML tag attributes from array
     */
    public static function attributes(array $attributes): string
    {
        $html = '';
        foreach ($attributes as $key => $value) {
            if (is_bool($value)) {
                if ($value) {
                    $html .= " $key";
                }
            } else {
                $html .= " $key=\"" . self::escape($value) . "\"";
            }
        }
        return $html;
    }

    /**
     * Generate a link tag
     */
    public static function link(string $url, string $text, array $attributes = []): string
    {
        $attrs = self::attributes($attributes);
        return "<a href=\"" . self::escape($url) . "\"$attrs>" . self::escape($text) . "</a>";
    }

    /**
     * Generate a form input
     */
    public static function input(string $type, string $name, string $value = '', array $attributes = []): string
    {
        $attributes['type'] = $type;
        $attributes['name'] = $name;
        $attributes['value'] = $value;

        $attrs = self::attributes($attributes);
        return "<input$attrs>";
    }
}
