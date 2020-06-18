<?php

declare(strict_types=1);

namespace Videni\Bundle\FractalBundle\Controller;

use Hateoas\Configuration\Route;
use Hateoas\Representation\Factory\PagerfantaFactory;
use Pagerfanta\Pagerfanta;
use Sylius\Bundle\ResourceBundle\Grid\View\ResourceGridView;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Sylius\Bundle\ResourceBundle\Controller\ResourcesCollectionProviderInterface;
use Sylius\Bundle\ResourceBundle\Controller\ResourcesResolverInterface;
use Sylius\Bundle\ResourceBundle\Controller\RequestConfiguration;

final class ResourcesCollectionProvider implements ResourcesCollectionProviderInterface
{
    /** @var ResourcesResolverInterface */
    private $resourcesResolver;

    /** @var PagerfantaFactory */
    private $pagerfantaRepresentationFactory;

    public function __construct(ResourcesResolverInterface $resourcesResolver, PagerfantaFactory $pagerfantaRepresentationFactory)
    {
        $this->resourcesResolver = $resourcesResolver;
        $this->pagerfantaRepresentationFactory = $pagerfantaRepresentationFactory;
    }

    /**
     * {@inheritdoc}
     *
     * @psalm-suppress MissingReturnType
     */
    public function get(RequestConfiguration $requestConfiguration, RepositoryInterface $repository)
    {
        $resources = $this->resourcesResolver->getResources($requestConfiguration, $repository);
        $paginationLimits = [];

        if ($resources instanceof ResourceGridView) {
            $paginator = $resources->getData();
            $paginationLimits = $resources->getDefinition()->getLimits();
        } else {
            $paginator = $resources;
        }

        if ($paginator instanceof Pagerfanta) {
            $request = $requestConfiguration->getRequest();

            $paginator->setMaxPerPage($this->resolveMaxPerPage(
                $request->query->has('limit') ? $request->query->getInt('limit') : null,
                $requestConfiguration->getPaginationMaxPerPage(),
                $paginationLimits
            ));
            $paginator->setCurrentPage($request->query->get('page', 1));

            // This prevents Pagerfanta from querying database from a template
            $paginator->getCurrentPageResults();

            if (!$requestConfiguration->isHtmlRequest()) {
                //Return if fractal transfomer is set.
                $transformer = $requestConfiguration->getVars()['transformer'] ?? null;
                if ($transformer) {
                    return $paginator;
                }

                $route = new Route($request->attributes->get('_route'), array_merge($request->attributes->get('_route_params'), $request->query->all()));

                return $this->pagerfantaRepresentationFactory->createRepresentation($paginator, $route);
            }
        }

        return $resources;
    }

    /**
     * @param int[] $gridLimits
     */
    private function resolveMaxPerPage(?int $requestLimit, int $configurationLimit, array $gridLimits = []): int
    {
        if (null === $requestLimit) {
            return reset($gridLimits) ?: $configurationLimit;
        }

        if (!empty($gridLimits)) {
            $maxGridLimit = max($gridLimits);

            return $requestLimit > $maxGridLimit ? $maxGridLimit : $requestLimit;
        }

        return $requestLimit;
    }
}
