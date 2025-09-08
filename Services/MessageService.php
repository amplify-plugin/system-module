<?php

namespace Amplify\System\Services;

use Amplify\System\Backend\Models\User;
use Amplify\System\Message\Facades\Messenger;
use Illuminate\Support\Facades\URL;

class MessageService
{
    public function sendOrderDetailsMessageToCustomer($message_data, $order, $customer): void
    {
        /*
         *  Generate customer button url for either quotation details or order details
         */
        if (optional($message_data)->button_url === '__customer_order_details_url__') {
            $button_url = str_replace(
                '__customer_order_details_url__',
                route('frontend.orders.show', $order->erp_order_id),
                optional($message_data)->button_url
            );
        } elseif (optional($message_data)->button_url === '__customer_quotation_details_url__') {
            $button_url = str_replace(
                '__customer_quotation_details_url__',
                route('frontend.quotations.show', $order->erp_order_id),
                optional($message_data)->button_url
            );
        }

        /*
         * Preparing message data
         */

        $data = [
            'customer' => $customer,
            'order' => $order,
            'subject' => $order->order_type == 1
                ? 'Quotation Received'
                : 'Order Received',
            'message_content' => optional($message_data)->email_body,
            'show_button' => optional($message_data)->show_button === 1,
            'button_url' => ! isset($button_url) ? '' : URL::to($button_url),
            'button_text' => optional($message_data)->button_text,
            'is_customer_mail' => true,
        ];
        /*
         * Get message sender receiver from DB
         */

        $response = $this->replaceMessageContentProperty($data);
        $sender = User::where('is_admin', 1)->first();
        $receiver = customer(true);
        $msg = $response['message_content'];

        Messenger::from($sender)
            ->to($receiver)
            ->message($msg)
            ->send();
    }

    public function sendDraftOrderDetailsMessageToCustomer($message_data, $order, $customer): void
    {
        /*
         *  Generate customer button url for either quotation details or order details
         */
        if (optional($message_data)->button_url === '__customer_order_details_url__') {
            $button_url = str_replace(
                '__customer_order_details_url__',
                route('frontend.orders.show', $order->erp_order_id),
                optional($message_data)->button_url
            );
        } elseif (optional($message_data)->button_url === '__customer_quotation_details_url__') {
            $button_url = str_replace(
                '__customer_quotation_details_url__',
                route('frontend.quotations.show', $order->erp_order_id),
                optional($message_data)->button_url
            );
        }

        /*
         * Preparing message data
         */

        $data = [
            'customer' => $customer,
            'order' => $order,
            'subject' => 'Draft Order Received',
            'message_content' => optional($message_data)->email_body,
            'show_button' => optional($message_data)->show_button === 1,
            'button_url' => ! isset($button_url) ? '' : URL::to($button_url),
            'button_text' => optional($message_data)->button_text,
            'is_customer_mail' => true,
        ];
        /*
         * Get message sender receiver from DB
         */

        $response = $this->replaceMessageContentProperty($data);
        $sender = User::where('is_admin', 1)->first();
        $receiver = customer(true);
        $msg = $response['message_content'];

        Messenger::from($sender)
            ->to($receiver)
            ->message($msg)
            ->send();
    }

    public function sendOrderDetailsMessageToAdmin($message_data, $order, $customer): void
    {
        /*
         *  Generate admin button url for either quotation details or order details
         */
        if (optional($message_data)->button_url === '__order_details_url__') {
            $button_url_admin = str_replace(
                '__order_details_url__',
                '/admin/order/'.$order->id.'/show',
                $message_data->button_url
            );
        } elseif (optional($message_data)->button_url === '__quotation_details_url__') {
            $button_url_admin = str_replace(
                '__quotation_details_url__',
                '/admin/quote/'.$order->id.'/show',
                $message_data->button_url
            );
        }

        /*
         * Preparing message data
         */
        $data = [
            'customer' => $customer,
            'order' => $order,
            'subject' => $order->order_type == 1
                ? 'Customer Quotation Received'
                : 'Customer Order Received',
            'message_content' => optional($message_data)->email_body,
            'show_button' => optional($message_data)->show_button === 1,
            'button_url' => ! isset($button_url_admin) ? '' : URL::to($button_url_admin),
            'button_text' => optional($message_data)->button_text,
            'is_customer_mail' => false,
        ];

        /*
         * Get message sender & receiver from DB
         */

        $response = $this->replaceMessageContentProperty($data);
        $sender = $customer->contact;
        $receiver = User::where('is_admin', 1)->first();
        $msg = $response['message_content'];

        Messenger::from($sender)
            ->to($receiver)
            ->message($msg)
            ->send();
    }

    protected function replaceMessageContentProperty($data): array
    {
        $data['message_content'] = str_replace(
            '__customer_name__',
            $data['customer']->customer_name,
            $data['message_content']
        );

        $data['message_content'] = str_replace(
            '__logged_in_user_name__',
            auth()->check() ? auth()->user()->name : (customer_check() ? customer(true)->name : 'Guest'),
            $data['message_content']
        );

        if (isset($data['order'])) {
            $data['message_content'] = str_replace(
                '__web_order_number__',
                $data['order']->web_order_number,
                $data['message_content']
            );

            $data['message_content'] = str_replace(
                '__total_amount__',
                $data['order']->total_amount,
                $data['message_content']
            );

            $data['message_content'] = str_replace(
                '__contact_name__',
                $data['order']->contact->name ?? '',
                $data['message_content']
            );

            $data['message_content'] = str_replace(
                '__notes__',
                ! empty($data['notes']) ? $data['notes'] : $data['order']->notes ?? '',
                $data['message_content']
            );

            $data['message_content'] = str_replace(
                '__customer_order_details_url__',
                '<a href="'.route('frontend.orders.show', $data['order']->erp_order_id).'">View Details</a>',
                $data['message_content']
            );

            $data['message_content'] = str_replace(
                '__customer_quotation_details_url__',
                '<a href="'.route('frontend.quotation.details', $data['order']->id).'">View Details</a>',
                '<a href="'.route('frontend.quotations.show', $data['order']->erp_order_id).'">View Details</a>',
                $data['message_content']
            );
        }

        return $data;
    }

    public function registrationRequestMessageToCustomer($message_data, $customer)
    {
        /*
         * Preparing message data
         */
        $data = [
            'customer' => $customer,
            'subject' => 'Customer Registration Request Received',
            'message_content' => $message_data->email_body,
            'show_button' => $message_data->show_button === 1,
            'button_url' => '',
            'button_text' => $message_data->button_text,
            'is_customer_mail' => false,
        ];

        /**
         * Sending message
         */
        $response = $this->replaceMessageContentProperty($data);
        $sender = User::where('is_admin', 1)->first();
        $receiver = $customer->contact;
        $msg = $response['message_content'];

        Messenger::from($sender)
            ->to($receiver)
            ->message($msg)
            ->send();
    }

    public function registrationRequestMessageToAdmin($message_data, $customer)
    {
        /*
         *  Generate admin button url
         */
        $button_url = str_replace(
            '__customer_details_url_for_request_received__',
            '/admin/customer-registration/'.$customer->id.'/show',
            $message_data->button_url
        );

        /*
         * Preparing message data
         */
        $data = [
            'customer' => $customer,
            'subject' => 'Customer Registration Request Received',
            'message_content' => $message_data->email_body,
            'show_button' => $message_data->show_button === 1,
            'button_url' => URL::to($button_url),
            'button_text' => $message_data->button_text,
            'is_customer_mail' => false,
        ];

        /**
         * Sending message
         */
        $response = $this->replaceMessageContentProperty($data);
        $sender = $customer->contact;
        $receiver = User::where('is_admin', 1)->first();
        $msg = $response['message_content'];

        Messenger::from($sender)
            ->to($receiver)
            ->message($msg)
            ->send();
    }

    public function registrationRequestAcceptedMessageToCustomer($message_data, $customer)
    {
        /*
       *  Generate customer button url
       */
        $button_url = str_replace(
            ':id',
            $customer->id,
            $message_data->button_url
        );
        /*
      * Preparing message data
      */
        $data = [
            'customer' => $customer,
            'subject' => 'Registration Request Accepted',
            'message_content' => $message_data->email_body,
            'show_button' => $message_data->show_button === 1,
            'button_url' => URL::to($button_url),
            'button_text' => $message_data->button_text,
            'is_customer_mail' => true,
        ];

        /**
         * Sending message
         */
        $response = $this->replaceMessageContentProperty($data);
        $sender = User::where('is_admin', 1)->first();
        $receiver = $customer->contact;
        $msg = $response['message_content'];

        Messenger::from($sender)
            ->to($receiver)
            ->message($msg)
            ->send();
    }

    public function updateOrderNoteMessageToCustomer($order, $notes, $message_data)
    {
        $customer = $order->customer;

        /*
         *  Generate customer button url for either quotation details or order details
         */
        if (optional($message_data)->button_url === '__customer_order_details_url__') {
            $button_url = str_replace(
                '__customer_order_details_url__',
                '/customer-profile-order-list-items?order_id='.$order->id,
                optional($message_data)->button_url
            );
        } elseif (optional($message_data)->button_url === '__customer_quotation_details_url__') {
            $button_url = str_replace(
                '__customer_quotation_details_url__',
                '/customer-profile-quotation-list-items?order_id='.$order->id,
                optional($message_data)->button_url
            );
        }

        /*
         * Preparing message data
         */
        $data = [
            'customer' => $customer,
            'order' => $order,
            'subject' => 'Order #'.$order->id.' note has been updated',
            'message_content' => optional($message_data)->email_body,
            'show_button' => optional($message_data)->show_button === 1,
            'button_url' => ! isset($button_url) ? '' : URL::to($button_url),
            'button_text' => optional($message_data)->button_text,
            'is_customer_mail' => true,
            'notes' => $notes,
        ];
        /*
         * Sending message
         */

        $response = $this->replaceMessageContentProperty($data);
        $sender = User::where('is_admin', 1)->first();
        $receiver = $customer->contact;
        $msg = $response['message_content'];

        Messenger::from($sender)
            ->to($receiver)
            ->message($msg)
            ->send();
    }

    public function updateOrderNoteMessageToAdmin($message_data, $order)
    {
        /*
         *  Generate customer button url for either quotation details or order details
         */
        if (optional($message_data)->button_url === '__admin_order_details_url__') {
            $button_url = str_replace(
                '__admin_order_details_url__',
                '/admin/order-line?order_line_id='.$order->id,
                optional($message_data)->button_url
            );
        }
        /*
         * Preparing message data
         */
        $data = [
            'customer' => $order->customer,
            'subject' => 'Order #'.$order->id.' note has been updated',
            'message_content' => $message_data->email_body,
            'show_button' => $message_data->show_button === 1,
            'button_url' => ! isset($button_url) ? '' : URL::to($button_url),
            'button_text' => $message_data->button_text,
            'is_customer_mail' => false,
            'order' => $order,
        ];
        $data['message_content'] = str_replace(
            '__web_order_number__',
            $order->id,
            $data['message_content']
        );
        /*
         * Sending message
         */

        $response = $this->replaceMessageContentProperty($data);
        $sender = optional($order->customer)->contact;
        $receiver = User::where('is_admin', 1)->first();
        $msg = $response['message_content'];

        Messenger::from($sender)
            ->to($receiver)
            ->message($msg)
            ->send();
    }

    public function catalogChangedMessageToAdmin($message_data, $productSyncInfo)
    {
        /*
      * Preparing message data
      */
        $data = [
            'subject' => 'Catalog has been updated',
            'message_content' => $message_data->email_body,
        ];

        /**
         * Sending message
         */
        $response = $this->replaceMessageContentProperty($data);
        $sender = User::where('is_admin', 1)->first();
        $receiver = $customer->contact;
        $msg = $response['message_content'];

        Messenger::from($sender)
            ->to($receiver)
            ->message($msg)
            ->send();
    }
}
