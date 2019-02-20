<?php

namespace Drupal\recurring_donation\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\recurring_donation\DonationType;
use Drupal\recurring_donation\Form\DonationForm;
use Drupal\recurring_donation\Form\DonationTypeSelectionForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Recurring PayPal donations' block.
 *
 * @Block(
 *   id = "recurring_donation_block",
 *   admin_label = @Translation("PayPal donations"),
 *   category = @Translation("Recurring PayPal Donations")
 * )
 */
class DonationBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

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
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, FormBuilderInterface $form_builder, ConfigFactoryInterface $config_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->formBuilder = $form_builder;
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
      $container->get('form_builder'),
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

    $build = [
      $this->formBuilder->getForm(DonationTypeSelectionForm::class),
    ];

    foreach (DonationType::getAll() as $donationType) {
      // Early opt-out if donation type is not enabled.
      if ($config->get($donationType . '.enabled') !== TRUE) {
        continue;
      }
      $build[] = $this->formBuilder->getForm(DonationForm::class, $donationType);
    }

    return $build;
  }

}
