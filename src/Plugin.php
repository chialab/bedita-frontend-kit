<?php

namespace Chialab\FrontendKit;

use Cake\Core\BasePlugin;
use Cake\Core\Configure;

/**
 * Plugin for Chialab\FrontendKit
 */
class Plugin extends BasePlugin
{
    /**
     * {@inheritDoc}
     */
    public function initialize()
    {
        parent::initialize();

        if (Configure::read('Status.level') === 'on') {
            // Ensure BEdita to load objects using `published` filter
            Configure::write('Publish.checkDate', true);
        }
    }
}
