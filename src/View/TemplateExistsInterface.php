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

/**
 * Template exists interface.
 */
interface TemplateExistsInterface
{
    /**
     * Returns true if the template exists.
     *
     * @param string $name Name of the view to check.
     * @return bool Template exists.
     */
    public function templateExists($name): bool;
}
