@extends('admin.layouts.app')

@section('title', 'System Manual')
@section('breadcrumb-parent', 'Help')
@section('breadcrumb-current', 'System Manual')

@section('content')
@php
    /*
     * The manual is data-driven: each entry below renders as one section card.
     * Update the arrays when modules change — keep steps short and imperative.
     */
    $manualGroups = [
        'Order Intake' => [
            [
                'id' => 'orders',
                'title' => 'Orders',
                'where' => 'Sidebar → Orders · /admin/orders',
                'route' => 'admin.orders.index',
                'who' => 'shipments.view / shipments.edit / shipments.manage',
                'purpose' => 'Central workspace for every vendor shipment: intake, packages, pricing, receiving coordination and tracking.',
                'steps' => [
                    'Open Orders to see the filterable list (status, vendor, region, fulfillment type). Export to Excel from the toolbar.',
                    'Create an order manually with Create Order — look up the vendor by phone or create one inline.',
                    'Inside an order, manage packages: add, edit, split into multiple destinations, upload photos, or auto-group items by recipient phone.',
                    'Use the Charges tab to add fees (or seed the standard pickup fee), then mark charges paid or waive them.',
                    'Record vendor payments in the Payments tab; download or print receipts.',
                    'Use the Receiving tab when the pickup arrives at the warehouse: confirm quantities, print barcode labels, then Finalize.',
                    'Track progress in the Tracking and Custody tabs (status timeline and label hand-offs).',
                    'Reject problematic submissions (with reason) or Reopen a rejected order after the vendor fixes it.',
                ],
                'statuses' => [
                    ['Draft', 'slate'], ['Submitted', 'blue'], ['Processing', 'blue'], ['Pickup assigned', 'amber'],
                    ['Picked up', 'amber'], ['At warehouse', 'violet'], ['Sorted', 'violet'], ['In transit', 'orange'],
                    ['At destination', 'orange'], ['Out for delivery', 'orange'], ['Handed to courier', 'orange'],
                    ['Delivered', 'emerald'], ['Cancelled', 'red'], ['Rejected', 'red'],
                ],
                'tips' => [
                    'Each package (item) has its own fulfillment type: warehouse delivery, self-pickup at a branch, or direct hand-off.',
                    'Splitting a package lets one declared item travel to two different recipients without re-creating the order.',
                ],
            ],
            [
                'id' => 'pickups',
                'title' => 'Pickup Assignments',
                'where' => 'Orders → Assign Driver · direct list /admin/pickups',
                'route' => 'admin.pickups.index',
                'who' => 'shipments.view (list) · shipments.edit (assign)',
                'purpose' => 'Assign a rider to collect a submitted shipment from the vendor and bring it to a warehouse.',
                'steps' => [
                    'From an order (or the Pickups list) choose Assign Driver.',
                    'Pick an available rider with the pickup capability and the receiving warehouse.',
                    'The rider sees the job in the driver app and advances it: En-route → Arrived → Picking up → Completed.',
                    'Edit an assignment to change rider or warehouse; Cancel it to return the order to the unassigned pool.',
                    'When the rider arrives at the warehouse, mark the assignment Received (or let warehouse staff receive it from Incoming Packages).',
                ],
                'statuses' => [
                    ['Assigned', 'blue'], ['En route', 'amber'], ['Arrived', 'amber'], ['Picking up', 'amber'],
                    ['Completed', 'emerald'], ['Cancelled', 'red'],
                ],
                'tips' => ['Receiving a pickup creates the warehouse receipt and moves the shipment to At Warehouse.'],
            ],
            [
                'id' => 'walkins',
                'title' => 'Walk-in Shipments',
                'where' => 'Sidebar → Walk-in · /admin/operations/walkin',
                'route' => 'warehouse.walkin.create',
                'who' => 'warehouse.receiving.manage',
                'purpose' => 'Fast intake for vendors who bring parcels to the warehouse counter — no pickup leg needed.',
                'steps' => [
                    'Open Walk-in and look up the vendor by phone (or create them inline).',
                    'Choose fulfillment and destination mode (one destination for all items, or per-item destinations).',
                    'Add each item: description, quantity, delivery method and destination details.',
                    'Save — the shipment, items and warehouse receipt are created in one step.',
                    'Print all barcode labels (or per-item) and stick them on the parcels.',
                    'Items immediately join the normal flow: ready for sorting, transport or delivery.',
                ],
                'tips' => ['Walk-ins skip the approval/pricing step — collect payment at the counter and record it on the shipment.'],
            ],
            [
                'id' => 'receiving',
                'title' => 'Receiving & Labels',
                'where' => 'Sidebar → Incoming Packages · /admin/operations/receipts/pending',
                'route' => 'warehouse.receipts.pending.index',
                'who' => 'warehouse.receiving.manage',
                'purpose' => 'Check in parcels delivered by pickup riders, verify quantities and generate barcode labels.',
                'steps' => [
                    'Open Incoming Packages — each row is a completed pickup awaiting check-in.',
                    'Open one to see declared items, rider photos and per-item confirmations.',
                    'For each item: confirm the received quantity, note damage or shortages, split or regroup if needed.',
                    'Print a barcode label per package and attach it.',
                    'Record any vendor payment taken at the desk (Payments panel).',
                    'Finalize the receipt — labels are locked in, and the shipment becomes At Warehouse.',
                ],
                'tips' => [
                    'Received Pickups keeps the historical archive of finalized receipts.',
                    'A package only becomes scannable/trackable once its label exists — always print before finalizing.',
                ],
            ],
        ],
        'Warehouse Operations' => [
            [
                'id' => 'warehouse-packages',
                'title' => 'Warehouse Packages',
                'where' => 'Sidebar → Warehouse Packages · /admin/operations/packages',
                'route' => 'warehouse.packages.index',
                'who' => 'warehouse.items.scan / shipments.view',
                'purpose' => 'Browse every labelled package in stock, manage fees, and move packages into sorting.',
                'steps' => [
                    'Use Warehouse Packages to find any item by tracking code, description or recipient.',
                    'Open a package to set its delivery fee, mark it paid, or send the recipient a delay notice.',
                    'Add the package to a sort batch directly from its detail page, or reprint its label.',
                    'Use the package detail page to review destination, payment and label information before dispatch.',
                ],
                'tips' => ['A package only appears here after receiving has created its warehouse label.'],
            ],
            [
                'id' => 'package-tracking',
                'title' => 'Package Tracking',
                'where' => 'Admin reference page · /admin/package-tracking',
                'route' => 'admin.package-tracking.index',
                'who' => 'shipments.view',
                'purpose' => 'Follow the custody chain from shelf to doorstep across warehouses and riders.',
                'steps' => [
                    'Search by tracking code, shipment number, recipient or package label.',
                    'Use the custody picture to see whether a package is unclaimed, claimed by a rider, in transit, or delivered.',
                    'Review Location Changes and Rider Transfers to audit every movement between riders and places.',
                    'Use the result to decide whether support should contact the warehouse, assigned rider, or recipient.',
                ],
                'tips' => ['Custody events are recorded automatically whenever a rider scans/claims a label — no manual logging needed.'],
            ],
            [
                'id' => 'sorting',
                'title' => 'Sorting (Sort Batches)',
                'where' => 'Sidebar → Sorting · /admin/operations/sorting',
                'route' => 'warehouse.sorting.index',
                'who' => 'warehouse.sorting.manage',
                'purpose' => 'Group received packages by destination before dispatching them as a transfer or a local delivery.',
                'steps' => [
                    'Create a batch and choose its dispatch mode: Transfer (to another warehouse) or Local Delivery.',
                    'Add eligible items (received, not yet sorted) — filter by destination to keep batches clean.',
                    'Seal the batch when complete; sealed batches are locked.',
                    'Reopen a sealed batch only to fix mistakes (before it has a manifest or run).',
                    'From a sealed batch, create a Transport Manifest (transfer) or a Delivery Run (local delivery) in one click.',
                ],
                'statuses' => [['Open', 'blue'], ['Sealed', 'emerald']],
                'tips' => ['One batch = one destination + one dispatch mode. Mixed destinations belong in separate batches.'],
                'diagram' => 'sorting',
            ],
            [
                'id' => 'transport',
                'title' => 'Transport Manifests',
                'where' => 'Sidebar → Transport Manifests · /admin/operations/manifests/transport',
                'route' => 'warehouse.manifests.transport.index',
                'who' => 'warehouse.manifest.manage',
                'purpose' => 'Move sealed batches between warehouses with verified loading, containers and a waybill.',
                'steps' => [
                    'Create a manifest from a sealed transfer batch — items and destination are inherited.',
                    'Optionally pack items into containers (create, label and print container labels).',
                    'Mark items loaded as they go on the vehicle (per item, per container, or Mark All Loaded).',
                    'Assign a driver with the transport capability, then Dispatch — the driver sees it in the app.',
                    'Resolve scan issues (barcode mismatches reported during loading) by approving or rejecting each exception.',
                    'Print the waybill for the vehicle.',
                    'At the destination: open Incoming Manifests, scan each item in, then Finalize Receipt — items join that warehouse\'s stock.',
                ],
                'statuses' => [
                    ['Draft', 'slate'], ['Assigned', 'blue'], ['Loading', 'amber'], ['In transit', 'orange'],
                    ['Arrived', 'violet'], ['Received', 'emerald'], ['Cancelled', 'red'],
                ],
                'tips' => ['Undo Dispatch / Undo Arrival exist for genuine mistakes — use them before any downstream action.'],
            ],
            [
                'id' => 'incoming-manifests',
                'title' => 'Incoming Manifests',
                'where' => 'Sidebar → Incoming Manifests · /admin/operations/manifests/incoming',
                'route' => 'warehouse.manifests.incoming.index',
                'who' => 'warehouse.manifest.manage',
                'purpose' => 'Receive inter-warehouse manifests at the destination branch and verify every package that arrived.',
                'steps' => [
                    'Open Incoming Manifests to see transfers headed to your warehouse.',
                    'Mark a manifest Arrived when the vehicle reaches the destination branch.',
                    'Scan each package or container as it is unloaded; use manual entry when a label cannot scan.',
                    'Record shortages, excess items or damaged packages before saving each receiving line.',
                    'Finalize Receipt only after all lines are checked — packages then join the destination warehouse stock.',
                ],
                'statuses' => [
                    ['In transit', 'orange'], ['Arrived', 'violet'], ['Receiving', 'amber'], ['Received', 'emerald'],
                ],
                'tips' => ['Do not finalize an incoming manifest until every discrepancy has notes attached.'],
            ],
        ],
        'Delivery' => [
            [
                'id' => 'delivery-runs',
                'title' => 'Delivery Runs',
                'where' => 'Sidebar → Delivery Runs · /admin/operations/deliveries/runs',
                'route' => 'warehouse.deliveries.runs.index',
                'who' => 'warehouse.delivery.assign',
                'purpose' => 'Last-mile dispatch: bundle packages into a run of stops and send a rider out to deliver.',
                'steps' => [
                    'Create a run from a sealed local-delivery batch (stops are auto-grouped per recipient) or from hand-picked items.',
                    'Assign a rider with the delivery capability, then Dispatch — the run appears in their app.',
                    'Each stop is one recipient: the rider arrives, the recipient receives an SMS OTP, and the rider confirms with the code plus a proof photo.',
                    'For bus-handoff stops the rider records the station, fare and courier phone instead of an OTP.',
                    'Resend a stop\'s verification code from the run page if the recipient never got the SMS.',
                    'Confirm hand-offs from the back office (stop level or single item) when a rider cannot.',
                    'Pending Confirmations lists hand-offs still awaiting a final check — clear it daily.',
                ],
                'statuses' => [
                    ['Draft', 'slate'], ['Assigned', 'blue'], ['Out for delivery', 'orange'],
                    ['Partially delivered', 'amber'], ['Completed', 'emerald'], ['Cancelled', 'red'],
                ],
                'tips' => ['Failed stops feed the Contact Queue automatically so the call team can rescue the delivery.'],
            ],
            [
                'id' => 'bus-handoffs',
                'title' => 'Bus Handoffs',
                'where' => 'Within Delivery Runs · public page /h/{token}',
                'who' => 'warehouse.delivery.assign (back office) · public link (recipient)',
                'purpose' => 'Intercity delivery via bus couriers, with SMS confirmation from the recipient closing the loop.',
                'steps' => [
                    'Mark the stop\'s delivery method as Bus Handoff (admin or rider can set it).',
                    'The rider hands the parcel to the bus courier and records station, fare and courier phone.',
                    'The recipient gets an SMS with a one-time link to the public confirmation page.',
                    'The recipient confirms receipt — the item becomes Delivered — or reports an issue (not received, damaged, courier delay).',
                    'Reported issues appear for follow-up; riders can re-send the confirmation link from the app.',
                ],
                'tips' => ['Configure the list of bus stations in Settings → Bus Stations before using this flow.'],
            ],
            [
                'id' => 'collections',
                'title' => 'Collections (Self-Pickup)',
                'where' => 'Operations direct page · /admin/operations/collections',
                'route' => 'warehouse.collections.index',
                'who' => 'warehouse.receiving.manage',
                'purpose' => 'Hand parcels over the counter to recipients who collect from the warehouse themselves.',
                'steps' => [
                    'Items with the self-pickup fulfillment type appear here as Ready once they reach the branch.',
                    'When the customer arrives, verify their identity and the shipment details.',
                    'Mark the shipment Handed Over — the collection timestamp and staff member are recorded.',
                ],
                'statuses' => [['Ready', 'blue'], ['Collected', 'emerald']],
            ],
        ],
        'People' => [
            [
                'id' => 'riders',
                'title' => 'Riders',
                'where' => 'Sidebar → Riders · /admin/riders-drivers',
                'route' => 'admin.drivers.index',
                'who' => 'drivers.view / drivers.edit / drivers.manage',
                'purpose' => 'Onboard and manage the field workforce and what each person is allowed to do.',
                'steps' => [
                    'Create a rider with name, Ghana phone number, email (their app login), vehicle details and a photo.',
                    'Grant capabilities: Pickup, Transport and/or Delivery — these gate which jobs they can be assigned.',
                    'Open a rider to review their assignments, transport manifests, delivery runs and activity log.',
                    'Toggle Active off to instantly stop new assignments (existing work should be reassigned first).',
                ],
                'tips' => ['Riders log into the mobile app with email or phone plus password; create the password here when onboarding.'],
            ],
            [
                'id' => 'rider-teams',
                'title' => 'Rider Teams & Handovers',
                'where' => 'Sidebar → Rider Teams · /admin/rider-teams',
                'route' => 'admin.rider-teams.index',
                'who' => 'drivers.view + delivery/manifest permissions',
                'purpose' => 'Group riders per warehouse and hand bundles of labelled packages to the team leader for distribution.',
                'steps' => [
                    'Create a team under a warehouse and add riders as members.',
                    'Designate one or more leaders — leaders receive and distribute team handovers.',
                    'Create a handover: select labelled packages and assign them to the team; print the handover sheet.',
                    'The leader scans/receives items in the app and allocates them to members.',
                    'Release or Recall labels to correct a handover before items go out.',
                ],
            ],
            [
                'id' => 'vendors',
                'title' => 'Vendors',
                'where' => 'Sidebar → Vendors · /admin/vendors',
                'route' => 'admin.vendors.index',
                'who' => 'vendors.view / vendors.edit / vendors.manage',
                'purpose' => 'Directory of the businesses that ship with you, with their history, payout details and security logs.',
                'steps' => [
                    'Create a vendor with name, Ghana phone (their OTP login), business name and payout account (MoMo network + number).',
                    'Open a vendor for shipment history, package list, app activity logs and OTP logs.',
                    'Toggle Active to block or restore app access; soft-deleted vendors can be Restored later.',
                    'Record payouts from the vendor page or the Commission Payouts module.',
                ],
                'tips' => ['Vendors sign in with phone + OTP only — no passwords to reset, but check OTP Logs when they report login trouble.'],
            ],
            [
                'id' => 'team-users',
                'title' => 'Users',
                'where' => 'Sidebar → Users · /admin/operations/users',
                'route' => 'warehouse.users.index',
                'who' => 'warehouse.users.view / create / edit / deactivate',
                'purpose' => 'Back-office staff accounts, scoped to a warehouse, with per-user activity dashboards.',
                'steps' => [
                    'Create a user with name, email/phone, password and one or more warehouse roles.',
                    'Users belong to a warehouse; HQ staff belong to the HQ warehouse and see everything.',
                    'Open a user to inspect their activity: orders processed, receipts, sorting, payments, deliveries and a full audit log.',
                    'Impersonate a user (HQ only) to see exactly what they see when debugging a complaint; Stop Impersonation returns you to your own session.',
                    'Deactivate accounts on staff exit — never share logins.',
                ],
            ],
        ],
        'Money' => [
            [
                'id' => 'payouts',
                'title' => 'Commission Payouts',
                'where' => 'Sidebar → Commission Payouts · /admin/vendor-payouts',
                'route' => 'admin.vendor-payouts.index',
                'who' => 'vendors.manage',
                'purpose' => 'Track what each vendor has earned and pay it out in recorded, confirmable batches.',
                'steps' => [
                    'The list shows each vendor\'s earned total, available balance, pending payouts and last payout date.',
                    'Create a payout for a vendor — the amount defaults to their available balance.',
                    'Mark the payout Sent when the transfer is initiated (MoMo/bank).',
                    'Confirm it once the vendor acknowledges receipt; the linked earnings are then settled.',
                ],
                'statuses' => [['Pending', 'amber'], ['Sent', 'blue'], ['Confirmed', 'emerald']],
                'tips' => ['Earnings accrue per shipment from the commission rate configured in Settings.'],
            ],
            [
                'id' => 'recipient-payments',
                'title' => 'Recipient Payments (COD)',
                'where' => 'Sidebar → Recipient Payments · /admin/operations/recipient-payments',
                'route' => 'warehouse.recipient-payments.index',
                'who' => 'recipient_payments.view / assign / reconcile / manage_wallets / override',
                'purpose' => 'Collect cash/MoMo from recipients on delivery and reconcile every cedi through sessions and wallets.',
                'steps' => [
                    'Delivered COD items create payment tasks automatically, grouped per recipient.',
                    'Set up Wallets first (MoMo provider, number, owner, warehouse) — they are where collected money lands.',
                    'A collector opens a Session at the start of a shift, then scans package barcodes as recipients pay.',
                    'Use task actions to assign work, log calls, set or override fees, and mark items (or whole groups) paid.',
                    'Close the session at end of shift and reconcile its total against the tasks.',
                    'Reports give daily/weekly collection summaries by location, worker and method.',
                ],
                'statuses' => [['Pending', 'slate'], ['Assigned', 'blue'], ['In progress', 'amber'], ['Paid', 'emerald']],
                'tips' => ['Only users with the override permission can waive or adjust a recipient fee — every override is logged.'],
            ],
        ],
        'Support & Outreach' => [
            [
                'id' => 'contacts',
                'title' => 'Contact Queue',
                'where' => 'Sidebar → Recipient Desk · /admin/operations/contacts',
                'route' => 'warehouse.contacts.index',
                'who' => 'warehouse.contacts.manage',
                'purpose' => 'Phone work-queue for parcels that need a human: failed deliveries, unreachable recipients, follow-ups.',
                'steps' => [
                    'Tasks are created automatically from failed/blocked deliveries; add one manually with Add to Queue.',
                    'Assign tasks to call workers individually, in bulk, or with Auto-Assign (even distribution).',
                    'Workers log each call with an outcome: resolved, callback (with date), or no answer.',
                    'Send a fresh verification code to the recipient mid-call when needed.',
                    'Resolve the task once the issue is cleared; Worker Stats tracks productivity.',
                    'Warehouse teams can Hand Over a stuck task to HQ.',
                ],
                'statuses' => [['Pending', 'slate'], ['Assigned', 'blue'], ['In progress', 'amber'], ['Resolved', 'emerald']],
            ],
            [
                'id' => 'marketing',
                'title' => 'Marketing Broadcasts',
                'where' => 'Sidebar → Marketing · /admin/marketing',
                'route' => 'admin.marketing.index',
                'who' => 'settings.view',
                'purpose' => 'Bulk announcements to vendors and riders over push, SMS or email.',
                'steps' => [
                    'Check the audience counters — they show how many vendors/riders are reachable per channel.',
                    'Compose: pick the audience (vendors, riders, both), the channel, and write title + message.',
                    'Preview, confirm and send; the Broadcast Log keeps history and delivery stats.',
                ],
                'tips' => ['SMS costs money per recipient — prefer push for routine announcements.'],
            ],
        ],
        'Network & Configuration' => [
            [
                'id' => 'warehouses',
                'title' => 'Warehouses & Capabilities',
                'where' => 'Sidebar → Warehouses · /admin/warehouses',
                'route' => 'admin.warehouses.index',
                'who' => 'warehouses.view / warehouses.manage',
                'purpose' => 'The branch network: each warehouse\'s location, staff, and which modules it is allowed to run.',
                'steps' => [
                    'Create a warehouse with name, code, region/district, address, contact phone and capacity.',
                    'The HQ flag marks headquarters — HQ users get global reach across the network.',
                    'Capabilities control what a branch can do (receiving, sorting, transport, delivery, recipient payments, contacts) and at what scope: own, selected branches, or global.',
                    'Review each warehouse\'s staff under its Users tab.',
                    'Deactivate a warehouse to freeze its operations without deleting history.',
                ],
                'tips' => ['If a branch can\'t see a module it should have, check Warehouse Capabilities before touching roles.'],
            ],
            [
                'id' => 'locations',
                'title' => 'Locations',
                'where' => 'Sidebar → Warehouses → Locations tab · /admin/locations',
                'route' => 'admin.locations.index',
                'who' => 'locations.view / locations.manage',
                'purpose' => 'The Ghana geography tree (Region → District → Town) used by every address picker in the system.',
                'steps' => [
                    'Manage Regions, Districts and Towns in their respective tabs.',
                    'Deactivate (rather than delete) areas you don\'t serve yet — they disappear from pickers but keep history intact.',
                    'Add towns as coverage expands; vendor and admin shipment forms search this list live.',
                ],
            ],
            [
                'id' => 'roles',
                'title' => 'Roles & Permissions',
                'where' => 'Sidebar → Roles · /admin/roles',
                'route' => 'admin.roles.index',
                'who' => 'roles.view / roles.manage',
                'purpose' => 'Permission sets that define what every back-office account can see and do.',
                'steps' => [
                    'Permissions follow module.action naming (e.g. shipments.view, vendors.manage, warehouse.sorting.manage).',
                    'Create a role, tick its permissions, and mark it as a warehouse role if branch managers may assign it.',
                    'Assign roles to staff in Users (warehouse users) or via the user\'s detail page.',
                    'Access is the intersection of role permissions and the user\'s warehouse capabilities — HQ bypasses capability limits.',
                    'Audit logs (Settings → Audit) record every admin action with before/after data.',
                ],
                'tips' => ['Prefer many small roles (e.g. "Sorter", "COD Desk") over one mega-role — easier to audit and revoke.'],
                'diagram' => 'access',
            ],
            [
                'id' => 'settings',
                'title' => 'Settings',
                'where' => 'Sidebar → Settings · /admin/settings',
                'route' => 'admin.settings.index',
                'who' => 'settings.view / settings.edit',
                'purpose' => 'Platform-wide configuration, integrations, reference data and system logs.',
                'steps' => [
                    'Platform: name, logo, support email/phone/address (shown on the public site and legal pages).',
                    'Integrations: SMS provider, SMTP mail, storage (local or S3), Firebase push credentials — each has a Test button; use it after every change.',
                    'Reference data: pickup vehicle types, bus stations, delivery failure reasons, delivery delay reasons.',
                    'Email templates: edit, preview and toggle the transactional emails; variables like the shipment number are inserted with placeholders.',
                    'Commission: the per-package vendor rate that drives earnings.',
                    'Logs: application, email, SMS, OTP, notification and admin audit logs — your first stop when "the SMS never arrived".',
                    'System health: cache clearing, queue and disk status.',
                ],
                'tips' => ['Changing the commission rate affects new shipments only — existing earnings are not recalculated.'],
            ],
        ],
        'Mobile Apps' => [
            [
                'id' => 'vendor-app',
                'title' => 'Vendor App & Portal',
                'where' => 'ParcelMan Express app · /vendor on the website',
                'who' => 'Vendors (phone + OTP sign-in)',
                'purpose' => 'Where vendors create shipments, track parcels, and manage earnings — the source of most orders.',
                'steps' => [
                    'Sign in: Ghana phone number → 6-digit SMS OTP. New numbers complete a short registration (name, business, email).',
                    'Home shows parcel counts, earnings summary and recent activity.',
                    'Send Package: pickup town, vehicle type (motorbike, aboboyaa, van, truck), then recipients — each with phone, destination and items with photos.',
                    'Save as draft or Submit; submitted shipments wait for HQ pricing and pickup assignment.',
                    'Parcel detail shows the assigned rider (with call button), per-recipient delivery status, and a live timeline.',
                    'Earnings shows balances and payout history; Account manages profile, payout account, notifications and account deletion.',
                ],
                'tips' => [
                    'OTPs expire in a few minutes and registration sessions in ten — vendors who stall must restart sign-in.',
                    'Vendors cannot edit recipients once the pickup is en-route; changes go through the back office.',
                ],
            ],
            [
                'id' => 'driver-app',
                'title' => 'Driver App & Portal',
                'where' => 'ParcelMan Express app · /driver on the website',
                'who' => 'Riders (email or phone + password sign-in)',
                'purpose' => 'The rider\'s entire workday: pickups, transports, deliveries, scanning and custody.',
                'steps' => [
                    'Home lists active pickups, transports, unfinished delivery runs, bus-handoff follow-ups and incoming package transfers.',
                    'Pickups: En-route → Arrived → confirm each item → Confirm Pickup, then deliver to the warehouse.',
                    'Scan (centre button): claim packages into personal or team custody by barcode — camera or manual entry.',
                    'Transports: Start Loading → scan items aboard → Depart → Arrive; scan mismatches become exceptions for admin review.',
                    'Deliveries: per stop — Arrive, enter the recipient\'s SMS OTP, take a proof photo, optionally collect a fee, Confirm. Bus-handoff stops record station + courier instead.',
                    'Failures use a reason list and automatically alert the back office.',
                    'Packages: see everything in custody; release, transfer to another rider, or view history.',
                    'Teams: leaders receive handovers, scan items in, and allocate them to members.',
                ],
                'tips' => [
                    'Scanning requires a live connection — there is no offline queue.',
                    'If the recipient\'s OTP never arrives, the back office can resend it from the delivery run page.',
                ],
            ],
        ],
    ];

    $statusTone = [
        'slate' => 'bg-slate-100 text-slate-700',
        'blue' => 'bg-blue-50 text-blue-700',
        'amber' => 'bg-amber-50 text-amber-700',
        'orange' => 'bg-orange-50 text-orange-700',
        'violet' => 'bg-violet-50 text-violet-700',
        'emerald' => 'bg-emerald-50 text-emerald-700',
        'red' => 'bg-red-50 text-red-700',
    ];

    $tocStatic = [
        ['overview', 'What is Parcelman?'],
        ['getting-started', 'Getting Started'],
        ['personas', 'Where Should I Start?'],
        ['lifecycle', 'Parcel Lifecycle'],
    ];
    $tocTail = [
        ['workflows', 'End-to-End Workflows'],
        ['glossary', 'Glossary'],
        ['faq', 'FAQ & Troubleshooting'],
    ];

    $manualLastUpdated = 'June 10, 2026';

    // Cross-links rendered as "Related" chips on each module card.
    $manualRelated = [
        'orders' => ['lifecycle', 'pickups', 'receiving'],
        'pickups' => ['orders', 'riders', 'receiving'],
        'walkins' => ['receiving', 'sorting', 'vendors'],
        'receiving' => ['pickups', 'warehouse-packages', 'sorting'],
        'warehouse-packages' => ['receiving', 'sorting', 'package-tracking'],
        'package-tracking' => ['warehouse-packages', 'delivery-runs', 'rider-teams'],
        'sorting' => ['transport', 'delivery-runs', 'warehouse-packages'],
        'transport' => ['sorting', 'incoming-manifests', 'riders'],
        'incoming-manifests' => ['transport', 'warehouse-packages'],
        'delivery-runs' => ['sorting', 'bus-handoffs', 'recipient-payments', 'contacts'],
        'bus-handoffs' => ['delivery-runs', 'settings'],
        'collections' => ['orders', 'warehouse-packages'],
        'riders' => ['rider-teams', 'pickups', 'delivery-runs'],
        'rider-teams' => ['riders', 'package-tracking', 'delivery-runs'],
        'vendors' => ['orders', 'payouts', 'vendor-app'],
        'team-users' => ['roles', 'warehouses'],
        'payouts' => ['vendors', 'settings'],
        'recipient-payments' => ['delivery-runs', 'contacts', 'settings'],
        'contacts' => ['delivery-runs', 'recipient-payments'],
        'marketing' => ['vendors', 'settings'],
        'warehouses' => ['locations', 'team-users', 'roles'],
        'locations' => ['warehouses'],
        'roles' => ['team-users', 'warehouses'],
        'settings' => ['roles', 'marketing', 'bus-handoffs'],
        'vendor-app' => ['orders', 'vendors', 'payouts'],
        'driver-app' => ['pickups', 'delivery-runs', 'riders', 'rider-teams'],
    ];

    $sectionTitles = [
        'overview' => 'What is Parcelman?',
        'getting-started' => 'Getting Started',
        'personas' => 'Where Should I Start?',
        'lifecycle' => 'Parcel Lifecycle',
        'workflows' => 'End-to-End Workflows',
        'glossary' => 'Glossary',
        'faq' => 'FAQ & Troubleshooting',
    ];
    foreach ($manualGroups as $groupModules) {
        foreach ($groupModules as $entry) {
            $sectionTitles[$entry['id']] = $entry['title'];
        }
    }

    $searchFor = function (array $m): string {
        $statuses = array_map(
            fn (array $status): string => implode(' ', $status),
            $m['statuses'] ?? [],
        );

        return strtolower(implode(' ', array_filter([
            $m['title'] ?? '',
            $m['where'] ?? '',
            $m['who'] ?? '',
            $m['purpose'] ?? '',
            implode(' ', $m['steps'] ?? []),
            implode(' ', $m['tips'] ?? []),
            implode(' ', $statuses),
        ])));
    };

    $moduleUrl = function (array $m): ?string {
        if (! empty($m['route']) && \Illuminate\Support\Facades\Route::has($m['route'])) {
            return route($m['route']);
        }

        return $m['href'] ?? null;
    };

    $staticSearchHaystacks = [
        'overview what is parcelman platform architecture portals roles admin warehouse vendor driver guards surfaces',
        'getting started login sign in navigation sidebar global search context switcher profile password impersonation notifications',
        'where should i start personas reading path onboarding new staff hq administrator warehouse manager receiving clerk counter sorter dispatcher cod desk collector call support worker rider operations',
        'parcel lifecycle statuses status flow pipeline shipment item fulfillment draft submitted delivered state machine diagram',
        'end to end workflows recipes standard shipment walk-in transfer bus handoff cod payout how do i swimlane diagram',
        'glossary terms definitions vocabulary shipment item package label barcode sort batch manifest container delivery run stop bus handoff custody handover walk-in cod recipient payment capability permission pickup assignment',
        'faq troubleshooting problems otp sms not received label reprint batch sealed reopen scan mismatch vendor cannot log in permission missing module recipient unreachable',
    ];
    $manualSearchHaystacks = [];

    foreach ($manualGroups as $modules) {
        foreach ($modules as $module) {
            $manualSearchHaystacks[] = $searchFor($module);
        }
    }

    $allSearchHaystacks = array_merge($staticSearchHaystacks, $manualSearchHaystacks);
@endphp

<div class="space-y-5" x-data="manualPage()">

    {{-- Hero --}}
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-slate-950 shadow-sm">
        <div class="relative px-4 py-5 sm:px-6">
            <div class="pointer-events-none absolute inset-y-0 right-0 w-1/2 bg-[radial-gradient(circle_at_top_right,rgba(249,115,22,0.22),transparent_58%)]"></div>
            <div class="relative flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                <div class="min-w-0 max-w-2xl">
                    <div class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/10 px-3 py-1 text-[10px] font-black uppercase tracking-[0.16em] text-orange-200">
                        Help &amp; Documentation
                    </div>
                    <h1 class="mt-3 text-2xl font-black tracking-tight text-white sm:text-3xl">System Usage Manual</h1>
                    <p class="mt-2 text-sm font-medium leading-6 text-slate-300">
                        How Parcelman Express works end to end — every back-office module, the warehouse workflows,
                        and the vendor &amp; rider apps. Use the search box or the contents list to jump straight to a topic.
                    </p>
                </div>
                <div class="flex w-full flex-col gap-2 sm:flex-row xl:w-auto">
                    <label class="relative flex-1 sm:min-w-[280px]">
                        <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/></svg>
                        <input type="search" x-model.debounce.150ms="q" placeholder="Search the manual…"
                               class="w-full rounded-xl border border-white/10 bg-white/10 py-2.5 pl-9 pr-3 text-sm font-semibold text-white outline-none transition placeholder:text-slate-400 focus:border-orange-400/60 focus:ring-4 focus:ring-orange-500/10">
                    </label>
                    <button type="button" onclick="window.print()"
                            class="inline-flex items-center justify-center gap-2 rounded-xl border border-white/10 bg-white/10 px-4 py-2.5 text-sm font-black text-white transition hover:bg-white/20 print:hidden">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4H7v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                        Print
                    </button>
                </div>
            </div>
        </div>
    </section>

    <div class="grid items-start gap-5 xl:grid-cols-[260px_minmax(0,1fr)]">

        {{-- Table of contents --}}
        <aside class="hidden xl:block sticky top-[76px] max-h-[calc(100vh-100px)] overflow-y-auto rounded-3xl border border-slate-200/80 bg-white p-4 shadow-sm print:hidden">
            <p class="px-2 text-[10px] font-black uppercase tracking-[0.16em] text-slate-400">Contents</p>
            <nav class="mt-2 space-y-4 text-sm">
                <div>
                    <p class="px-2 text-[11px] font-black uppercase tracking-wide text-orange-600">Start Here</p>
                    <div class="mt-1 space-y-0.5">
                        @foreach($tocStatic as [$id, $label])
                            <a href="#{{ $id }}" class="block rounded-lg px-2 py-1.5 font-semibold transition" :class="tocCls(@js($id))">{{ $label }}</a>
                        @endforeach
                    </div>
                </div>
                @foreach($manualGroups as $groupName => $modules)
                    <div x-show="groupVisible(@js(array_map($searchFor, $modules)))">
                        <p class="px-2 text-[11px] font-black uppercase tracking-wide text-orange-600">{{ $groupName }}</p>
                        <div class="mt-1 space-y-0.5">
                            @foreach($modules as $m)
                                <a href="#{{ $m['id'] }}" x-show="matches(@js($searchFor($m)))"
                                   class="block rounded-lg px-2 py-1.5 font-semibold transition" :class="tocCls(@js($m['id']))">{{ $m['title'] }}</a>
                            @endforeach
                        </div>
                    </div>
                @endforeach
                <div>
                    <p class="px-2 text-[11px] font-black uppercase tracking-wide text-orange-600">Reference</p>
                    <div class="mt-1 space-y-0.5">
                        @foreach($tocTail as [$id, $label])
                            <a href="#{{ $id }}" class="block rounded-lg px-2 py-1.5 font-semibold transition" :class="tocCls(@js($id))">{{ $label }}</a>
                        @endforeach
                    </div>
                </div>
            </nav>
        </aside>

        {{-- Manual body --}}
        <div class="min-w-0 space-y-5">

            {{-- Jump menu (below xl, where the TOC is hidden) --}}
            <div class="xl:hidden print:hidden">
                <select @change="if ($event.target.value) location.href = $event.target.value"
                        class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-bold text-slate-700 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                    <option value="">Jump to a section…</option>
                    <optgroup label="Start Here">
                        @foreach($tocStatic as [$id, $label])
                            <option value="#{{ $id }}">{{ $label }}</option>
                        @endforeach
                    </optgroup>
                    @foreach($manualGroups as $groupName => $modules)
                        <optgroup label="{{ $groupName }}">
                            @foreach($modules as $m)
                                <option value="#{{ $m['id'] }}">{{ $m['title'] }}</option>
                            @endforeach
                        </optgroup>
                    @endforeach
                    <optgroup label="Reference">
                        @foreach($tocTail as [$id, $label])
                            <option value="#{{ $id }}">{{ $label }}</option>
                        @endforeach
                    </optgroup>
                </select>
            </div>

            {{-- Overview --}}
            <section id="overview" class="manual-section scroll-mt-24 overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-sm" x-show="matches('overview what is parcelman platform architecture portals roles admin warehouse vendor driver guards surfaces')">
                <div class="border-b border-slate-200/60 px-5 py-4">
                    <div class="flex items-center gap-2">
                        <h2 class="text-lg font-extrabold text-slate-900">What is Parcelman?</h2>
                        @include('admin.manual.partials.copy-link', ['anchor' => 'overview'])
                    </div>
                    <p class="text-sm text-slate-500">The platform at a glance.</p>
                </div>
                <div class="space-y-4 p-5 text-sm leading-6 text-slate-700">
                    <p>
                        Parcelman Express is a multi-warehouse parcel logistics platform for Ghana. Vendors (shops and social
                        sellers) request parcel pickups from an app; riders collect, warehouses receive, sort and forward;
                        and parcels reach recipients by rider delivery, bus handoff or counter collection — with payments,
                        custody and notifications tracked the whole way.
                    </p>
                    <div class="grid gap-3 sm:grid-cols-2">
                        @foreach([
                            ['Admin portal', 'This back office at /admin. HQ staff run the network: orders, pricing, people, money and configuration.'],
                            ['Warehouse portal', 'The same back office scoped to one branch: walk-ins, receiving, sorting, transport, delivery and COD desks.'],
                            ['Vendor app & portal', 'Mobile-first shipment creation and tracking. Vendors sign in with phone + OTP.'],
                            ['Driver app & portal', 'The rider\'s toolkit: pickups, scanning, transports, delivery runs. Riders sign in with email or phone + password.'],
                        ] as [$t, $d])
                            <div class="rounded-2xl border border-slate-200 bg-slate-50/60 p-4">
                                <p class="font-extrabold text-slate-900">{{ $t }}</p>
                                <p class="mt-1 text-slate-600">{{ $d }}</p>
                            </div>
                        @endforeach
                    </div>
                    <p>
                        One public page completes the loop: recipients of bus-handoff parcels confirm receipt through a
                        tokenised SMS link — no account needed.
                    </p>
                </div>
            </section>

            {{-- Getting started --}}
            <section id="getting-started" class="manual-section scroll-mt-24 overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-sm" x-show="matches('getting started login sign in navigation sidebar global search context switcher profile password impersonation notifications')">
                <div class="border-b border-slate-200/60 px-5 py-4">
                    <div class="flex items-center gap-2">
                        <h2 class="text-lg font-extrabold text-slate-900">Getting Started</h2>
                        @include('admin.manual.partials.copy-link', ['anchor' => 'getting-started'])
                    </div>
                    <p class="text-sm text-slate-500">Signing in and finding your way around.</p>
                </div>
                <div class="p-5">
                    <ol class="manual-steps">
                        <li>Sign in at <span class="font-bold">/admin/login</span> with your email <em>or</em> Ghana phone number plus password. Accounts are created by an administrator in Users.</li>
                        <li>The sidebar adapts to your permissions and warehouse — you only see modules you can use. Collapse it with the toggle for more space.</li>
                        <li>Global search (top bar, 2+ characters) finds shipments, packages, vendors, riders and transactions in one box.</li>
                        <li>HQ users can switch warehouse context from the header to view the network through any branch's eyes.</li>
                        <li>Your avatar menu holds Profile (name, email, phone), Change Password, and the notifications log.</li>
                        <li>HQ admins can impersonate a branch user from their profile page when debugging — a banner shows until you stop impersonating.</li>
                    </ol>
                </div>
            </section>

            {{-- Persona reading paths --}}
            <section id="personas" class="manual-section scroll-mt-24 overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-sm" x-show="matches('where should i start personas reading path onboarding new staff hq administrator warehouse manager receiving clerk counter sorter dispatcher cod desk collector call support worker rider operations')">
                <div class="border-b border-slate-200/60 px-5 py-4">
                    <div class="flex items-center gap-2">
                        <h2 class="text-lg font-extrabold text-slate-900">Where Should I Start?</h2>
                        @include('admin.manual.partials.copy-link', ['anchor' => 'personas'])
                    </div>
                    <p class="text-sm text-slate-500">Pick your role — read these sections in order and skip the rest until you need it.</p>
                </div>
                <div class="grid gap-3 p-5 sm:grid-cols-2">
                    @foreach([
                        ['HQ administrator', 'You run the whole network: pricing, people, money and configuration.', ['overview', 'lifecycle', 'orders', 'pickups', 'warehouses', 'roles', 'settings']],
                        ['Warehouse manager', 'You own one branch end to end, from intake to dispatch and staff.', ['lifecycle', 'walkins', 'receiving', 'sorting', 'delivery-runs', 'team-users', 'contacts']],
                        ['Receiving / counter clerk', 'You check parcels in, print labels and serve walk-in vendors.', ['walkins', 'receiving', 'warehouse-packages', 'collections']],
                        ['Sorter / dispatch coordinator', 'You batch parcels and get them onto the right vehicle or run.', ['sorting', 'transport', 'incoming-manifests', 'delivery-runs']],
                        ['COD desk collector', 'You collect recipient payments and reconcile every cedi.', ['recipient-payments', 'warehouse-packages', 'contacts', 'faq']],
                        ['Call / support worker', 'You rescue stuck deliveries and answer "where is my parcel?".', ['contacts', 'package-tracking', 'delivery-runs', 'bus-handoffs', 'faq']],
                        ['Rider operations', 'You onboard riders, build teams and keep custody clean.', ['riders', 'rider-teams', 'driver-app', 'package-tracking']],
                        ['Vendor support', 'You onboard vendors and resolve their app and payout questions.', ['vendors', 'vendor-app', 'payouts', 'orders']],
                    ] as [$persona, $blurb, $path])
                        <div class="rounded-2xl border border-slate-200 bg-slate-50/40 p-4">
                            <p class="font-extrabold text-slate-900">{{ $persona }}</p>
                            <p class="mt-1 text-sm leading-6 text-slate-600">{{ $blurb }}</p>
                            <ol class="mt-3 flex flex-wrap items-center gap-y-1.5 text-[11px] font-bold">
                                @foreach($path as $i => $pid)
                                    @if($i > 0)
                                        <svg class="mx-1 h-3 w-3 shrink-0 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                                    @endif
                                    <li><a href="#{{ $pid }}" class="rounded-full bg-white px-2.5 py-1 text-slate-600 ring-1 ring-slate-200 transition hover:bg-orange-50 hover:text-orange-700 hover:ring-orange-200">{{ $sectionTitles[$pid] ?? $pid }}</a></li>
                                @endforeach
                            </ol>
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- Lifecycle --}}
            <section id="lifecycle" class="manual-section scroll-mt-24 overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-sm" x-show="matches('parcel lifecycle statuses status flow pipeline shipment item fulfillment draft submitted delivered state machine diagram')">
                <div class="border-b border-slate-200/60 px-5 py-4">
                    <div class="flex items-center gap-2">
                        <h2 class="text-lg font-extrabold text-slate-900">The Parcel Lifecycle</h2>
                        @include('admin.manual.partials.copy-link', ['anchor' => 'lifecycle'])
                    </div>
                    <p class="text-sm text-slate-500">Every parcel follows the same pipeline — each stage maps to one module below.</p>
                </div>
                <div class="space-y-5 p-5 text-sm text-slate-700">

                    {{-- Lifecycle flow diagram --}}
                    <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-slate-50/40 p-3">
                        <svg viewBox="0 0 760 474" class="h-auto w-full" style="min-width:640px" role="img" aria-label="Parcel lifecycle flow diagram">
                            <title>Parcel lifecycle flow</title>
                            <defs>
                                <marker id="arr-lc" viewBox="0 0 10 10" refX="9" refY="5" markerWidth="6.5" markerHeight="6.5" orient="auto-start-reverse">
                                    <path d="M0,0 L10,5 L0,10 z" fill="#94a3b8"/>
                                </marker>
                            </defs>

                            <text x="16" y="20" font-size="10" fill="#94a3b8">Solid = main path &#183; Dashed = exception or shortcut &#183; Orange = delivery branches (one per fulfillment type)</text>

                            <g>
                                <rect x="24" y="38" width="124" height="52" rx="10" fill="#ffffff" stroke="#e2e8f0"/>
                                <text x="86" y="60" text-anchor="middle" font-size="12.5" font-weight="700" fill="#0f172a">Draft</text>
                                <text x="86" y="76" text-anchor="middle" font-size="10.5" fill="#64748b">vendor composing</text>

                                <rect x="188" y="38" width="124" height="52" rx="10" fill="#ffffff" stroke="#e2e8f0"/>
                                <text x="250" y="60" text-anchor="middle" font-size="12.5" font-weight="700" fill="#0f172a">Submitted</text>
                                <text x="250" y="76" text-anchor="middle" font-size="10.5" fill="#64748b">awaiting review</text>

                                <rect x="352" y="38" width="124" height="52" rx="10" fill="#ffffff" stroke="#e2e8f0"/>
                                <text x="414" y="60" text-anchor="middle" font-size="12.5" font-weight="700" fill="#0f172a">Processing</text>
                                <text x="414" y="76" text-anchor="middle" font-size="10.5" fill="#64748b">priced, charges set</text>

                                <rect x="516" y="38" width="124" height="52" rx="10" fill="#ffffff" stroke="#e2e8f0"/>
                                <text x="578" y="60" text-anchor="middle" font-size="12.5" font-weight="700" fill="#0f172a">Pickup</text>
                                <text x="578" y="76" text-anchor="middle" font-size="10.5" fill="#64748b">rider collects</text>

                                <line x1="148" y1="64" x2="186" y2="64" stroke="#94a3b8" stroke-width="1.5" marker-end="url(#arr-lc)"/>
                                <line x1="312" y1="64" x2="350" y2="64" stroke="#94a3b8" stroke-width="1.5" marker-end="url(#arr-lc)"/>
                                <line x1="476" y1="64" x2="514" y2="64" stroke="#94a3b8" stroke-width="1.5" marker-end="url(#arr-lc)"/>
                            </g>

                            <g>
                                <rect x="270" y="128" width="124" height="40" rx="10" fill="#f8fafc" stroke="#e2e8f0" stroke-dasharray="4 3"/>
                                <text x="332" y="152" text-anchor="middle" font-size="11.5" font-weight="600" fill="#64748b">Rejected &#8617; reopen</text>
                                <path d="M 414 92 L 414 110 Q 414 122 402 124 L 396 125" fill="none" stroke="#94a3b8" stroke-width="1.5" marker-end="url(#arr-lc)"/>
                                <path d="M 268 148 Q 250 148 250 120 L 250 94" fill="none" stroke="#94a3b8" stroke-width="1.5" stroke-dasharray="4 3" marker-end="url(#arr-lc)"/>
                            </g>

                            <g>
                                <path d="M 640 64 Q 706 64 706 110 Q 706 196 648 196 L 644 196" fill="none" stroke="#94a3b8" stroke-width="1.5" marker-end="url(#arr-lc)"/>

                                <rect x="516" y="170" width="124" height="52" rx="10" fill="#ffffff" stroke="#e2e8f0"/>
                                <text x="578" y="192" text-anchor="middle" font-size="12.5" font-weight="700" fill="#0f172a">At warehouse</text>
                                <text x="578" y="208" text-anchor="middle" font-size="10.5" fill="#64748b">received + labelled</text>

                                <rect x="352" y="170" width="124" height="52" rx="10" fill="#ffffff" stroke="#e2e8f0"/>
                                <text x="414" y="192" text-anchor="middle" font-size="12.5" font-weight="700" fill="#0f172a">Sorted</text>
                                <text x="414" y="208" text-anchor="middle" font-size="10.5" fill="#64748b">batched by destination</text>

                                <rect x="188" y="170" width="124" height="52" rx="10" fill="#ffffff" stroke="#e2e8f0"/>
                                <text x="250" y="192" text-anchor="middle" font-size="12.5" font-weight="700" fill="#0f172a">In transit</text>
                                <text x="250" y="208" text-anchor="middle" font-size="10.5" fill="#64748b">if other city</text>

                                <rect x="24" y="170" width="124" height="52" rx="10" fill="#ffffff" stroke="#e2e8f0"/>
                                <text x="86" y="192" text-anchor="middle" font-size="12.5" font-weight="700" fill="#0f172a">At destination</text>
                                <text x="86" y="208" text-anchor="middle" font-size="10.5" fill="#64748b">branch stock</text>

                                <line x1="514" y1="196" x2="478" y2="196" stroke="#94a3b8" stroke-width="1.5" marker-end="url(#arr-lc)"/>
                                <line x1="350" y1="196" x2="314" y2="196" stroke="#94a3b8" stroke-width="1.5" marker-end="url(#arr-lc)"/>
                                <line x1="186" y1="196" x2="150" y2="196" stroke="#94a3b8" stroke-width="1.5" marker-end="url(#arr-lc)"/>

                                <path d="M 414 222 Q 414 252 380 258 L 130 258 Q 92 258 88 230 L 87 224" fill="none" stroke="#94a3b8" stroke-width="1.5" stroke-dasharray="4 3"/>
                                <text x="252" y="250" text-anchor="middle" font-size="10" fill="#94a3b8">local delivery — skips transit</text>
                            </g>

                            <g>
                                <line x1="86" y1="222" x2="86" y2="296" stroke="#94a3b8" stroke-width="1.5"/>
                                <line x1="86" y1="296" x2="630" y2="296" stroke="#94a3b8" stroke-width="1.5"/>
                                <line x1="120" y1="296" x2="120" y2="318" stroke="#94a3b8" stroke-width="1.5" marker-end="url(#arr-lc)"/>
                                <line x1="380" y1="296" x2="380" y2="318" stroke="#94a3b8" stroke-width="1.5" marker-end="url(#arr-lc)"/>
                                <line x1="630" y1="296" x2="630" y2="318" stroke="#94a3b8" stroke-width="1.5" marker-end="url(#arr-lc)"/>

                                <rect x="40" y="320" width="160" height="58" rx="10" fill="#fff7ed" stroke="#fed7aa"/>
                                <text x="120" y="343" text-anchor="middle" font-size="12.5" font-weight="700" fill="#0f172a">Rider delivery</text>
                                <text x="120" y="360" text-anchor="middle" font-size="10.5" fill="#9a3412">OTP + proof photo</text>

                                <rect x="300" y="320" width="160" height="58" rx="10" fill="#fff7ed" stroke="#fed7aa"/>
                                <text x="380" y="343" text-anchor="middle" font-size="12.5" font-weight="700" fill="#0f172a">Bus handoff</text>
                                <text x="380" y="360" text-anchor="middle" font-size="10.5" fill="#9a3412">SMS link confirms</text>

                                <rect x="550" y="320" width="160" height="58" rx="10" fill="#fff7ed" stroke="#fed7aa"/>
                                <text x="630" y="343" text-anchor="middle" font-size="12.5" font-weight="700" fill="#0f172a">Self-pickup</text>
                                <text x="630" y="360" text-anchor="middle" font-size="10.5" fill="#9a3412">counter handover</text>
                            </g>

                            <g>
                                <line x1="120" y1="378" x2="120" y2="402" stroke="#94a3b8" stroke-width="1.5"/>
                                <line x1="380" y1="378" x2="380" y2="402" stroke="#94a3b8" stroke-width="1.5"/>
                                <line x1="630" y1="378" x2="630" y2="402" stroke="#94a3b8" stroke-width="1.5"/>
                                <line x1="120" y1="402" x2="630" y2="402" stroke="#94a3b8" stroke-width="1.5"/>
                                <line x1="380" y1="402" x2="380" y2="414" stroke="#94a3b8" stroke-width="1.5" marker-end="url(#arr-lc)"/>

                                <rect x="300" y="416" width="160" height="44" rx="10" fill="#ffffff" stroke="#ea580c" stroke-width="1.5"/>
                                <text x="380" y="443" text-anchor="middle" font-size="12.5" font-weight="800" fill="#ea580c">Delivered &#10003;</text>
                            </g>
                        </svg>
                    </div>

                    <div class="grid gap-4 lg:grid-cols-2">
                        <div class="rounded-2xl border border-slate-200 p-4">
                            <p class="text-xs font-black uppercase tracking-wide text-slate-400">Shipment statuses</p>
                            <div class="mt-2 flex flex-wrap gap-1.5">
                                @foreach([['draft','slate'],['submitted','blue'],['processing','blue'],['pickup_assigned','amber'],['picked_up','amber'],['at_warehouse','violet'],['sorted','violet'],['in_transit','orange'],['at_destination','orange'],['out_for_delivery','orange'],['handed_to_courier','orange'],['delivered','emerald'],['cancelled','red'],['rejected','red']] as [$s, $tone])
                                    <span class="rounded-md px-2 py-1 text-[11px] font-bold {{ $statusTone[$tone] }}">{{ $s }}</span>
                                @endforeach
                            </div>
                        </div>
                        <div class="rounded-2xl border border-slate-200 p-4">
                            <p class="text-xs font-black uppercase tracking-wide text-slate-400">Per-package fulfillment types</p>
                            <ul class="mt-2 space-y-1.5">
                                <li><span class="font-bold text-slate-900">warehouse</span> — standard rider delivery to the recipient's door.</li>
                                <li><span class="font-bold text-slate-900">self_pickup</span> — the recipient collects from a branch (Collections).</li>
                                <li><span class="font-bold text-slate-900">direct</span> — handed straight to a courier/bus without the warehouse leg.</li>
                            </ul>
                            <p class="mt-3 text-xs text-slate-500">Packages track their own status independently of the parent shipment, so one order can be partly delivered while the rest is still in transit.</p>
                        </div>
                    </div>
                </div>
            </section>

            {{-- Module sections --}}
            @foreach($manualGroups as $groupName => $modules)
                @foreach($modules as $m)
                    <section id="{{ $m['id'] }}" class="manual-section scroll-mt-24 overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-sm" x-show="matches(@js($searchFor($m)))">
                        <div class="border-b border-slate-200/60 px-5 py-4">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div class="flex items-center gap-2">
                                    <h2 class="text-lg font-extrabold text-slate-900">{{ $m['title'] }}</h2>
                                    @include('admin.manual.partials.copy-link', ['anchor' => $m['id']])
                                </div>
                                <span class="rounded-full bg-slate-100 px-3 py-1 text-[10px] font-black uppercase tracking-wide text-slate-500">{{ $groupName }}</span>
                            </div>
                            <p class="mt-1 text-sm text-slate-600">{{ $m['purpose'] }}</p>
                            <div class="mt-2 flex flex-wrap gap-x-5 gap-y-1 text-xs font-semibold text-slate-500">
                                @php($moduleLink = $moduleUrl($m))
                                <span>
                                    <span class="text-slate-400">Where:</span>
                                    @if($moduleLink)
                                        <a href="{{ $moduleLink }}" class="text-orange-700 underline decoration-orange-200 underline-offset-2 transition hover:text-orange-800 hover:decoration-orange-400">{{ $m['where'] }}</a>
                                    @else
                                        {{ $m['where'] }}
                                    @endif
                                </span>
                                <span><span class="text-slate-400">Access:</span> {{ $m['who'] }}</span>
                            </div>
                        </div>
                        <div class="space-y-4 p-5">

                            @if(($m['diagram'] ?? null) === 'sorting')
                                {{-- Sorting dispatch decision diagram --}}
                                <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-slate-50/40 p-3">
                                    <svg viewBox="0 0 760 312" class="h-auto w-full" style="min-width:600px" role="img" aria-label="Sorting dispatch decision diagram">
                                        <title>Sorting dispatch decision</title>
                                        <defs>
                                            <marker id="arr-st" viewBox="0 0 10 10" refX="9" refY="5" markerWidth="6.5" markerHeight="6.5" orient="auto-start-reverse">
                                                <path d="M0,0 L10,5 L0,10 z" fill="#94a3b8"/>
                                            </marker>
                                        </defs>

                                        <rect x="280" y="16" width="200" height="44" rx="10" fill="#ffffff" stroke="#e2e8f0"/>
                                        <text x="380" y="35" text-anchor="middle" font-size="12.5" font-weight="700" fill="#0f172a">Item received &amp; labelled</text>
                                        <text x="380" y="51" text-anchor="middle" font-size="10.5" fill="#64748b">at warehouse</text>

                                        <line x1="380" y1="60" x2="380" y2="86" stroke="#94a3b8" stroke-width="1.5" marker-end="url(#arr-st)"/>

                                        <rect x="255" y="88" width="250" height="44" rx="10" fill="#ffffff" stroke="#e2e8f0"/>
                                        <text x="380" y="107" text-anchor="middle" font-size="12.5" font-weight="700" fill="#0f172a">Add to sort batch &#8594; Seal</text>
                                        <text x="380" y="123" text-anchor="middle" font-size="10.5" fill="#64748b">one destination per batch</text>

                                        <path d="M 330 132 Q 200 152 190 178" fill="none" stroke="#94a3b8" stroke-width="1.5" marker-end="url(#arr-st)"/>
                                        <path d="M 430 132 Q 560 152 570 178" fill="none" stroke="#94a3b8" stroke-width="1.5" marker-end="url(#arr-st)"/>
                                        <text x="232" y="160" text-anchor="middle" font-size="10.5" font-weight="700" fill="#c2410c">transfer</text>
                                        <text x="532" y="160" text-anchor="middle" font-size="10.5" font-weight="700" fill="#c2410c">local delivery</text>

                                        <rect x="70" y="182" width="240" height="52" rx="10" fill="#fff7ed" stroke="#fed7aa"/>
                                        <text x="190" y="203" text-anchor="middle" font-size="12.5" font-weight="700" fill="#0f172a">Transport manifest</text>
                                        <text x="190" y="220" text-anchor="middle" font-size="10.5" fill="#9a3412">containers &#183; load scans &#183; waybill</text>

                                        <rect x="450" y="182" width="240" height="52" rx="10" fill="#fff7ed" stroke="#fed7aa"/>
                                        <text x="570" y="203" text-anchor="middle" font-size="12.5" font-weight="700" fill="#0f172a">Delivery run</text>
                                        <text x="570" y="220" text-anchor="middle" font-size="10.5" fill="#9a3412">stops &#183; OTP or bus handoff</text>

                                        <line x1="190" y1="234" x2="190" y2="260" stroke="#94a3b8" stroke-width="1.5" marker-end="url(#arr-st)"/>
                                        <line x1="570" y1="234" x2="570" y2="260" stroke="#94a3b8" stroke-width="1.5" marker-end="url(#arr-st)"/>

                                        <rect x="70" y="262" width="240" height="40" rx="10" fill="#ffffff" stroke="#e2e8f0"/>
                                        <text x="190" y="286" text-anchor="middle" font-size="11.5" font-weight="600" fill="#475569">Scanned into destination branch stock</text>

                                        <rect x="450" y="262" width="240" height="40" rx="10" fill="#ffffff" stroke="#e2e8f0"/>
                                        <text x="570" y="286" text-anchor="middle" font-size="11.5" font-weight="600" fill="#475569">Delivered to recipients</text>
                                    </svg>
                                </div>
                            @endif

                            @if(($m['diagram'] ?? null) === 'access')
                                {{-- Access model diagram --}}
                                <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-slate-50/40 p-3">
                                    <svg viewBox="0 0 720 268" class="h-auto w-full" style="min-width:580px" role="img" aria-label="Access model diagram: permissions intersect capabilities">
                                        <title>Access model: role permissions intersect warehouse capabilities</title>
                                        <defs>
                                            <marker id="arr-am" viewBox="0 0 10 10" refX="9" refY="5" markerWidth="6.5" markerHeight="6.5" orient="auto-start-reverse">
                                                <path d="M0,0 L10,5 L0,10 z" fill="#94a3b8"/>
                                            </marker>
                                        </defs>

                                        <rect x="24" y="104" width="130" height="52" rx="10" fill="#ffffff" stroke="#e2e8f0"/>
                                        <text x="89" y="126" text-anchor="middle" font-size="12.5" font-weight="700" fill="#0f172a">Staff user</text>
                                        <text x="89" y="142" text-anchor="middle" font-size="10" fill="#64748b">belongs to a warehouse</text>

                                        <path d="M 154 118 Q 190 90 216 66" fill="none" stroke="#94a3b8" stroke-width="1.5" marker-end="url(#arr-am)"/>
                                        <path d="M 154 142 Q 190 170 216 194" fill="none" stroke="#94a3b8" stroke-width="1.5" marker-end="url(#arr-am)"/>

                                        <rect x="220" y="32" width="220" height="58" rx="10" fill="#ffffff" stroke="#e2e8f0"/>
                                        <text x="330" y="55" text-anchor="middle" font-size="12.5" font-weight="700" fill="#0f172a">Role permissions</text>
                                        <text x="330" y="72" text-anchor="middle" font-size="10.5" fill="#64748b">what actions (shipments.view&#8230;)</text>

                                        <rect x="220" y="170" width="220" height="58" rx="10" fill="#ffffff" stroke="#e2e8f0"/>
                                        <text x="330" y="193" text-anchor="middle" font-size="12.5" font-weight="700" fill="#0f172a">Warehouse capabilities</text>
                                        <text x="330" y="210" text-anchor="middle" font-size="10.5" fill="#64748b">which modules + scope</text>

                                        <path d="M 440 61 Q 490 80 510 106" fill="none" stroke="#94a3b8" stroke-width="1.5" marker-end="url(#arr-am)"/>
                                        <path d="M 440 199 Q 490 180 510 154" fill="none" stroke="#94a3b8" stroke-width="1.5" marker-end="url(#arr-am)"/>

                                        <rect x="512" y="104" width="186" height="58" rx="10" fill="#fff7ed" stroke="#fed7aa"/>
                                        <text x="605" y="127" text-anchor="middle" font-size="12.5" font-weight="700" fill="#0f172a">Effective access</text>
                                        <text x="605" y="144" text-anchor="middle" font-size="10.5" fill="#9a3412">permission &#8743; capability</text>

                                        <rect x="478" y="196" width="220" height="40" rx="10" fill="#f8fafc" stroke="#e2e8f0" stroke-dasharray="4 3"/>
                                        <text x="588" y="214" text-anchor="middle" font-size="10.5" font-weight="700" fill="#64748b">HQ warehouse bypasses</text>
                                        <text x="588" y="228" text-anchor="middle" font-size="10.5" font-weight="700" fill="#64748b">capability limits</text>
                                        <path d="M 588 194 L 596 166" fill="none" stroke="#94a3b8" stroke-width="1.5" stroke-dasharray="4 3" marker-end="url(#arr-am)"/>
                                    </svg>
                                </div>
                            @endif

                            <ol class="manual-steps">
                                @foreach($m['steps'] as $step)
                                    <li>{{ $step }}</li>
                                @endforeach
                            </ol>
                            @if(!empty($m['statuses']))
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <span class="mr-1 text-xs font-black uppercase tracking-wide text-slate-400">Statuses</span>
                                    @foreach($m['statuses'] as [$label, $tone])
                                        <span class="rounded-md px-2 py-1 text-[11px] font-bold {{ $statusTone[$tone] }}">{{ $label }}</span>
                                    @endforeach
                                </div>
                            @endif
                            @foreach($m['tips'] ?? [] as $tip)
                                <div class="flex items-start gap-2.5 rounded-2xl border border-orange-100 bg-orange-50/60 px-4 py-3 text-sm text-orange-900">
                                    <svg class="mt-0.5 h-4 w-4 shrink-0 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                                    <span>{{ $tip }}</span>
                                </div>
                            @endforeach
                            @if(!empty($manualRelated[$m['id']]))
                                <div class="flex flex-wrap items-center gap-1.5 border-t border-slate-100 pt-4">
                                    <span class="mr-1 text-xs font-black uppercase tracking-wide text-slate-400">Related</span>
                                    @foreach($manualRelated[$m['id']] as $rid)
                                        <a href="#{{ $rid }}" class="rounded-full bg-slate-100 px-3 py-1 text-[11px] font-bold text-slate-600 transition hover:bg-orange-50 hover:text-orange-700">{{ $sectionTitles[$rid] ?? $rid }}</a>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </section>
                @endforeach
            @endforeach

            {{-- End-to-end workflows --}}
            <section id="workflows" class="manual-section scroll-mt-24 overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-sm" x-show="matches('end to end workflows recipes standard shipment walk-in transfer bus handoff cod payout how do i swimlane diagram')">
                <div class="border-b border-slate-200/60 px-5 py-4">
                    <div class="flex items-center gap-2">
                        <h2 class="text-lg font-extrabold text-slate-900">End-to-End Workflows</h2>
                        @include('admin.manual.partials.copy-link', ['anchor' => 'workflows'])
                    </div>
                    <p class="text-sm text-slate-500">The most common journeys, start to finish.</p>
                </div>
                <div class="space-y-4 p-5">

                    {{-- Standard shipment swimlane diagram --}}
                    <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-slate-50/40 p-3">
                        <svg viewBox="0 0 940 372" class="h-auto w-full" style="min-width:760px" role="img" aria-label="Swimlane diagram of a standard shipment across vendor, back office, rider, warehouse and recipient">
                            <title>Standard shipment swimlane</title>
                            <defs>
                                <marker id="arr-sw" viewBox="0 0 10 10" refX="9" refY="5" markerWidth="6.5" markerHeight="6.5" orient="auto-start-reverse">
                                    <path d="M0,0 L10,5 L0,10 z" fill="#94a3b8"/>
                                </marker>
                            </defs>

                            <text x="16" y="20" font-size="10" fill="#94a3b8">A standard vendor shipment, left to right. Dashed step applies to intercity parcels only.</text>

                            @foreach([['Vendor', 34], ['Back office', 100], ['Rider', 166], ['Warehouse', 232], ['Recipient', 298]] as $i => [$lane, $laneY])
                                <rect x="96" y="{{ $laneY }}" width="828" height="58" rx="8" fill="{{ $i % 2 === 0 ? '#f8fafc' : '#ffffff' }}" stroke="#f1f5f9"/>
                                <text x="88" y="{{ $laneY + 33 }}" text-anchor="end" font-size="11" font-weight="800" fill="#475569">{{ $lane }}</text>
                            @endforeach

                            <rect x="108" y="42" width="112" height="42" rx="9" fill="#ffffff" stroke="#e2e8f0"/>
                            <text x="164" y="60" text-anchor="middle" font-size="11" font-weight="700" fill="#0f172a">Create &amp; submit</text>
                            <text x="164" y="75" text-anchor="middle" font-size="9.5" fill="#64748b">from the app</text>

                            <rect x="226" y="108" width="112" height="42" rx="9" fill="#ffffff" stroke="#e2e8f0"/>
                            <text x="282" y="126" text-anchor="middle" font-size="11" font-weight="700" fill="#0f172a">Price &amp; assign</text>
                            <text x="282" y="141" text-anchor="middle" font-size="9.5" fill="#64748b">charges + pickup</text>

                            <rect x="344" y="174" width="112" height="42" rx="9" fill="#ffffff" stroke="#e2e8f0"/>
                            <text x="400" y="192" text-anchor="middle" font-size="11" font-weight="700" fill="#0f172a">Pick up</text>
                            <text x="400" y="207" text-anchor="middle" font-size="9.5" fill="#64748b">from the vendor</text>

                            <rect x="462" y="240" width="112" height="42" rx="9" fill="#ffffff" stroke="#e2e8f0"/>
                            <text x="518" y="258" text-anchor="middle" font-size="11" font-weight="700" fill="#0f172a">Receive &amp; sort</text>
                            <text x="518" y="273" text-anchor="middle" font-size="9.5" fill="#64748b">label &#183; batch &#183; seal</text>

                            <rect x="580" y="240" width="112" height="42" rx="9" fill="#f8fafc" stroke="#cbd5e1" stroke-dasharray="4 3"/>
                            <text x="636" y="258" text-anchor="middle" font-size="11" font-weight="700" fill="#475569">Transport</text>
                            <text x="636" y="273" text-anchor="middle" font-size="9.5" fill="#94a3b8">if intercity</text>

                            <rect x="698" y="174" width="112" height="42" rx="9" fill="#fff7ed" stroke="#fed7aa"/>
                            <text x="754" y="192" text-anchor="middle" font-size="11" font-weight="700" fill="#0f172a">Deliver</text>
                            <text x="754" y="207" text-anchor="middle" font-size="9.5" fill="#9a3412">OTP + proof photo</text>

                            <rect x="816" y="306" width="112" height="42" rx="9" fill="#fff7ed" stroke="#fed7aa"/>
                            <text x="872" y="324" text-anchor="middle" font-size="11" font-weight="700" fill="#0f172a">Confirm &amp; pay</text>
                            <text x="872" y="339" text-anchor="middle" font-size="9.5" fill="#9a3412">COD &#8594; payment task</text>

                            <path d="M 220 70 Q 248 78 260 104" fill="none" stroke="#94a3b8" stroke-width="1.5" marker-end="url(#arr-sw)"/>
                            <path d="M 338 136 Q 366 144 378 170" fill="none" stroke="#94a3b8" stroke-width="1.5" marker-end="url(#arr-sw)"/>
                            <path d="M 456 202 Q 484 210 496 236" fill="none" stroke="#94a3b8" stroke-width="1.5" marker-end="url(#arr-sw)"/>
                            <line x1="574" y1="261" x2="578" y2="261" stroke="#94a3b8" stroke-width="1.5" marker-end="url(#arr-sw)"/>
                            <path d="M 692 254 Q 716 240 730 218" fill="none" stroke="#94a3b8" stroke-width="1.5" marker-end="url(#arr-sw)"/>
                            <path d="M 810 202 Q 844 230 862 302" fill="none" stroke="#94a3b8" stroke-width="1.5" marker-end="url(#arr-sw)"/>
                        </svg>
                    </div>

                    <div class="grid gap-4 lg:grid-cols-2">
                    @foreach([
                        ['Standard vendor shipment', [
                            'Vendor submits a shipment from the app.',
                            'Orders: review, price (Charges tab), then assign a pickup rider.',
                            'Rider collects and delivers to the warehouse; staff finalize Receiving and print labels.',
                            'Sorting: batch by destination and seal.',
                            'Local parcels → Delivery Run; other-city parcels → Transport Manifest first.',
                            'Rider delivers with recipient OTP + proof photo. COD lands in Recipient Payments for collection.',
                        ]],
                        ['Walk-in at the counter', [
                            'Walk-in: look up or create the vendor, enter items and destinations.',
                            'Collect payment at the desk and record it.',
                            'Print and attach labels — done; parcels join sorting immediately.',
                        ]],
                        ['Inter-warehouse transfer', [
                            'Sorting: seal a Transfer batch for the destination branch.',
                            'Create the Transport Manifest; pack containers and mark items loaded.',
                            'Assign a transport rider and Dispatch; resolve any scan exceptions.',
                            'Destination branch: Incoming Manifests → scan items in → Finalize Receipt.',
                        ]],
                        ['Bus handoff delivery', [
                            'Set the stop\'s delivery method to Bus Handoff.',
                            'Rider hands the parcel to the bus courier — records station, fare, courier phone.',
                            'Recipient confirms via the SMS link (or reports an issue).',
                            'Chase outstanding confirmations from Pending Confirmations / the rider\'s follow-up list.',
                        ]],
                        ['COD collection & reconciliation', [
                            'Delivered COD items create payment tasks automatically.',
                            'Collector opens a Session and scans barcodes as recipients pay.',
                            'Mark items/groups paid; log calls for recipients who stall.',
                            'Close the session and reconcile its total; review the daily report.',
                        ]],
                        ['Vendor commission payout', [
                            'Commission Payouts: pick a vendor with available balance.',
                            'Create the payout, transfer via MoMo/bank, then Mark Sent.',
                            'Confirm when the vendor acknowledges — earnings are settled.',
                        ]],
                    ] as [$title, $steps])
                        <div class="rounded-2xl border border-slate-200 p-4">
                            <p class="font-extrabold text-slate-900">{{ $title }}</p>
                            <ol class="manual-steps mt-3">
                                @foreach($steps as $step)
                                    <li>{{ $step }}</li>
                                @endforeach
                            </ol>
                        </div>
                    @endforeach
                    </div>
                </div>
            </section>

            {{-- Glossary --}}
            <section id="glossary" class="manual-section scroll-mt-24 overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-sm" x-show="matches('glossary terms definitions vocabulary shipment item package label barcode sort batch manifest container delivery run stop bus handoff custody handover walk-in cod recipient payment capability permission pickup assignment')">
                <div class="border-b border-slate-200/60 px-5 py-4">
                    <div class="flex items-center gap-2">
                        <h2 class="text-lg font-extrabold text-slate-900">Glossary</h2>
                        @include('admin.manual.partials.copy-link', ['anchor' => 'glossary'])
                    </div>
                    <p class="text-sm text-slate-500">The vocabulary the whole system is built on — most confusion starts here.</p>
                </div>
                <div class="grid gap-x-6 gap-y-4 p-5 text-sm leading-6 sm:grid-cols-2">
                    @foreach([
                        ['Shipment (order)', 'A vendor\'s request to move parcels. One shipment holds one or more items and carries the charges, payments and pickup details.'],
                        ['Item / package', 'One parcel inside a shipment, with its own recipient, status and fulfillment type. Items can finish at different times.'],
                        ['Label', 'The printed barcode identity a package gets at receiving. A package only becomes scannable and trackable once its label exists.'],
                        ['Tracking code / barcode', 'The value on the label. Scanned at every custody change — claim, load, handover, delivery.'],
                        ['Pickup assignment', 'A rider\'s job to collect a shipment from the vendor and bring it to a warehouse.'],
                        ['Walk-in', 'A shipment created at the warehouse counter when the vendor brings parcels in person — no pickup leg.'],
                        ['Sort batch', 'A group of received packages headed to one destination. Open while being filled; sealed when locked for dispatch.'],
                        ['Transport manifest', 'The document that moves a sealed transfer batch between warehouses — containers, load scans and the printed waybill.'],
                        ['Container', 'A physical box or crate inside a transport manifest. Packages are loaded and scanned per container.'],
                        ['Delivery run', 'A rider\'s last-mile trip, made of stops. Dispatching a run pushes it to the rider\'s app.'],
                        ['Stop', 'One recipient location inside a run. A stop can hold several packages, even from different vendors.'],
                        ['Bus handoff', 'Intercity delivery through a bus courier. The recipient confirms receipt via a tokenised SMS link.'],
                        ['Custody', 'Who physically holds a package right now — shelf, rider or delivered. Every claim and handoff is logged automatically.'],
                        ['Team handover', 'A bulk transfer of labelled packages from a warehouse to a rider-team leader, who allocates them to members.'],
                        ['COD / recipient payment', 'Money collected from the recipient on or after delivery, recorded in collector sessions and reconciled into wallets.'],
                        ['Permission vs capability', 'A permission (on a role) says what a person may do; a capability (on a warehouse) says what the branch may do. Access needs both — HQ skips the capability check.'],
                    ] as [$term, $definition])
                        <div class="flex gap-3">
                            <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-orange-400"></span>
                            <p class="text-slate-600"><span class="font-extrabold text-slate-900">{{ $term }}</span> — {{ $definition }}</p>
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- FAQ --}}
            <section id="faq" class="manual-section scroll-mt-24 overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-sm" x-show="matches('faq troubleshooting problems otp sms not received label reprint batch sealed reopen scan mismatch vendor cannot log in permission missing module recipient unreachable')">
                <div class="border-b border-slate-200/60 px-5 py-4">
                    <div class="flex items-center gap-2">
                        <h2 class="text-lg font-extrabold text-slate-900">FAQ &amp; Troubleshooting</h2>
                        @include('admin.manual.partials.copy-link', ['anchor' => 'faq'])
                    </div>
                    <p class="text-sm text-slate-500">Quick answers to the questions support hears most.</p>
                </div>
                <div class="divide-y divide-slate-100 p-2">
                    @foreach([
                        ['The recipient never received the delivery OTP.', 'Open the delivery run, find the stop and use Resend Code. Check Settings → SMS Logs to confirm the gateway accepted it; if SMS is failing platform-wide, test the SMS integration in Settings.'],
                        ['A vendor cannot sign in to the app.', 'Vendors log in by phone + OTP. Check their OTP Logs on the vendor page for failed attempts, confirm the phone number is correct and the account is Active, and verify SMS delivery in Settings → SMS Logs.'],
                        ['A package label is damaged or lost.', 'Reprint it from the package detail page (Packages → Print Label) or from the order\'s Receiving tab. Riders can also type a barcode manually instead of scanning.'],
                        ['I sealed a sort batch too early.', 'Open the batch and use Reopen — possible until a transport manifest or delivery run has been created from it. After that, fix the downstream document instead.'],
                        ['Loading scans report a mismatch.', 'Each mismatch becomes a scan issue on the transport manifest. Compare the expected vs scanned barcode and Approve (accept the item aboard) or Reject (keep it off the manifest).'],
                        ['A staff member can\'t see a module they need.', 'Two gates apply: their role must include the permission, and their warehouse must have the capability. Check Roles for the permission, then Warehouses → Capabilities for the branch.'],
                        ['The recipient is unreachable at delivery.', 'The rider marks the stop failed with a reason; a Contact Queue task is created automatically. The call team logs attempts and either reschedules (callback) or resolves the task.'],
                        ['A bus-handoff parcel was never confirmed.', 'Find it in Pending Confirmations or the rider\'s follow-up list. Re-send the confirmation SMS; if the recipient reports an issue, it routes to follow-up with the reason attached.'],
                        ['How do I correct a wrongly recorded COD amount?', 'Users with the override permission can adjust or waive the fee on the payment task. Every override is logged with the actor and reason.'],
                        ['Where do I see who changed what?', 'Settings → Admin Audit Logs records every back-office action with the user, route and data changes. Per-user history is also on each Users detail page.'],
                    ] as [$q, $a])
                        <details class="group px-3 py-3">
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-3 rounded-xl px-2 py-1.5 text-sm font-extrabold text-slate-800 transition hover:text-orange-700">
                                {{ $q }}
                                <svg class="h-4 w-4 shrink-0 text-slate-400 transition group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </summary>
                            <p class="px-2 pt-1.5 text-sm leading-6 text-slate-600">{{ $a }}</p>
                        </details>
                    @endforeach
                </div>
            </section>

            <section x-show="q.trim() && !groupVisible(@js($allSearchHaystacks))" x-cloak
                     class="scroll-mt-24 rounded-3xl border border-dashed border-slate-300 bg-white px-5 py-10 text-center shadow-sm">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/></svg>
                </div>
                <h2 class="mt-3 text-base font-extrabold text-slate-900">No results found</h2>
                <p class="mx-auto mt-1 max-w-md text-sm leading-6 text-slate-500">
                    Try another module name, route, status, permission or workflow keyword.
                </p>
            </section>

            <p class="px-1 pb-2 text-xs text-slate-400">
                Last updated: <span class="font-semibold">{{ $manualLastUpdated }}</span>.
                This manual describes the system as built. When a module changes, update
                <span class="font-semibold">resources/views/admin/manual/index.blade.php</span> in the same change.
            </p>
        </div>
    </div>
</div>

@push('styles')
    <style>
    [x-cloak] { display: none !important; }
    .manual-steps { counter-reset: manual-step; display: grid; gap: 0.5rem; }
    .manual-steps li { position: relative; padding-left: 2.1rem; font-size: 0.875rem; line-height: 1.55rem; color: #334155; }
    .manual-steps li::before {
        counter-increment: manual-step;
        content: counter(manual-step);
        position: absolute; left: 0; top: 0.1rem;
        display: flex; align-items: center; justify-content: center;
        width: 1.4rem; height: 1.4rem; border-radius: 0.5rem;
        background: #fff7ed; color: #c2410c; font-size: 0.7rem; font-weight: 900;
        box-shadow: inset 0 0 0 1px #fed7aa;
    }
    .manual-toc-active { background: #fff7ed; color: #c2410c; }
    @media print {
        .wh-sidebar, .print\:hidden, header { display: none !important; }
        main { margin-left: 0 !important; padding: 0 !important; }
        section { break-inside: avoid; box-shadow: none !important; }
    }
</style>
@endpush

@push('scripts')
<script>
    function manualPage() {
        return {
            q: '',
            active: '',
            copied: '',
            init() {
                this.$nextTick(() => {
                    const observer = new IntersectionObserver((entries) => {
                        entries.forEach((entry) => {
                            if (entry.isIntersecting) this.active = entry.target.id;
                        });
                    }, { rootMargin: '-15% 0px -75% 0px' });
                    document.querySelectorAll('section.manual-section[id]').forEach((s) => observer.observe(s));
                });
            },
            matches(haystack) {
                const query = this.q.trim().toLowerCase();
                if (!query) return true;
                return query.split(/\s+/).every(term => haystack.includes(term));
            },
            groupVisible(haystacks) {
                return haystacks.some(h => this.matches(h));
            },
            tocCls(id) {
                return this.active === id
                    ? 'manual-toc-active'
                    : 'text-slate-600 hover:bg-orange-50 hover:text-orange-700';
            },
            copyLink(id) {
                const url = `${location.origin}${location.pathname}#${id}`;
                if (navigator.clipboard && navigator.clipboard.writeText) navigator.clipboard.writeText(url);
                this.copied = id;
                setTimeout(() => { if (this.copied === id) this.copied = ''; }, 1500);
            },
        };
    }
</script>
@endpush
@endsection
