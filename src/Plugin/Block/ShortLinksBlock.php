<?php

namespace Drupal\short_links\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'ShortLinksBlock' block.
 *
 * @Block(
 *  id = "shrtend_link_block",
 *  admin_label = @Translation("Shrtend link block"),
 * )
 */
class ShortLinksBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {

    $build = \Drupal::formBuilder()->getForm('Drupal\short_links\Form\ShortLinksForm');
    return $build;
  }

}
