<?php

declare(strict_types=1);

namespace Drupal\custom_event_registration\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\custom_event_registration\Service\EventService;
use Drupal\custom_event_registration\Service\RegistrationService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for filtering event registrations in admin listing.
 */
class RegistrationFilterForm extends FormBase {

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
   * Constructs a RegistrationFilterForm object.
   *
   * @param \Drupal\custom_event_registration\Service\EventService $eventService
   *   The event service.
   * @param \Drupal\custom_event_registration\Service\RegistrationService $registrationService
   *   The registration service.
   */
  public function __construct(EventService $eventService, RegistrationService $registrationService) {
    $this->eventService = $eventService;
    $this->registrationService = $registrationService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('custom_event_registration.event_service'),
      $container->get('custom_event_registration.registration_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'custom_event_registration_filter_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['#prefix'] = '<div id="registration-filter-form-wrapper">';
    $form['#suffix'] = '</div>';

    $form['filters'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Filter Registrations'),
    ];

    // Event Date dropdown.
    $event_dates = $this->eventService->getAllEventDates();
    $selected_date = $form_state->getValue('event_date') ?? '';

    $form['filters']['event_date'] = [
      '#type' => 'select',
      '#title' => $this->t('Event Date'),
      '#options' => ['' => $this->t('- All Dates -')] + $event_dates,
      '#default_value' => $selected_date,
      '#ajax' => [
        'callback' => '::updateEventNameAndResultsCallback',
        'wrapper' => 'filter-results-wrapper',
        'event' => 'change',
      ],
    ];

    // Event Name dropdown - filtered by selected date.
    $event_names = [];
    if (!empty($selected_date)) {
      $event_names = $this->eventService->getEventsByDate($selected_date);
    }
    else {
      // Show all events if no date selected.
      $event_names = $this->eventService->getAllEventsOptions();
    }

    $selected_event = $form_state->getValue('event_id') ?? '';

    $form['filters']['event_name_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'event-name-filter-wrapper'],
    ];

    $form['filters']['event_name_wrapper']['event_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Event Name'),
      '#options' => ['' => $this->t('- All Events -')] + $event_names,
      '#default_value' => $selected_event,
      '#ajax' => [
        'callback' => '::updateResultsCallback',
        'wrapper' => 'filter-results-wrapper',
        'event' => 'change',
      ],
    ];

    // Results wrapper.
    $form['results_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'filter-results-wrapper'],
    ];

    // Participant count.
    $count = 0;
    $event_name_display = '';

    if (!empty($selected_event)) {
      $count = $this->registrationService->getRegistrationCount((int) $selected_event);
      $event = $this->eventService->getEvent((int) $selected_event);
      $event_name_display = $event ? $event->event_name : '';
    }
    elseif (!empty($selected_date)) {
      $count = $this->registrationService->getRegistrationCountByDate($selected_date);
      $event_name_display = $this->t('Events on @date', ['@date' => $selected_date]);
    }

    $form['results_wrapper']['participant_count'] = [
      '#type' => 'markup',
      '#markup' => '<div class="participant-count"><strong>' .
        $this->t('Total Participants: @count', ['@count' => $count]) .
        '</strong>' .
        (!empty($event_name_display) ? ' (' . $event_name_display . ')' : '') .
        '</div>',
      '#prefix' => '<div id="participant-count-wrapper">',
      '#suffix' => '</div>',
    ];

    // Registrations table.
    $registrations = $this->getFilteredRegistrations($selected_date, $selected_event);
    $form['results_wrapper']['registrations_table'] = $this->buildRegistrationsTable($registrations);

    // Export CSV button.
    $export_url = Url::fromRoute('custom_event_registration.csv_export', [], [
      'query' => [
        'event_id' => $selected_event ?: NULL,
        'event_date' => $selected_date ?: NULL,
      ],
    ]);

    $form['results_wrapper']['export_csv'] = [
      '#type' => 'link',
      '#title' => $this->t('Export to CSV'),
      '#url' => $export_url,
      '#attributes' => [
        'class' => ['button', 'button--primary'],
        'id' => 'export-csv-button',
      ],
    ];

    return $form;
  }

  /**
   * Builds the registrations table.
   *
   * @param array $registrations
   *   The registrations data.
   *
   * @return array
   *   The table render array.
   */
  protected function buildRegistrationsTable(array $registrations): array {
    $header = [
      'name' => $this->t('Name'),
      'email' => $this->t('Email'),
      'event_date' => $this->t('Event Date'),
      'college_name' => $this->t('College Name'),
      'department' => $this->t('Department'),
      'submission_date' => $this->t('Submission Date'),
    ];

    $rows = [];
    foreach ($registrations as $registration) {
      $rows[] = [
        'name' => ['data' => ['#markup' => htmlspecialchars($registration->full_name, ENT_QUOTES, 'UTF-8')]],
        'email' => ['data' => ['#markup' => htmlspecialchars($registration->email, ENT_QUOTES, 'UTF-8')]],
        'event_date' => $registration->event_date,
        'college_name' => ['data' => ['#markup' => htmlspecialchars($registration->college_name, ENT_QUOTES, 'UTF-8')]],
        'department' => ['data' => ['#markup' => htmlspecialchars($registration->department, ENT_QUOTES, 'UTF-8')]],
        'submission_date' => date('Y-m-d H:i:s', (int) $registration->created),
      ];
    }

    return [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No registrations found.'),
      '#attributes' => ['id' => 'registrations-table'],
      '#prefix' => '<div id="registrations-table-wrapper">',
      '#suffix' => '</div>',
    ];
  }

  /**
   * Gets filtered registrations.
   *
   * @param string|null $event_date
   *   The event date filter.
   * @param string|null $event_id
   *   The event ID filter.
   *
   * @return array
   *   The filtered registrations.
   */
  protected function getFilteredRegistrations(?string $event_date, ?string $event_id): array {
    $event_date = !empty($event_date) ? $event_date : NULL;
    $event_id_int = !empty($event_id) ? (int) $event_id : NULL;

    return $this->registrationService->getFilteredRegistrations($event_date, $event_id_int);
  }

  /**
   * AJAX callback to update event name dropdown and results.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The AJAX response.
   */
  public function updateEventNameAndResultsCallback(array &$form, FormStateInterface $form_state): AjaxResponse {
    $response = new AjaxResponse();

    // Update event name dropdown.
    $response->addCommand(new ReplaceCommand(
      '#event-name-filter-wrapper',
      $form['filters']['event_name_wrapper']
    ));

    // Update results wrapper.
    $response->addCommand(new ReplaceCommand(
      '#filter-results-wrapper',
      $form['results_wrapper']
    ));

    return $response;
  }

  /**
   * AJAX callback to update results only.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The AJAX response.
   */
  public function updateResultsCallback(array &$form, FormStateInterface $form_state): AjaxResponse {
    $response = new AjaxResponse();

    // Update results wrapper.
    $response->addCommand(new ReplaceCommand(
      '#filter-results-wrapper',
      $form['results_wrapper']
    ));

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    // Form uses AJAX, no traditional submit needed.
  }

}
