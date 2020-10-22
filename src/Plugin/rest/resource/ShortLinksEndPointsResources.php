<?php

namespace Drupal\short_links\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\rest\ModifiedResourceResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\Core\Database\Database;
use Drupal\Component\Utility\UrlHelper;

/**
 * Provide endpoints to generate short links.
 *
 * @RestResource(
 *   id = "shortlinks_endpoint",
 *   label = @Translation("Generate Links Endpoint"),
 *   uri_paths = {
 *     "canonical" = "/endpoint/generate-links",
 *     "https://www.drupal.org/link-relations/create" = "/endpoint/generate-links"
 *   }
 * )
 */
class ShortLinksEndPointsResources extends ResourceBase {

  /**
   * Current user instance.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Construct new resource.
   *
   * @param array $configuration
   *   Containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param array $serializer_formats
   *   The available serialization formats.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   User instance.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    AccountProxyInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);

    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('short_links'),
      $container->get('current_user')
    );
  }

  /**
   * Responds to POST requests.
   *
   * @return \Drupal\rest\ModifiedResourceResponse
   *   The HTTP response object.
   */
  public function post($data) {

    if (!$this->currentUser->hasPermission('access content')) {
      throw new AccessDeniedHttpException();
    }

    $long_url = $data['web_link'];
    $response = [];

    // Check valid domain name.
    if (UrlHelper::isValid($long_url, TRUE)) {
      // Genertae random string.
      global $base_url;
      /*get form filed values*/

      $uid = 0;

      $identifier = ShortLinksEndPointsResources::randomIdentifier();

      $short_url = $base_url . '/shortlinks/' . $identifier;

      /*Insert data*/
      $conn = Database::getConnection();
      $conn->insert('short_links')->fields(
        [
          'uid' => $uid,
          'long_url' => $long_url,
          'short_url' => $short_url,
          'identifier' => $identifier,
        ]
      )->execute();

      $response['status'] = 1;
      $response['short_url'] = $short_url;
    }
    else {
      $response['status'] = 0;
      $response['error'] = 'Please provide a valid web address';
    }

    return new ModifiedResourceResponse($response);
  }

  /**
   * Generate random alphanumeric identifier.
   */
  public function randomIdentifier() {
    $random_url = "";
    $domain = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890";
    $len = strlen($domain);
    $n = rand(5, 9);
    for ($i = 0; $i < $n; $i++) {
      // Generate a random index to pick
      // characters.
      $index = rand(0, $len - 1);

      // Concatenating the character
      // in resultant string.
      $random_url = $random_url . $domain[$index];
    }
    return $random_url;
  }

}
