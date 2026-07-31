<?php

namespace Ometra\HelaSdk\Clients;

use Illuminate\Http\Client\Response;
use Ometra\HelaSdk\Dtos\ApiResponseDto;
use Ometra\HelaSdk\Dtos\ActivityDto;
use Ometra\HelaSdk\Dtos\AuthTokenDto;
use Ometra\HelaSdk\Dtos\DtoCollection;
use Ometra\HelaSdk\Dtos\DocumentDto;
use Ometra\HelaSdk\Dtos\GenericDto;
use Ometra\HelaSdk\Dtos\InvoiceDto;
use Ometra\HelaSdk\Dtos\OfferDto;
use Ometra\HelaSdk\Dtos\NotificationPreferencesDto;
use Ometra\HelaSdk\Dtos\OrderDto;
use Ometra\HelaSdk\Dtos\PortabilityDto;
use Ometra\HelaSdk\Dtos\ServiceBulkOperationDto;
use Ometra\HelaSdk\Dtos\ServiceDto;
use Ometra\HelaSdk\Dtos\ServiceGroupDto;
use Ometra\HelaSdk\Dtos\ScheduledTopupDto;
use Ometra\HelaSdk\Dtos\TaxProfileDto;
use Ometra\HelaSdk\Dtos\UserProfileDto;
use Ometra\HelaSdk\Dtos\WalletBalanceDto;
use Ometra\HelaSdk\Dtos\WalletTransactionDto;

class AusterClientsApiClient extends HelaAppClient
{
    public function token(): ?string
    {
        $token = parent::token();

        if ($token === null) {
            return null;
        }

        if (preg_match('/^[A-Z]+-(.+)$/', $token, $matches) === 1) {
            $token = $matches[1];
        }

        return $this->tokenType() . '-' . $token;
    }

    public function tokenType(): string
    {
        $type = $this->config()['token_type'] ?? 'API';

        return is_string($type) && $type !== '' ? strtoupper($type) : 'API';
    }

    /**
     * @param array<string, mixed> $data
     */
    public function login(array $data): AuthTokenDto
    {
        return $this->dto($this->postWithoutToken('/clients-api/authentication/login', $data), AuthTokenDto::class);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function signup(array $data): GenericDto
    {
        return $this->dto($this->postWithoutToken('/clients-api/authentication/signup', $data), GenericDto::class);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function socialLogin(array $data): AuthTokenDto
    {
        return $this->dto($this->postWithoutToken('/clients-api/authentication/social-login', $data), AuthTokenDto::class);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function requestPasswordReset(array $data): ApiResponseDto
    {
        return $this->apiResponse($this->postWithoutToken('/clients-api/authentication/password/reset', $data));
    }

    public function validatePasswordResetToken(string $token): GenericDto
    {
        return $this->dto($this->httpWithoutToken()->get('/clients-api/authentication/password/reset/' . $token), GenericDto::class);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function resetPassword(string $token, array $data): ApiResponseDto
    {
        return $this->apiResponse($this->postWithoutToken('/clients-api/authentication/password/reset/' . $token, $data));
    }

    public function logout(): ApiResponseDto
    {
        return $this->apiResponse($this->get('/clients-api/authentication/logout'));
    }

    public function logoutAll(): ApiResponseDto
    {
        return $this->apiResponse($this->get('/clients-api/authentication/logout-all'));
    }

    public function clientProfile(): UserProfileDto
    {
        return $this->dto($this->get('/clients-api/client-profile'), UserProfileDto::class);
    }

    public function userProfile(): UserProfileDto
    {
        return $this->dto($this->get('/clients-api/user-profile'), UserProfileDto::class);
    }

    public function getNotificationPreferences(): NotificationPreferencesDto
    {
        return $this->dto(
            $this->get('/clients-api/user-profile/notification-preferences'),
            NotificationPreferencesDto::class,
        );
    }

    /** @param list<array{notification_key: string, channels: list<string>}> $preferences */
    public function updateNotificationPreferences(array $preferences): NotificationPreferencesDto
    {
        return $this->dto(
            $this->put('/clients-api/user-profile/notification-preferences', ['preferences' => $preferences]),
            NotificationPreferencesDto::class,
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    public function heartbeat(array $data): ApiResponseDto
    {
        return $this->apiResponse($this->post('/clients-api/monitoring/heartbeat', $data));
    }

    /**
     * @param array<string, mixed> $data
     */
    public function createInstanceUser(array $data): UserProfileDto
    {
        return $this->dto($this->post('/clients-api/instances/create/user', $data), UserProfileDto::class);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateInstanceUserByEmail(string $email, array $data): UserProfileDto
    {
        return $this->dto(
            $this->post('/clients-api/instances/update/user/' . rawurlencode($email), $data),
            UserProfileDto::class,
        );
    }

    public function deleteInstanceUserByEmail(string $email): ApiResponseDto
    {
        return $this->apiResponse($this->delete('/clients-api/instances/delete/user/' . rawurlencode($email)));
    }

    /**
     * @param array<string, mixed> $query
     *
     * @return DtoCollection<GenericDto>
     */
    public function simCards(
        array $query = [],
        string|int|float|null $filter = null,
        ?string $product = null,
        ?string $type = null,
        string|int|bool|null $status = null,
        ?bool $paginate = null,
        ?int $page = null,
        ?int $perPage = null,
    ): DtoCollection {
        return $this->dtoCollection(
            $this->get('/clients-api/sim-cards', $this->mergeQuery($query, compact(
                'filter',
                'product',
                'type',
                'status',
                'paginate',
                'page',
                'perPage',
            ))),
            GenericDto::class,
        );
    }

    /**
     * @param array<string, mixed> $query
     */
    public function balance(array $query = []): GenericDto
    {
        return $this->dto($this->get('/clients-api/accounting/balance', $query), GenericDto::class);
    }

    /**
     * @param array<string, mixed> $query
     *
     * @return DtoCollection<InvoiceDto>
     */
    public function invoices(
        array $query = [],
        string|int|float|null $filter = null,
        int|string|null $year = null,
        int|string|null $month = null,
        ?bool $paginate = null,
        ?int $page = null,
        ?int $perPage = null,
    ): DtoCollection {
        return $this->dtoCollection(
            $this->get('/clients-api/accounting/invoices', $this->mergeQuery($query, compact(
                'filter',
                'year',
                'month',
                'paginate',
                'page',
                'perPage',
            ))),
            InvoiceDto::class,
        );
    }

    public function invoice(int|string $invoiceId): InvoiceDto
    {
        return $this->dto($this->get('/clients-api/accounting/invoices/' . $invoiceId), InvoiceDto::class);
    }

    public function downloadInvoice(int|string $invoiceId): Response
    {
        return $this->get('/clients-api/accounting/invoices/download/' . $invoiceId);
    }

    /**
     * @param array<string, mixed> $query
     *
     * @return DtoCollection<OfferDto>
     */
    public function catalogOffers(
        array $query = [],
        string|int|float|null $filter = null,
        string|int|bool|null $status = null,
        ?string $product = null,
        array|string|null $serviceType = null,
        ?bool $allowsNewLineActivation = null,
    ): DtoCollection {
        return $this->dtoCollection(
            $this->get('/clients-api/catalogs/offers', $this->mergeQuery($query, compact(
                'filter',
                'status',
                'product',
                'serviceType',
                'allowsNewLineActivation',
            ))),
            OfferDto::class,
        );
    }

    /**
     * @return DtoCollection<GenericDto>
     */
    public function paymentMethods(): DtoCollection
    {
        return $this->dtoCollection($this->get('/clients-api/catalogs/payment-methods'), GenericDto::class);
    }

    public function referralProgram(): GenericDto
    {
        return $this->dto($this->get('/clients-api/catalogs/referral-program'), GenericDto::class);
    }

    /**
     * @param array<string, mixed> $query
     *
     * @return DtoCollection<GenericDto>
     */
    public function cfdi(
        array $query = [],
        string|int|float|null $filter = null,
        int|string|null $year = null,
        int|string|null $month = null,
        ?bool $paginate = null,
        ?int $page = null,
        ?int $perPage = null,
    ): DtoCollection {
        return $this->dtoCollection(
            $this->get('/clients-api/cfdi', $this->mergeQuery($query, compact(
                'filter',
                'year',
                'month',
                'paginate',
                'page',
                'perPage',
            ))),
            GenericDto::class,
        );
    }

    /**
     * @return DtoCollection<OrderDto>
     */
    public function cfdiOrders(): DtoCollection
    {
        return $this->dtoCollection($this->get('/clients-api/cfdi/orders'), OrderDto::class);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function requestCfdi(array $data): ApiResponseDto
    {
        return $this->apiResponse($this->post('/clients-api/cfdi/request', $data));
    }

    public function downloadCfdi(string $uid, string $format): Response
    {
        return $this->get('/clients-api/cfdi/download/' . $uid . '/' . $format);
    }

    /**
     * @param array<string, mixed> $query
     *
     * @return DtoCollection<OrderDto>
     */
    public function orders(
        array $query = [],
        string|int|float|null $filter = null,
        ?string $type = null,
        ?string $dateFrom = null,
        ?string $dateTo = null,
        ?bool $paginate = null,
        ?int $page = null,
        ?int $perPage = null,
    ): DtoCollection {
        return $this->dtoCollection(
            $this->get('/clients-api/orders', $this->mergeQuery($query, compact(
                'filter',
                'type',
                'dateFrom',
                'dateTo',
                'paginate',
                'page',
                'perPage',
            ))),
            OrderDto::class,
        );
    }

    public function order(int|string $orderId): OrderDto
    {
        return $this->dto($this->get('/clients-api/orders/' . $orderId), OrderDto::class);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function createOrder(array $data): OrderDto
    {
        return $this->dto($this->post('/clients-api/orders', $data), OrderDto::class);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function addOrderPayment(int|string $orderId, array $data): ApiResponseDto
    {
        return $this->apiResponse($this->post('/clients-api/orders/' . $orderId . '/payments', $data));
    }

    /** @param array<string, mixed> $data */
    public function cancelOrder(int|string $orderId, array $data = []): ApiResponseDto
    {
        return $this->apiResponse($this->delete('/clients-api/orders/' . $orderId, $data));
    }

    /** @param array<string, mixed> $query @return DtoCollection<ActivityDto> */
    public function activity(array $query = []): DtoCollection
    {
        return $this->dtoCollection($this->get('/clients-api/activity', $query), ActivityDto::class);
    }

    /** @return DtoCollection<ScheduledTopupDto> */
    public function scheduledTopups(): DtoCollection
    {
        return $this->dtoCollection($this->get('/clients-api/scheduled-topups'), ScheduledTopupDto::class, 'schedules');
    }

    /** @param array<string, mixed> $data */
    public function previewScheduledTopup(array $data): GenericDto
    {
        return $this->dto($this->post('/clients-api/scheduled-topups/preview', $data), GenericDto::class, 'preview');
    }

    /** @param array<string, mixed> $data */
    public function createScheduledTopup(array $data): ScheduledTopupDto
    {
        return $this->dto($this->post('/clients-api/scheduled-topups', $data), ScheduledTopupDto::class, 'schedule');
    }

    /** @param array<string, mixed> $data */
    public function updateScheduledTopup(int|string $scheduleId, array $data): ScheduledTopupDto
    {
        return $this->dto($this->put('/clients-api/scheduled-topups/' . $scheduleId, $data), ScheduledTopupDto::class, 'schedule');
    }

    public function pauseScheduledTopup(int|string $scheduleId): ScheduledTopupDto
    {
        return $this->dto($this->post('/clients-api/scheduled-topups/' . $scheduleId . '/pause'), ScheduledTopupDto::class, 'schedule');
    }

    public function resumeScheduledTopup(int|string $scheduleId): ScheduledTopupDto
    {
        return $this->dto($this->post('/clients-api/scheduled-topups/' . $scheduleId . '/resume'), ScheduledTopupDto::class, 'schedule');
    }

    public function cancelScheduledTopup(int|string $scheduleId): ScheduledTopupDto
    {
        return $this->dto($this->delete('/clients-api/scheduled-topups/' . $scheduleId), ScheduledTopupDto::class, 'schedule');
    }

    /**
     * @param array<string, mixed> $query
     *
     * @return DtoCollection<GenericDto>
     */
    public function portabilities(array $query = []): DtoCollection
    {
        return $this->dtoCollection($this->get('/clients-api/portability', $query), PortabilityDto::class);
    }

    public function portability(int|string $portabilityId): PortabilityDto
    {
        return $this->dto($this->get('/clients-api/portability/' . $portabilityId), PortabilityDto::class);
    }

    /**
     * @return DtoCollection<PortabilityDto>
     */
    public function portabilityTransitories(): DtoCollection
    {
        return $this->dtoCollection($this->get('/clients-api/portability/transitories'), PortabilityDto::class);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function validatePortability(array $data): GenericDto
    {
        return $this->dto($this->post('/clients-api/portability/validate', $data), GenericDto::class);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function requestPortability(array $data): GenericDto
    {
        return $this->dto($this->post('/clients-api/portability/request', $data), GenericDto::class);
    }

    public function deletePortability(int|string $portabilityId): ApiResponseDto
    {
        return $this->apiResponse($this->delete('/clients-api/portability/' . $portabilityId));
    }

    /**
     * @param array<string, mixed> $query
     *
     * @return DtoCollection<ServiceDto>
     */
    public function services(
        array $query = [],
        string|int|float|null $filter = null,
        array|string|null $product = null,
        array|string|null $serviceType = null,
        array|string|null $status = null,
        ?string $imei = null,
        int|string|null $groupId = null,
        ?bool $onlyActive = null,
        ?bool $paginate = null,
        ?int $page = null,
        ?int $perPage = null,
        ?string $sort = null,
        ?string $direction = null,
    ): DtoCollection {
        return $this->dtoCollection(
            $this->get('/clients-api/services', $this->mergeQuery($query, compact(
                'filter',
                'product',
                'serviceType',
                'status',
                'imei',
                'groupId',
                'onlyActive',
                'paginate',
                'page',
                'perPage',
                'sort',
                'direction',
            ))),
            ServiceDto::class,
        );
    }

    /**
     * @param array<string, mixed> $query
     *
     * @return DtoCollection<ServiceGroupDto>
     */
    public function serviceGroups(
        array $query = [],
        string|int|float|null $filter = null,
        ?bool $paginate = null,
        ?int $page = null,
        ?int $perPage = null,
    ): DtoCollection {
        return $this->dtoCollection(
            $this->get('/clients-api/service-groups', $this->mergeQuery($query, compact(
                'filter',
                'paginate',
                'page',
                'perPage',
            ))),
            ServiceGroupDto::class,
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    public function createServiceGroup(array $data): ServiceGroupDto
    {
        return $this->dto($this->post('/clients-api/service-groups', $data), ServiceGroupDto::class);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateServiceGroup(int|string $groupId, array $data): ServiceGroupDto
    {
        return $this->dto($this->patch('/clients-api/service-groups/' . $groupId, $data), ServiceGroupDto::class);
    }

    public function deleteServiceGroup(int|string $groupId): ApiResponseDto
    {
        return $this->apiResponse($this->delete('/clients-api/service-groups/' . $groupId));
    }

    /**
     * @param array<int, int|string> $serviceIds
     */
    public function syncServiceGroupServices(int|string $groupId, array $serviceIds): GenericDto
    {
        return $this->dto(
            $this->put('/clients-api/service-groups/' . $groupId . '/services', ['service_ids' => $serviceIds]),
            GenericDto::class,
        );
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function serviceBulkCapabilities(array $payload): GenericDto
    {
        return $this->dto($this->post('/clients-api/services/bulk-actions/capabilities', $payload), GenericDto::class);
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function previewServiceBulkAction(array $payload): GenericDto
    {
        return $this->dto($this->post('/clients-api/services/bulk-actions/preview', $payload), GenericDto::class);
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function storeServiceBulkAction(array $payload): ServiceBulkOperationDto
    {
        return $this->dto(
            $this->post('/clients-api/services/bulk-actions', $payload),
            ServiceBulkOperationDto::class,
            'operation',
        );
    }

    public function serviceBulkOperation(int|string $operationId): ServiceBulkOperationDto
    {
        return $this->dto(
            $this->get('/clients-api/services/bulk-operations/' . $operationId),
            ServiceBulkOperationDto::class,
            'operation',
        );
    }

    public function retryServiceBulkOperation(int|string $operationId): ServiceBulkOperationDto
    {
        return $this->dto(
            $this->post('/clients-api/services/bulk-operations/' . $operationId . '/retry'),
            ServiceBulkOperationDto::class,
            'operation',
        );
    }

    public function latestServiceBulkOperation(): ServiceBulkOperationDto
    {
        return $this->dto(
            $this->get('/clients-api/services/bulk-operations/latest'),
            ServiceBulkOperationDto::class,
            'operation',
        );
    }

    public function service(string $msisdn): ServiceDto
    {
        return $this->dto($this->get('/clients-api/services/' . $msisdn), ServiceDto::class);
    }

    public function serviceProfile(string $msisdn): GenericDto
    {
        return $this->dto($this->get('/clients-api/services/' . $msisdn . '/profile'), GenericDto::class);
    }

    /**
     * @return DtoCollection<GenericDto>
     */
    public function serviceBags(string $msisdn): DtoCollection
    {
        return $this->dtoCollection($this->get('/clients-api/services/' . $msisdn . '/bags'), GenericDto::class);
    }

    /**
     * @return DtoCollection<OfferDto>
     */
    public function replacementOptions(string $msisdn): DtoCollection
    {
        return $this->dtoCollection($this->get('/clients-api/services/' . $msisdn . '/replacement-options'), OfferDto::class);
    }

    /**
     * @return DtoCollection<OfferDto>
     */
    public function activateOptions(string $msisdn): DtoCollection
    {
        return $this->dtoCollection($this->get('/clients-api/services/' . $msisdn . '/activate-options'), OfferDto::class);
    }

    /**
     * @return DtoCollection<OfferDto>
     */
    public function topupOptions(string $msisdn): DtoCollection
    {
        return $this->dtoCollection($this->get('/clients-api/services/' . $msisdn . '/topup-options'), OfferDto::class);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function activateService(string $msisdn, array $data = []): OrderDto
    {
        return $this->dto($this->post('/clients-api/services/' . $msisdn . '/activate', $data), OrderDto::class);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function topupService(string $msisdn, array $data): OrderDto
    {
        return $this->dto($this->post('/clients-api/services/' . $msisdn . '/topup', $data), OrderDto::class);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function renewService(string $msisdn, array $data): OrderDto
    {
        return $this->dto($this->post('/clients-api/services/' . $msisdn . '/renew', $data), OrderDto::class);
    }

    /**
     * @return DtoCollection<OfferDto>
     */
    public function renewOptions(string $msisdn): DtoCollection
    {
        return $this->dtoCollection($this->get('/clients-api/services/' . $msisdn . '/renew-options'), OfferDto::class);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function replaceOffer(string $msisdn, array $data): ApiResponseDto
    {
        return $this->apiResponse($this->post('/clients-api/services/' . $msisdn . '/replace-offer', $data));
    }

    /**
     * @param array<string, mixed> $data
     */
    public function replaceSimCard(string $msisdn, array $data): ApiResponseDto
    {
        return $this->apiResponse($this->post('/clients-api/services/' . $msisdn . '/replace-sim-card', $data));
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateServiceName(string $msisdn, array $data): ApiResponseDto
    {
        return $this->apiResponse($this->post('/clients-api/services/' . $msisdn . '/update-name', $data));
    }

    public function suspendService(string $msisdn): ApiResponseDto
    {
        return $this->apiResponse($this->post('/clients-api/services/' . $msisdn . '/suspend'));
    }

    public function resumeService(string $msisdn): ApiResponseDto
    {
        return $this->apiResponse($this->post('/clients-api/services/' . $msisdn . '/resume'));
    }

    public function imeiLock(string $imei): ApiResponseDto
    {
        return $this->apiResponse($this->post('/clients-api/imei/' . $imei . '/lock'));
    }

    public function imeiUnlock(string $imei): ApiResponseDto
    {
        return $this->apiResponse($this->post('/clients-api/imei/' . $imei . '/unlock'));
    }

    /**
     * @param array<string, mixed> $query
     */
    public function walletBalance(array $query = []): WalletBalanceDto
    {
        return $this->dto($this->get('/clients-api/wallet', $query), WalletBalanceDto::class);
    }

    /**
     * Backwards-compatible alias for walletBalance().
     *
     * @param array<string, mixed> $query
     */
    public function wallet(array $query = []): WalletBalanceDto
    {
        return $this->walletBalance($query);
    }

    /**
     * @param array<string, mixed> $query
     *
     * @return DtoCollection<GenericDto>
     */
    public function walletTransactions(array $query = []): DtoCollection
    {
        return $this->dtoCollection($this->get('/clients-api/wallet/transactions', $query), WalletTransactionDto::class);
    }

    /**
     * @param array<string, mixed> $query
     *
     * @return DtoCollection<DocumentDto>
     */
    public function documents(array $query = []): DtoCollection
    {
        return $this->dtoCollection($this->get('/clients-api/documents', $query), DocumentDto::class);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function storeDocument(string $documentKey, array $data): DocumentDto
    {
        return $this->dto($this->postMultipart('/clients-api/documents/' . rawurlencode($documentKey), $data), DocumentDto::class);
    }

    /**
     * @return DtoCollection<DocumentDto>
     */
    public function documentVersions(string $documentKey): DtoCollection
    {
        return $this->dtoCollection(
            $this->get('/clients-api/documents/' . rawurlencode($documentKey) . '/versions'),
            DocumentDto::class,
        );
    }

    public function downloadDocument(string $documentKey, int|string $documentId): Response
    {
        return $this->get(
            '/clients-api/documents/' . rawurlencode($documentKey) . '/versions/' . $documentId . '/download',
        );
    }

    /**
     * @param array<string, mixed> $query
     *
     * @return DtoCollection<TaxProfileDto>
     */
    public function taxProfiles(array $query = []): DtoCollection
    {
        return $this->dtoCollection($this->get('/clients-api/tax-profiles', $query), TaxProfileDto::class);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function createTaxProfile(array $data): TaxProfileDto
    {
        return $this->dto($this->post('/clients-api/tax-profiles', $data), TaxProfileDto::class);
    }

    public function taxProfileCatalogs(): GenericDto
    {
        return $this->dto($this->get('/clients-api/tax-profiles/catalogs'), GenericDto::class);
    }

    public function taxProfile(string $uid): TaxProfileDto
    {
        return $this->dto($this->get('/clients-api/tax-profiles/' . rawurlencode($uid)), TaxProfileDto::class);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateTaxProfile(string $uid, array $data): TaxProfileDto
    {
        return $this->dto($this->put('/clients-api/tax-profiles/' . rawurlencode($uid), $data), TaxProfileDto::class);
    }

    public function deleteTaxProfile(string $uid): ApiResponseDto
    {
        return $this->apiResponse($this->delete('/clients-api/tax-profiles/' . rawurlencode($uid)));
    }

    /**
     * @param array<string, mixed> $query
     *
     * @return DtoCollection<UserProfileDto>
     */
    public function users(
        array $query = [],
        string|int|float|null $filter = null,
        ?bool $paginate = null,
        ?int $page = null,
        ?int $perPage = null,
    ): DtoCollection {
        return $this->dtoCollection(
            $this->get('/clients-api/users', $this->mergeQuery($query, compact(
                'filter',
                'paginate',
                'page',
                'perPage',
            ))),
            UserProfileDto::class,
        );
    }

    public function user(string $clientUserUri): UserProfileDto
    {
        return $this->dto($this->get('/clients-api/users/' . $clientUserUri), UserProfileDto::class);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function createUser(array $data): UserProfileDto
    {
        return $this->dto($this->post('/clients-api/users', $data), UserProfileDto::class);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateUser(string $clientUserUri, array $data): UserProfileDto
    {
        return $this->dto($this->put('/clients-api/users/' . $clientUserUri, $data), UserProfileDto::class);
    }

    public function deleteUser(string $clientUserUri): ApiResponseDto
    {
        return $this->apiResponse($this->delete('/clients-api/users/' . $clientUserUri));
    }
}
