<?php

declare(strict_types=1);

namespace Drupal\custom_event_registration\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\custom_event_registration\Service\EventService;
use Drupal\custom_event_registration\Service\RegistrationService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for user event registration.
 */
class EventRegistrationForm extends FormBase {

  /**
   * The event service.
   *
   * @var \Drupal\custom_event_registration\Service\EventService
   */
  protected EventService $eventService;

  /**
   * The registration service.
   *
   * @var \Drupal\custom_event_registration\Service\RegistrationService
   */
  protected RegistrationService $registrationService;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected MessengerInterface $messenger;

  /**
   * Constructs an EventRegistrationForm object.
   *
   * @param \Drupal\custom_event_registration\Service\EventService $eventService
   *   The event service.
   * @param \Drupal\custom_event_registration\Service\RegistrationService $registrationService
   *   The registration service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(
    EventService $eventService,
    RegistrationService $registrationService,
    MessengerInterface $messenger
  ) {
    $this->eventService = $eventService;
    $this->registrationService = $registrationService;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('custom_event_registration.event_service'),
      $container->get('custom_event_registration.registration_service'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'custom_event_registration_registration_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    // Check if registration is open.
    if (!$this->eventService->isRegistrationOpen()) {
      $form['message'] = [
        '#type' => 'markup',
        '#markup' => '<div class="messages messages--warning">' .
          $this->t('Event registration is currently closed. Please check back later.') .
          '</div>',
      ];
      return $form;
    }

    $form['#prefix'] = '<div id="event-registration-form-wrapper">';
    $form['#suffix'] = '</div>';

    $form['description'] = [
      '#type' => 'markup',
      '#markup' => '<p>' . $this->t('Please fill in all the required fields to register for an event.') . '</p>',
    ];

    // Personal Information Section.
    $form['personal_info'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Personal Information'),
    ];

    $form['personal_info']['full_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Full Name'),
      '#required' => TRUE,
      '#maxlength' => 255,
      '#description' => $this->t('Enter your full name (letters, spaces, hyphens, and apostrophes only).'),
    ];

    $form['personal_info']['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email Address'),
      '#required' => TRUE,
      '#maxlength' => 255,
      '#description' => $this->t('Enter a valid email address.'),
    ];

    $form['personal_info']['college_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('College Name'),
      '#required' => TRUE,
      '#maxlength' => 255,
      '#description' => $this->t('Enter your college/institution name (no special characters).'),
    ];

    $form['personal_info']['department'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Department'),
      '#required' => TRUE,
      '#maxlength' => 255,
      '#description' => $this->t('Enter your department name (no special characters).'),
    ];

    // Event Selection Section.
    $form['event_selection'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Event Selection'),
    ];

    // Get available categories from active events.
    $categories = $this->eventService->getActiveCategoriesOptions();

    $form['event_selection']['category'] = [
      '#type' => 'select',
      '#title' => $this->t('Category'),
      '#required' => TRUE,
      '#options' => ['' => $this->t('- Select Category -')] + $categories,
      '#description' => $this->t('Select the event category.'),
      '#ajax' => [
        'callback' => '::updateEventDateCallback',
        'wrapper' => 'event-date-wrapper',
        'event' => 'change',
      ],
    ];

    // Event Date dropdown - depends on category selection.
    $selected_category = $form_state->getValue('category');
    $event_dates = [];

    if (!empty($selected_category)) {
      $event_dates = $this->eventService->getEventDatesByCategory($selected_category);
    }

    $form['event_selection']['event_date_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'event-date-wrapper'],
    ];

    $form['event_selection']['event_date_wrapper']['event_date'] = [
      '#type' => 'select',
      '#title' => $this->t('Event Date'),
      '#required' => TRUE,
      '#options' => ['' => $this->t('- Select Event Date -')] + $event_dates,
      '#description' => $this->t('Select the event date.'),
      '#validated' => TRUE,
      '#ajax' => [
        'callback' => '::updateEventNameCallback',
        'wrapper' => 'event-name-wrapper',
        'event' => 'change',
      ],
    ];

    // Event Name dropdown - depends on category and date selection.
    $selected_date = $form_state->getValue('event_date');
    $event_names = [];

    if (!empty($selected_category) && !empty($selected_date)) {
      $event_names = $this->eventService->getEventsByCategoryAndDate($selected_category, $selected_date);
    }

    $form['event_selection']['event_name_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'event-name-wrapper'],
    ];

    $form['event_selection']['event_name_wrapper']['event_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Event Name'),
      '#required' => TRUE,
      '#options' => ['' => $this->t('- Select Event Name -')] + $event_names,
      '#description' => $this->t('Select the event you want to register for.'),
      '#validated' => TRUE,
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Register'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * AJAX callback to update event date dropdown.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The AJAX response.
   */
  public function updateEventDateCallback(array &$form, FormStateInterface $form_state): AjaxResponse {
    $response = new AjaxResponse();

    // Replace the event date wrapper.
    $response->addCommand(new ReplaceCommand(
      '#event-date-wrapper',
      $form['event_selection']['event_date_wrapper']
    ));

    // Also reset and replace the event name wrapper.
    $response->addCommand(new ReplaceCommand(
      '#event-name-wrapper',
      $form['event_selection']['event_name_wrapper']
    ));

    return $response;
  }

  /**
   * AJAX callback to update event name dropdown.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The AJAX response.
   */
  public function updateEventNameCallback(array &$form, FormStateInterface $form_state): AjaxResponse {
    $response = new AjaxResponse();

    $response->addCommand(new ReplaceCommand(
      '#event-name-wrapper',
      $form['event_selection']['event_name_wrapper']
    ));

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    // Skip validation if registration is closed.
    if (!$this->eventService->isRegistrationOpen()) {
      return;
    }

    // Validate full name.
    $full_name = trim($form_state->getValue('full_name') ?? '');
    if (empty($full_name)) {
      $form_state->setErrorByName('full_name', $this->t('Full name is required.'));
    }
    elseif (mb_strlen($full_name) > 255) {
      $form_state->setErrorByName('full_name', $this->t('Full name must be less than 255 characters.'));
    }
    elseif (!$this->registrationService->validateFullName($full_name)) {
      $form_state->setErrorByName('full_name', $this->t('Full name can only contain letters, spaces, hyphens, apostrophes, and periods.'));
    }

    // Validate email.
    $email = trim($form_state->getValue('email') ?? '');
    if (empty($email)) {
      $form_state->setErrorByName('email', $this->t('Email address is required.'));
    }
    elseif (mb_strlen($email) > 255) {
      $form_state->setErrorByName('email', $this->t('Email must be less than 255 characters.'));
    }
    elseif (!$this->registrationService->validateEmail($email)) {
      $form_state->setErrorByName('email', $this->t('Please enter a valid email address.'));
    }

    // Validate college name.
    $college_name = trim($form_state->getValue('college_name') ?? '');
    if (empty($college_name)) {
      $form_state->setErrorByName('college_name', $this->t('College name is required.'));
    }
    elseif (mb_strlen($college_name) > 255) {
      $form_state->setErrorByName('college_name', $this->t('College name must be less than 255 characters.'));
    }
    elseif (!$this->registrationService->validateCollegeName($college_name)) {
      $form_state->setErrorByName('college_name', $this->t('College name can only contain letters, numbers, spaces, and basic punctuation.'));
    }

    // Validate department.
    $department = trim($form_state->getValue('department') ?? '');
    if (empty($department)) {
      $form_state->setErrorByName('department', $this->t('Department is required.'));
    }
    elseif (mb_strlen($department) > 255) {
      $form_state->setErrorByName('department', $this->t('Department must be less than 255 characters.'));
    }
    elseif (!$this->registrationService->validateDepartment($department)) {
      $form_state->setErrorByName('department', $this->t('Department can only contain letters, numbers, spaces, and basic punctuation.'));
    }

    // Validate event selection.
    $event_id = $form_state->getValue('event_id');
    if (empty($event_id)) {
      $form_state->setErrorByName('event_id', $this->t('Please select an event.'));
      return;
    }

    // Verify event exists and is still active.
    $event = $this->eventService->getEvent((int) $event_id);
    if (!$event) {
      $form_state->setErrorByName('event_id', $this->t('The selected event is no longer available.'));
      return;
    }

    // Check for duplicate registration.
    if ($this->registrationService->isDuplicateRegistration($email, (int) $event_id)) {
      $form_state->setErrorByName('email', $this->t('You have already registered for an event on this date with this email address.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $data = [
      'full_name' => mb_substr(trim($form_state->getValue('full_name') ?? ''), 0, 255),
      'email' => mb_substr(trim($form_state->getValue('email') ?? ''), 0, 255),
      'college_name' => mb_substr(trim($form_state->getValue('college_name') ?? ''), 0, 255),
      'department' => mb_substr(trim($form_state->getValue('department') ?? ''), 0, 255),
      'event_id' => (int) $form_state->getValue('event_id'),
    ];

    $registration_id = $this->registrationService->createRegistration($data);

    if ($registration_id) {
      $event = $this->eventService->getEvent($data['event_id']);
      $this->messenger->addStatus($this->t('Thank you for registering! Your registration for "@event" on @date has been confirmed. A confirmation email has been sent to @email.', [
        '@event' => $event ? $event->event_name : '',
        '@date' => $event ? $event->event_date : '',
        '@email' => $data['email'],
      ]));

      // Clear form values.
      $form_state->setRebuild(FALSE);
    }
    else {
      $this->messenger->addError($this->t('There was an error processing your registration. Please try again.'));
    }
  }

}
