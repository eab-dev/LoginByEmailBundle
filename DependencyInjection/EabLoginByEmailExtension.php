<?php

namespace Eab\LoginByEmailBundle\DependencyInjection;

use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class EabLoginByEmailExtension extends Extension implements PrependExtensionInterface
{
    /**
     * {@inheritdoc}
     */
    public function load( array $configs, ContainerBuilder $container )
    {
        $configuration = new Configuration();
        $this->processConfiguration( $configuration, $configs );

        $loader = new YamlFileLoader( $container, new FileLocator( __DIR__.'/../Resources/config' ));
        $loader->load( 'services.yml' );
        //$loader->load( 'default_settings.yml' );
    }

    /**
     * Loads EabLoginByEmailBundle configuration.
     *
     * @param ContainerBuilder $container
     */
    public function prepend( ContainerBuilder $container )
    {
        /* $configFile = __DIR__ . '/../Resources/config/override.yml';
        $config = Yaml::parse( file_get_contents( $configFile ) );
        $container->prependExtensionConfig( 'ezpublish', $config );
        $container->addResource( new FileResource( $configFile ) ); */
    }
}
