<?php

namespace Amplify\System\Factories;

use Amplify\System\Cms\Jobs\SendFormResponse;
use App\Jobs\CatalogChangedJob;
use App\Jobs\ContactAccountRequestAcceptedJob;
use App\Jobs\ContactAccountRequestReceivedJob;
use App\Jobs\CreateOrderFromQuotation;
use App\Jobs\CustomProduct\CoilQuoteRequestJob;
use App\Jobs\DraftOrderReceivedJob;
use App\Jobs\ModelOrSerialNumberResearchJob;
use App\Jobs\OrderNotesUpdatedJob;
use App\Jobs\OrderReceivedJob;
use App\Jobs\OrderRequestApprovedJob;
use App\Jobs\OrderRequestRejectedJob;
use App\Jobs\OrderRuleNotifyJob;
use App\Jobs\OrderWaitingApprovalJob;
use App\Jobs\QuotationReceivedJob;
use App\Jobs\RegistrationRequestAcceptedJob;
use App\Jobs\RegistrationRequestReceivedJob;
use App\Jobs\ResetPassword;
use App\Models\Event;

class NotificationFactory
{
    /**
     * @param  array|string  $e_code
     * @param  mixed  $args
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
     * @param  mixed  $args
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
                RegistrationRequestReceivedJob::dispatch($event_code, $args['customer_id']);
                break;

            case Event::CONTACT_ACCOUNT_REQUEST_RECEIVED:
                ContactAccountRequestReceivedJob::dispatch($event_code, $args['contact_id']);
                break;

            case Event::CONTACT_ACCOUNT_REQUEST_ACCEPTED:
                ContactAccountRequestAcceptedJob::dispatch($event_code, $args['contact_id']);
                break;

            case Event::ORDER_NOTES_UPDATED:
                OrderNotesUpdatedJob::dispatch($event_code, $args['customer_order_note_id']);
                break;

            case Event::REGISTRATION_REQUEST_ACCEPTED:
                RegistrationRequestAcceptedJob::dispatch($event_code, $args['customer_id']);
                break;

            case Event::ORDER_RECEIVED:
                OrderReceivedJob::dispatch(
                    $event_code, $args['order_id'],
                    $args['customer_id'],
                    $args['guest_customer_email'],
                    $args['guest_customer_name'],
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
                QuotationReceivedJob::dispatch($event_code, $args['order_id'], $args['customer_id']);
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
        }
    }
}
