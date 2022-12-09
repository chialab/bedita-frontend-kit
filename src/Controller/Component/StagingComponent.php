<?php
declare(strict_types=1);

namespace Chialab\FrontendKit\Controller\Component;

use Cake\Controller\Component;
use Cake\Core\Configure;

/**
 * Staging component
 */
class StagingComponent extends Component
{
    /**
     * @inheritDoc
     */
    protected $_defaultConfig = [
        'requireAuth' => null,
        'authConfig' => [],
    ];

    /**
     * Check if staging auth is required.
     *
     * @return bool
     */
    protected function isAuthRequired(): bool
    {
        return $this->getConfig('requireAuth', Configure::read('StagingSite', false));
    }

    /**
     * @inheritDoc
     */
    public function initialize(array $config): void
    {
        if ($this->isAuthRequired()) {
            $this->getController()->loadComponent('Authentication.Authentication', $this->getConfig('authConfig'));
        }
    }
}
