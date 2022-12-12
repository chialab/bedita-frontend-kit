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
use Cake\Http\ServerRequest;
use Cake\View\Exception\MissingTemplateException;
use Cake\View\View;
use Chialab\FrontendKit\View\TemplateExistsInterface;
use Generator;

/**
 * Render for BEdita frontends.
 */
trait RenderTrait
{
    /**
     * Gets the request instance.
     *
     * @return \Cake\Http\ServerRequest
     */
    abstract public function getRequest(): ServerRequest;

    /**
     * Constructs the view class instance based on the current configuration.
     *
     * @param string|null $viewClass Optional namespaced class name of the View class to instantiate.
     * @return \Cake\View\View
     */
    abstract public function createView(string|null $viewClass = null): View;

    /**
     * The render method of the controller.
     *
     * @param string|null $view View to use for rendering
     * @param string|null $layout Layout to use
     * @return \Cake\Http\Response A response object containing the rendered view.
     */
    abstract public function render(string|null $view = null, string|null $layout = null): Response;

    /**
     * Get the templatePath based on controller name and request prefix.
     *
     * @return string
     */
    abstract protected function _templatePath(): string;

    /**
     * Generate a list of templates to try to use for the given object.
     *
     * @param \BEdita\Core\Model\Entity\ObjectEntity $object The main object.
     * @param \BEdita\Core\Model\Entity\Folder $ancestors A list of ancestors.
     * @return \Generator A generator function.
     */
    public function getTemplatesToIterate(ObjectEntity $object, Folder ...$ancestors): Generator
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
     * @param string $templates Templates to search.
     * @return \Cake\Http\Response
     */
    public function renderFirstTemplate(string ...$templates): Response
    {
        /**
         * Create the view instance.
         */
        $view = $this->createView();

        /**
         * Template paths are set by the controller in the {@see \Cake\Controller\Controller::render()} method.
         * We are using the same logic here to check the very same template file.
         */
        if (!$view->getTemplatePath()) {
            $view->setTemplatePath($this->_templatePath());
        }
        if (!$view->getTemplate()) {
            $action = $this->getRequest()->getParam('action');
            if ($action !== null) {
                $view->setTemplate($this->getRequest()->getParam('action'));
            }
        }

        foreach ($templates as $template) {
            try {
                if ($view instanceof TemplateExistsInterface && !$view->templateExists($template)) {
                    continue;
                }

                return $this->render($template);
            } catch (MissingTemplateException) {
                continue;
            }
        }

        throw new MissingTemplateException(array_pop($template));
    }
}
