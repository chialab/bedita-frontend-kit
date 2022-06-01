<?php
declare(strict_types=1);

namespace Chialab\FrontendKit\Twig;

use Cake\I18n\Number;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * File extension for Twig.
 */
class FileExtension extends AbstractExtension
{
    /**
     * Media type category map.
     *
     * @var array
     */
    protected const MEDIA_TYPE_CATEGORIES = [
        'application/x-abiword' => 'word',
        'application/msword' => 'word',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'word',
        'application/vnd.oasis.opendocument.text' => 'word',
        'application/vnd.oasis.opendocument.presentation' => 'presentation',
        'application/vnd.ms-powerpoint' => 'presentation',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'presentation',
        'application/vnd.oasis.opendocument.spreadsheet' => 'spreadsheet',
        'application/vnd.ms-excel' => 'spreadsheet',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'spreadsheet',
        'application/x-freearc' => 'archive',
        'application/x-bzip' => 'archive',
        'application/x-bzip2' => 'archive',
        'application/gzip' => 'archive',
        'application/vnd.rar' => 'archive',
        'application/x-tar' => 'archive',
        'application/zip' => 'archive',
        'application/x-7z-compressed' => 'archive',
        'application/vnd.amazon.ebook' => 'ebook',
        'application/epub+zip' => 'ebook',
        'application/vnd.ms-fontobject' => 'font',
        'text/css' => 'css',
        'text/html' => 'html',
        'application/xhtml+xml' => 'xml',
        'application/xml' => 'xml',
        'text/xml' => 'xml',
        'application/vnd.mozilla.xul+xml' => 'xml',
        'text/javascript' => 'javascript',
        'application/javascript' => 'javascript',
        'text/json' => 'json',
        'application/json' => 'json',
        'application/ld+json' => 'json',
        'text/calendar' => 'calendar',
        'application/pdf' => 'pdf',
        'application/rtf' => 'text',
    ];

    /**
     * @inheritDoc
     */
    public function getFilters()
    {
        return [
            new TwigFilter('format_file_size', [$this, 'formatFileSize']),
            new TwigFilter('media_type_category', [$this, 'mediaTypeCategory']),
        ];
    }

    /**
     * Format bytes size.
     *
     * @param int|string $size Byte size.
     * @return string
     */
    public function formatFileSize($size): string
    {
        return Number::toReadableSize($size);
    }

    /**
     * Get the category type of the media.
     *
     * @param string $mediaType The media type string.
     * @return string
     */
    public function mediaTypeCategory(string $mediaType): string
    {
        $mediaType = strtolower($mediaType);

        return static::MEDIA_TYPE_CATEGORIES[$mediaType] ?? explode('/', $mediaType, 2)[0];
    }
}
