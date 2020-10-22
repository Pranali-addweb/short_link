<?php

namespace Drupal\short_links\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Database\Database;

/**
 * Class ShortLinksForm for shorten link.
 */
class ShortLinksForm extends FormBase {

  /**
   * Get form Id using getFormId().
   */
  public function getFormId() {
    return 'short_links.form';
  }

  /**
   * Build form buildForm().
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['longurl'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Web Link'),
      '#placeholder' => $this->t('Shorten your link here'),
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Shorten'),
    ];
    return $form;
  }

  /**
   * Validate form validateForm().
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    $url_value = $form_state->getValue('longurl');
    $value = trim($url_value);
    if (!UrlHelper::isValid($value, TRUE)) {
      $form_state->setErrorByName('longurl', t('The URL %url is not valid.', ['%url' => $value,
      ]));
    }
  }

  /**
   * Submit form submitForm().
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    global $base_url;
    $long_url = $form_state->getValue('longurl');
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
    $short_url = $base_url . '/shortlinks/' . $random_url;
    $user = \Drupal::currentUser()->id();
    $conn = Database::getConnection();
    $conn->insert('short_links')->fields(
      [
        'uid' => $user,
        'long_url' => $long_url,
        'short_url' => $short_url,
        'identifier' => $random_url,
      ]
    )->execute();
    $form_state->setRedirect('short_links.data');

  }

}
