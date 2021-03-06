<?php

namespace Drupal\maklerweather\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Utility\Token;
use Drupal\maklerweather\WeatherService;


/**
 * Provides a block with a Weather Widget.
 *
 * @Block(
 *   id = "maklerweather_block",
 *   admin_label = @Translation("Makler Weather"),
 * )
 */
class WeatherBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * The module handler.
   *
   * @var \Drupal\maklerweather\WeatherService
   */
  protected $weatherservice;

  /**
   * Constructs a Drupal\Component\Plugin\PluginBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\maklerweather\WeatherService $weatherservice
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   * @param \Drupal\Core\Utility\Token $token
   *   The token service.
   *
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, WeatherService $weatherservice, ModuleHandlerInterface $module_handler, Token $token) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->weatherservice = $weatherservice;
    $this->moduleHandler = $module_handler;
    $this->token = $token;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('maklerweather.weather_service'),
      $container->get('module_handler'),
      $container->get('token')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();
    $output = json_decode($this->weatherservice->getWeatherInformation($config), TRUE);

    if (!empty($output)) {
      $build = $this->weatherservice->getCurrentWeatherInformation($output, $config);
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $config = $this->getConfiguration();

    if ($this->moduleHandler->moduleExists("token")) {
      $form['token_help'] = [
        '#type' => 'markup',
        '#token_types' => ['user'],
        '#theme' => 'token_tree_link',
      ];
    }

    $form['input_value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Enter the Value for selected option'),
      '#required' => TRUE,
      '#description' => $this->t('Format: <code>City_name,country_id</code> Z.B.: <a href="https://openweathermap.org/find?q=duren" target="_blank">Für Düren</a> - "Düren, DE"'),
      '#default_value' => !empty($config['input_value']) ? $config['input_value'] : '',
    ];

    $form['count'] = [
      '#type' => 'number',
      '#min' => '1',
      '#title' => $this->t('Enter the number count'),
      '#default_value' => !empty($config['count']) ? $config['count'] : '1',
      '#required' => TRUE,
      '#description' => $this->t('Select the count in case of hourlyforecast maximum value should be 36 and in case of daily forecast maximum value should be 7. in case of current weather forecast value is the default value'),
    ];

    $weatherdata = array(
      'name' => $this->t('City Name'),
      'description' => $this->t('Weather description'),
      'icon' => $this->t('Weather icon'),
      'temp' => $this->t('Current Temperature'),
    );
    $form['weatherdata'] = array(
      '#type' => 'details',
      '#title' => $this->t('Output Option available for current weather'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    );
    $form['weatherdata']['items'] = array(
      '#type' => 'checkboxes',
      '#options' => $weatherdata,
      '#description' => $this->t('Select output data you want to display.'),
      '#default_value' => !empty($config['outputitems']) ? $config['outputitems'] : array(
        'name',
        'description',
        'icon',
        'temp',
      ),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    if ($this->moduleHandler->moduleExists("token")) {
      $user = $form_state->getValue('account');
      $message = $this->token->replace($form_state->getValue('input_value'), ['user' => $user]);
    }
    $this->setConfigurationValue('outputitems', $form_state->getValue('weatherdata')['items']);
    if (!empty($message)) {
      $this->setConfigurationValue('input_value', $message);
    }
    else {
      $this->setConfigurationValue('input_value', $form_state->getValue('input_value'));
    }
    $this->setConfigurationValue('count', $form_state->getValue('count'));
  }

}
