<?php
declare(strict_types=1);

namespace Chialab\FrontendKit\Controller\Component;

use Cake\Controller\Component;
use Cake\Event\EventInterface;

/**
 * Staging component
 */
class StagingComponent extends Component
{
    /**
     * @inheritDoc
     */
    protected $_defaultConfig = [
        'requireAuth' => true,
        'authConfig' => [],
    ];

    /**
     * Check if authentication is required.
     *
     * @return bool
     */
    public function isAuthRequired(): bool
    {
        return $this->getConfig('requireAuth');
    }

    /**
     * @inheritDoc
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        if ($this->isAuthRequired()) {
            $this->getController()->loadComponent('Authentication.Authentication', $this->getConfig('authConfig'));
        }
    }

    /**
     * @inheritDoc
     */
    public function beforeRender(EventInterface $event): void
    {
        $this->getController()->set('_staging', true);
    }
}
