<?php

declare(strict_types=1);

namespace Drupal\custom_event_registration\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\custom_event_registration\Service\EventService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for creating/editing event configurations.
 */
class EventConfigForm extends FormBase {

  /**
   * The event service.
   *
   * @var \Drupal\custom_event_registration\Service\EventService
   */
  protected EventService $eventService;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected MessengerInterface $messenger;

  /**
   * Constructs an EventConfigForm object.
   *
   * @param \Drupal\custom_event_registration\Service\EventService $eventService
   *   The event service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(EventService $eventService, MessengerInterface $messenger) {
    $this->eventService = $eventService;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('custom_event_registration.event_service'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'custom_event_registration_event_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['#prefix'] = '<div id="event-config-form-wrapper">';
    $form['#suffix'] = '</div>';

    $form['description'] = [
      '#type' => 'markup',
      '#markup' => '<p>' . $this->t('Create a new event configuration. All fields are required.') . '</p>',
    ];

    $form['event_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Event Name'),
      '#required' => TRUE,
      '#maxlength' => 255,
      '#description' => $this->t('Enter the name of the event.'),
    ];

    $form['category'] = [
      '#type' => 'select',
      '#title' => $this->t('Category'),
      '#required' => TRUE,
      '#options' => [
        '' => $this->t('- Select Category -'),
      ] + $this->eventService->getCategoryOptions(),
      '#description' => $this->t('Select the event category.'),
    ];

    $form['registration_start'] = [
      '#type' => 'date',
      '#title' => $this->t('Registration Start Date'),
      '#required' => TRUE,
      '#description' => $this->t('The date when registration opens.'),
    ];

    $form['registration_end'] = [
      '#type' => 'date',
      '#title' => $this->t('Registration End Date'),
      '#required' => TRUE,
      '#description' => $this->t('The date when registration closes.'),
    ];

    $form['event_date'] = [
      '#type' => 'date',
      '#title' => $this->t('Event Date'),
      '#required' => TRUE,
      '#description' => $this->t('The date when the event takes place.'),
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Create Event'),
      '#button_type' => 'primary',
    ];

    // Display existing events.
    $events = $this->eventService->getAllEvents();

    if (!empty($events)) {
      $form['existing_events'] = [
        '#type' => 'details',
        '#title' => $this->t('Existing Events'),
        '#open' => TRUE,
      ];

      $header = [
        'event_name' => $this->t('Event Name'),
        'category' => $this->t('Category'),
        'registration_start' => $this->t('Registration Start'),
        'registration_end' => $this->t('Registration End'),
        'event_date' => $this->t('Event Date'),
        'status' => $this->t('Status'),
      ];

      $rows = [];
      $today = date('Y-m-d');

      foreach ($events as $event) {
        $status = '';
        if ($today < $event->registration_start) {
          $status = $this->t('Upcoming');
        }
        elseif ($today >= $event->registration_start && $today <= $event->registration_end) {
          $status = $this->t('Registration Open');
        }
        else {
          $status = $this->t('Registration Closed');
        }

        $rows[$event->id] = [
          'event_name' => $event->event_name,
          'category' => $event->category,
          'registration_start' => $event->registration_start,
          'registration_end' => $event->registration_end,
          'event_date' => $event->event_date,
          'status' => $status,
        ];
      }

      $form['existing_events']['events_table'] = [
        '#type' => 'table',
        '#header' => $header,
        '#rows' => $rows,
        '#empty' => $this->t('No events configured yet.'),
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    $registration_start = $form_state->getValue('registration_start');
    $registration_end = $form_state->getValue('registration_end');
    $event_date = $form_state->getValue('event_date');

    // Validate registration end date is after start date.
    if ($registration_start && $registration_end && $registration_end < $registration_start) {
      $form_state->setErrorByName('registration_end', $this->t('Registration end date must be after or equal to the start date.'));
    }

    // Validate event date is after registration start.
    if ($registration_start && $event_date && $event_date < $registration_start) {
      $form_state->setErrorByName('event_date', $this->t('Event date must be after or equal to the registration start date.'));
    }

    // Validate event name is not empty after trimming.
    $event_name = trim($form_state->getValue('event_name'));
    if (empty($event_name)) {
      $form_state->setErrorByName('event_name', $this->t('Event name cannot be empty.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $data = [
      'event_name' => trim($form_state->getValue('event_name')),
      'category' => $form_state->getValue('category'),
      'registration_start' => $form_state->getValue('registration_start'),
      'registration_end' => $form_state->getValue('registration_end'),
      'event_date' => $form_state->getValue('event_date'),
    ];

    $event_id = $this->eventService->createEvent($data);

    if ($event_id) {
      $this->messenger->addStatus($this->t('Event "@name" has been created successfully.', [
        '@name' => $data['event_name'],
      ]));
    }
    else {
      $this->messenger->addError($this->t('There was an error creating the event. Please try again.'));
    }
  }

}
