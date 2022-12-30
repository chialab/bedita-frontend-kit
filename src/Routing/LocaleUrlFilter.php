<?php
declare(strict_types=1);

namespace Chialab\FrontendKit\Routing;

use Cake\Http\ServerRequest;

/**
 * Router url filter for locale param.
 */
class LocaleUrlFilter
{
    /**
     * The request param name.
     *
     * @var string
     */
    protected string $param;

    /**
     * The name prefix for named routes.
     *
     * @var string
     */
    protected string $namePrefix;

    /**
     * Instantiate the filter.
     *
     * @param string $param The request param name.
     * @param string $namePrefix The name prefix for named routes.
     */
    public function __construct(string $param = 'locale', string $namePrefix = 'lang:')
    {
        $this->param = $param;
        $this->namePrefix = $namePrefix;
    }

    /**
     * Exec url filter.
     *
     * @param array $params Request params.
     * @param \Cake\Http\ServerRequest|null $request The request.
     * @return array Updated params.
     */
    public function __invoke(array $params, ServerRequest|null $request = null): array
    {
        if (isset($params[$this->param])) {
            if ($params[$this->param] === false) {
                $params[$this->param] = null;
            }
        } elseif ($request !== null && $request->getParam($this->param)) {
            $params[$this->param] = $request->getParam($this->param);
        }

        if (isset($params[$this->param]) && !empty($params['_name']) && substr($params['_name'], 0, strlen($this->namePrefix)) !== $this->namePrefix) {
            $params['_name'] = $this->namePrefix . $params['_name'];
        }

        return $params;
    }
}
