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
use Chialab\FrontendKit\Twig\I18nExtension;
use Chialab\FrontendKit\Twig\SortByExtension;
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

        $this->getTwig()->addExtension(new SortByExtension());
        $this->getTwig()->addExtension(new I18nExtension());

        $this->loadHelper('Flash');
        $this->loadHelper('Form');
        $this->loadHelper('Html');
        $this->loadHelper('Paginator');
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
        $this->loadHelper('Chialab/FrontendKit.Manager', [
            'enabled' => $isStaging,
            'managerUrl' => Configure::read('Manage.manager.url'),
        ]);
        if ($isStaging) {
            $this->loadHelper('Authentication.Identity');
        }
    }
}
