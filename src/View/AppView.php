<?php
declare(strict_types=1);

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
use Cake\TwigView\View\TwigView;
use Cake\View\Exception\MissingHelperException;
use Cake\View\Exception\MissingTemplateException;
use Chialab\FrontendKit\Twig\FileExtension;
use Chialab\FrontendKit\Twig\I18nExtension;
use Chialab\FrontendKit\Twig\SortByExtension;

/**
 * Application View
 *
 * Your application's default view class
 *
 * @link https://book.cakephp.org/4/en/views.html#the-app-view
 */
class AppView extends TwigView implements TemplateExistsInterface
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
    public function initialize(): void
    {
        parent::initialize();

        $this->loadHelper('Flash');
        $this->loadHelper('Form');
        $this->loadHelper('Html');
        $this->loadHelper('Paginator');
        $this->loadHelper('Time');
        $this->loadHelper('Url');
        $this->loadHelper('BEdita/I18n.I18n');
        $this->loadHelper('Chialab/FrontendKit.Metadata');
        $this->loadHelper('Chialab/FrontendKit.DateRanges');
        $this->loadHelper('Chialab/FrontendKit.Download');
        $this->loadHelper('Chialab/FrontendKit.Placeholders');
        $this->loadHelper('Chialab/FrontendKit.Placeholders');

        try {
            $this->loadHelper('Chialab/Rna.Rna');
        } catch (MissingHelperException) {
            // RNA plugin not found
        }

        $fallbackImage = Configure::read('FallbackImage');
        $fallback = $fallbackImage ? $this->Url->image($fallbackImage) : null;
        $this->loadHelper('Chialab/FrontendKit.Media');
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

    /**
     * @inheritDoc
     */
    protected function initializeExtensions(): void
    {
        parent::initializeExtensions();

        $twig = $this->getTwig();
        $twig->addExtension(new FileExtension());
        $twig->addExtension(new I18nExtension());
        $twig->addExtension(new SortByExtension());
    }

    /**
     * @inheritDoc
     */
    public function templateExists($name): bool
    {
        try {
            return is_string($this->_getTemplateFileName($name));
        } catch (MissingTemplateException $err) {
            return false;
        }
    }
}
