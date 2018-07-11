<?php
/**
 * @file
 * Contains \Drupal\node_subscription\Plugin\Block\SubscribeBlock
 */
namespace Drupal\node_subscription\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormInterface;


/**
 * Provides a 'Subscribe' block.
 *
 * @Block(
 *   id = "subscribe_block",
 *   admin_label = @Translation("Subscribe Here"),
 *   category = @Translation("Blocks")
 * )
 */
class SubscribeBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $form = \Drupal::formBuilder()->getForm('Drupal\node_subscription\Form\SubscribeForm');
    return $form;
  }
}

