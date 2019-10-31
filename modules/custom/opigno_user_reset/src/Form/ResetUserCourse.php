<?php

/**
 * @file
 * Contains \Drupal\product_import\Form\ProductImportForm.
 */

namespace Drupal\opigno_user_reset\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\group\Entity\Group;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;

class ResetUserCourse extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'reset_user_course_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state, $user = NULL, $group = NULL) {
    $form = [];

    $form_state->set('user', $user);
    $form_state->set('group_id', $group);

    $form['markup'] = [
      '#markup' => '<div>Are you sure you want to reset class progress?</div>',
    ];

    $form['confirm'] = [
      '#type' => 'submit',
      '#value' => t('Confirm')
    ];

    $form['cancel'] = [
      '#type' => 'submit',
      '#value' => t('Cancel'),
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $user = $form_state->get('user');
    $group = $form_state->get('group_id');

    $button = $form_state->getValue('op');
    $button_text = $button->render();
    if($button_text == "Confirm") {
      $connection = \Drupal::database();
      $connection->delete('opigno_learning_path_step_achievements')
        ->condition('uid', $user)
        ->condition('gid', $group)
        ->execute();

      $connection->delete('opigno_learning_path_achievements')
        ->condition('uid', $user)
        ->condition('gid', $group)
        ->execute();

      $connection->delete('user_module_status')
        ->condition('user_id', $user)
        ->condition('learning_path', $group)
        ->execute();

      $connection->update('opigno_learning_path_group_user_status')
        ->fields([
          'status' => 1
        ])
        ->condition('uid', $user)
        ->condition('gid', $group)
        ->execute();

      $group_object = Group::load($group);
      $user_object = User::load($user);
      if($group_object) {
        $group_object->removeMember($user_object);
      }
    }

    $url = Url::fromRoute('entity.user.canonical', ['user' => $user]);
    $form_state->setRedirectUrl($url);
  }

}

