<?php

namespace Drupal\recurring_donation\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
  public function defaultConfiguration() {
    // Provide block configuration defaults.
    return [
      'options' => '5, 10, 15',
      'currency_code' => 'USD',
      'currency_sign' => '$',
      'unit' => 'M',
      'duration' => '1',
      'types' => [
        'single' => $this->t('Single donation'),
        'recurring' => $this->t('Recurring donation'),
      ],
    ];
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
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $config = $this->getConfiguration();

    // Wrap fieldsets in Bartik themed classes.
    $form['container'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['entity-meta']],
    ];

    // Loop over donation types.
    foreach ($config['types'] as $key => $value) {

      $form['container'][$key] = [
        '#type' => 'details',
        '#title' => $value,
        '#open' => TRUE,
      ];

      $form['container'][$key]['enable'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Enable'),
        '#default_value' => isset($config['values'][$key]['enable']) ? $config['values'][$key]['enable'] : '',
      ];

      $form['container'][$key]['receiver'] = [
        '#type' => 'textfield',
        '#title' => $this->t('PayPal receiving account'),
        '#description' => $this->t("The PayPal account's e-mail address"),
        '#default_value' => isset($config['values'][$key]['receiver']) ? $config['values'][$key]['receiver'] : '',
        '#required' => TRUE,
      ];

      $form['container'][$key]['options'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Pre-defined amounts'),
        '#default_value' => isset($config['values'][$key]['options']) ? $config['values'][$key]['options'] : $config['options'],
      ];

      $form['container'][$key]['custom'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Allow custom amount'),
        '#default_value' => isset($config['values'][$key]['custom']) ? $config['values'][$key]['custom'] : '',
      ];

      $form['container'][$key]['return'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Return URL'),
        '#description' => $this->t('The return URL upon successful payment'),
        '#default_value' => isset($config['values'][$key]['return']) ? $config['values'][$key]['return'] : '',
      ];

      $form['container'][$key]['currency_code'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Currency code'),
        '#description' => $this->t('ISO 4217 Currency Code'),
        '#default_value' => isset($config['values'][$key]['currency_code']) ? $config['values'][$key]['currency_code'] : $config['currency_code'],
      ];

      $form['container'][$key]['currency_sign'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Currency sign'),
        '#description' => $this->t('The currency sign'),
        '#default_value' => isset($config['values'][$key]['currency_sign']) ? $config['values'][$key]['currency_sign'] : $config['currency_sign'],
      ];

      if ($key === 'recurring') {

        $form['container'][$key]['unit'] = [
          '#type' => 'select',
          '#title' => $this->t('Recurring unit'),
          '#options' => [
            'D' => $this->t('day'),
            'W' => $this->t('week'),
            'M' => $this->t('month'),
            'Y' => $this->t('year'),
          ],
          '#default_value' => isset($config['values'][$key]['unit']) ? $config['values'][$key]['unit'] : $config['unit'],
        ];

        $form['container'][$key]['duration'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Recurring duration'),
          '#default_value' => isset($config['values'][$key]['duration']) ? $config['values'][$key]['duration'] : $config['duration'],
        ];

      }

    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    // Update block configuration.
    $this->setConfigurationValue('service', $values['service']);
    $this->setConfigurationValue('values', $values['container']);
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Get this block's configuration.
    $config = $this->getConfiguration();
    // Get module's translatable configuration.
    $paypal_config = $this->configFactory->get('paypal_donations.settings');
    // Loop over donation types.
    foreach ($config['types'] as $key => $type) {
      // Unset unchecked donation types.
      if (!$config['values'][$key]['enable']) {
        unset($config['types'][$key]);
      }
      // Process checked donation types.
      else {
        // Prepare $options.
        $options = explode(',', str_replace(' ', '', $config['values'][$key]['options']));

        // Reset types array.
        $config['types'][$key] = [
          'name' => $key,
          'label' => $paypal_config->get('label.' . $key),
          'receiver' => $config['values'][$key]['receiver'],
          'return' => $config['values'][$key]['return'],
          'custom' => $config['values'][$key]['custom'],
          'currency_code' => $config['values'][$key]['currency_code'],
          'currency_sign' => $config['values'][$key]['currency_sign'],
        ];

        if (!empty($options)) {
          $config['types'][$key]['options'] = $options;
        }

        if ($key === 'recurring') {
          $config['types'][$key]['unit'] = $config['values'][$key]['unit'];
          $config['types'][$key]['duration'] = $config['values'][$key]['duration'];
        }
      }
    }
    // Return theme render array & add library.
    return [
      '#theme' => 'paypal_block',
      '#types' => $config['types'],
      '#service' => (!$config['service']) ? 'sandbox.' : '',
      '#button' => $paypal_config->get('button'),
      '#attached' => [
        'library' => [
          'paypal_donations/paypal-donations',
        ],
      ],
    ];

  }

}
