<?php

namespace Drupal\maklerweather;

use Drupal\Component\Utility\Html;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\ClientInterface;

/**
 * WeatherService.
 */
class WeatherService {

  /**
   * Base uri of maklerweather api.
   *
   * @var Drupal\maklerweather
   */
  public static $baseUri = 'http://api.openweathermap.org/';

  /**
   * The HTTP client to fetch the feed data with.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * Constructs a database object.
   *
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The Guzzle HTTP client.
   */
  public function __construct(ClientInterface $http_client) {
    $this->httpClient = $http_client;
  }

  /**
   * Get a complete query for the API.
   *
   * @param $options
   *
   * @return array
   */
  public function createRequest($options) {
    $query = [];
    $appid_config = \Drupal::config('maklerweather.settings')->get('appid');
    $query['appid'] = Html::escape($appid_config);
    $query['cnt'] = $options['count'];
    $input_data = Html::escape($options['input_value']);
    $query['q'] = $input_data;
    $query['lang'] = 'de';

    return $query;
  }

  /**
   * Return the data from the API in xml format.
   */
  public function getWeatherInformation($options) {
    try {
      $response = $this->httpClient->request('GET', self::$baseUri . '/data/2.5/weather', ['query' => $this->createRequest($options)]);
    } catch (GuzzleException $e) {
      watchdog_exception('maklerweather', $e);

      return FALSE;
    }

    return $response->getBody()->getContents();
  }

  /**
   * Return an array containing the current weather information.
   */
  public function getCurrentWeatherInformation($output, $config) {
    $html = [];


    foreach ($config['outputitems'] as $value) {
      if (!empty($config['outputitems'][$value])) {
        switch ($config['outputitems'][$value]) {

          case 'name':
            $html[$value] = $output['name'];
            break;

          case 'description':
            $html[$value]['description'] = $output['weather'][0]['description'];
            break;

          case 'image':
            $html[$value]['image'] = $output['weather'][0]['icon'];
            break;

          case 'temp':
            $html[$value] = round($output['main']['temp'] - 273.15) . '°C';
            break;
        }
      }
    }

    //    \Drupal::logger('_name')->notice(json_encode($output['name']));
    //    \Drupal::logger('_description')->notice(json_encode($output['weather'][0]['description']));
    //    \Drupal::logger('_icon')->notice(json_encode($output['weather'][0]['icon']));
    //    \Drupal::logger('_temp')->notice(json_encode($output['main']['temp']) - 273.15 . '°C');

    $build[] = [
      '#theme' => 'maklerweather',
      '#maklerweather_detail' => $html,
      '#attached' => [
        'library' => [
          'maklerweather/maklerweather_theme',
        ],
      ],
      '#cache' => ['max-age' => 0],
    ];

    \Drupal::logger('_build_config')->notice(json_encode($config));
    \Drupal::logger('_build_output')->notice(json_encode($output));
    \Drupal::logger('_build')->notice(json_encode($build));
    \Drupal::logger('_html')->notice(json_encode($html));

    return $build;
  }

}
