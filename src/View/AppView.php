<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link      https://cakephp.org CakePHP(tm) Project
 * @since     3.0.0
 * @license   https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Chialab\FrontendKit\View;

use Cake\Core\Configure;
use Cake\Utility\Hash;
use Cake\Utility\Inflector;
use Twig\TwigFilter;
use WyriHaximus\TwigView\View\TwigView;

/**
 * Application View
 *
 * Your application's default view class
 *
 * @link https://book.cakephp.org/3/en/views.html#the-app-view
 */
class AppView extends TwigView
{
    /**
     * Initialization hook method.
     *
     * Use this method to add common initialization code like loading helpers.
     *
     * e.g. `$this->loadHelper('Html');`
     *
     * @return void
     */
    public function initialize()
    {
        parent::initialize();

        $this->getTwig()
            ->addFilter(new TwigFilter(
                'sort_by_multi',
                function (iterable $it, array $attributes, bool $desc = false): iterable {
                    if (!is_array($it)) {
                        $it = iterator_to_array($it);
                    }

                    usort($it, function ($a, $b) use ($attributes) {
                        $aVals = array_filter(array_map(fn($attr) => Hash::get($a, $attr), $attributes));
                        $bVals = array_filter(array_map(fn($attr) => Hash::get($b, $attr), $attributes));

                        while (true) {
                            $aVal = array_shift($aVals);
                            $bVal = array_shift($bVals);

                            if ($aVal === null && $bVal === null) {
                                return 0;
                            }

                            if ($aVal === null) {
                                return -1;
                            }

                            if ($bVal === null) {
                                return 1;
                            }

                            $comparison = $aVal <=> $bVal;
                            if (is_string($aVal) && is_string($bVal)) {
                                $comparison = strcasecmp($aVal, $bVal);
                            }

                            if ($comparison !== 0) {
                                return $comparison;
                            }
                        }
                    });

                    if ($desc) {
                        $it = array_reverse($it);
                    }

                    return collection($it);
                }
            ));

        $this->getTwig()
            ->addFilter(new TwigFilter(
                'sort_by',
                fn(iterable $it, string $attribute, bool $desc = false, bool $numeric = false): iterable => collection($it)
                    ->sortBy($attribute, $desc ? SORT_DESC : SORT_ASC, $numeric ? SORT_NATURAL : SORT_LOCALE_STRING)
            ));

        $this->getTwig()
            ->addFilter(new TwigFilter(
                'asset',
                fn(string $path, $plugin): string => Inflector::dasherize($plugin) . '/' . $path
            ));

        $this->loadHelper('Flash');
        $this->loadHelper('Form');
        $this->loadHelper('Html');
        $this->loadHelper('Paginator', ['templates' => 'paginator-templates']);
        $this->loadHelper('Time');
        $this->loadHelper('Url');
        $this->loadHelper('BEdita/I18n.I18n');
        $this->loadHelper('Chialab/Rna.Rna');
        $this->loadHelper('Chialab/FrontendKit.DateRanges');

        $fallbackImage = Configure::read('FallbackImage');
        $fallback = $fallbackImage ? $this->Url->image($fallbackImage) : null;
        $this->loadHelper('Chialab/FrontendKit.Thumb', [
            'fallbackImage' => $fallback,
        ]);
        $this->loadHelper('Chialab/FrontendKit.Poster', [
            'fallbackImage' => $fallback,
        ]);

        $isStaging = Configure::read('StagingSite', false);
        if ($isStaging) {
            $this->loadHelper('Authentication.Identity');
            $this->loadHelper('Chialab/FrontendKit.Manager', [
                'enabled' => true,
                'managerUrl' => Configure::read('Manage.manager.url'),
            ]);
        }
    }
}
