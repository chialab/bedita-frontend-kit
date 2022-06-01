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
     * @inheritDoc
     */
    public function getFilters()
    {
        return [
            new TwigFilter('readable_size', [$this, 'readableSize']),
            new TwigFilter('mime_type', [$this, 'mimeType']),
        ];
    }

    /**
     * Format bytes size.
     *
     * @param int|string $size Byte size.
     * @return string
     */
    public function readableSize($size): string
    {
        return Number::toReadableSize($size);
    }

    /**
     * Get the generic type of the mime.
     *
     * @param string $mime The mime type string.
     * @return string
     */
    public function mimeType(string $mime): string
    {
        $mime = strtolower($mime);

        switch ($mime) {
            case 'application/x-abiword':
            case 'application/msword':
            case 'application/vnd.openxmlformats-officedocument.wordprocessingml.document':
            case 'application/vnd.oasis.opendocument.text':
                return 'word';
            case 'application/vnd.oasis.opendocument.presentation':
            case 'application/vnd.ms-powerpoint':
            case 'application/vnd.openxmlformats-officedocument.presentationml.presentation':
                return 'presentation';
            case 'application/vnd.oasis.opendocument.spreadsheet':
            case 'application/vnd.ms-excel':
            case 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet':
                return 'spreadsheet';
            case 'application/x-freearc':
            case 'application/x-bzip':
            case 'application/x-bzip2':
            case 'application/gzip':
            case 'application/vnd.rar':
            case 'application/x-tar':
            case 'application/zip':
            case 'application/x-7z-compressed':
                return 'archive';
            case 'application/vnd.amazon.ebook':
            case 'application/epub+zip':
                return 'ebook';
            case 'application/vnd.ms-fontobject':
                return 'font';
            case 'text/css':
                return 'css';
            case 'text/html':
                return 'html';
            case 'application/xhtml+xml':
            case 'application/xml':
            case 'text/xml':
            case 'application/vnd.mozilla.xul+xml':
                return 'xml';
            case 'text/javascript':
            case 'application/javascript':
                return 'javascript';
            case 'text/json':
            case 'application/json':
            case 'application/ld+json':
                return 'json';
            case 'text/calendar':
                return 'calendar';
            case 'application/pdf':
                return 'pdf';
            case 'application/rtf':
                return 'text';
            default:
                return explode('/', $mime)[0];
        }
    }
}
