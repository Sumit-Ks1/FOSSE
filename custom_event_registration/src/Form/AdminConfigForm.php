<?php

declare(strict_types=1);

namespace Drupal\custom_event_registration\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Admin configuration form for event registration settings.
 */
class AdminConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['custom_event_registration.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'custom_event_registration_admin_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('custom_event_registration.settings');

    $form['description'] = [
      '#type' => 'markup',
      '#markup' => '<p>' . $this->t('Configure email notification settings for event registrations.') . '</p>',
    ];

    $form['email_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Email Notification Settings'),
    ];

    $form['email_settings']['admin_email'] = [
      '#type' => 'email',
      '#title' => $this->t('Admin Notification Email Address'),
      '#default_value' => $config->get('admin_email') ?? '',
      '#description' => $this->t('Enter the email address where admin notifications should be sent.'),
      '#maxlength' => 255,
    ];

    $form['email_settings']['admin_notification_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Admin Notifications'),
      '#default_value' => $config->get('admin_notification_enabled') ?? FALSE,
      '#description' => $this->t('When enabled, an email notification will be sent to the admin email address for each new registration.'),
    ];

    $form['info'] = [
      '#type' => 'details',
      '#title' => $this->t('Email Information'),
      '#open' => FALSE,
    ];

    $form['info']['content'] = [
      '#type' => 'markup',
      '#markup' => '<p>' . $this->t('The following emails are sent by this module:') . '</p>' .
        '<ul>' .
        '<li><strong>' . $this->t('User Confirmation Email') . ':</strong> ' .
        $this->t('Sent to the registrant upon successful registration. Contains event details and registration information.') . '</li>' .
        '<li><strong>' . $this->t('Admin Notification Email') . ':</strong> ' .
        $this->t('Sent to the configured admin email when a new registration is received. Only sent if enabled above.') . '</li>' .
        '</ul>',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    parent::validateForm($form, $form_state);

    $admin_email = $form_state->getValue('admin_email');
    $admin_notification_enabled = $form_state->getValue('admin_notification_enabled');

    // If admin notifications are enabled, admin email is required.
    if ($admin_notification_enabled && empty($admin_email)) {
      $form_state->setErrorByName('admin_email', $this->t('Admin email address is required when admin notifications are enabled.'));
    }

    // Validate email format if provided.
    if (!empty($admin_email) && !filter_var($admin_email, FILTER_VALIDATE_EMAIL)) {
      $form_state->setErrorByName('admin_email', $this->t('Please enter a valid email address.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->config('custom_event_registration.settings')
      ->set('admin_email', $form_state->getValue('admin_email'))
      ->set('admin_notification_enabled', (bool) $form_state->getValue('admin_notification_enabled'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
