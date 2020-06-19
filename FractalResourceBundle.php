<?php

declare(strict_types=1);

namespace Videni\Bundle\FractalResourceBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Videni\Bundle\FractalResourceBundle\DependencyInjection\Compiler\RegisterTransfomerPass;
use Videni\Bundle\FractalResourceBundle\DependencyInjection\Compiler\FormErrorHandlerPass;

final class FractalResourceBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new RegisterTransfomerPass());
        $container->addCompilerPass(new FormErrorHandlerPass());
    }
}
