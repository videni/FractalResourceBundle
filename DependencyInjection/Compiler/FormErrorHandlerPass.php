<?php

declare(strict_types=1);

namespace Videni\Bundle\FractalResourceBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Videni\Bundle\FractalResourceBundle\Serializer\FosFormErrorHandler;
use Videni\Bundle\FractalResourceBundle\Serializer\FormErrorHandler;

final class FormErrorHandlerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('fos_rest.serializer.form_error_handler')) {
            return;
        }

        $container->register(FosFormErrorHandler::class, FosFormErrorHandler::class)
            ->setDecoratedService('fos_rest.serializer.form_error_handler')
            ->addArgument(new Reference(FormErrorHandler::class));
    }
}
