<?php
/*
 * (c) 2010 - Cooperativa de Trabajo Alyssa Limitada
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Plugin configuration.
 *
 * Adds the custom Doctrine_Query class for Doctrine
 *
 * @package    sfAlyssaDoctrineObjectPathBehaviorPlugin
 * @subpackage config
 * @author     Sergio Fabian Vier <sergio.vier@alyssa-it.com>
 * @version    SVN: $Id$
 */
class sfAlyssaDoctrineObjectPathPluginConfiguration extends sfPluginConfiguration
{

  /**
   * @see sfPluginConfiguration
   */
  public function configure()
  {
    $a = array_search('sfAlyssaDoctrineObjectPathPluginConfiguration', $this->configuration->getPlugins());
    $b = array_search('sfDoctrinePlugin', $this->configuration->getPlugins());

    if ($a > $b)
    {
      throw new LogicException('The sfAlyssaDoctrineObjectPathPluginConfiguration plugin must be enabled before sfDoctrinePlugin');
    }
  }

  /**
   * @see sfPluginConfiguration
   */
  public function initialize()
  {

    $this->dispatcher->connect('doctrine.configure', array($this, 'configureDoctrine'));

  }

  /**
   * Configures Doctrine.
   *
   * Adds custom query and collection classes if none are setup already.
   *
   * @param sfEvent $event A symfony event
   */
  public function configureDoctrine(sfEvent $event)
  {

    // configure Doctrine_Query class only supported in Doctrine >= 1.2
    if (strpos(Doctrine::VERSION, '1.2') !== false){

      $manager = $event->getSubject();

      if ('Doctrine_Query' == $manager->getAttribute(Doctrine::ATTR_QUERY_CLASS))
      {
        $manager->setAttribute(Doctrine::ATTR_QUERY_CLASS, 'sfAlyssaDoctrineQuery');
      }

    }

  }

}
