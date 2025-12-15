<?php

namespace Amplify\System\Services;

use Amplify\ErpApi\Wrappers\Customer as ErpCustomer;
use Amplify\System\Backend\Models\Contact;
use Amplify\System\Backend\Models\Customer;
use Amplify\System\Backend\Models\EventAction;
use Amplify\System\Jobs\DispatchEmailJob;
use Amplify\System\Ticket\Models\Ticket;
use Amplify\System\Ticket\Models\TicketDepartment;
use Amplify\System\Traits\OrderEmailFormatingTrait;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\URL;

class EmailService
{
    use OrderEmailFormatingTrait;

    private $replaceAbleKeys = ['email_content', 'subject'];

    public function sendOrderDetailsEmailToCustomer($email_action, $order, $customer, $guestCustomerEmail, $guestCustomerName, $contact = null): void
    {
        $email_data = $email_action->eventTemplate;

        /*
         *  Generate customer button url for either quotation details or order details
         */
        if (optional($email_data)->button_url === '__customer_order_details_url__') {
            $button_url = str_replace(
                '__customer_order_details_url__',
                route('frontend.orders.show', $order->erp_order_id),
                optional($email_data)->button_url
            );
        } elseif (optional($email_data)->button_url === '__customer_quotation_details_url__') {
            $button_url = str_replace(
                '__customer_quotation_details_url__',
                route('frontend.quotations.show', $order->erp_order_id),
                optional($email_data)->button_url
            );
        }

        /*
         * Preparing email data
         */
        $data = [
            'customer' => $customer,
            'order' => $order,
            'erp_order_id' => $order->erp_order_id,
            'subject' => optional($email_data)->subject,
            'email_content' => optional($email_data)->email_body,
            'show_button' => optional($email_data)->show_button === 1,
            'button_url' => !isset($button_url) ? '' : URL::to($button_url),
            'button_text' => optional($email_data)->button_text,
            'guest_customer_name' => $guestCustomerName,
            'is_customer_mail' => true,
            'body_width' => '90%'
        ];
        /*
         * Dispatch order  email job
         */

        $this->dispatchEmailJobs(
            $this->replaceMailContentProperty($data),
            $this->getRecipientsEmail($customer, $email_action, null, $guestCustomerEmail, $contact)
        );
    }

    public function sendFormResponseToTargets($email_action, array $values): void
    {
        $email_data = $email_action->eventTemplate;

        $data = $values;
        $data['subject'] = str_replace(array_keys($values), array_values($values), $email_data->subject);
        $data['email_content'] = str_replace(array_keys($values), array_values($values), $email_data->email_body);
        $this->dispatchEmailJobs($data, $this->getRecipientsEmail(null, $email_action));
    }

    public function sendDraftOrderDetailsEmailToCustomer($email_action, $order, $customer): void
    {
        $email_data = $email_action->eventTemplate;

        /*
         *  Generate customer button url for either quotation details or order details
         */
        if (optional($email_data)->button_url === '__customer_order_details_url__') {
            $button_url = str_replace(
                '__customer_order_details_url__',
                route('frontend.orders.show', $order->erp_order_id),
                optional($email_data)->button_url
            );
        } elseif (optional($email_data)->button_url === '__customer_quotation_details_url__') {
            $button_url = str_replace(
                '__customer_quotation_details_url__',
                route('frontend.quotations.show', $order->erp_order_id),
                optional($email_data)->button_url
            );
        }

        /*
         * Preparing email data
         */
        $data = [
            'customer' => $customer,
            'order' => $order,
            'subject' => optional($email_data)->subject,
            'email_content' => optional($email_data)->email_body,
            'show_button' => optional($email_data)->show_button === 1,
            'button_url' => !isset($button_url) ? '' : URL::to($button_url),
            'button_text' => optional($email_data)->button_text,
            'is_customer_mail' => true,
        ];
        /*
         * Dispatch order  email job
         */
        $this->dispatchEmailJobs(
            $this->replaceMailContentProperty($data),
            $this->getRecipientsEmail($customer, $email_action)
        );
    }

    public function sendOrderDetailsEmailToAdmin($email_action, $order, $customer): void
    {
        $email_data = $email_action->eventTemplate;

        /*
         *  Generate admin button url for either quotation details or order details
         */
        if (optional($email_data)->button_url === '__order_details_url__') {
            $button_url_admin = str_replace(
                '__order_details_url__',
                '/admin/order/' . $order->id . '/show',
                $email_data->button_url
            );
        } elseif (optional($email_data)->button_url === '__quotation_details_url__') {
            $button_url_admin = str_replace(
                '__quotation_details_url__',
                '/admin/quote/' . $order->id . '/show',
                $email_data->button_url
            );
        }

        /*
         * Preparing email data
         */
        $data = [
            'customer' => $customer,
            'order' => $order,
            'subject' => optional($email_data)->subject,
            'email_content' => optional($email_data)->email_body,
            'show_button' => optional($email_data)->show_button === 1,
            'button_url' => !isset($button_url_admin) ? '' : URL::to($button_url_admin),
            'button_text' => optional($email_data)->button_text,
            'is_customer_mail' => false,
        ];

        /*
         * Dispatch order submit email job
         */
        $this->dispatchEmailJobs(
            $this->replaceMailContentProperty($data),
            $this->getRecipientsEmail($customer, $email_action)
        );
    }

    public function sendCustomerPartNumberDeletedNotification($email_action, $args)
    {
        $email_data = $email_action->eventTemplate;

        $customer = $args['customer'];
        $product = $args['product'];

        $replacement = [
            '__customer_name__' => $customer->customer_name ?? '',
            '__customer_code__' => $customer->customer_code ?? '',
            '__product_code__' => $product->product_code ?? '',
            '__product_name__' => $product->product_name ?? '',
            '__customer_part_number__' => $args['customer_product_code'] ?? '',
        ];

        $data = [
            'customer' => $customer,
            'subject' => optional($email_data)->subject,
            'email_content' => str_replace(array_keys($replacement), array_values($replacement), optional($email_data)->email_body),
            'show_button' => optional($email_data)->show_button === 1,
            'button_url' => frontendSingleProductURL($product),
            'button_text' => optional($email_data)->button_text
        ];

        /*
         * Dispatch order submit email job
         */
        $this->dispatchEmailJobs(
            $this->replaceMailContentProperty($data),
            $this->getRecipientsEmail($args['customer'], $email_action)
        );
    }

    public function sendCustomerRegistrationReportNotification($email_action, $args)
    {
        $email_data = $email_action->eventTemplate;

        $attachments = $args['attachments'];

        unset($args['attachments']);

        $data = [
                'subject' => str_replace(array_keys($args), array_values($args), optional($email_data)->subject),
                'email_content' => str_replace(array_keys($args), array_values($args), optional($email_data)->email_body),
                'show_button' => false,
                'attachments' => $attachments
            ] + $args;

        /*
         * Dispatch order submit email job
         */
        $this->dispatchEmailJobs(
            $this->replaceMailContentProperty($data),
            $this->getRecipientsEmail(null, $email_action)
        );
    }

    protected function replaceOTPMailContentProperty($data): array
    {
        $data['email_content'] = str_replace(
            '__code__',
            $data['otp'],
            $data['email_content']
        );

        return $data;
    }

    protected function replaceMailContentProperty($data): array
    {
        foreach ($this->replaceAbleKeys as $key) {
            $data[$key] = str_replace(
                '__logged_in_user_name__',
                auth()->check() ? auth()->user()->name : (customer_check() ? customer(true)->name : 'Guest'),
                $data[$key]
            );

            if (isset($data['customer'])) {
                $customer = $data['customer'] instanceof Customer
                    ? $data['customer']
                    : Customer::with('addresses', 'industryClassification')->find($data['customer']->id);

                // Customer-related replacements
                $data[$key] = str_replace(
                    '__customer_name__',
                    $customer->customer_name ?? '',
                    $data[$key]
                );

                $data[$key] = str_replace(
                    '__customer_code__',
                    $customer->customer_code ?? '',
                    $data[$key]
                );

                $data[$key] = str_replace(
                    '__email_address__',
                    $customer->email ?? '',
                    $data[$key]
                );

                $data[$key] = str_replace(
                    '__phone_no__',
                    $customer->phone ?? '',
                    $data[$key]
                );

                $data[$key] = str_replace(
                    '__industry_classification__',
                    $customer->industryClassification->name ?? '',
                    $data[$key]
                );

                // Address-related replacements (using the first address as an example)
                $address = $customer->addresses->first();

                if ($address) {
                    $data[$key] = str_replace(
                        '__address_name__',
                        $address->address_name ?? '',
                        $data[$key]
                    );

                    $data[$key] = str_replace(
                        '__address_1__',
                        $address->address_1 ?? '',
                        $data[$key]
                    );

                    $data[$key] = str_replace(
                        '__country_code__',
                        $address->country_code ?? '',
                        $data[$key]
                    );

                    $data[$key] = str_replace(
                        '__city__',
                        $address->city ?? '',
                        $data[$key]
                    );

                    $data[$key] = str_replace(
                        '__state__',
                        $address->state ?? '',
                        $data[$key]
                    );

                    $data[$key] = str_replace(
                        '__zip_code__',
                        $address->zip_code ?? '',
                        $data[$key]
                    );
                }
            }

            if (isset($data['contact'])) {
                $data[$key] = str_replace(
                    '__account_number__',
                    $data['contact']->customer_code,
                    $data[$key]
                );

                $data[$key] = str_replace(
                    '__email_address__',
                    $data['contact']->email,
                    $data[$key]
                );

                $data[$key] = str_replace(
                    '__full_name__',
                    $data['contact']->name,
                    $data[$key]
                );
            }

            if (isset($data['otp'])) {
                $data[$key] = str_replace(
                    '__otp__',
                    $data['otp'],
                    $data[$key]
                );
            }

            if (isset($data['order'])) {
                $data = $this->replaceContentsForOrder($data, $key);
            }

            if (isset($data['customer_order_rule_track'])) {
                $data[$key] = str_replace(
                    '__approver_name__',
                    $data['customer'] instanceof Customer ? $data['customer']->customer_name : $data['customer']->name,
                    $data[$key]
                );

                $data[$key] = str_replace(
                    '__contact_name__',
                    $data['customer_order_rule_track']?->customerOrder->contact->name ?? '',
                    $data[$key]
                );

                $data[$key] = str_replace(
                    '__web_order_number__',
                    $data['customer_order_rule_track']?->customerOrder->web_order_number ?? '',
                    $data[$key]
                );

                $data[$key] = str_replace(
                    '__status_notes__',
                    $data['customer_order_rule_track']->notes,
                    $data[$key]
                );
            }

            if (isset($data['coil_data'])) {
                // Contact info.
                $data[$key] = str_replace(
                    '__contact_name__',
                    $data['coil_data']['contact_name'],
                    $data[$key]
                );
                $data[$key] = str_replace(
                    '__method_of_contact__',
                    $data['coil_data']['method_of_contact'],
                    $data[$key]
                );
                $data[$key] = str_replace(
                    '__company_name__',
                    $data['coil_data']['company_name'],
                    $data[$key]
                );
                $data[$key] = str_replace(
                    '__country__',
                    $data['coil_data']['country'],
                    $data[$key]
                );
                $data[$key] = str_replace(
                    '__city__',
                    $data['coil_data']['city'],
                    $data[$key]
                );
                $data[$key] = str_replace(
                    '__state__',
                    $data['coil_data']['state'],
                    $data[$key]
                );
                $data[$key] = str_replace(
                    '__zipcode__',
                    $data['coil_data']['zipcode'],
                    $data[$key]
                );
                $data[$key] = str_replace(
                    '__address__',
                    $data['coil_data']['address'],
                    $data[$key]
                );

                // Measurement info.
                $data[$key] = str_replace(
                    '__finned_width__',
                    $data['coil_data']['measurement_one_display'],
                    $data[$key]
                );
                $data[$key] = str_replace(
                    '__back_flange_length__',
                    $data['coil_data']['measurement_two_display'],
                    $data[$key]
                );
                $data[$key] = str_replace(
                    '__finned_height__',
                    $data['coil_data']['measurement_three_display'],
                    $data[$key]
                );
                $data[$key] = str_replace(
                    '__front_flange_length__',
                    $data['coil_data']['measurement_four_display'],
                    $data[$key]
                );
                $data[$key] = str_replace(
                    '__finned_length__',
                    $data['coil_data']['measurement_five_display'],
                    $data[$key]
                );
                $data[$key] = str_replace(
                    '__casing_height__',
                    $data['coil_data']['measurement_six_display'],
                    $data[$key]
                );
                $data[$key] = str_replace(
                    '__casing_width__',
                    $data['coil_data']['measurement_seven_display'],
                    $data[$key]
                );
                $data[$key] = str_replace(
                    '__coil_is_coated__',
                    $data['coil_data']['coil_is_coated'],
                    $data[$key]
                );
                $data[$key] = str_replace(
                    '__copper_tube__',
                    $data['coil_data']['copper_tube'],
                    $data[$key]
                );
                $data[$key] = str_replace(
                    '__number_of_fins_per_inc__',
                    $data['coil_data']['number_of_fins_per_inc'],
                    $data[$key]
                );
                $data[$key] = str_replace(
                    '__number_of_tubes__',
                    $data['coil_data']['number_of_tubes'],
                    $data[$key]
                );

                // Requested Qty and Notes.
                $data[$key] = str_replace(
                    '__requested_quantity__',
                    $data['coil_data']['qty'],
                    $data[$key]
                );
                $data[$key] = str_replace(
                    '__notes__',
                    $data['coil_data']['notes'],
                    $data[$key]
                );
            }

            if (isset($data['research_data'])) {
                // Contact info.
                $data[$key] = str_replace(
                    '__manufacturer_name__',
                    $data['research_data']['manufacturer_name'],
                    $data[$key]
                );
                $data[$key] = str_replace(
                    '__model_number__',
                    $data['research_data']['model_number'],
                    $data[$key]
                );
                $data[$key] = str_replace(
                    '__serial_number__',
                    $data['research_data']['serial_number'],
                    $data[$key]
                );
                $data[$key] = str_replace(
                    '__part_description__',
                    $data['research_data']['part_description'],
                    $data[$key]
                );
                $data[$key] = str_replace(
                    '__account_or_business_name__',
                    $data['research_data']['account_or_business_name'],
                    $data[$key]
                );
                $data[$key] = str_replace(
                    '__zip_code__',
                    $data['research_data']['zip_code'],
                    $data[$key]
                );
                $data[$key] = str_replace(
                    '__method_of_contact__',
                    $data['research_data']['method_of_contact'],
                    $data[$key]
                );
            }

            if (isset($data['erp_quotation_data'])) {
                $data[$key] = str_replace(
                    '__erp_quote_number__',
                    $data['erp_quotation_data']->QuoteNumber,
                    $data[$key]
                );
                $data[$key] = str_replace(
                    '__contact_name__',
                    $data['contact']->name,
                    $data[$key]
                );
                $data[$key] = str_replace(
                    '__customer_name__',
                    $data['erp_quotation_data']->CustomerName,
                    $data[$key]
                );
                $data[$key] = str_replace(
                    '__shipping_address__',
                    $this->getQuoteShippingAddress($data['erp_quotation_data']),
                    $data[$key]
                );
                $data[$key] = str_replace(
                    '__total_amount__',
                    $data['erp_quotation_data']->QuoteAmount,
                    $data[$key]
                );
                $data[$key] = str_replace(
                    '__notes__',
                    json_encode($data['erp_quotation_data']->OrderNotes),
                    $data[$key]
                );
                $data[$key] = str_replace(
                    '__customer_quotation_details__',
                    view(
                        'system::email.quote.details',
                        ['details' => $data['erp_quotation_data']['QuoteDetail']]
                    )->render(),
                    $data[$key]
                );
            }

            if (isset($data['additional_data'])) {
                $data[$key] = str_replace(
                    '__po_number__',
                    $data['additional_data']['po_number'],
                    $data[$key]
                );
                $data[$key] = str_replace(
                    '__shipping_method__',
                    $data['additional_data']['shipping_method'],
                    $data[$key]
                );
                $data[$key] = str_replace(
                    '__special_instruction__',
                    $data['additional_data']['special_instruction'],
                    $data[$key]
                );
            }

            if (!empty($data['guest_customer_name'])) {
                $data[$key] = str_replace(
                    '__customer_name__',
                    $data['guest_customer_name'],
                    $data[$key]
                );
            }

        }

        return $data;
    }

    public function resetPasswordEmailToCustomer(EventAction $email_action, $otp, $customer)
    {
        $eventTemplate = $email_action->eventTemplate;

        $data = [
            'contact' => $customer,
            'otp' => $otp,
            'email_content' => $eventTemplate->email_body,
            'subject' => 'Password Reset OTP',
            'is_customer_mail' => true,
        ];

        $this->dispatchEmailJobs(
            $this->replaceMailContentProperty($data),
            $this->getRecipientsEmail($customer, $email_action)
        );
    }

    public function sendOrderFromQuotationNotification(EventAction $email_action, $quotation, $contact, $additionalInfo)
    {
        $eventTemplate = $email_action->eventTemplate;

        $data = [
            'contact' => $contact,
            'subject' => $eventTemplate->subject,
            'erp_quotation_data' => $quotation,
            'additional_data' => $additionalInfo,
            'email_content' => $eventTemplate->email_body,
            'is_customer_mail' => true,
        ];

        $this->dispatchEmailJobs(
            $this->replaceMailContentProperty($data),
            $this->getRecipientsEmail($contact, $email_action, $quotation)
        );
    }

    public function orderRuleCheckedEmailToApprover(EventAction $email_action, $contact, $customerOrderRuleTrack)
    {
        $eventTemplate = $email_action->eventTemplate;

        if ($eventTemplate?->button_url === '__customer_order_approval_details_url__') {
            $button_url = str_replace(
                '__customer_order_approval_details_url__',
                route('frontend.order-approvals.show', $customerOrderRuleTrack->id),
                $eventTemplate?->button_url
            );
        } else {
            $button_url = $eventTemplate?->button_url;
        }

        $data = [
            'customer' => $contact,
            'subject' => $eventTemplate->subject,
            'email_content' => $eventTemplate->email_body,
            'customer_order_rule_track' => $customerOrderRuleTrack,
            'show_button' => $eventTemplate->show_button,
            'button_url' => URL::to($button_url),
            'button_text' => $eventTemplate->button_text,
            'is_customer_mail' => true,
        ];

        $this->dispatchEmailJobs(
            $this->replaceMailContentProperty($data),
            $this->getRecipientsEmail($contact, $email_action)
        );
    }

    public function orderWaitingEmailToCustomer(EventAction $email_action, $contact, $customerOrderRuleTrack)
    {
        $eventTemplate = $email_action->eventTemplate;

        if ($eventTemplate?->button_url === '__customer_order_approval_details_url__') {
            $button_url = str_replace(
                '__customer_order_approval_details_url__',
                route('frontend.order-approvals.show', $customerOrderRuleTrack->id),
                $eventTemplate?->button_url
            );
        } else {
            $button_url = $eventTemplate?->button_url;
        }

        $data = [
            'customer' => $contact,
            'subject' => $eventTemplate->subject,
            'email_content' => $eventTemplate->email_body,
            'customer_order_rule_track' => $customerOrderRuleTrack,
            'show_button' => $eventTemplate->show_button,
            'button_url' => URL::to($button_url),
            'button_text' => $eventTemplate->button_text,
            'is_customer_mail' => true,
        ];

        $this->dispatchEmailJobs(
            $this->replaceMailContentProperty($data),
            $this->getRecipientsEmail($contact, $email_action)
        );
    }

    public function orderRequestApprovedEmailToCustomer(EventAction $email_action, $contact, $customerOrderRuleTrack)
    {

        $eventTemplate = $email_action->eventTemplate;

        if ($eventTemplate?->button_url === '__customer_order_approval_details_url__') {
            $button_url = str_replace(
                '__customer_order_approval_details_url__',
                route('frontend.order-approvals.show', $customerOrderRuleTrack->id),
                $eventTemplate?->button_url
            );
        } else {
            $button_url = $eventTemplate?->button_url;
        }

        $data = [
            'customer' => $contact,
            'subject' => $eventTemplate->subject,
            'email_content' => $eventTemplate->email_body,
            'customer_order_rule_track' => $customerOrderRuleTrack,
            'show_button' => $eventTemplate->show_button,
            'button_url' => URL::to($button_url),
            'button_text' => $eventTemplate->button_text,
            'is_customer_mail' => true,
        ];

        $this->dispatchEmailJobs(
            $this->replaceMailContentProperty($data),
            $this->getRecipientsEmail($contact, $email_action)
        );
    }

    public function orderRequestRejectedEmailToCustomer(EventAction $email_action, $contact, $customerOrderRuleTrack)
    {

        $eventTemplate = $email_action->eventTemplate;

        if ($eventTemplate?->button_url === '__customer_order_approval_details_url__') {
            $button_url = str_replace(
                '__customer_order_approval_details_url__',
                route('frontend.order-approvals.show', $customerOrderRuleTrack->id),
                $eventTemplate?->button_url
            );
        } else {
            $button_url = $eventTemplate?->button_url;
        }

        $data = [
            'customer' => $contact,
            'subject' => $eventTemplate->subject,
            'email_content' => $eventTemplate->email_body,
            'customer_order_rule_track' => $customerOrderRuleTrack,
            'show_button' => $eventTemplate->show_button,
            'button_url' => URL::to($button_url),
            'button_text' => $eventTemplate->button_text,
            'is_customer_mail' => true,
        ];

        $this->dispatchEmailJobs(
            $this->replaceMailContentProperty($data),
            $this->getRecipientsEmail($contact, $email_action)
        );
    }

    public function contactAccountRegistrationRequestReceivedEmail(EventAction $email_action, $contact)
    {
        $eventTemplate = $email_action->eventTemplate;

        /*
         *  Generate customer button url
         */
        $button_url = str_replace(
            '__contacts_details_url_for_account_request_received__',
            '/admin/contact/' . $contact->id . '/edit',
            $eventTemplate->button_url
        );

        /*
         * Preparing email data
         */
        $data = [
            'contact' => $contact,
            'subject' => $eventTemplate->subject,
            'email_content' => $eventTemplate->email_body,
            'show_button' => $eventTemplate->show_button === 1,
            'button_url' => URL::to($button_url),
            'button_text' => $eventTemplate->button_text,
            'is_customer_mail' => true,
        ];

        $this->dispatchEmailJobs(
            $this->replaceMailContentProperty($data),
            $this->getRecipientsEmail($contact, $email_action)
        );
    }

    public function contactAccountRegistrationRequestAcceptedEmail(EventAction $email_action, $contact)
    {
        $eventTemplate = $email_action->eventTemplate;

        /*
         *  Generate customer button url
         */
        $button_url = str_replace(
            '__contacts_details_url_for_account_request_accepted__',
            '/admin/contact/' . $contact->id . '/show',
            $eventTemplate->button_url
        );

        /*
         * Preparing email data
         */
        $data = [
            'contact' => $contact,
            'subject' => $eventTemplate->subject,
            'email_content' => $eventTemplate->email_body,
            'show_button' => $eventTemplate->show_button === 1,
            'button_url' => URL::to($button_url),
            'button_text' => $eventTemplate->button_text,
            'is_customer_mail' => true,
        ];

        $this->dispatchEmailJobs(
            $this->replaceMailContentProperty($data),
            $this->getRecipientsEmail($contact, $email_action)
        );
    }

    public function registrationRequestEmailToCustomer(EventAction $email_action, $customer)
    {
        $eventTemplate = $email_action->eventTemplate;

        /*
         *  Generate customer button url
         */
        $button_url = str_replace(
            ':id',
            $customer->id,
            $eventTemplate->button_url
        );

        /*
         * Preparing email data
         */
        $data = [
            'customer' => $customer,
            'subject' => $eventTemplate->subject,
            'email_content' => $eventTemplate->email_body,
            'show_button' => $eventTemplate->show_button === 1,
            'button_url' => URL::to($button_url),
            'button_text' => $eventTemplate->button_text,
            'is_customer_mail' => true,
        ];

        $this->dispatchEmailJobs(
            $this->replaceMailContentProperty($data),
            $this->getRecipientsEmail($customer, $email_action)
        );
    }

    public function registrationRequestEmailToAdmin($email_action, $customer)
    {
        $email_data = $email_action->eventTemplate;

        /*
         *  Generate admin button url
         */
        $button_url = str_replace(
            '__customer_details_url_for_request_received__',
            '/admin/customer-registration/' . $customer->id . '/show',
            $email_data->button_url
        );

        /*
         * Preparing email data
         */
        $data = [
            'customer' => $customer,
            'subject' => $email_data->subject,
            'email_content' => $email_data->email_body,
            'show_button' => $email_data->show_button === 1,
            'button_url' => URL::to($button_url),
            'button_text' => $email_data->button_text,
            'is_customer_mail' => false,
        ];

        /**
         * Sending email
         */
        $this->dispatchEmailJobs(
            $this->replaceMailContentProperty($data),
            $this->getRecipientsEmail($customer, $email_action)
        );
    }

    public function registrationRequestAcceptedEmailToCustomer($email_action, $customer)
    {
        $email_data = $email_action->eventTemplate;

        /*
         *  Generate customer button url
         */
        $button_url = str_replace(
            ':id',
            $customer->id,
            $email_data->button_url
        );
        /*
        * Preparing email data
        */
        $data = [
            'customer' => $customer,
            'subject' => $email_data->subject,
            'email_content' => $email_data->email_body,
            'show_button' => $email_data->show_button === 1,
            'button_url' => URL::to($button_url),
            'button_text' => $email_data->button_text,
            'is_customer_mail' => true,
        ];
        /*
         * Sending email
         */
        $this->dispatchEmailJobs(
            $this->replaceMailContentProperty($data),
            $this->getRecipientsEmail($customer, $email_action)
        );
    }

    public function registrationRequestAcceptedEmailToAdmin($email_action, $customer)
    {
        $email_data = $email_action->eventTemplate;

        /*
        *  Generate admin button url
        */
        $button_url = str_replace(
            '__customer_details_url_for_request_accepted__',
            '/admin/customer/' . $customer->id . '/show',
            $email_data->button_url
        );
        /*
         * Preparing email data
         */
        $data = [
            'customer' => $customer,
            'subject' => $email_data->subject,
            'email_content' => $email_data->email_body,
            'show_button' => $email_data->show_button === 1,
            'button_url' => URL::to($button_url),
            'button_text' => $email_data->button_text,
            'is_customer_mail' => false,
        ];
        /*
        * Sending email
        */
        $this->dispatchEmailJobs(
            $this->replaceMailContentProperty($data),
            $this->getRecipientsEmail($customer, $email_action)
        );
    }

    public function updateOrderNoteEmailToCustomer($order, $notes, $email_action)
    {
        $email_data = $email_action->eventTemplate;

        $customer = $order->customer;
        /*
         *  Generate customer button url for either quotation details or order details
         */
        if (optional($email_data)->button_url === '__customer_order_details_url__') {
            $button_url = str_replace(
                '__customer_order_details_url__',
                '/customer-profile-order-list-items?order_id=' . $order->erp_order_id,
                optional($email_data)->button_url
            );
        } elseif (optional($email_data)->button_url === '__customer_quotation_details_url__') {
            $button_url = str_replace(
                '__customer_quotation_details_url__',
                '/customer-profile-quotation-list-items?order_id=' . $order->erp_order_id,
                optional($email_data)->button_url
            );
        }

        /*
         * Preparing email data
         */
        $data = [
            'customer' => $customer,
            'order' => $order,
            'subject' => optional($email_data)->subject,
            'email_content' => optional($email_data)->email_body,
            'show_button' => optional($email_data)->show_button === 1,
            'button_url' => !isset($button_url) ? '' : URL::to($button_url),
            'button_text' => optional($email_data)->button_text,
            'is_customer_mail' => true,
            'notes' => $notes,
        ];
        /*
         * Sending email
         */
        $this->dispatchEmailJobs(
            $this->replaceMailContentProperty($data),
            $this->getRecipientsEmail($customer, $email_action)
        );
    }

    public function updateOrderNoteEmailToAdmin($email_action, $order)
    {
        $email_data = $email_action->eventTemplate;

        /*
         *  Generate customer button url for either quotation details or order details
         */
        if (optional($email_data)->button_url === '__admin_order_details_url__') {
            $button_url = str_replace(
                '__admin_order_details_url__',
                '/admin/order-line?order_line_id=' . $order->id,
                optional($email_data)->button_url
            );
        }
        /*
         * Preparing email data
         */
        $data = [
            'customer' => $order->customer,
            'order' => $order,
            'subject' => $email_data->subject,
            'email_content' => $email_data->email_body,
            'show_button' => $email_data->show_button === 1,
            'button_url' => !isset($button_url) ? '' : URL::to($button_url),
            'button_text' => $email_data->button_text,
            'is_customer_mail' => false,
        ];
        /*
         * Sending email
         */
        DispatchEmailJob::dispatch($this->replaceMailContentProperty($data), $this->getRecipientsEmail($order->customer, $email_action));
    }

    public function catalogChangedEmailToAdmin($email_action, $productSyncInfo)
    {
        $email_data = $email_action->eventTemplate;

        /*
         * Preparing email data
         */
        $data = [
            'subject' => $email_data->subject,
            'email_content' => $email_data->email_body,
        ];
        /*
         * Sending email
         */
        DispatchEmailJob::dispatch($this->replaceMailContentProperty($data), $this->getRecipientsEmail(null, $email_action));
    }

    public function coilQuoteRequestEmailToAdmin($email_action, $coilData, $customer)
    {
        $pdfName = uniqid() . '.pdf';
        $eventTemplate = $email_action->eventTemplate;
        Pdf::loadView('custom-item::evaporator_coil_pdf', ['info' => $coilData])->save(storage_path($pdfName));

        /*
         * Preparing email data
         */
        $data = [
            'coil_data' => $coilData,
            'subject' => $eventTemplate->subject,
            'email_content' => $eventTemplate->email_body,
            'attachments' => [storage_path($pdfName)],
            'show_button' => $eventTemplate->show_button,
            'button_url' => !isset($eventTemplate->button_url) ? '' : URL::to($eventTemplate->button_url),
            'button_text' => $eventTemplate->button_text,
        ];

        $this->dispatchEmailJobs(
            $this->replaceMailContentProperty($data),
            $this->getRecipientsEmail($customer, $email_action)
        );
        unlink(storage_path($pdfName));
    }

    public function modelOrSerialNumberResearchEmailToAdmin($email_action, $researchData, $customer, $uploadedFile)
    {
        $eventTemplate = $email_action->eventTemplate;

        /*
         * Preparing email data
         */
        $data = [
            'research_data' => $researchData,
            'subject' => $eventTemplate->subject,
            'email_content' => $eventTemplate->email_body,
            'attachments' => $uploadedFile ? [$uploadedFile] : [],
            'show_button' => $eventTemplate->show_button,
            'button_url' => !isset($eventTemplate->button_url) ? '' : URL::to($eventTemplate->button_url),
            'button_text' => $eventTemplate->button_text,
        ];

        $this->dispatchEmailJobs(
            $this->replaceMailContentProperty($data),
            $this->getRecipientsEmail($customer, $email_action)
        );

        if ($uploadedFile) {
            unlink($uploadedFile);
        }
    }

    private function dispatchEmailJobs($emailContent, $recipients)
    {
        foreach ($recipients ?? [] as $email) {
            DispatchEmailJob::dispatch($emailContent, $email);
        }
    }

    private function getContactEmail($customer, $contact)
    {
        if (!empty($contact) && $contact instanceof Contact) {
            return $contact->email;
        }

        if ($customer instanceof Customer && !empty($customer->contact)) {
            return $customer->contact->email;
        }

        return '';
    }

    /**
     * @return array|false
     */
    private function getRecipientsEmail($customer, $email_action, $quotation = null, $guestCustomerEmail = null, $contact = null)
    {
        $emails = [];

        if (!empty($guestCustomerEmail)) {
            $emails[] = $guestCustomerEmail;
        }

        if ($email_action->is_get_admin) {
            $emails[] = config('mail.admin_email');
        }

        if ($customer instanceof Customer) {
            if ($email_action->is_get_customer) {
                $emails[] = $customer->email;
            }

            if ($email_action->is_get_business_contact) {
                $emails[] = $customer->business_contact;
            }

            if ($email_action->is_get_contact) {
                $emails[] = $this->getContactEmail($customer, $contact);
            }
        }

        if ($customer instanceof Contact) {
            if ($email_action->is_get_customer) {
                $emails[] = !empty($customer->customer) ? $customer->customer->email : '';
            }

            if ($email_action->is_get_business_contact) {
                $emails[] = !empty($customer->customer) ? $customer->customer->business_contact : '';
            }

            if ($email_action->is_get_contact) {
                $emails[] = $customer->email;
            }
        }

        if ($customer instanceof ErpCustomer) {
            if ($email_action->is_get_salesperson) {
                $emails[] = $customer->SalesPersonEmail;
            }
        }

        if ($email_action->is_quote_sales_person && !empty($quotation)) {
            $emails[] = $quotation->QuotedByEmail;
        }

        $extra_emails = explode(',', str_replace([' ', "\t", "\n", "\r"], '', trim($email_action->recipient_emails)));

        $filteredEmails = filter_var_array([...$emails, ...$extra_emails], FILTER_SANITIZE_EMAIL);

        if (empty($filteredEmails)) {
            return [];
        }

        return array_filter($filteredEmails, function ($email) {
            return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
        }) ?? [];
    }

    private function getQuoteShippingAddress(mixed $erp_quotation_data): string
    {
        $address = implode(
            ' ',
            [
                $erp_quotation_data->ShipToAddress1 ?? '',
                $erp_quotation_data->ShipToAddress2 ?? '',
                $erp_quotation_data->ShipToAddress3 ?? '',
            ]
        );

        $shippingAddress = '';

        if (!empty($erp_quotation_data->ShipTo)) {
            $shippingAddress .= "<p>Ship To: $erp_quotation_data->ShipTo</p>";
        }

        if (!empty($erp_quotation_data->ShipToNumber)) {
            $shippingAddress .= "<p>Ship To Number: $erp_quotation_data->ShipToNumber</p>";
        }

        if (!empty($erp_quotation_data->ShipToContact)) {
            $shippingAddress .= "<p>Ship To Contact: $erp_quotation_data->ShipToContact</p>";
        }

        if (!empty($address)) {
            $shippingAddress .= "<p>Address: $address</p>";
        }

        if (!empty($erp_quotation_data->ShipToCity)) {
            $shippingAddress .= "<p>City: $erp_quotation_data->ShipToCity</p>";
        }

        if (!empty($erp_quotation_data->ShipToState)) {
            $shippingAddress .= "<p>State: $erp_quotation_data->ShipToState</p>";
        }

        if (!empty($erp_quotation_data->ShipToZipCode)) {
            $shippingAddress .= "<p>Zip Code: $erp_quotation_data->ShipToZipCode</p>";
        }

        return $shippingAddress;
    }

    public function sendWishlistProductRestockedEmail(EventAction $emailAction, $contact)
    {
        $eventTemplate = $emailAction->eventTemplate;

        $data = [
            'contact' => $contact,
            'subject' => $eventTemplate->subject,
            'email_content' => $eventTemplate->email_body,
            'is_customer_mail' => true,
        ];

        $this->dispatchEmailJobs(
            $this->replaceMailContentProperty($data),
            $this->getRecipientsEmail($contact, $emailAction)
        );
    }

    public function sendTicketCreatedNotificationEmail(EventAction $emailAction, Ticket $ticket)
    {
        $eventTemplate = $emailAction->eventTemplate;

        $department = TicketDepartment::findOrFail($ticket->departments_name_id);
        $contact = Contact::findOrFail($ticket->sender_id);

        $replacement_array = [
            '__ticket_subject__' => $ticket->subject ?? '',
            '__ticket_priority__' => Ticket::PRIORITY_LABEL[$ticket->priority] ?? 'N/A',
            '__ticket_department__' => $department->name,
            '__ticket_url__' => URL::to('/admin/ticket/' . $ticket->id . '/show'),
            '__ticket_content__' => $ticket->message,
        ];

        $button_url_admin = str_replace(
            '__ticket_url__',
            URL::to('/admin/ticket/' . $ticket->id . '/show'),
            $eventTemplate->button_url
        );

        $data = [
            'ticket' => $ticket,
            'subject' => strtr($emailAction->eventTemplate->subject, $replacement_array),
            'email_content' => strtr($emailAction->eventTemplate->email_body, $replacement_array),
            'show_button' => $eventTemplate->show_button === 1,
            'button_url' => !isset($button_url_admin) ? '' : URL::to($button_url_admin),
            'button_text' => $eventTemplate->button_text,
        ];

        $emails = $this->getRecipientsEmail($contact, $emailAction);
        array_push($emails, $department->email);

        $this->dispatchEmailJobs(
            $this->replaceMailContentProperty($data),
            $emails
        );
    }
}
