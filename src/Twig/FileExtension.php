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
        ];
    }

    /**
     * Format bytes size.
     *
     * @param int|string $size Byte size.
     * @return string
     */
    public function readableSize(int $size): string
    {
        return Number::toReadableSize($size);
    }
}
