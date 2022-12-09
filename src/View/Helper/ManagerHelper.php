<?php
declare(strict_types=1);

namespace Chialab\FrontendKit\View\Helper;

use ArrayAccess;
use Cake\View\Helper;

/**
 * Helper to set BEdita Manager refs
 */
class ManagerHelper extends Helper
{
    protected $_defaultConfig = [
        'enabled' => true,
        'managerUrl' => null,
    ];

    /**
     * Return current object var from view vars
     *
     * @return \ArrayAccess|array
     */
    protected function getObject(): array|ArrayAccess
    {
        $candidates = array_filter([
            $this->getView()->get('_main'),
            'object',
            'folder',
        ]);
        foreach ($candidates as $varName) {
            $var = $this->getView()->get($varName);
            if (!empty($var) && (is_array($var) || $var instanceof ArrayAccess)) {
                return $var;
            }
        }

        return null;
    }

    /**
     * Get Manager view object/folder URL
     *
     * @return string|null
     */
    public function getViewUrl(): ?string
    {
        if (!$this->isEnabled()) {
            return null;
        }

        $object = $this->getObject();
        if ($object === null) {
            return null;
        }

        $managerUrl = $this->getConfig('managerUrl');

        if (!$managerUrl) {
            return null;
        }

        $managerEditUrl = $managerUrl . '/view/' . $object['id'];

        return $managerEditUrl;
    }

    /**
     * Return true if "staging" env
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->getConfig('enabled') === true;
    }
}
