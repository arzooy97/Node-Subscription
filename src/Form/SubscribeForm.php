<?php
/**
 * @file
 * Contains \Drupal\node_subscription\Form\SubscribeForm
 */
namespace Drupal\node_subscription\Form;

use Drupal\Core\Database\Database;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node_subscription\Entity\NodeSubscriptionStorage;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ChangedCommand;
use Drupal\Core\Ajax\CssCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\InvokeCommand;

/**
 * Provides a subscribe form.
 */
class SubscribeForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'subscribe_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['user_choice'] = [
      '#type' => 'checkboxes',
      '#options' => array(
        'a' =>t('Content'),
        'b' =>t('Comment')
      ),
      '#prefix' => '<div id="subscription-result"></div>',
      '#ajax' => array(
        'callback' => 'Drupal\node_subscription\Form\SubscribeForm::validateCallback',
        'effect' => 'fade',
        'event' => 'change',
        'progress' => array(
          'type' => 'throbber',
          'message' => NULL,
        ),
      ),
    ];


    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Subscribe'),
      '#button_type' => 'primary',
    );
   
    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    //Neither comment nor content selected
    if ($form_state->getValue('user_choice')['a'] == !'a' && $form_state->getValue('user_choice')['b'] == !'b') {
      $form_state->setErrorByName('user_choice',t('Please select atleast one option.', array('%user_choice' => $value)));
      return;
    }
  }

  public function validateCallback(array &$form, FormStateInterface $form_state) {
    $ajax_response = new AjaxResponse();
    $value = $form_state->getValue('user_choice');

    $node = \Drupal::routeMatch()->getParameter('node');
    if ($node instanceof \Drupal\node\NodeInterface) {
      $node_id = $node->id();
    }
    $user_id = \Drupal::currentUser()->id();
    
    $storage = \Drupal::entityTypeManager()->getStorage('node_subscription_storage');
    $uids = \Drupal::entityQuery('node_subscription_storage')
          ->execute();
    $users = $storage->loadMultiple($uids);

    $counter = 1;

    foreach ($users as $user) {
      $uid[$counter] = $user->get("field_userid")->getString();
      $nid[$counter] = $user->get("field_nodeid")->getString();
      $comment_var[$counter] = $user->get("field_comment")->value;
      $content_var[$counter] = $user->get("field_content")->value;

      $counter++;
    }

    while($counter > 1) {
      $counter--;
      if($nid[$counter] === $node_id && $content_var[$counter] == 1 && $user_id == $uid[$counter] && $form_state->getValue('user_choice')['a'] === 'a') {
         $text = 'Already subscribed';
         $color = 'red';
         $ajax_response->addCommand(new HtmlCommand('#subscription-result', $text));
         $ajax_response->addCommand(new InvokeCommand('#subscription-result', 'css', array('color', $color)));
         return $ajax_response;
      }
      if($nid[$counter] === $node_id && $comment_var[$counter] == 1 && $user_id == $uid[$counter] && $form_state->getValue('user_choice')['b'] === 'b') {
        $text = 'Already subscribed';
        $color = 'red';
        $ajax_response->addCommand(new HtmlCommand('#subscription-result', $text));
        $ajax_response->addCommand(new InvokeCommand('#subscription-result', 'css', array('color', $color)));
        return $ajax_response;
      }
     
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $user = \Drupal::currentUser();
    $node = \Drupal::routeMatch()->getParameter('node');
    $nid = $node->nid->value;
    $content_var = FALSE;
    $comment_var = FALSE;

    if($form_state->getValue('user_choice')['a'] === 'a') {
      $content_var= TRUE;
    }

    if($form_state->getValue('user_choice')['b'] === 'b') {
      $comment_var= TRUE;
    }

    $entity_fill = NodeSubscriptionStorage::create([
      'name' => 'Subscription',
      'field_content' => $content_var,
      'field_comment' => $comment_var,
      'field_nodeid' => ['target_id' => $nid],
      'field_userid' => ['target_id' => $user->id()]
    ]);
    $entity_fill->save();
    drupal_set_message(t('Thankyou for your subscription. You will now get email when any node is updated.'));
  }
}
