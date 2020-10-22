<?php

namespace Drupal\short_links\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\user\Entity\User;

/**
 * Class ShortLinksController.
 */
class ShortLinksController extends ControllerBase {

  /**
   * Return shorten link.
   */
  public function shortlink() {

    /* display short links */
    global $base_url;
    $user = \Drupal::currentUser()->id();
    if (!empty($user)) {
      $results = \Drupal::database()->select('short_links', 's')
        ->fields('s', ['id', 'long_url', 'short_url', 'identifier'])
        ->condition('s.uid', $user, '=')
        ->orderBy('s.id', 'DESC')
        ->range(0, 1)
        ->execute();
    }
    else {
      $results = \Drupal::database()->select('short_links', 's')
        ->fields('s', ['id', 'long_url', 'short_url', 'identifier'])
        ->orderBy('s.id', 'DESC')
        ->condition('s.uid', '0', '=')
        ->range(0, 1)
        ->execute();
    }

    $header = [
      t('Web URL'), t('Short URL'),
    ];
    $rows = [];
    foreach ($results as $result) {
      $long_url = $result->long_url;
      $short_url = $result->short_url;
      $identifier = $result->identifier;
      $rows[] = [
        $long_url,
        [
          'data' => new FormattableMarkup('<a href=":link">@name</a>', [':link' => $base_url . '/shortlinks/' . $identifier, '@name' => $short_url]),
        ],
      ];
    }
    return [
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    ];
  }

  /**
   * Redirect to actual link.
   */
  public function getlink($shorturl) {

    $user = \Drupal::currentUser()->id();
    $ip_add = \Drupal::request()->getClientIp();
    $results = \Drupal::database()->select('short_links', 's')
      ->fields('s', ['id', 'long_url', 'short_url', 'identifier'])
      ->condition('s.identifier', $shorturl, '=')
      ->execute();
    $result = $results->fetchAssoc();
    $long_url = $result['long_url'];
    $identifier = $result['identifier'];

    $conn = Database::getConnection();
    $conn->insert('short_links_details')->fields(
      [
        'uid' => $user,
        'long_url' => $long_url,
        'identifier' => $identifier,
        'redirection_time' => strtotime("now"),
        'ip_add' => $ip_add,
      ]
    )->execute();
    return new TrustedRedirectResponse($long_url);

  }

  /**
   * Return shorten link analytics report table data.
   */
  public function analyticreport() {

    global $base_url;
    $results = \Drupal::database()->select('short_links', 's')
      ->fields('s', ['id', 'long_url', 'identifier', 'short_url'])
      ->orderBy('s.id', 'DESC')
      ->execute();
    $header = [
      t('Sr.no'), t('Web URL'), t('Identifier'), t('Short URL'), t('Show Details'),
    ];
    $rows = [];
    $sr_no = 0;
    foreach ($results as $result) {
      $sr_no += 1;
      $id = $result->id;
      $long_url = $result->long_url;
      $identifier = $result->identifier;
      $short_url = $result->short_url;
      $rows[] = [
        $sr_no,
        $long_url,
        $identifier,
        $short_url,
        [
          'data' => new FormattableMarkup('<a href=":link">@name</a>', [':link' => $base_url . '/show-detail/' . $identifier, '@name' => 'Details']),
        ],
      ];
    }
    return [
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    ];
  }

  /**
   * Return shorten link analytics report table data.
   */
  public function analyticreportdetail($url_identifier) {

    $results = \Drupal::database()->select('short_links_details', 's')
      ->fields('s',
        ['id', 'uid', 'long_url', 'identifier', 'redirection_time', 'ip_add']
      )
      ->condition('identifier', $url_identifier, '=')
      ->orderBy('s.id', 'DESC')
      ->execute();
    $header = [
      t('Sr.no'), t('User'), t('Web URL'), t('Identifier'), t('Redirection Time'), t('Ip Address'),
    ];
    $rows = [];
    $sr_no = 0;
    foreach ($results as $result) {
      $sr_no += 1;
      $id = $result->id;
      $uid = $result->uid;
      $user_name = "";
      if ($uid != 0) {
        $user = User::load($uid);
        $user_name = $user->name->value;
      }
      else {
        $user_name = "Anonymous";
      }
      $long_url = $result->long_url;
      $identifier = $result->identifier;
      $redirection_time = date('Y-m-d H:i:s', $result->redirection_time);
      $ip_add = $result->ip_add;
      $rows[] = [
        $sr_no,
        $user_name,
        $long_url,
        $identifier,
        $redirection_time,
        $ip_add,
      ];
    }
    return [
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    ];
  }

}
