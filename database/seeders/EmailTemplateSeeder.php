<?php

namespace Database\Seeders;

use App\Models\EmailTemplate;
use Illuminate\Database\Seeder;

class EmailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->templates() as $template) {
            EmailTemplate::query()->updateOrCreate(
                ['key' => $template['key']],
                $template
            );
        }
    }

    private function templates(): array
    {
        return [
            [
                'key' => EmailTemplate::VENDOR_WELCOME,
                'name' => 'Vendor Welcome',
                'category' => 'Vendor',
                'recipient_type' => 'vendor',
                'subject' => 'Welcome to {{ platform_name }}, {{ vendor_name }}',
                'body_html' => '<p>Hello {{ vendor_name }},</p><p>Your vendor account on {{ platform_name }} is ready. You can now submit shipments and track their progress.</p><p><a href="{{ login_url }}">Open your vendor account</a></p>',
                'body_text' => "Hello {{ vendor_name }},\n\nYour vendor account on {{ platform_name }} is ready. You can now submit shipments and track their progress.\n\nOpen your vendor account: {{ login_url }}",
                'variables' => ['vendor_name', 'platform_name', 'login_url'],
                'is_enabled' => true,
                'is_system' => true,
            ],
            [
                'key' => EmailTemplate::SHIPMENT_SUBMITTED,
                'name' => 'Shipment Submitted',
                'category' => 'Shipments',
                'recipient_type' => 'vendor',
                'subject' => 'Shipment {{ shipment_number }} submitted',
                'body_html' => '<p>Hello {{ vendor_name }},</p><p>Shipment <strong>{{ shipment_number }}</strong> has been submitted and is waiting for processing.</p>',
                'body_text' => "Hello {{ vendor_name }},\n\nShipment {{ shipment_number }} has been submitted and is waiting for processing.",
                'variables' => ['vendor_name', 'shipment_number', 'platform_name'],
                'is_enabled' => false,
                'is_system' => true,
            ],
            [
                'key' => EmailTemplate::PICKUP_ASSIGNED,
                'name' => 'Pickup Assigned',
                'category' => 'Pickups',
                'recipient_type' => 'vendor',
                'subject' => 'Pickup assigned for {{ shipment_number }}',
                'body_html' => '<p>Hello {{ vendor_name }},</p><p>Your rider {{ driver_name }} has been assigned to pick up shipment <strong>{{ shipment_number }}</strong>.</p><p>Destination warehouse: {{ warehouse_name }}.</p>',
                'body_text' => "Hello {{ vendor_name }},\n\nYour rider {{ driver_name }} has been assigned to pick up shipment {{ shipment_number }}.\nDestination warehouse: {{ warehouse_name }}.",
                'variables' => ['vendor_name', 'shipment_number', 'driver_name', 'driver_phone', 'warehouse_name'],
                'is_enabled' => false,
                'is_system' => true,
            ],
            [
                'key' => EmailTemplate::PACKAGE_AT_WAREHOUSE,
                'name' => 'Package at Warehouse',
                'category' => 'Warehouse',
                'recipient_type' => 'vendor',
                'subject' => 'Shipment {{ shipment_number }} reached the warehouse',
                'body_html' => '<p>Hello {{ vendor_name }},</p><p>Shipment <strong>{{ shipment_number }}</strong> has reached {{ warehouse_name }}.</p>',
                'body_text' => "Hello {{ vendor_name }},\n\nShipment {{ shipment_number }} has reached {{ warehouse_name }}.",
                'variables' => ['vendor_name', 'shipment_number', 'warehouse_name'],
                'is_enabled' => false,
                'is_system' => true,
            ],
            [
                'key' => EmailTemplate::PAYMENT_REQUIRED,
                'name' => 'Payment Required',
                'category' => 'Payments',
                'recipient_type' => 'recipient_vendor',
                'subject' => 'Payment required for {{ shipment_number }}',
                'body_html' => '<p>Hello {{ recipient_name }},</p><p>A delivery payment of {{ amount }} {{ currency }} is required for package {{ tracking_code }}.</p>',
                'body_text' => "Hello {{ recipient_name }},\n\nA delivery payment of {{ amount }} {{ currency }} is required for package {{ tracking_code }}.",
                'variables' => ['recipient_name', 'vendor_name', 'shipment_number', 'tracking_code', 'amount', 'currency'],
                'is_enabled' => false,
                'is_system' => true,
            ],
            [
                'key' => EmailTemplate::PAYMENT_RECEIVED,
                'name' => 'Payment Received',
                'category' => 'Payments',
                'recipient_type' => 'recipient_vendor',
                'subject' => 'Payment received for {{ shipment_number }}',
                'body_html' => '<p>Hello {{ recipient_name }},</p><p>Payment for package {{ tracking_code }} has been recorded. Reference: {{ payment_reference }}.</p>',
                'body_text' => "Hello {{ recipient_name }},\n\nPayment for package {{ tracking_code }} has been recorded. Reference: {{ payment_reference }}.",
                'variables' => ['recipient_name', 'vendor_name', 'shipment_number', 'tracking_code', 'amount', 'currency', 'payment_reference'],
                'is_enabled' => false,
                'is_system' => true,
            ],
            [
                'key' => EmailTemplate::PACKAGE_READY_FOR_COLLECTION,
                'name' => 'Package Ready for Collection',
                'category' => 'Collection',
                'recipient_type' => 'recipient',
                'subject' => 'Package {{ shipment_number }} is ready for collection',
                'body_html' => '<p>Hello {{ recipient_name }},</p><p>Your package is ready for collection at {{ warehouse_name }}. Please bring a valid ID.</p>',
                'body_text' => "Hello {{ recipient_name }},\n\nYour package is ready for collection at {{ warehouse_name }}. Please bring a valid ID.",
                'variables' => ['recipient_name', 'shipment_number', 'warehouse_name', 'warehouse_address'],
                'is_enabled' => false,
                'is_system' => true,
            ],
            [
                'key' => EmailTemplate::DELIVERY_OUT_FOR_DELIVERY,
                'name' => 'Delivery Out for Delivery',
                'category' => 'Delivery',
                'recipient_type' => 'recipient_vendor',
                'subject' => 'Package {{ shipment_number }} is out for delivery',
                'body_html' => '<p>Hello {{ recipient_name }},</p><p>Your package on run {{ run_number }} is out for delivery.</p>',
                'body_text' => "Hello {{ recipient_name }},\n\nYour package on run {{ run_number }} is out for delivery.",
                'variables' => ['recipient_name', 'vendor_name', 'shipment_number', 'run_number', 'driver_name', 'driver_phone'],
                'is_enabled' => false,
                'is_system' => true,
            ],
            [
                'key' => EmailTemplate::DELIVERY_COMPLETED,
                'name' => 'Delivery Completed',
                'category' => 'Delivery',
                'recipient_type' => 'recipient_vendor',
                'subject' => 'Package {{ shipment_number }} delivered',
                'body_html' => '<p>Hello {{ recipient_name }},</p><p>Your package has been delivered successfully.</p>',
                'body_text' => "Hello {{ recipient_name }},\n\nYour package has been delivered successfully.",
                'variables' => ['recipient_name', 'vendor_name', 'shipment_number', 'run_number', 'delivered_at'],
                'is_enabled' => false,
                'is_system' => true,
            ],
            [
                'key' => EmailTemplate::PASSWORD_RESET,
                'name' => 'Password Reset',
                'category' => 'Auth',
                'recipient_type' => 'platform_user',
                'subject' => 'Reset your {{ platform_name }} password',
                'body_html' => '<p>Hello {{ user_name }},</p><p>Use this link to reset your password: <a href="{{ reset_url }}">{{ reset_url }}</a></p>',
                'body_text' => "Hello {{ user_name }},\n\nUse this link to reset your password: {{ reset_url }}",
                'variables' => ['user_name', 'platform_name', 'reset_url', 'expires_in'],
                'is_enabled' => false,
                'is_system' => true,
            ],
        ];
    }
}
