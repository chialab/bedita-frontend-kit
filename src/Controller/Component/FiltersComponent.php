<?php
namespace Chialab\FrontendKit\Controller\Component;

use Cake\Controller\Component;

/**
 * Filters component.
 */
class FiltersComponent extends Component
{
    /**
     * Default configuration.
     *
     * @var array
     */
    protected $_defaultConfig = [
        'params' => [
            'query' => 'q',
        ],
    ];

    /**
     * Convert query params to BEdita filter array.
     *
     * @return array The filter array.
     */
    public function fromQuery(): array
    {
        $filter = [];
        $request = $this->getController()->getRequest();

        $params = $this->getConfig('params');
        if (isset($params['query']) && !empty($request->getQuery($params['query']))) {
            $filter['query'] = $request->getQuery($params['query']);
        }

        return $filter;
    }
}
