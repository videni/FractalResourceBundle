<?php 

namespace Videni\Bundle\FractalResourceBundle\View;

use Sylius\Bundle\ResourceBundle\Controller\ViewHandlerInterface;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\Response;
use Sylius\Bundle\ResourceBundle\Controller\RequestConfiguration;
use Pagerfanta\Pagerfanta;
use League\Fractal\Pagination\PagerfantaPaginatorAdapter;
use Symfony\Component\Routing\RouterInterface;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;
use Symfony\Component\HttpFoundation\Request;

class ViewHandler implements ViewHandlerInterface
{
    /** @var RestViewHandler */
    private $decorated;

    private $router;

    public function __construct(ViewHandlerInterface $decorated, RouterInterface $router)
    {
        $this->decorated = $decorated;
        $this->router = $router;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(RequestConfiguration $requestConfiguration, View $view): Response
    {
        $transformer = $requestConfiguration->getVars()['transfomer'] ?? null;
        if ($requestConfiguration->isHtmlRequest() || !$transformer) {
            return $this->decorated->handle($requestConfiguration, $view);
        }

        $request = $requestConfiguration->getRequest();
        $data = $view->getData();
        $resource = $this->createResource($transformer, $data, $request);
        $view->setData($resource);

        return $this->decorated->handle($view);
    }

    protected function createResource($transformer, $data,  Request $request)
    {
        $router = $this->router;

        if ($data instanceof Pagerfanta) {
            $filteredResults = $data->getCurrentPageResults();

            $paginatorAdapter = new PagerfantaPaginatorAdapter($data, function(int $page) use ($request, $router) {
                $route = $request->attributes->get('_route');
                $inputParams = $request->attributes->get('_route_params');
                $newParams = array_merge($inputParams, $request->query->all());
                $newParams['page'] = $page;

                return $router->generate($route, $newParams, RouterInterface::ABSOLUTE_URL);
            });

            $resource = new Collection($filteredResults, $transformer);
            $resource->setPaginator($paginatorAdapter);

            return $resource;
        } 
        
        return new Item($data, $transformer);
    }
}