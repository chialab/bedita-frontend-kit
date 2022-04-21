<?php
namespace Chialab\FrontendKit\Controller\Component;

use Cake\Controller\Component;
use Cake\Utility\Hash;

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
        foreach ($params as $dest => $source) {
            $value = $request->getQuery($source);
            if ($value !== null) {
                $filter = Hash::insert($filter, $dest, $value);
            }
        }

        return $filter;
    }
}
