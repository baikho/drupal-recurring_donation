<?php

namespace Drupal\recurring_donation\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class IPNRouteSubscriber.
 *
 * This route subscriber intercepts the default route for the IPN Listener if
 * the path is specified in the IPNSettingsForm.
 *
 * @package Drupal\recurring_donation\EventSubscriber
 */
class IPNRouteSubscriber extends RouteSubscriberBase {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new NodeAdminRouteSubscriber.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('recurring_donation.ipn')) {
      $config = $this->configFactory->get('recurring_donation.settings');
      if ($config->get('ipn.enabled') === TRUE) {
        $ipnPath = $config->get('ipn.path');
        // Override the default path for the IPN Listener if specified.
        if (!empty($ipnPath)) {
          $route->setPath($ipnPath);
        }
      }
    }
  }

}
