<?php
declare(strict_types=1);

namespace Chialab\FrontendKit\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;
use function Cake\I18n\__;
use function Cake\I18n\__d;
use function Cake\I18n\__dn;
use function Cake\I18n\__dx;
use function Cake\I18n\__dxn;
use function Cake\I18n\__n;
use function Cake\I18n\__x;
use function Cake\I18n\__xn;

/**
 * I18n extension for Twig.
 */
class I18nExtension extends AbstractExtension
{
    /**
     * Get declared functions.
     *
     * @return array<\Twig\TwigFunction>
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('__', __(...)),
            new TwigFunction('__n', __n(...)),
            new TwigFunction('__d', __d(...)),
            new TwigFunction('__dn', __dn(...)),
            new TwigFunction('__x', __x(...)),
            new TwigFunction('__xn', __xn(...)),
            new TwigFunction('__dx', __dx(...)),
            new TwigFunction('__dxn', __dxn(...)),
        ];
    }

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
