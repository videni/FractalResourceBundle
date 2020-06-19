<?php 

declare(strict_types=1);

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
use Sylius\Component\Resource\Model\ResourceInterface;
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
        if ($requestConfiguration->isHtmlRequest()) {
            return $this->decorated->handle($requestConfiguration, $view);
        }
        
        $this->createResource($requestConfiguration, $view);

        return $this->decorated->handle($requestConfiguration, $view);
    }

    protected function createResource(RequestConfiguration $requestConfiguration, View $view)
    {
        $transformer = $this->getTransformer($requestConfiguration);
        if (!$transformer) {
            return;
        }

        $request = $requestConfiguration->getRequest();
        $router = $this->router;
        
        $data = $view->getData();
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

            $view->setData($resource);

            return ;
        } 
        
        if ($data instanceof ResourceInterface) {
            $view->setData(new Item($data, $transformer));
        }
    }

    private function getTransformer(RequestConfiguration $requestConfiguration)
    {
        return $requestConfiguration->getVars()['transfomer']?? null;
    }
}