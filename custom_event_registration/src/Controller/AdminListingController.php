<?php

declare(strict_types=1);

namespace Drupal\custom_event_registration\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\custom_event_registration\Service\EventService;
use Drupal\custom_event_registration\Service\RegistrationService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Controller for admin listing and CSV export functionality.
 */
class AdminListingController extends ControllerBase {

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
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected FormBuilderInterface $formBuilder;

  /**
   * Constructs an AdminListingController object.
   *
   * @param \Drupal\custom_event_registration\Service\EventService $eventService
   *   The event service.
   * @param \Drupal\custom_event_registration\Service\RegistrationService $registrationService
   *   The registration service.
   * @param \Drupal\Core\Form\FormBuilderInterface $formBuilder
   *   The form builder.
   */
  public function __construct(
    EventService $eventService,
    RegistrationService $registrationService,
    FormBuilderInterface $formBuilder
  ) {
    $this->eventService = $eventService;
    $this->registrationService = $registrationService;
    $this->formBuilder = $formBuilder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('custom_event_registration.event_service'),
      $container->get('custom_event_registration.registration_service'),
      $container->get('form_builder')
    );
  }

  /**
   * Displays the admin listing page.
   *
   * @return array
   *   A render array for the listing page.
   */
  public function listing(): array {
    // Build the filter form.
    $filter_form = $this->formBuilder->getForm('Drupal\custom_event_registration\Form\RegistrationFilterForm');

    return [
      '#type' => 'container',
      '#attributes' => ['class' => ['event-registration-admin-listing']],
      'filter_form' => $filter_form,
      '#attached' => [
        'library' => ['core/drupal.ajax'],
      ],
    ];
  }

  /**
   * Exports registrations to CSV.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\StreamedResponse
   *   The CSV file response.
   */
  public function exportCsv(Request $request): StreamedResponse {
    $event_id = $request->query->get('event_id');
    $event_date = $request->query->get('event_date');

    // Get registrations based on filters.
    $event_id_int = !empty($event_id) ? (int) $event_id : NULL;
    $registrations = $this->registrationService->getRegistrationsForExport($event_id_int);

    // If event_date is provided but no event_id, filter by date.
    if (empty($event_id) && !empty($event_date)) {
      $registrations = array_filter($registrations, function ($registration) use ($event_date) {
        return $registration->event_date === $event_date;
      });
    }

    // Generate filename.
    $filename = 'event_registrations_' . date('Y-m-d_His') . '.csv';

    $response = new StreamedResponse(function () use ($registrations) {
      $handle = fopen('php://output', 'w');

      // Add BOM for UTF-8.
      fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

      // Write CSV header.
      fputcsv($handle, [
        'ID',
        'Full Name',
        'Email',
        'College Name',
        'Department',
        'Event Name',
        'Event Date',
        'Category',
        'Registration Date',
      ]);

      // Write data rows.
      foreach ($registrations as $registration) {
        fputcsv($handle, [
          $registration->id,
          $registration->full_name,
          $registration->email,
          $registration->college_name,
          $registration->department,
          $registration->event_name,
          $registration->event_date,
          $registration->category,
          date('Y-m-d H:i:s', $registration->created),
        ]);
      }

      fclose($handle);
    });

    $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
    $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
    $response->headers->set('Cache-Control', 'private, no-cache, no-store, must-revalidate');
    $response->headers->set('Pragma', 'no-cache');
    $response->headers->set('Expires', '0');

    return $response;
  }

  /**
   * AJAX callback to get events by category.
   *
   * @param string $category
   *   The category to filter by.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response with event dates.
   */
  public function getEventsByCategory(string $category): JsonResponse {
    $category = urldecode($category);

    $event_dates = $this->eventService->getEventDatesByCategory($category);

    return new JsonResponse([
      'success' => TRUE,
      'event_dates' => $event_dates,
    ]);
  }

  /**
   * AJAX callback to get events by date (for admin listing).
   *
   * @param string $date
   *   The date to filter by.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response with events.
   */
  public function getEventsByDate(string $date): JsonResponse {
    $date = urldecode($date);

    $events = $this->eventService->getEventsByDate($date);

    return new JsonResponse([
      'success' => TRUE,
      'events' => $events,
    ]);
  }

  /**
   * AJAX callback to get registration data.
   *
   * @param int $event_id
   *   The event ID.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response with registration data.
   */
  public function getRegistrationData(int $event_id): JsonResponse {
    $registrations = $this->registrationService->getRegistrationsByEventId($event_id);
    $count = $this->registrationService->getRegistrationCount($event_id);
    $event = $this->eventService->getEvent($event_id);

    $data = [];
    foreach ($registrations as $registration) {
      $data[] = [
        'id' => $registration->id,
        'full_name' => $registration->full_name,
        'email' => $registration->email,
        'college_name' => $registration->college_name,
        'department' => $registration->department,
        'event_date' => $registration->event_date,
        'created' => date('Y-m-d H:i:s', $registration->created),
      ];
    }

    return new JsonResponse([
      'success' => TRUE,
      'count' => $count,
      'event_name' => $event ? $event->event_name : '',
      'registrations' => $data,
    ]);
  }

}
