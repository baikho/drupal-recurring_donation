<?php

namespace Drupal\recurring_donation\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\recurring_donation\DonationTypes;

/**
 * Provides a 'Recurring PayPal donations' block.
 *
 * @Block(
 *   id = "recurring_donation_block",
 *   admin_label = @Translation("Recurring PayPal donations"),
 *   category = @Translation("Forms")
 * )
 */
class DonationBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Stores the configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new PaypalDonateBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $config_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIfHasPermission($account, 'access recurring paypal donations');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    $config = $this->configFactory->get('recurring_donation.settings');
    $types = [];

    foreach (DonationTypes::getTypes() as $donationType) {
      // Early opt-out if not enabled.
      if ($donationType === DonationTypes::RECURRING && (bool) $config->get('recurring.enabled') !== TRUE) {
        continue;
      }

      $types[$donationType] = [
        'name' => $donationType,
        'label' => $config->get($donationType . '.label'),
        'receiver' => $config->get('receiver'),
        'return' => $config->get('return'),
        'custom' => $config->get('custom'),
        'currency_code' => $config->get('currency_code'),
        'currency_sign' => $config->get('currency_sign'),
      ];

      // Prepare pre-defined amounts.
      $options = explode(',', str_replace(' ', '', $config->get('options')));

      if (!empty($options)) {
        $types[$donationType]['options'] = $options;
      }

      if ($donationType === DonationTypes::RECURRING) {
        $types[$donationType]['unit'] = $config->get('unit');
        $types[$donationType]['duration'] = $config->get('duration');
      }
    }

    return [
      '#theme' => 'recurring_donation_block',
      '#types' => $types,
      '#env' => $config->get('env') !== TRUE ? 'sandbox.' : '',
      '#button' => $config->get('button'),
      '#attached' => [
        'library' => [
          'recurring_donation/recurring-donation',
        ],
      ],
    ];

  }

}
