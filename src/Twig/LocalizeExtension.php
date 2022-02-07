<?php
declare(strict_types=1);

namespace Chialab\FrontendKit\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Localize extension for Twig.
 */
class LocalizeExtension extends AbstractExtension
{
    /**
     * @inheritDoc
     */
    public function getFilters()
    {
        return [
            new TwigFilter('localize', [$this, 'localize']),
        ];
    }

    /**
     * Localize a string.
     *
     * @param string $str String to localize.
     * @return string
     */
    public function localize(string $str): string
    {
        return __($str);
    }
}
