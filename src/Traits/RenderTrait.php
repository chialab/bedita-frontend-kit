<?php
declare(strict_types=1);

/**
 * BEdita, API-first content management framework
 * Copyright 2020 ChannelWeb Srl, Chialab Srl
 *
 * This file is part of BEdita: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * See LICENSE.LGPL or <http://gnu.org/licenses/lgpl-3.0.html> for more details.
 */
namespace Chialab\FrontendKit\Traits;

use BEdita\Core\Model\Entity\Folder;
use BEdita\Core\Model\Entity\ObjectEntity;
use Cake\Http\Response;
use Cake\View\Exception\MissingTemplateException;

/**
 * Render for BEdita frontends.
 */
trait RenderTrait
{
    /**
     * The render method of the controller.
     *
     * @param string|null $view View to use for rendering
     * @param string|null $layout Layout to use
     * @return \Cake\Http\Response A response object containing the rendered view.
     */
    abstract public function render($view = null, $layout = null);

    /**
     * Generate a list of templates to try to use for the given object.
     *
     * @param \BEdita\Core\Model\Entity\ObjectEntity $object The main object.
     * @param \BEdita\Core\Model\Entity\Folder $ancestors A list of ancestors.
     * @return \Generator A generator function.
     */
    public function getTemplatesToIterate(ObjectEntity $object, Folder ...$ancestors): \Generator
    {
        yield $object->uname;

        $chain = iterator_to_array($object->object_type->getFullInheritanceChain());
        foreach ($ancestors as $ancestor) {
            foreach ($chain as $type) {
                yield sprintf('%s.%s', $ancestor->uname, $type->name);
            }
        }

        $type = array_shift($chain);
        yield $type->name;

        foreach ($chain as $type) {
            yield $type->name;
        }
    }

    /**
     * Render first found template.
     *
     * @param string ...$templates Templates to search.
     * @return \Cake\Http\Response
     */
    public function renderFirstTemplate(string ...$templates): Response
    {
        foreach ($templates as $template) {
            try {
                return $this->render($template);
            } catch (MissingTemplateException $e) {
                continue;
            }
        }

        throw new MissingTemplateException(__('None of the searched templates was found'));
    }
}
