<?php

namespace Amplify\System\Factories;

use Amplify\System\Backend\Models\Event;
use Amplify\System\Cms\Jobs\SendFormResponse;
use Amplify\System\Jobs\CatalogChangedJob;
use Amplify\System\Jobs\ContactAccountRequestAcceptedJob;
use Amplify\System\Jobs\ContactAccountRequestReceivedJob;
use Amplify\System\Jobs\ContactAccountRequestVerificationJob;
use Amplify\System\Jobs\CreateOrderFromQuotation;
use Amplify\System\Jobs\CustomerPartNumberDeletedJob;
use Amplify\System\Jobs\CustomerRegistrationReportGeneratedJob;
use Amplify\System\Jobs\CustomProduct\CoilQuoteRequestJob;
use Amplify\System\Jobs\DraftOrderReceivedJob;
use Amplify\System\Jobs\ModelOrSerialNumberResearchJob;
use Amplify\System\Jobs\OrderNotesUpdatedJob;
use Amplify\System\Jobs\OrderReceivedJob;
use Amplify\System\Jobs\OrderRequestApprovedJob;
use Amplify\System\Jobs\OrderRequestRejectedJob;
use Amplify\System\Jobs\OrderRuleNotifyJob;
use Amplify\System\Jobs\OrderWaitingApprovalJob;
use Amplify\System\Jobs\QuotationReceivedJob;
use Amplify\System\Jobs\RegistrationRequestAcceptedJob;
use Amplify\System\Jobs\RegistrationRequestReceivedJob;
use Amplify\System\Jobs\ResetPassword;
use Amplify\System\Jobs\TicketCreatedNotifyJob;
use Illuminate\Support\Facades\Log;

class NotificationFactory
{
    /**
     * @param array|string $e_code
     * @param mixed $args
     * @return void
     */
    public static function call($e_code, $args)
    {
        if (is_array($e_code)) {
            foreach ($e_code as $code) {
                self::dispatchJob($code, $args);
            }
        } else {
            self::dispatchJob($e_code, $args);
        }
    }

    /**
     * @param mixed $args
     * @return void
     */
    public static function callIf(bool $condition, $event_code, $args)
    {
        if ($condition) {
            self::call($event_code, $args);
        }
    }

    /**
     * @return void
     */
    private static function dispatchJob(string $event_code, $args)
    {
        switch ($event_code) {
            case Event::REGISTRATION_REQUEST_RECEIVED:
                RegistrationRequestReceivedJob::dispatch($event_code, $args);
                break;

            case Event::REGISTRATION_REQUEST_ACCEPTED:
                RegistrationRequestAcceptedJob::dispatch($event_code, $args['customer_id']);
                break;

            case Event::CONTACT_ACCOUNT_REQUEST_RECEIVED:
                ContactAccountRequestReceivedJob::dispatch($event_code, $args['contact_id']);
                break;

            case Event::CONTACT_ACCOUNT_REQUEST_VERIFICATION:
                ContactAccountRequestVerificationJob::dispatch($event_code, $args['contact_id']);
                break;

            case Event::CONTACT_ACCOUNT_REQUEST_ACCEPTED:
                ContactAccountRequestAcceptedJob::dispatch($event_code, $args['contact_id']);
                break;

            case Event::ORDER_NOTES_UPDATED:
                OrderNotesUpdatedJob::dispatch($event_code, $args['customer_order_note_id']);
                break;

            case Event::ORDER_RECEIVED:
                OrderReceivedJob::dispatch(
                    $event_code,
                    $args['order_id'],
                    $args['customer_id'],
                    $args['guest_customer_email'],
                    $args['guest_customer_name'],
                    $args['contact_id'],
                );
                break;

            case Event::ORDER_ACCEPTED:
                //                OrderReceivedJob::dispatch($event_code, $args['order_id'], $args['customer_id']);
                break;

            case Event::ORDER_REJECTED:
                //                OrderReceivedJob::dispatch($event_code, $args['order_id'], $args['customer_id']);
                break;

            case Event::DRAFT_RECEIVED:
                DraftOrderReceivedJob::dispatch($event_code, $args['order_id'], $args['customer_id']);
                break;

            case Event::QUOTATION_RECEIVED:
                QuotationReceivedJob::dispatch(
                    $event_code,
                    $args['order_id'],
                    $args['customer_id'],
                    $args['guest_customer_email'],
                    $args['guest_customer_name'],
                    $args['contact_id'],
                );
                break;

            case Event::CATALOG_CHANGED:
                CatalogChangedJob::dispatch($event_code, $args);
                break;

            case Event::PAYMENT_SUCCESSFUL:
                //                OrderReceivedJob::dispatch($event_code, $args['order_id'], $args['customer_id']);
                break;

            case Event::PAYMENT_FAILED:
                //                OrderReceivedJob::dispatch($event_code, $args['order_id'], $args['customer_id']);
                break;

            case Event::RESET_PASSWORD:
                ResetPassword::dispatch($event_code, $args);
                break;

            case Event::FORM_SUBMITTED:
                SendFormResponse::dispatch($args['event_code'], $args['value']);
                break;

            case Event::ORDER_RULE_CHECKED:
                OrderRuleNotifyJob::dispatch($event_code, $args);
                break;

            case Event::ORDER_WAITING_APPROVAL:
                OrderWaitingApprovalJob::dispatch($event_code, $args);
                break;

            case Event::ORDER_REQUEST_APPROVED:
                OrderRequestApprovedJob::dispatch($event_code, $args);
                break;

            case Event::ORDER_REQUEST_REJECTED:
                OrderRequestRejectedJob::dispatch($event_code, $args);
                break;

            case Event::CUSTOM_COIL_ORDER_RECEIVED:
                CoilQuoteRequestJob::dispatch($event_code, $args);
                break;

            case Event::MODEL_SERIAL_NUMBER_RESEARCH:
                ModelOrSerialNumberResearchJob::dispatch($event_code, $args);
                break;

            case Event::CREATE_ORDER_FROM_QUOTATION:
                CreateOrderFromQuotation::dispatch($event_code, $args);
                break;

            case Event::CUSTOMER_PART_NUMBER_DELETED:
                CustomerPartNumberDeletedJob::dispatch($event_code, $args);
                break;

            case Event::CUSTOMER_REGISTRATION_REPORT_GENERATED:
                CustomerRegistrationReportGeneratedJob::dispatch($event_code, $args);
                break;

            case Event::WISHLIST_PRODUCT_RESTOCKED:
                if (class_exists(\Amplify\Wishlist\Jobs\WishlistProductRestockedJob::class)) {
                    \Amplify\Wishlist\Jobs\WishlistProductRestockedJob::dispatch($event_code, $args);
                } else {
                    Log::error('Wishlist Plugin Not Installed');
                }
                break;

            case Event::TICKET_CREATED:
                TicketCreatedNotifyJob::dispatch($event_code, $args['ticket']);
                break;
        }
    }
}
