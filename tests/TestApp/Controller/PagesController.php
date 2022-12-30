<?php
declare(strict_types=1);

namespace Chialab\FrontendKit\Test\TestApp\Controller;

use Cake\Controller\Controller;
use Chialab\FrontendKit\Traits\GenericActionsTrait;
use Chialab\FrontendKit\Traits\RenderTrait;

/**
 * Pages controller, for testing.
 */
class PagesController extends Controller
{
    use GenericActionsTrait;
    use RenderTrait;

    public function initialize(): void
    {
        parent::initialize();

        $this->loadComponent('Chialab/FrontendKit.Objects');
        $this->loadComponent('Chialab/FrontendKit.Filters');
        $this->loadComponent('Chialab/FrontendKit.Publication', [
            'publication' => 'root-1',
        ]);
    }
}
