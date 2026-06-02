# Parcelman End-to-End UAT Checklist

Use this guide to test the full Parcelman operational flow across the admin backend, warehouse operations, vendor mobile app, rider/driver mobile app, SMS, notifications, payments, and package tracking.

## How To Use This Checklist

- Test on the same environment the client team will use, for example staging or live.
- Record the tester name, date, package/shipment numbers, tracking codes, screenshots, and any error message for every failed item.
- For every checklist item, mark one result: `Pass`, `Fail`, `Blocked`, or `Not Applicable`.
- Do not reuse the same package for every test. Create separate test shipments for direct delivery, bus handoff, warehouse transfer, failed delivery, and payment testing.
- Confirm both desktop and mobile layouts where a backend page is used by operations staff.

## System Flow Overview

Parcelman starts with a vendor request and ends when every package is delivered, handed over, collected, failed, or otherwise resolved. The main idea is that every package should be traceable from the moment the vendor requests pickup until the final status is recorded.

### 1. Vendor Creates A Shipment Request

The vendor uses the mobile app to request pickup. They first enter the pickup location, optionally request a pickup vehicle, then upload package photos, tag each photo with a recipient phone number, enter the total package quantity, and submit. At this stage, the vendor has not fully described every package yet; the warehouse will complete the package details when receiving.

### 2. Pickup Rider Collects From Vendor

Admin or warehouse staff assigns a pickup rider. The rider sees the pickup in the mobile app, goes to the vendor, confirms the pickup quantity, uploads proof photos, and completes pickup. After this, the shipment moves into the warehouse receiving queue.

### 3. Warehouse Receives And Creates Operational Packages

Warehouse staff opens the pending receipt, reviews the vendor photos, fills in package details, recipient details, delivery location, quantity, bus handoff or warehouse forwarding options where needed, and prints labels. Once the receipt is finalized, the package becomes part of the warehouse package ledger. From this point, the package can be sorted, transferred, delivered, handed to a bus station, collected at warehouse, or paid for.

### 4. Warehouse Packages Becomes The Main Ledger

The Warehouse Packages page is the operational ledger. It should show every package that has passed through the warehouse, even if the package has already moved to another warehouse, gone out for delivery, been handed to a bus station, delivered, returned, or collected. This is the main place to track custody, status, delivery fee/payment, sort batch, manifest, delivery run, ETA, rider changes, and audit history.

### 5. Package Routing Splits Into Four Common Paths

After receiving, packages normally follow one of these paths:

- **Local Accra delivery:** A rider scans or receives package custody, starts deliveries, sets ETA, goes to the recipient, confirms delivery with OTP/proof, or marks failed with a reason.
- **Outside Accra warehouse transfer:** The package is sorted into a batch, moved through a transport manifest to another warehouse, received at the destination warehouse, and then delivered from there.
- **Bus station handoff:** A rider takes the package to a bus station/courier, records station/proof, then follows up later with the recipient or vendor using a confirmation code or public confirmation link before final delivery is recorded.
- **Warehouse collection / self pickup:** The recipient or authorized person collects the package directly from the warehouse, and staff records the handover.

### 6. Rider Team Flow

For local deliveries, a team leader can scan packages into team custody and distribute them to team members. Once assigned, the team member should see those packages in My Packages and can start delivery without rescanning the same package. Riders can still scan additional packages personally when needed.

### 7. Payments And Delivery Fees

Delivery fees can be set and collected through the recipient payment workflow or from package details where permitted. Payment staff must have an assigned wallet and an open payment session before recording actual payments. Paid records should show the fee, wallet, reference, receipt, paid date, and who marked it paid.

### 8. ETA, Delays, And Customer Communication

Riders can set package-level ETAs when delivering. Admin staff can monitor missing or overdue ETAs and manually send delay notices to customers. Delay reasons come from settings, and notices should be logged in SMS/notification logs.

### 9. Audit And Accountability

The system should always show who last touched a package: pickup rider, warehouse staff, sorting/transport staff, delivery rider, bus handoff rider, payment staff, or admin. Rider location changes, rider-to-rider transfers, bus handoff confirmations, payment actions, and admin overrides should all be visible in package details or the relevant monitoring pages.

## Test Accounts Needed

Prepare these users before testing:

- [ ] Super admin or HQ admin with full operations access.
- [ ] Warehouse manager or warehouse operations user.
- [ ] Recipient payment staff with payment wallet access.
- [ ] Rider with pickup capability.
- [ ] Rider with delivery capability.
- [ ] Rider with transport capability.
- [ ] Rider with bus handoff capability.
- [ ] Rider team leader and one team member.
- [ ] Vendor account with mobile app access.
- [ ] Test recipient phone number that can receive SMS.

## Setup And Settings

### Platform Setup

| Test | Steps | Expected Result | Result / Notes |
| --- | --- | --- | --- |
| Admin login | Log in to `/admin/login` with an admin user. | Dashboard opens and the sidebar matches the user's permissions. | |
| Warehouse context | Change or confirm the active warehouse context from the backend header/context selector. | Pages show data for the selected warehouse only. | |
| Warehouse users | Open `/admin/operations/users`. Create/edit a warehouse user and assign correct roles. | User can log in only to pages allowed by role/permissions. | |
| Riders/drivers | Open `/admin/riders-drivers`. Create/edit a rider, add profile photo, phone, capabilities, and password confirmation. | Rider appears in lists, photo renders, email is optional, password confirmation works. | |
| Warehouses | Open `/admin/warehouses`. Create/edit warehouse and capabilities. | Active warehouse appears in operational flows and selectors. | |

### Settings Tabs

| Test | Steps | Expected Result | Result / Notes |
| --- | --- | --- | --- |
| Pickup Vehicles | Open `/admin/settings?tab=pickup-vehicles`. Add, edit, deactivate, search, export. | Vehicle types save without icons, list latest first, search/export work. | |
| Bus Stations | Open `/admin/settings?tab=bus-stations`. Add, edit, deactivate stations. | Active stations load on rider bus handoff dropdown; free typing still allowed. | |
| Delivery Failure Reasons | Open `/admin/settings?tab=delivery-failure-reasons`. Add/edit/delete/deactivate a reason. | Active reasons load in failure/report issue dropdowns; deleted reasons do not break old records. | |
| Delivery Delay Reasons | Open `/admin/settings?tab=delivery-delay-reasons`. Add/edit/delete/deactivate a delay reason. | Active delay reasons load in admin delay notice modal. | |
| Delivery ETA settings | Open `/admin/settings?tab=delivery`. Confirm ETA grace period and no-ETA threshold. | Values save and influence ETA warning states on package/delivery pages. | |
| SMS logs | Open `/admin/settings?tab=sms-logs`. Send a test SMS or bus confirmation code. | SMS attempt appears in SMS logs with phone, message, status, and timestamp. | |
| OTP logs | Open `/admin/settings?tab=otp-logs`. Trigger an OTP flow. | OTP event appears in OTP logs where applicable. | |
| Notification logs | Open `/admin/settings?tab=notification-logs`. Trigger vendor/rider notification. | Notification attempt is logged. | |

## Vendor Shipment Flow

### Vendor Login And Profile

| Test | Steps | Expected Result | Result / Notes |
| --- | --- | --- | --- |
| Vendor OTP login | Open vendor app, enter phone, verify OTP. | Existing vendor lands on vendor home. | |
| Vendor registration | Use a new phone number and complete registration. | New vendor account is created and can create shipments. | |
| Vendor profile | Edit vendor profile details. | Saved changes persist after app refresh/reopen. | |
| Vendor payout account | Open vendor earnings, tap Set payout account, add MoMo network, account name, and Ghana phone number. | Phone validation works; after save, earnings screen refreshes and no longer prompts to set account. | |

### Create Shipment Request

| Test | Steps | Expected Result | Result / Notes |
| --- | --- | --- | --- |
| Step 1 pickup location | Open Send a Package and select/type pickup location first. | Search suggestions work; Continue to Photos is disabled until pickup location is set. | |
| Step 1 optional pickup vehicle empty | Leave pickup vehicle request empty and continue. | Submission path remains allowed; vehicle request is not required. | |
| Step 1 optional pickup vehicle selected | Add `1 Motorbike` or `2 Aboboyaa`, then continue. | Vehicle summary appears in review and later backend pickup details. | |
| Step 2 photos | Add package photos from camera/gallery. | Photos preview correctly and can be opened/removed. | |
| Step 2 recipient phone tagging | Tag every photo with recipient phone using the phone modal. | Ghana phone validation works; Review Request is disabled until all photos are tagged. | |
| Step 3 total quantity | On review, enter total package quantity. | Submit is disabled until quantity is at least 1; quantity appears in vendor detail and backend receiving flow. | |
| Submit request | Submit after pickup location, photos, tagged phones, and quantity are complete. | Shipment request is created and visible to backend/admin. | |
| Edit shipment request | Open an editable shipment and update photos/location/quantity/vehicle request. | Changes save; if vehicle section is untouched existing requests remain unchanged. | |
| Clear vehicle request | Edit a shipment with vehicle requests, clear all selected vehicles, save. | Existing vehicle requests are removed. | |
| Vendor parcel list | Open vendor parcels/shipments. Search/filter and open details. | Package statuses, photos, pickup rider, delivery rider, ETA, bus courier info, and delivery details display correctly. | |

## Pickup Flow

| Test | Steps | Expected Result | Result / Notes |
| --- | --- | --- | --- |
| Admin assign pickup | From admin orders/pickups, assign a pickup rider to a vendor shipment. | Pickup appears in rider app pickup list. | |
| Rider starts pickup | Rider opens pickup, marks en route, then arrived. | Backend pickup status updates. | |
| Rider confirms pickup | Rider enters collected quantity and proof photos, then confirms pickup. | Pickup moves to warehouse pending receipt queue. | |
| Location failure tolerance | Deny GPS permission while confirming pickup or delivery-related action where location is requested. | Submission is still allowed when required non-location fields are provided. | |
| Warehouse pending receipt | Open `/admin/operations/receipts/pending`. | Pickup appears as pending receipt with vendor/photos/quantity. | |

## Warehouse Receiving And Walk-In

| Test | Steps | Expected Result | Result / Notes |
| --- | --- | --- | --- |
| Pending receipt workspace | Open a pending receipt and edit package description, quantity, recipient name/phone, town/location, instructions, bus station option, and forward warehouse option. | Searchable town/location works; fields save correctly. | |
| Receipt photos | View vendor uploaded photos; upload additional receipt photos. | Lightbox opens full page; photos save and can be viewed. | |
| Photo removal rule | Remove photos until none remain. | Save is blocked unless at least one package/pickup/receipt fallback photo remains. | |
| Print labels with count | Print labels and choose number of labels where supported. | Correct number of labels prints; tracking/barcode scans without spaces. | |
| Finalize receipt | Finalize warehouse receipt. | Package appears in Warehouse Packages and sorting eligibility. | |
| Walk-in shipment | Open `/admin/operations/walkin` and create a walk-in vendor shipment. | Same form concept works, labels print, package enters warehouse flow. | |

## Warehouse Packages Ledger

| Test | Steps | Expected Result | Result / Notes |
| --- | --- | --- | --- |
| Package ledger opens | Open `/admin/operations/packages`. | Page title/menu is Warehouse Packages; old `/warehouse/items/received` redirects/aliases correctly. | |
| Passed-through rule | Check a package that was transferred or delivered after passing through the warehouse. | It still appears for that warehouse. | |
| Desktop table | Check table columns, row density, action buttons, pagination, export, column view. | Table matches operational table style; no duplicate placeholder text. | |
| Mobile layout | Use narrow viewport/mobile browser. | Packages render as readable cards with usable actions. | |
| Search | Search by shipment number, tracking code, barcode, recipient, driver, batch, manifest, run. | Matching rows return correctly. | |
| Filters | Apply filters for received date, delivered date, package status, custody, sort batch, manifest, delivery, payment, vendor, delivery fee range, staff. | Filters apply only when Apply Filters is clicked; chips/clear filters work. | |
| Summary card filter | Click summary cards such as Payment Due or Total Paid. | Table filters to the selected summary state and clears unrelated filters. | |
| Custody column | Inspect packages held by rider/driver and packages at warehouse. | Driver name and phone show when driver has custody; otherwise At warehouse appears cleanly. | |
| Payment column | Inspect no-fee, due, and paid packages. | No duplicate text; paid amount, wallet/reference, paid date, and paid-by user show correctly. | |
| Edit package | Use inline Edit Package. | Modal uses receiving/walk-in concept; package/recipient/location/photos/warehouse forwarding edit correctly subject to locks. | |
| Print package labels | Use Print labels action. | Print works when package has receipt assignment and label count can be chosen. | |

## Package Detail Page

| Test | Steps | Expected Result | Result / Notes |
| --- | --- | --- | --- |
| Open detail | Open `/admin/operations/packages/{id}` from package name/link. | Detail page opens directly, not just filtered listing. | |
| Package details | Review package, vendor, recipient, photos, tracking code, quantity, labels. | All key data is visible and human-friendly dates use AM/PM. | |
| Custody card | Review current/last custody. | Shows warehouse, rider/driver name/phone, bus handoff, or delivered state correctly. | |
| Sorting/manifest/delivery cards | Review associated batch, manifest, delivery run/stop. | Statuses are capitalized and linked where applicable. | |
| Payment actions | Set delivery fee and mark paid with permitted user. | Wallet/session rules apply; paid-by user and transaction details show. | |
| Payment permission | Try payment actions with user lacking permission/session/wallet. | User can save allowed details only; actual payment is blocked when required wallet/session permission is missing. | |
| ETA/delay | View ETA and delay history; send delay notice if eligible. | Delay modal uses delivery delay reasons, editable message, optional revised ETA, and logs notification. | |
| Bus handoff timeline | Open a bus handoff package. | Shows who handed off, station, proof, confirmation source, confirmed/reported date, and admin/rider/public source. | |
| Rider audits | Open a package with rider location change or transfer history. | Audit log includes location change and transfer request/outcome. | |

## Sorting And Transport

| Test | Steps | Expected Result | Result / Notes |
| --- | --- | --- | --- |
| Create sort batch | Open `/admin/operations/sorting`; create batch and add eligible packages. | Eligible packages can be added; ineligible locked packages are blocked. | |
| Seal/reopen sort batch | Seal an open batch, then attempt edits; reopen if allowed. | Sealed batch prevents changes until reopened by permitted user. | |
| Create manifest from batch | Create transport manifest from sealed batch. | Manifest links to batch and packages. | |
| Assign transport rider | Open `/admin/operations/manifests/transport`; assign rider/driver. | Manifest appears in rider transport list. | |
| Rider transport load | Rider starts loading and scans package labels. | Loaded/not-loaded statuses update; scan exceptions are logged. | |
| Dispatch/arrival | Dispatch manifest and mark arrived at destination warehouse. | Incoming manifest appears at destination warehouse. | |
| Incoming receipt | Open `/admin/operations/manifests/incoming`, scan/receive packages, finalize. | Package is now visible in receiving warehouse ledger while still visible in prior warehouse's passed-through ledger. | |

## Delivery Flow A: Local Accra Delivery

| Test | Steps | Expected Result | Result / Notes |
| --- | --- | --- | --- |
| Prepare local package | Receive a package whose delivery destination is within Accra/local delivery area. | Package is available for local delivery, not transport/warehouse transfer. | |
| Rider personal scan | Delivery rider scans package label from Scan screen in Personal mode. | Package appears in My Packages as ready/available. | |
| Team leader team scan | Team leader opens Scan screen in Rider Team mode, selects team, and scans package labels into team custody. | Package appears in team handover/team custody with received count updated. | |
| Team leader distribute | Team leader opens team handover and assigns labels to a team member, or lets member scan/claim from the handover. | Team member sees assigned package in My Packages without needing to rescan the physical package again. | |
| Team member start deliveries | Team member opens My Packages and starts deliveries for team-assigned labels. | Delivery run is created/opened from assigned labels; package moves into In Delivery state. | |
| Self-scanned rider start deliveries | Rider with self-scanned packages starts deliveries from My Packages. | Delivery run is created/opened and packages group into stops by recipient/location. | |
| Set ETA | Rider opens stop/package and sets ETA using quick option/custom time. | ETA appears per package in rider app, admin package list/detail, delivery run detail, and vendor parcel detail. | |
| Edit ETA | Rider edits ETA before final delivery. | ETA updates and history is recorded. | |
| Confirm local delivery | Rider arrives, gets OTP, enters code, proof photo, delivered count, optional in-field fee. | Package status becomes delivered; proof/date/rider show in admin and vendor app. | |
| Failed delivery | Rider marks a stop/package failed and chooses backend-managed failure reason. | Package is not delivered; reason appears in admin details. | |
| Location unavailable | Rider confirms delivery while location is denied/unavailable. | Submission is allowed if required proof/OTP or allowed skip fields are complete. | |

## Delivery Flow B: Outside Accra Warehouse Transfer

Use this path for packages that should move from one warehouse to another before final delivery.

| Test | Steps | Expected Result | Result / Notes |
| --- | --- | --- | --- |
| Prepare outside-Accra package | Receive package and set forward/destination warehouse where applicable. | Package is available for sorting/transport, not immediate local delivery. | |
| Add to sort batch | Warehouse user opens `/admin/operations/sorting`, creates/opens batch, adds package. | Package is linked to sort batch and removed from unrelated eligible lists where appropriate. | |
| Seal sort batch | Seal the batch. | Batch becomes locked for transport creation unless reopened by permitted user. | |
| Create transport manifest | Create transport manifest from sealed batch or add package/container to manifest. | Manifest shows package, batch, route, source/destination warehouse. | |
| Assign transport rider | Assign manifest to transport rider. | Rider sees manifest in mobile Transport list. | |
| Rider loads packages | Transport rider starts loading and scans package labels. | Loaded/not-loaded statuses update; exceptions are recorded for wrong/missing scans. | |
| Dispatch manifest | Warehouse dispatches manifest. | Manifest status changes to dispatched/in transit. | |
| Transport rider marks arrival | Rider or warehouse marks manifest arrived at destination. | Destination warehouse sees incoming manifest. | |
| Destination warehouse receives | Destination warehouse opens `/admin/operations/manifests/incoming`, scans/receives items, finalizes. | Package now appears in destination warehouse ledger and remains visible in source warehouse passed-through ledger. | |
| Final delivery from destination | Destination rider/team starts local delivery from destination warehouse. | Local delivery flow can continue from the new warehouse. | |

## Delivery Flow C: Bus Station Handoff And Follow-Up

| Test | Steps | Expected Result | Result / Notes |
| --- | --- | --- | --- |
| Bus station setup | Confirm stations exist in `/admin/settings?tab=bus-stations`. | Active stations load in rider handoff dropdown. | |
| Prepare bus handoff package | Receive package with bus handoff delivery method or update eligible package to bus handoff before locked/final states. | Package is routed into bus handoff delivery flow. | |
| Rider starts bus handoff run | Rider receives package through scan/team assignment and starts deliveries. | Bus handoff stop/run appears in rider deliveries. | |
| Confirm handoff | Rider confirms bus handoff, selects or types station, enters courier phone if available, uploads proof. | Package status becomes handed off, not delivered; station/proof is saved. | |
| Bus station packages page | Open `/admin/operations/bus-station-packages`. | Handoff package appears with station, rider, confirmation status, and filters. | |
| Rider follow-up list | Rider opens Bus Follow-ups. | Stops handed off by that rider appear grouped by bus stop, with status counts. | |
| Follow-up detail | Open a stop/package detail. | Shows package name, tracking number, run/stop context, recipient/sender contact, station, proof button, and confirmation status. | |
| Send confirmation code | Tap Confirm Delivery, choose recipient/vendor, send code. | Mixed alphanumeric code SMS is sent without "Parcelman" prefix; countdown appears in modal; SMS log records the send. | |
| Confirm by rider code | Enter received code before expiry. | Package becomes delivered by rider confirmation; admin can still override/reconfirm. | |
| Expired/invalid code | Enter expired or wrong code. | Confirmation is rejected with clear error. | |
| Report problem | Open Report Problem modal and choose a failure reason from backend dropdown. | Issue/not received state saves and appears in admin. | |
| Public confirmation link | Open `/h/{token}` from SMS. Confirm received. | Public page confirms package and token cannot be reused after final state. | |
| Public issue report | Open `/h/{token}` and report issue with reason/comment. | Issue appears in admin bus/package details. | |
| Admin override | Admin confirms/fails/reopens a handoff package where applicable. | Source shows admin action; audit/timeline captures actor and timestamp. | |

## Delivery Flow D: Warehouse Collection / Self Pickup Handoff

Use this path where the recipient or authorized person collects from a warehouse instead of rider doorstep delivery or bus-station handoff.

| Test | Steps | Expected Result | Result / Notes |
| --- | --- | --- | --- |
| Prepare collection package | Receive package and mark or route it as self pickup/warehouse collection where applicable. | Package appears in collections/self-pickup workflow. | |
| Collections list | Open `/admin/operations/collections`. Search/filter for the package or shipment. | Package appears with recipient/vendor/package details. | |
| Verify collector | Confirm recipient/authorized collector information before handover. | Staff can identify who is collecting and what package is being released. | |
| Complete handover | Use the collection handover action. | Package becomes collected/delivered by warehouse handoff; actor and timestamp are recorded. | |
| Package detail audit | Open package detail after handover. | Collection handoff appears in package timeline/audit and vendor sees final status. | |

## Recipient Payments And Delivery Fees

| Test | Steps | Expected Result | Result / Notes |
| --- | --- | --- | --- |
| Wallet setup | Open `/admin/operations/recipient-payments`, create wallet, assign users. | Wallet appears for assigned payment staff. | |
| Payment session | Start today's payment session for wallet. | Staff can process actual payment only with open session. | |
| No wallet/session block | Use staff with no wallet or no open session. | Save details may be allowed, but actual payment is blocked. | |
| Recipient payment task | Scan/find package in recipient payments and set delivery fee. | Fee shows as due on package ledger/detail. | |
| Mark paid | Process payment with fee, reference, call result, receipt screenshot. | Payment status becomes paid; paid-by user, wallet, session, reference, and receipt are visible. | |
| Reports | Open `/admin/operations/recipient-payments/reports`. Filter by date, staff, wallet, amount, status. | Report totals and rows match processed payments. | |
| Package payment visibility | Return to package ledger/detail. | Delivery fee and payment status reflect recipient payment workflow. | |

## ETA And Delay Notifications

| Test | Steps | Expected Result | Result / Notes |
| --- | --- | --- | --- |
| No ETA threshold | Start delivery and leave ETA empty past configured threshold. | Admin package list/detail shows No ETA past threshold when data loads. | |
| ETA overdue | Set ETA, wait/pass ETA plus grace period. | Admin surfaces show ETA overdue. | |
| Send delay notice from package list | Open `/admin/operations/packages`, click delay action for eligible package. | Modal opens with reason dropdown, Datepicker JS revised ETA, editable message, notify checkboxes. | |
| Send delay notice from package detail | Open package detail and send notice. | Delay event logs; recipient SMS/vendor notification logs are created. | |
| Send delay notice from delivery run | Open `/admin/operations/deliveries/runs/{run}` and send item delay notice. | Same behavior as package list/detail. | |
| Vendor sees ETA/delay | Open vendor parcel detail. | ETA and delay reason appear in package bio/details; delivered date appears in details, not beside top badge. | |

## Rider Location Change And Package Transfer

| Test | Steps | Expected Result | Result / Notes |
| --- | --- | --- | --- |
| Change location eligible | Rider opens My Packages package detail before delivery run starts, taps Change Location. | Modal opens. | |
| Submit location change | Enter new delivery location and required proof photo. | Location updates immediately; package list refreshes. | |
| Change location ineligible | Try changing location after package is in active delivery run or final state. | Action is hidden or disabled. | |
| Admin monitor location changes | Open `/admin/operations/package-location-changes`. | Row shows package, rider, old/new location, proof photo lightbox, changed date. | |
| Transfer package eligible | Rider opens package detail before delivery run, taps Transfer Package, enters receiving rider phone. | Pending transfer is created and sender cannot start delivery for that package while pending. | |
| Incoming transfer | Receiving rider opens transfer screen/list. | Incoming request appears with package and sender. | |
| Accept transfer | Receiving rider accepts. | Custody moves to receiver; package appears in receiver My Packages. | |
| Reject/cancel transfer | Receiving rider rejects or sender cancels. | Custody remains with sender; audit records outcome. | |
| Admin monitor transfers | Open `/admin/operations/rider-package-transfers`. | Transfer status, from rider, to rider, timestamps, and package link are correct. | |

## Vendor Earnings And Payouts

| Test | Steps | Expected Result | Result / Notes |
| --- | --- | --- | --- |
| Earnings balance | Open vendor earnings after delivered commissionable packages. | Awaiting payout, lifetime earnings, and paid out totals are correct. | |
| Payout account required | Try to process payout for vendor without payout account. | Backend clearly shows payout account missing. | |
| Admin vendor detail | Open admin vendor profile. | Payout account is visible and editable; Ghana phone validation works. | |
| Process payout | Open `/admin/vendor-payouts` or vendor payout area, create/send/confirm payout. | Payout moves through pending/sent/confirmed and vendor earnings update. | |

## Contacts, Marketing, And Customer Messaging

| Test | Steps | Expected Result | Result / Notes |
| --- | --- | --- | --- |
| Contact queue | Open `/admin/operations/contacts`. Assign/log call/send code/resolve. | Task status updates; delivered-by rider or agent is indicated where applicable. | |
| Marketing page | Open `/admin/marketing`. Compose and history tabs work. | Broadcast can be sent by channel/audience and appears in broadcast table. | |
| Marketing filters | Search/filter broadcasts by channel, recipient/audience, date, status. | Rows and counts match filters. | |
| SMS test | Send a test SMS from settings. | SMS arrives and log row is created. | |
| Push test | Send a test push notification. | Notification arrives on app/device where configured and log row is created. | |

## Permissions, Audit, And Security

| Test | Steps | Expected Result | Result / Notes |
| --- | --- | --- | --- |
| Warehouse scoping | Log in as user assigned to one warehouse and open package ledger. | User sees only packages that passed through their warehouse. | |
| Super admin/backoffice access | Log in as super admin. | User lands in backoffice/admin context unless explicitly assigned/using warehouse operations context. | |
| Payment permissions | Use a user without recipient payment permission. | Payment processing actions are hidden or rejected. | |
| Delay notice permission | Use a user without delivery assign/manage permission. | Delay notice actions are hidden or rejected. | |
| Settings edit permission | Use settings view-only user. | User can view but cannot create/edit/delete settings rows. | |
| Admin audit logs | Perform admin create/edit/delete/payment actions and check audit logs. | Actor, action, target, date, and metadata are captured. | |
| Vendor/rider isolation | Try opening another vendor/rider's restricted API data. | API rejects unauthorized access. | |
| Public token security | Reuse or guess `/h/{token}`. | Invalid/expired/final tokens cannot be used. | |

## Mobile App Regression

| Test | Steps | Expected Result | Result / Notes |
| --- | --- | --- | --- |
| iOS app launch | Open app on iOS simulator/device. | App launches and navigation works. | |
| Android app launch | Open app on Android emulator/device. | App loads from dev client/server and screens render correctly. | |
| App restart | Log in, kill app, reopen. | Correct auth/session state restores. | |
| Offline handling | Disconnect internet and try actions that require backend. | App shows clear error; critical scan/claim should require proper connection. | |
| Camera permissions | Try scan/photo actions without camera permission. | App asks for permission and recovers when granted. | |
| Phone validation | Test recipient/vendor/rider transfer/MoMo phone fields. | Ghana phone validation matches Send Parcel phone behavior. | |
| Image upload | Upload package/proof/payment photos. | Upload succeeds and images render through signed/storage URLs. | |
| Date formatting | Review dates across app/backend. | Dates are human-friendly and use AM/PM. | |

## Quick Acceptance Run

This is not a separate feature. It is a shorter end-to-end test run for the client team lead after deployment, before asking everyone to do the full checklist. One person can coordinate it, but it needs at least a vendor, warehouse/admin user, payment staff, and rider.

| Owner | Step | Expected Result | Result / Notes |
| --- | --- | --- | --- |
| Admin | Log in and confirm settings tabs load: pickup vehicles, bus stations, delivery failure reasons, delivery delay reasons, SMS logs. | Core setup pages are available. | |
| Vendor | Create a shipment request in the mobile app: pickup location first, optional vehicle request if needed, photos, recipient phone tags, total quantity, submit. | Request appears in backend. | |
| Admin/Warehouse | Assign pickup rider. | Pickup appears in rider app. | |
| Pickup rider | Complete pickup with quantity and proof photo. | Shipment moves to pending warehouse receipt. | |
| Warehouse | Receive/finalize package and print labels. | Package appears in `/admin/operations/packages`. | |
| Warehouse | Run one local Accra package through delivery. | Rider can start delivery, set ETA, confirm/ fail delivery, and admin/vendor can see final result. | |
| Team leader + team member | Scan package into rider team custody and assign/share to member. | Member sees package in My Packages and can start delivery without rescanning. | |
| Warehouse + transport rider | Run one outside-Accra package through sort batch, transport manifest, dispatch, arrival, and incoming receipt. | Package appears in both passed-through warehouse ledgers and can continue delivery from destination warehouse. | |
| Bus handoff rider | Run one bus-station handoff: confirm handoff with station/proof, then follow up by SMS code or public link. | Package stays handed off until confirmed delivered; source of confirmation is visible in admin. | |
| Warehouse | Run one warehouse collection/self-pickup handoff. | Package is handed over at warehouse and audit shows who completed it. | |
| Payment staff | Set delivery fee and mark paid using wallet/session. | Payment status, paid-by user, wallet, reference, and receipt appear in package ledger/detail and reports. | |
| Admin | Send one delay notice from package list/detail or delivery run detail. | Recipient/vendor notice is sent/logged and delay history appears. | |
| Vendor | Reopen parcel details. | Vendor sees current package status, ETA/delay info where applicable, bus courier info where applicable, delivery date, and earnings/payout status. | |
| Admin | Open package detail and logs. | Custody, payment, delivery, handoff, ETA, rider location/transfer audits, SMS logs, notification logs, and admin audit logs show the actions performed. | |

## Final Signoff

| Area | Signoff Name | Date | Notes |
| --- | --- | --- | --- |
| Vendor app | | | |
| Rider/driver app | | | |
| Warehouse operations | | | |
| Finance/recipient payments | | | |
| Admin/settings/security | | | |
| SMS/notifications | | | |
| Full end-to-end flow | | | |
