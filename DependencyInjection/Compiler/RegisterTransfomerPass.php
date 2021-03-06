<?php

declare(strict_types=1);

namespace Videni\Bundle\FractalResourceBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use SamJ\FractalBundle\ContainerAwareManager;
use Symfony\Component\DependencyInjection\Compiler;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;

final class RegisterTransfomerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(ContainerAwareManager::class)) {
            return;
        }

        $transfomers = [];
        foreach ($container->findTaggedServiceIds('videni.fractal_transfomer') as $id => $attributes) {
            $key = $attributes[0]['key']?? null;
            if (!$key) {
                throw new \InvalidArgumentException('Tagged fractal transfomers needs to have `key` attribute.');
            }

            $transfomers[$key] = new Reference($id);
        }

        $container
            ->setAlias('videni.fractal_transfomer_locator', (string) ServiceLocatorTagPass::register($container, $transfomers))
            ->setPublic(true);

        $container
            ->getDefinition(ContainerAwareManager::class)
            ->addMethodCall('setContainer', [new Reference('videni.fractal_transfomer_locator')]);
    }
}
