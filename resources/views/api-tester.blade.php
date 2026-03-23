<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>API Tester - Parcelman Express</title>
    <link rel="icon" type="image/jpeg" href="{{ asset('favicon.jpg') }}">
    @vite('resources/css/pages/api-tester.css')
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-title">Parcelman API Explorer</div>
                <div class="search-box">
                    <input type="text" id="searchInput" placeholder="Filter endpoints..." oninput="filterEndpoints()">
                </div>
            </div>

            <nav class="sidebar-nav" id="sidebarNav">
                <!-- v1 Folder -->
                <div>
                    <div class="folder-header" onclick="toggleFolder('v1')">
                        <span class="folder-chevron open" id="chevron-v1">▶</span>
                        <span>v1</span>
                    </div>

                    <div id="folder-v1">
                        <!-- Vendor Folder -->
                        <div class="subfolder-header" onclick="toggleFolder('vendor')">
                            <span class="folder-chevron open" id="chevron-vendor">▶</span>
                            <span>Vendor</span>
                        </div>

                        <div id="folder-vendor">
                            <!-- Auth Group -->
                            <div class="group-header nested" onclick="toggleGroup('auth')">
                                <span class="folder-chevron open" id="chevron-auth">▶</span>
                                <span>Auth</span>
                            </div>

                            <div id="group-auth">
                                <!-- Endpoints will be populated by JS -->
                            </div>

                            <!-- Profile Group -->
                            <div class="group-header nested" onclick="toggleGroup('profile')">
                                <span class="folder-chevron open" id="chevron-profile">▶</span>
                                <span>Profile</span>
                            </div>

                            <div id="group-profile">
                                <!-- Endpoints will be populated by JS -->
                            </div>

                            <!-- Location Group -->
                            <div class="group-header nested" onclick="toggleGroup('location')">
                                <span class="folder-chevron open" id="chevron-location">▶</span>
                                <span>Location</span>
                            </div>

                            <div id="group-location">
                                <!-- Endpoints will be populated by JS -->
                            </div>

                            <!-- Shipments Group -->
                            <div class="group-header nested" onclick="toggleGroup('shipments')">
                                <span class="folder-chevron open" id="chevron-shipments">▶</span>
                                <span>Shipments</span>
                            </div>

                            <div id="group-shipments">
                                <!-- Endpoints will be populated by JS -->
                            </div>

                            <!-- Shipment Items Group -->
                            <div class="group-header nested" onclick="toggleGroup('shipment-items')">
                                <span class="folder-chevron open" id="chevron-shipment-items">▶</span>
                                <span>Shipment Items</span>
                            </div>

                            <div id="group-shipment-items">
                                <!-- Endpoints will be populated by JS -->
                            </div>

                            <!-- Invoices Group -->
                            <div class="group-header nested" onclick="toggleGroup('invoices')">
                                <span class="folder-chevron open" id="chevron-invoices">▶</span>
                                <span>Invoices</span>
                            </div>

                            <div id="group-invoices">
                                <!-- Endpoints will be populated by JS -->
                            </div>

                            <!-- Notifications Group -->
                            <div class="group-header nested" onclick="toggleGroup('vendor-notifications')">
                                <span class="folder-chevron open" id="chevron-vendor-notifications">▶</span>
                                <span>Notifications</span>
                            </div>

                            <div id="group-vendor-notifications">
                                <!-- Endpoints will be populated by JS -->
                            </div>
                        </div>

                        <!-- Driver Folder -->
                        <div class="subfolder-header" onclick="toggleFolder('driver')">
                            <span class="folder-chevron open" id="chevron-driver">▶</span>
                            <span>Driver</span>
                        </div>

                        <div id="folder-driver">
                            <!-- Driver Auth Group -->
                            <div class="group-header nested" onclick="toggleGroup('driver-auth')">
                                <span class="folder-chevron open" id="chevron-driver-auth">▶</span>
                                <span>Auth</span>
                            </div>

                            <div id="group-driver-auth">
                                <!-- Endpoints will be populated by JS -->
                            </div>

                            <!-- Driver Profile Group -->
                            <div class="group-header nested" onclick="toggleGroup('driver-profile')">
                                <span class="folder-chevron open" id="chevron-driver-profile">▶</span>
                                <span>Profile</span>
                            </div>

                            <div id="group-driver-profile">
                                <!-- Endpoints will be populated by JS -->
                            </div>

                            <!-- Driver Pickups Group -->
                            <div class="group-header nested" onclick="toggleGroup('driver-assignments')">
                                <span class="folder-chevron open" id="chevron-driver-assignments">▶</span>
                                <span>Pickups</span>
                            </div>

                            <div id="group-driver-assignments">
                                <!-- Endpoints will be populated by JS -->
                            </div>

                            <!-- Driver Transports Group -->
                            <div class="group-header nested" onclick="toggleGroup('driver-transports')">
                                <span class="folder-chevron open" id="chevron-driver-transports">â–¶</span>
                                <span>Transports</span>
                            </div>

                            <div id="group-driver-transports">
                                <!-- Endpoints will be populated by JS -->
                            </div>

                            <!-- Driver Deliveries Group -->
                            <div class="group-header nested" onclick="toggleGroup('driver-deliveries')">
                                <span class="folder-chevron open" id="chevron-driver-deliveries">â–¶</span>
                                <span>Deliveries</span>
                            </div>

                            <div id="group-driver-deliveries">
                                <!-- Endpoints will be populated by JS -->
                            </div>

                            <!-- Driver Notifications Group -->
                            <div class="group-header nested" onclick="toggleGroup('driver-notifications')">
                                <span class="folder-chevron open" id="chevron-driver-notifications">▶</span>
                                <span>Notifications</span>
                            </div>

                            <div id="group-driver-notifications">
                                <!-- Endpoints will be populated by JS -->
                            </div>
                        </div>
                    </div>
                </div>

                <div class="no-results hidden" id="noResults">
                    No matching endpoints
                </div>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Top Bar -->
            <div class="top-bar">
                <div class="breadcrumb">
                    v1 / Vendor / <span id="breadcrumbGroup">Auth</span> / <span id="breadcrumbEndpoint">Select an endpoint</span>
                </div>
                <div class="token-indicators">
                    <div class="token-indicator">
                        <span class="token-dot" id="vendorTokenDot"></span>
                        <span>Vendor</span>
                    </div>
                    <div class="token-indicator">
                        <span class="token-dot" id="driverTokenDot"></span>
                        <span>Driver</span>
                    </div>
                </div>
            </div>

            <!-- URL Bar -->
            <div class="url-bar">
                <div class="url-builder">
                    <div class="method-display" id="methodDisplay">GET</div>
                    <div class="url-input-wrapper">
                        <input type="text" class="url-input" id="urlInput" readonly placeholder="Select an endpoint">
                        <button class="copy-btn" onclick="copyUrl()">Copy</button>
                    </div>
                    <button class="send-btn" id="sendBtn" onclick="sendRequest()" disabled>
                        <span id="sendText">Send</span>
                        <span class="spinner hidden" id="sendSpinner"></span>
                    </button>
                </div>
                <p class="endpoint-desc" id="endpointDesc"></p>
            </div>

            <!-- Split Pane -->
            <div class="split-pane">
                <!-- Request Pane -->
                <div class="request-pane">
                    <div class="pane-header">
                        <button class="tab-btn active" data-tab="docs" onclick="switchTab('request', 'docs')">Docs</button>
                        <button class="tab-btn hidden" data-tab="params" onclick="switchTab('request', 'params')" id="paramsTabBtn">Params</button>
                        <button class="tab-btn" data-tab="body" onclick="switchTab('request', 'body')">Body</button>
                        <button class="tab-btn" data-tab="auth" onclick="switchTab('request', 'auth')">Auth</button>
                        <button class="tab-btn" data-tab="headers" onclick="switchTab('request', 'headers')">Headers</button>
                    </div>

                    <div class="pane-content">
                        <!-- Docs Tab -->
                        <div id="tab-docs">
                            <div class="empty-state" id="docsEmpty">
                                <div class="empty-state-text">Select an endpoint to view documentation</div>
                            </div>
                            <div id="docsContent" class="hidden"></div>
                        </div>

                        <!-- Params Tab -->
                        <div id="tab-params" class="hidden">
                            <div id="urlParamsSection" class="hidden">
                                <div class="docs-section">
                                    <div class="docs-section-title">URL Parameters</div>
                                    <p class="docs-hint">These parameters are part of the URL path</p>
                                </div>
                                <div id="urlParamsContainer">
                                    <!-- URL params will be rendered here -->
                                </div>
                            </div>

                            <div id="queryParamsSection" class="hidden" style="margin-top: 14px;">
                                <div class="docs-section">
                                    <div class="docs-section-title">Query Parameters</div>
                                    <p class="docs-hint">These parameters are appended to the URL as filters</p>
                                </div>
                                <div id="queryParamsContainer">
                                    <!-- Query params will be rendered here -->
                                </div>
                            </div>
                        </div>

                        <!-- Body Tab -->
                        <div id="tab-body" class="hidden">
                            <div id="formInputsContainer" class="hidden">
                                <!-- Form inputs will be rendered here -->
                            </div>
                            <textarea class="code-editor" id="requestBody" placeholder="Request body (JSON)"></textarea>
                        </div>

                        <!-- Auth Tab -->
                        <div id="tab-auth" class="hidden">
                            <div class="form-group">
                                <label class="form-label">Bearer Token</label>
                                <input type="text" class="form-input" id="bearerToken" placeholder="Auto-filled from login" oninput="onTokenInput()">
                            </div>
                        </div>

                        <!-- Headers Tab -->
                        <div id="tab-headers" class="hidden">
                            <div class="docs-section device-headers-info">
                                <div class="docs-section-title">Device Headers (Optional)</div>
                                <p class="docs-hint">Mobile apps should send these headers for activity logging:</p>
                            </div>
                            <table class="headers-table">
                                <thead>
                                    <tr>
                                        <th>Header</th>
                                        <th>Value</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>X-Device-Type</td>
                                        <td><input type="text" id="header-device-type" placeholder="android / ios / web" value="web"></td>
                                    </tr>
                                    <tr>
                                        <td>X-Device-Name</td>
                                        <td><input type="text" id="header-device-name" placeholder="Device model" value="API Tester"></td>
                                    </tr>
                                    <tr>
                                        <td>X-OS-Version</td>
                                        <td><input type="text" id="header-os-version" placeholder="OS version" value="Web Browser"></td>
                                    </tr>
                                    <tr>
                                        <td>X-App-Version</td>
                                        <td><input type="text" id="header-app-version" placeholder="App version" value="1.0.0"></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Response Pane -->
                <div class="response-pane">
                    <div class="response-status" id="responseStatus">
                        <div class="status-item">
                            <span class="status-label">Status:</span>
                            <span class="status-value" id="statusCode">-</span>
                        </div>
                        <div class="status-item">
                            <span class="status-label">Time:</span>
                            <span class="status-value" id="responseTime">-</span>
                        </div>
                    </div>

                    <div class="response-body" id="responseBody">
                        <button class="copy-response-btn" onclick="copyResponse()">Copy</button>
                        <pre id="responseContent">Send a request to see the response</pre>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast -->
    <div class="toast" id="toast"></div>

    <script>
        // API Endpoints Configuration
        const endpoints = [
            {
                method: 'POST',
                url: '/api/v1/auth/vendor/send-otp',
                name: 'Send OTP',
                description: 'Send OTP to any valid Ghana phone number. Works for both login (existing vendors) and registration (new users). Has 60-second cooldown.',
                auth: false,
                group: 'auth',
                fields: [
                    { name: 'phone', type: 'string', required: true, description: 'Ghana phone (0244xxx or +233244xxx)', example: '+233244123456' }
                ],
                sampleBody: {
                    phone: '+233244123456'
                },
                exampleResponses: {
                    '200': {
                        success: true,
                        message: 'OTP sent successfully.',
                        data: { expires_in: 300 }
                    },
                    '200_rate_limited': {
                        success: false,
                        message: 'Please wait before requesting another OTP.',
                        data: { retry_after: 45 }
                    }
                }
            },
            {
                method: 'POST',
                url: '/api/v1/auth/vendor/verify-phone',
                name: 'Verify Phone',
                description: 'Verify phone number with OTP. If vendor exists, returns user + token for login. If not, returns user_exists: false so client can redirect to registration.',
                auth: false,
                group: 'auth',
                fields: [
                    { name: 'phone', type: 'string', required: true, description: 'Ghana phone (0244xxx or +233244xxx)', example: '+233244123456' },
                    { name: 'otp', type: 'string', required: true, description: 'Exactly 6 digits sent via SMS', example: '123456' },
                    { name: 'fcm_token', type: 'string', required: false, description: 'Firebase Cloud Messaging device token for push notifications (captured at login time)', example: 'fBXQ4v...' }
                ],
                sampleBody: {
                    phone: '+233244123456',
                    otp: '123456',
                    fcm_token: 'fBXQ4v...'
                },
                exampleResponses: {
                    '200_existing': {
                        success: true,
                        message: 'Login successful.',
                        data: {
                            user_exists: true,
                            user: {
                                id: 1,
                                name: 'John Doe',
                                business_name: 'John\'s Delivery',
                                phone: '+233244123456',
                                email: 'john@example.com'
                            },
                            token: '1|abc123xyz...'
                        }
                    },
                    '200_new': {
                        success: true,
                        message: 'Phone verified. Please complete registration.',
                        data: {
                            user_exists: false
                        }
                    },
                    '422': {
                        success: false,
                        message: 'Invalid or expired OTP.'
                    }
                }
            },
            {
                method: 'POST',
                url: '/api/v1/auth/vendor/register',
                name: 'Register',
                description: 'Register a new vendor account. Requires a recently verified OTP (within 10 minutes). Call send-otp + verify-phone first.',
                auth: false,
                group: 'auth',
                fields: [
                    { name: 'name', type: 'string', required: true, description: 'Vendor\'s full name', example: 'John Doe' },
                    { name: 'business_name', type: 'string', required: false, description: 'Business name (optional)', example: 'John\'s Delivery' },
                    { name: 'phone', type: 'string', required: true, description: 'Ghana phone (0244xxx or +233244xxx)', example: '+233244123456' },
                    { name: 'email', type: 'string', required: false, description: 'Email address (optional)', example: 'john@example.com' },
                    { name: 'fcm_token', type: 'string', required: false, description: 'Firebase Cloud Messaging device token for push notifications (captured at registration time)', example: 'fBXQ4v...' }
                ],
                sampleBody: {
                    name: 'John Doe',
                    business_name: 'John\'s Delivery',
                    phone: '+233244123456',
                    email: 'john@example.com',
                    fcm_token: 'fBXQ4v...'
                },
                exampleResponses: {
                    '200': {
                        success: true,
                        message: 'Registration successful.',
                        data: {
                            user: {
                                id: 1,
                                name: 'John Doe',
                                business_name: 'John\'s Delivery',
                                phone: '+233244123456',
                                email: 'john@example.com'
                            },
                            token: '2|def456uvw...'
                        }
                    },
                    '422_exists': {
                        success: false,
                        message: 'This phone is already registered.'
                    },
                    '422_expired': {
                        success: false,
                        message: 'Phone verification expired. Please verify your phone again.'
                    }
                }
            },
            {
                method: 'POST',
                url: '/api/v1/auth/vendor/logout',
                name: 'Logout',
                description: 'Logout and revoke the current access token.',
                auth: true,
                group: 'auth',
                fields: [],
                sampleBody: {},
                exampleResponses: {
                    '200': {
                        success: true,
                        message: 'Logged out successfully.'
                    },
                    '401': {
                        success: false,
                        message: 'Unauthenticated.'
                    }
                }
            },
            // Vendor Profile Endpoints
            {
                method: 'GET',
                url: '/api/v1/vendor/profile',
                name: 'Get Profile',
                description: 'Get the authenticated vendor\'s profile information.',
                auth: true,
                group: 'profile',
                fields: [],
                sampleBody: {},
                exampleResponses: {
                    '200': {
                        success: true,
                        message: 'Profile retrieved successfully.',
                        data: {
                            user: {
                                id: 1,
                                name: 'John Doe',
                                business_name: 'John\'s Delivery',
                                phone: '+233244123456',
                                email: 'john@example.com'
                            }
                        }
                    },
                    '401': {
                        success: false,
                        message: 'Unauthenticated.'
                    }
                }
            },
            {
                method: 'PUT',
                url: '/api/v1/vendor/profile',
                name: 'Update Profile',
                description: 'Update the authenticated vendor\'s profile. Phone number cannot be changed.',
                auth: true,
                group: 'profile',
                fields: [
                    { name: 'name', type: 'string', required: true, description: 'Vendor\'s full name', example: 'John Updated' },
                    { name: 'business_name', type: 'string', required: false, description: 'Business name (optional)', example: 'New Business Name' },
                    { name: 'email', type: 'string', required: false, description: 'Email address (optional)', example: 'newemail@example.com' }
                ],
                sampleBody: {
                    name: 'John Updated',
                    business_name: 'New Business Name',
                    email: 'newemail@example.com'
                },
                exampleResponses: {
                    '200': {
                        success: true,
                        message: 'Profile updated successfully.',
                        data: {
                            user: {
                                id: 1,
                                name: 'John Updated',
                                business_name: 'New Business Name',
                                phone: '+233244123456',
                                email: 'newemail@example.com'
                            }
                        }
                    },
                    '401': {
                        success: false,
                        message: 'Unauthenticated.'
                    },
                    '422': {
                        success: false,
                        message: 'The name field is required.'
                    }
                }
            },
            {
                method: 'POST',
                url: '/api/v1/vendor/fcm-token',
                name: 'Update FCM Token',
                description: 'Save or update the Firebase Cloud Messaging push notification token for this vendor\'s device. Call this after obtaining a token from the Firebase SDK on the client side.',
                auth: true,
                group: 'profile',
                fields: [
                    { name: 'fcm_token', type: 'string', required: true, description: 'Firebase Cloud Messaging device token', example: 'fBXQ4v...' }
                ],
                sampleBody: {
                    fcm_token: 'fBXQ4v...'
                },
                exampleResponses: {
                    '200': {
                        success: true,
                        message: 'FCM token updated successfully.'
                    },
                    '401': {
                        success: false,
                        message: 'Unauthenticated.'
                    },
                    '422': {
                        success: false,
                        message: 'The fcm token field is required.'
                    }
                }
            },
            {
                method: 'DELETE',
                url: '/api/v1/vendor/account',
                name: 'Delete Account',
                description: 'Permanently delete the vendor\'s account. This will revoke all API tokens, deactivate the account, and soft-delete it. This action cannot be undone from the app.',
                auth: true,
                group: 'profile',
                fields: [],
                sampleBody: {},
                exampleResponses: {
                    '200': {
                        success: true,
                        message: 'Your account has been deleted successfully.'
                    },
                    '401': {
                        success: false,
                        message: 'Unauthenticated.'
                    }
                }
            },
            // ==================== DRIVER ENDPOINTS ====================
            // Driver Auth Endpoints
            {
                method: 'POST',
                url: '/api/v1/driver/login',
                name: 'Login',
                description: 'Login with email and password. Drivers are created by admin.',
                auth: false,
                group: 'driver-auth',
                userType: 'driver',
                fields: [
                    { name: 'email', type: 'string', required: true, description: 'Driver\'s email address', example: 'driver@example.com' },
                    { name: 'password', type: 'string', required: true, description: 'Driver\'s password', example: 'password123' },
                    { name: 'fcm_token', type: 'string', required: false, description: 'Firebase Cloud Messaging device token for push notifications (captured at login time)', example: 'fBXQ4v...' }
                ],
                sampleBody: {
                    email: 'driver@example.com',
                    password: 'password123',
                    fcm_token: 'fBXQ4v...'
                },
                exampleResponses: {
                    '200': {
                        success: true,
                        message: 'Login successful.',
                        data: {
                            user: {
                                id: 1,
                                name: 'John Driver',
                                email: 'driver@example.com',
                                phone: '+233244123456',
                                vehicle_type: 'motorcycle',
                                vehicle_number: 'GR-1234-20',
                                license_number: 'DL123456',
                                base_location: 'Accra Central',
                                status: 'available',
                                is_active: true,
                                task_capabilities: ['pickup', 'delivery']
                            },
                            token: '1|drivertoken123...'
                        }
                    },
                    '401': {
                        success: false,
                        message: 'Invalid email or password.'
                    },
                    '401_inactive': {
                        success: false,
                        message: 'Your account has been deactivated.'
                    }
                }
            },
            {
                method: 'POST',
                url: '/api/v1/driver/logout',
                name: 'Logout',
                description: 'Logout and revoke the current access token. Status set to offline.',
                auth: true,
                group: 'driver-auth',
                userType: 'driver',
                fields: [],
                sampleBody: {},
                exampleResponses: {
                    '200': {
                        success: true,
                        message: 'Logged out successfully.'
                    },
                    '401': {
                        success: false,
                        message: 'Unauthenticated.'
                    }
                }
            },
            // Driver Profile Endpoints
            {
                method: 'GET',
                url: '/api/v1/driver/profile',
                name: 'Get Profile',
                description: 'Get the authenticated driver\'s profile information.',
                auth: true,
                group: 'driver-profile',
                userType: 'driver',
                fields: [],
                sampleBody: {},
                exampleResponses: {
                    '200': {
                        success: true,
                        message: 'Profile retrieved successfully.',
                        data: {
                            user: {
                                id: 1,
                                name: 'John Driver',
                                email: 'driver@example.com',
                                phone: '+233244123456',
                                vehicle_type: 'motorcycle',
                                vehicle_number: 'GR-1234-20',
                                license_number: 'DL123456',
                                base_location: 'Accra Central',
                                status: 'available',
                                is_active: true,
                                task_capabilities: ['pickup', 'delivery']
                            }
                        }
                    },
                    '401': {
                        success: false,
                        message: 'Unauthenticated.'
                    }
                }
            },
            {
                method: 'PUT',
                url: '/api/v1/driver/profile',
                name: 'Update Profile',
                description: 'Update the driver\'s profile. Email cannot be changed.',
                auth: true,
                group: 'driver-profile',
                userType: 'driver',
                useFormInputs: true,
                fields: [
                    { name: 'name', type: 'string', required: false, description: 'Driver\'s full name', example: 'John Updated' },
                    { name: 'phone', type: 'string', required: false, description: 'Ghana phone (0244xxx or +233244xxx)', example: '+233244999999' },
                    { name: 'vehicle_type', type: 'enum', required: false, description: 'Vehicle type', options: ['motorcycle', 'car', 'van', 'truck'], example: 'car' },
                    { name: 'vehicle_number', type: 'string', required: false, description: 'Vehicle registration number', example: 'GR-5678-21' },
                    { name: 'license_number', type: 'string', required: false, description: 'Driver\'s license number', example: 'DL789012' },
                    { name: 'base_location', type: 'string', required: false, description: 'Home/base location', example: 'Tema' }
                ],
                sampleBody: {
                    name: 'John Updated',
                    phone: '+233244999999',
                    vehicle_type: 'car',
                    vehicle_number: 'GR-5678-21',
                    license_number: 'DL789012',
                    base_location: 'Tema'
                },
                exampleResponses: {
                    '200': {
                        success: true,
                        message: 'Profile updated successfully.',
                        data: {
                            user: {
                                id: 1,
                                name: 'John Updated',
                                email: 'driver@example.com',
                                phone: '+233244999999',
                                vehicle_type: 'car',
                                vehicle_number: 'GR-5678-21',
                                license_number: 'DL789012',
                                base_location: 'Tema',
                                status: 'available',
                                is_active: true,
                                task_capabilities: ['pickup', 'delivery']
                            }
                        }
                    },
                    '401': {
                        success: false,
                        message: 'Unauthenticated.'
                    },
                    '422': {
                        success: false,
                        message: 'Vehicle type must be: motorcycle, car, van, or truck.'
                    }
                }
            },
            {
                method: 'PUT',
                url: '/api/v1/driver/change-password',
                name: 'Change Password',
                description: 'Change password. Requires current password verification. All other sessions will be logged out.',
                auth: true,
                group: 'driver-profile',
                userType: 'driver',
                fields: [
                    { name: 'current_password', type: 'string', required: true, description: 'Current password', example: 'oldpassword123' },
                    { name: 'new_password', type: 'string', required: true, description: 'New password (min 6 characters)', example: 'newpassword456' },
                    { name: 'confirm_password', type: 'string', required: true, description: 'Must match new_password', example: 'newpassword456' }
                ],
                sampleBody: {
                    current_password: 'oldpassword123',
                    new_password: 'newpassword456',
                    confirm_password: 'newpassword456'
                },
                exampleResponses: {
                    '200': {
                        success: true,
                        message: 'Password changed successfully.',
                        data: {
                            user: {
                                id: 1,
                                name: 'John Driver',
                                email: 'driver@example.com',
                                phone: '+233244123456',
                                vehicle_type: 'motorcycle',
                                vehicle_number: 'GR-1234-20',
                                license_number: 'DL123456',
                                base_location: 'Accra Central',
                                status: 'available',
                                is_active: true,
                                task_capabilities: ['pickup', 'delivery']
                            },
                            token: '2|newdrivertoken...'
                        }
                    },
                    '401': {
                        success: false,
                        message: 'Unauthenticated.'
                    },
                    '422_password': {
                        success: false,
                        message: 'Current password is incorrect.'
                    },
                    '422_validation': {
                        success: false,
                        message: 'New password must be at least 6 characters.'
                    }
                }
            },
            {
                method: 'POST',
                url: '/api/v1/driver/fcm-token',
                name: 'Update FCM Token',
                description: 'Save or update the Firebase Cloud Messaging push notification token for this driver\'s device. Call this after obtaining a token from the Firebase SDK on the client side.',
                auth: true,
                group: 'driver-profile',
                userType: 'driver',
                fields: [
                    { name: 'fcm_token', type: 'string', required: true, description: 'Firebase Cloud Messaging device token', example: 'fBXQ4v...' }
                ],
                sampleBody: {
                    fcm_token: 'fBXQ4v...'
                },
                exampleResponses: {
                    '200': {
                        success: true,
                        message: 'FCM token updated successfully.'
                    },
                    '401': {
                        success: false,
                        message: 'Unauthenticated.'
                    },
                    '422': {
                        success: false,
                        message: 'The fcm token field is required.'
                    }
                }
            },
            // ============ LOCATION ENDPOINTS ============
            {
                method: 'GET',
                url: '/api/v1/vendor/regions',
                name: 'List Regions',
                description: 'Get all active Ghana regions for address dropdowns.',
                auth: true,
                group: 'location',
                userType: 'vendor',
                fields: [],
                sampleBody: null,
                exampleResponses: {
                    '200': {
                        success: true,
                        message: 'Regions retrieved successfully.',
                        data: {
                            regions: [
                                {
                                    id: 1,
                                    name: 'Greater Accra',
                                    code: 'GAR',
                                    districts: [
                                        { id: 1, region_id: 1, name: 'Accra Metropolitan', code: 'GAR-AMA' },
                                        { id: 2, region_id: 1, name: 'Tema Metropolitan', code: 'GAR-TMA' }
                                    ]
                                },
                                {
                                    id: 2,
                                    name: 'Ashanti',
                                    code: 'ASH',
                                    districts: [
                                        { id: 30, region_id: 2, name: 'Kumasi Metropolitan', code: 'ASH-KMA' }
                                    ]
                                }
                            ]
                        }
                    },
                    '401': {
                        success: false,
                        message: 'Unauthenticated.'
                    }
                }
            },
            {
                method: 'GET',
                url: '/api/v1/vendor/regions/{region}/districts',
                name: 'List Districts',
                description: 'Get all active districts for a specific region.',
                auth: true,
                group: 'location',
                userType: 'vendor',
                urlParams: [
                    { name: 'region', type: 'dropdown', required: true, description: 'Select a region', source: 'regions', labelField: 'name', valueField: 'id' }
                ],
                fields: [],
                sampleBody: null,
                exampleResponses: {
                    '200': {
                        success: true,
                        message: 'Districts retrieved successfully.',
                        data: {
                            region: { id: 1, name: 'Greater Accra', code: 'GAR' },
                            districts: [
                                { id: 1, name: 'Accra Metropolitan', code: 'GAR-AMA' },
                                { id: 2, name: 'Tema Metropolitan', code: 'GAR-TMA' }
                            ]
                        }
                    },
                    '401': {
                        success: false,
                        message: 'Unauthenticated.'
                    },
                    '404': {
                        success: false,
                        message: 'Region not found.'
                    }
                }
            },
            {
                method: 'GET',
                url: '/api/v1/vendor/locations/search',
                name: 'Search Locations',
                description: 'Typeahead search for Ghana towns and cities. Returns matching locations with their district and region. Requires at least 2 characters. Results are ranked with prefix matches first (max 12 results).',
                auth: true,
                group: 'location',
                userType: 'vendor',
                fields: [
                    { name: 'q', type: 'string', required: true, description: 'Search query (min 2 characters). Matches town/city names that start with or contain the query.', example: 'Kasoa' }
                ],
                sampleBody: null,
                exampleResponses: {
                    '200': {
                        success: true,
                        message: 'Search results retrieved.',
                        data: {
                            locations: [
                                {
                                    id: 45,
                                    name: 'Kasoa',
                                    type: 'town',
                                    district: { id: 12, name: 'Awutu Senya East' },
                                    region: { id: 3, name: 'Central Region' },
                                    display: 'Kasoa, Awutu Senya East, Central Region'
                                },
                                {
                                    id: 46,
                                    name: 'Kasoa New Market',
                                    type: 'town',
                                    district: { id: 12, name: 'Awutu Senya East' },
                                    region: { id: 3, name: 'Central Region' },
                                    display: 'Kasoa New Market, Awutu Senya East, Central Region'
                                }
                            ]
                        }
                    },
                    '200_empty': {
                        success: true,
                        message: 'Search results retrieved.',
                        data: { locations: [] }
                    },
                    '401': {
                        success: false,
                        message: 'Unauthenticated.'
                    }
                }
            },
            // ============ SHIPMENT ENDPOINTS ============
            {
                method: 'GET',
                url: '/api/v1/vendor/shipments',
                name: 'List Shipments',
                description: 'Get vendor shipments with filtering using offset/limit pagination (same structure as List Invoices).',
                auth: true,
                group: 'shipments',
                userType: 'vendor',
                fields: [
                    { name: 'search', type: 'string', required: false, description: 'Search by shipment number, recipient, pickup contact, or item details', example: 'PCM-2026' },
                    { name: 'from_date', type: 'date', required: false, description: 'Created date from (YYYY-MM-DD)', example: '2026-01-01' },
                    { name: 'to_date', type: 'date', required: false, description: 'Created date to (YYYY-MM-DD)', example: '2026-12-31' },
                    { name: 'status', queryName: 'status[]', type: 'multiselect', required: false, description: 'Shipment statuses (array)', options: ['draft', 'submitted', 'invoice_sent', 'invoice_accepted', 'pickup_assigned', 'picked_up', 'at_warehouse', 'sorted', 'in_transit', 'at_destination', 'out_for_delivery', 'delivered', 'cancelled'], example: 'status[]=submitted&status[]=invoice_sent' },
                    { name: 'include', type: 'enum', required: false, description: 'Optional includes. Use `pickup_details` to include item-level pickup confirmations/photos in each shipment item.', options: ['pickup_details'], example: 'pickup_details' },
                    { name: 'limit', type: 'number', required: false, description: 'Number of items to return (max 100)', example: '15' },
                    { name: 'offset', type: 'number', required: false, description: 'Number of items to skip', example: '0' },
                    { name: 'sort_by', type: 'enum', required: false, description: 'Sort field. Allowed: id, shipment_number, status, destination_mode, delivery_recipient_name, pickup_contact_name, submitted_at, created_at, updated_at', options: ['created_at', 'updated_at', 'id', 'shipment_number', 'status', 'destination_mode', 'delivery_recipient_name', 'pickup_contact_name', 'submitted_at'] },
                    { name: 'sort_order', type: 'enum', required: false, description: 'Sort direction', options: ['asc', 'desc'] }
                ],
                sampleBody: null,
                exampleResponses: {
                    '200': {
                        success: true,
                        message: 'Shipments retrieved successfully.',
                        data: {
                            shipments: [
                                {
                                    id: 2,
                                    shipment_number: 'PCM-2026-00002',
                                    status: 'invoice_accepted',
                                    fulfillment_type: 'warehouse',
                                    destination_mode: 'single',
                                    pickup: {
                                        contact_name: 'Kwame Mensah',
                                        contact_phone: '+233244123456',
                                        location: {
                                            type: 'dropdown',
                                            region: 'Greater Accra',
                                            region_id: 1,
                                            district: 'Accra Metropolitan',
                                            district_id: 1,
                                            town: 'Labone',
                                            latitude: null,
                                            longitude: null,
                                            gh_post_address: null,
                                            landmark: 'Blue gate'
                                        },
                                        instructions: 'Pickup from reception'
                                    },
                                    delivery: {
                                        recipient_name: 'Ama Mensah',
                                        recipient_phone: '+233241234567',
                                        location: {
                                            type: 'dropdown',
                                            region: 'Greater Accra',
                                            region_id: 1,
                                            district: 'Accra Metropolitan',
                                            district_id: 1,
                                            town: 'Osu',
                                            latitude: null,
                                            longitude: null,
                                            gh_post_address: null,
                                            landmark: 'Near the market'
                                        },
                                        instructions: 'Call before delivery'
                                    },
                                    items: [
                                        {
                                            id: 21,
                                            description: '55-inch TV',
                                            quantity: 1,
                                            status: 'pending',
                                            tracking_code: 'TRK8A3F2K9X',
                                            delivery: null,
                                            images: [
                                                'https://gateway.storjshare.io/shaxi/demo/shipments/2/items/21/1706284800_abc123.jpg?X-Amz-Signature=...'
                                            ],
                                            created_at: '2026-02-10T11:20:00+00:00',
                                            updated_at: '2026-02-10T11:20:00+00:00'
                                        }
                                    ],
                                    collection: null,
                                    can_edit: false,
                                    can_delete: false,
                                    can_submit: false,
                                    submitted_at: '2026-02-10T11:30:00+00:00',
                                    created_at: '2026-02-10T11:14:37+00:00',
                                    updated_at: '2026-02-10T14:54:13+00:00',
                                    invoice: {
                                        id: 12,
                                        invoice_number: 'INV-2026-01000',
                                        shipment_id: 2,
                                        shipment_number: 'PCM-2026-00002',
                                        status: 'accepted',
                                        pickup_fee: 50.0,
                                        transport_fee: 120.0,
                                        handling_fee: 20.0,
                                        other_fee: 8.0,
                                        total_amount: 198.0,
                                        currency: 'GHS',
                                        notes: 'Fragile items',
                                        vendor_notes: 'Proceed with pickup',
                                        rejection_reason: null,
                                        cancel_reason: null,
                                        is_active: true,
                                        sent_at: '2026-02-10T12:00:00+00:00',
                                        accepted_at: '2026-02-10T13:24:00+00:00',
                                        rejected_at: null,
                                        cancelled_at: null,
                                        created_at: '2026-02-10T11:40:00+00:00',
                                        updated_at: '2026-02-10T13:24:00+00:00'
                                    },
                                    invoice_history: [
                                        {
                                            id: 11,
                                            invoice_number: 'INV-2026-00999',
                                            shipment_id: 2,
                                            shipment_number: 'PCM-2026-00002',
                                            status: 'rejected',
                                            pickup_fee: 45.0,
                                            transport_fee: 115.0,
                                            handling_fee: 20.0,
                                            other_fee: 8.0,
                                            total_amount: 188.0,
                                            currency: 'GHS',
                                            notes: 'Initial invoice',
                                            vendor_notes: null,
                                            rejection_reason: 'Transport fee mismatch',
                                            cancel_reason: null,
                                            is_active: false,
                                            sent_at: '2026-02-10T10:10:00+00:00',
                                            accepted_at: null,
                                            rejected_at: '2026-02-10T10:45:00+00:00',
                                            cancelled_at: null,
                                            created_at: '2026-02-10T10:00:00+00:00',
                                            updated_at: '2026-02-10T10:45:00+00:00'
                                        },
                                        {
                                            id: 12,
                                            invoice_number: 'INV-2026-01000',
                                            shipment_id: 2,
                                            shipment_number: 'PCM-2026-00002',
                                            status: 'accepted',
                                            pickup_fee: 50.0,
                                            transport_fee: 120.0,
                                            handling_fee: 20.0,
                                            other_fee: 8.0,
                                            total_amount: 198.0,
                                            currency: 'GHS',
                                            notes: 'Revised invoice',
                                            vendor_notes: 'Proceed with pickup',
                                            rejection_reason: null,
                                            cancel_reason: null,
                                            is_active: true,
                                            sent_at: '2026-02-10T12:00:00+00:00',
                                            accepted_at: '2026-02-10T13:24:00+00:00',
                                            rejected_at: null,
                                            cancelled_at: null,
                                            created_at: '2026-02-10T11:40:00+00:00',
                                            updated_at: '2026-02-10T13:24:00+00:00'
                                        }
                                    ],
                                    pickup_assignment: {
                                        id: 7,
                                        status: 'en_route',
                                        status_label: 'En Route',
                                        driver_name: 'Kojo Driver',
                                        driver_phone: '+233245000001',
                                        driver: {
                                            id: 4,
                                            name: 'Kojo Driver',
                                            phone: '+233245000001',
                                            vehicle_type: 'van',
                                            vehicle_number: 'GR-1234-24'
                                        },
                                        timeline: {
                                            assigned: { at: '2026-02-10T15:00:00+00:00' },
                                            en_route: { at: '2026-02-10T15:10:00+00:00' },
                                            arrived_pickup: { at: null, latitude: null, longitude: null },
                                            picked_up: { at: null },
                                            arrived_warehouse: { at: null, warehouse: null },
                                            received: { at: null, warehouse: null, received_by_user_id: null, notes: null },
                                            completed: { at: null },
                                            cancelled: { at: null, reason: null }
                                        }
                                    }
                                },
                                {
                                    id: 3,
                                    shipment_number: 'PCM-2026-00003',
                                    status: 'draft',
                                    fulfillment_type: 'warehouse',
                                    destination_mode: 'per_item',
                                    pickup: {
                                        contact_name: 'Yaw Asante',
                                        contact_phone: '+233247000333',
                                        location: {
                                            type: 'coordinates',
                                            region: null,
                                            region_id: null,
                                            district: null,
                                            district_id: null,
                                            town: null,
                                            latitude: '5.59130000',
                                            longitude: '-0.18640000',
                                            gh_post_address: null,
                                            landmark: 'Near police station'
                                        },
                                        instructions: null
                                    },
                                    delivery: null,
                                    items: [
                                        {
                                            id: 31,
                                            description: 'Boxed blender',
                                            quantity: 1,
                                            status: 'pending',
                                            tracking_code: 'TRK2F5G8H1J',
                                            delivery: {
                                                recipient_name: 'Kofi Boateng',
                                                recipient_phone: '+233241111111',
                                                location: {
                                                    type: 'gh_post',
                                                    region: null,
                                                    region_id: null,
                                                    district: null,
                                                    district_id: null,
                                                    town: null,
                                                    latitude: null,
                                                    longitude: null,
                                                    gh_post_address: 'GA-234-8891',
                                                    landmark: 'Opposite fuel station'
                                                },
                                                instructions: 'Leave with security'
                                            },
                                            images: [],
                                            created_at: '2026-02-10T18:12:00+00:00',
                                            updated_at: '2026-02-10T18:12:00+00:00'
                                        }
                                    ],
                                    collection: null,
                                    can_edit: true,
                                    can_delete: true,
                                    can_submit: true,
                                    submitted_at: null,
                                    created_at: '2026-02-10T18:10:00+00:00',
                                    updated_at: '2026-02-10T18:12:00+00:00',
                                    invoice: null,
                                    invoice_history: [],
                                    pickup_assignment: null
                                }
                            ],
                            pagination: {
                                offset: 0,
                                limit: 15,
                                total: 2,
                                has_more: false,
                                next_offset: null,
                                current_page: 1,
                                last_page: 1,
                                per_page: 15
                            }
                        }
                    }
                }
            },
            {
                method: 'POST',
                url: '/api/v1/vendor/shipments',
                name: 'Create Shipment',
                description: 'Create a draft shipment with pickup details and destination mode. Send `destination_mode` as `single` or `per_item`.',
                auth: true,
                group: 'shipments',
                userType: 'vendor',
                bodyType: 'formdata',
                useFormInputs: true,
                fields: [
                    { name: 'destination_mode', type: 'enum', required: true, description: 'Destination mode. Pass exact value: `single` or `per_item`. `single` = use shipment-level delivery fields. `per_item` = do not send shipment-level delivery fields; provide delivery on each item.', options: ['single', 'per_item'], labels: { single: 'Single destination for all items', per_item: 'Each item has its own destination' } },
                    { name: 'fulfillment_type', type: 'enum', required: false, description: 'Fulfillment method (single destination only). `warehouse` = standard warehouse delivery (default). `self_pickup` = recipient collects from warehouse. `direct` = driver delivers directly after pickup, no warehouse stop.', options: ['warehouse', 'self_pickup', 'direct'], labels: { warehouse: 'Warehouse Delivery (default)', self_pickup: 'Self Pickup by Recipient', direct: 'Direct Delivery' }, showWhen: { field: 'destination_mode', value: 'single' } },

                    { name: 'pickup_contact_name', type: 'string', required: true, description: 'Pickup contact full name', example: 'Kwame Mensah' },
                    { name: 'pickup_contact_phone', type: 'string', required: true, description: 'Pickup contact phone number', example: '+233244123456' },
                    { name: 'pickup_contact_phone_confirm', type: 'string', required: true, description: 'Confirm pickup phone (must match)', example: '+233244123456' },
                    { name: '_pickup_location_method', type: 'enum', uiOnly: true, required: false, description: 'Pickup location input method (UI only)', options: ['dropdown', 'coordinates', 'gh_post'], labels: { dropdown: 'Location Search (town/city)', coordinates: 'GPS Coordinates', gh_post: 'Ghana Post Address' } },
                    { name: '_pickup_location_search', type: 'location-search', uiOnly: true, required: false, description: 'Search for a town or city — selecting a result auto-fills the region ID, district ID and town below', fills: { region_id: 'pickup_region_id', district_id: 'pickup_district_id', town: 'pickup_town' }, showWhen: { field: '_pickup_location_method', value: 'dropdown' } },
                    { name: 'pickup_region_id', type: 'string', required: false, description: 'Auto-filled from location search (region ID)', example: '1', readonly: true, showWhen: { field: '_pickup_location_method', value: 'dropdown' } },
                    { name: 'pickup_district_id', type: 'string', required: false, description: 'Auto-filled from location search (district ID)', example: '3', readonly: true, showWhen: { field: '_pickup_location_method', value: 'dropdown' } },
                    { name: 'pickup_town', type: 'string', required: false, description: 'Auto-filled from location search (town name)', example: 'Osu', readonly: true, showWhen: { field: '_pickup_location_method', value: 'dropdown' } },
                    { name: 'pickup_latitude', type: 'number', required: false, description: 'Pickup GPS latitude (-90 to 90)', example: '5.5913', showWhen: { field: '_pickup_location_method', value: 'coordinates' } },
                    { name: 'pickup_longitude', type: 'number', required: false, description: 'Pickup GPS longitude (-180 to 180)', example: '-0.1864', showWhen: { field: '_pickup_location_method', value: 'coordinates' } },
                    { name: 'pickup_gh_post_address', type: 'string', required: false, description: 'Pickup Ghana Post address', example: 'GA-123-4567', showWhen: { field: '_pickup_location_method', value: 'gh_post' } },
                    { name: 'pickup_landmark', type: 'string', required: false, description: 'Pickup landmark', example: 'Near the big church' },
                    { name: 'pickup_instructions', type: 'string', required: false, description: 'Pickup instructions', example: 'Call before arrival' },

                    { name: 'delivery_recipient_name', type: 'string', required: false, description: 'Delivery recipient full name (single mode only)', example: 'Ama Mensah', showWhen: { field: 'destination_mode', value: 'single' } },
                    { name: 'delivery_recipient_phone', type: 'string', required: false, description: 'Delivery recipient phone (single mode only)', example: '+233241234567', showWhen: { field: 'destination_mode', value: 'single' } },
                    { name: 'delivery_recipient_phone_confirm', type: 'string', required: false, description: 'Confirm delivery phone (single mode only)', example: '+233241234567', showWhen: { field: 'destination_mode', value: 'single' } },
                    { name: '_delivery_location_method', type: 'enum', uiOnly: true, required: false, description: 'Delivery location input method (single mode only)', options: ['dropdown', 'coordinates', 'gh_post'], labels: { dropdown: 'Location Search (town/city)', coordinates: 'GPS Coordinates', gh_post: 'Ghana Post Address' }, showWhen: { field: 'destination_mode', value: 'single' } },
                    { name: '_delivery_location_search', type: 'location-search', uiOnly: true, required: false, description: 'Search for a town or city — selecting a result auto-fills the region ID, district ID and town below', fills: { region_id: 'delivery_region_id', district_id: 'delivery_district_id', town: 'delivery_town' }, showWhen: { field: '_delivery_location_method', value: 'dropdown' } },
                    { name: 'delivery_region_id', type: 'string', required: false, description: 'Auto-filled from location search (region ID)', example: '1', readonly: true, showWhen: { field: '_delivery_location_method', value: 'dropdown' } },
                    { name: 'delivery_district_id', type: 'string', required: false, description: 'Auto-filled from location search (district ID)', example: '3', readonly: true, showWhen: { field: '_delivery_location_method', value: 'dropdown' } },
                    { name: 'delivery_town', type: 'string', required: false, description: 'Auto-filled from location search (town name)', example: 'Tema', readonly: true, showWhen: { field: '_delivery_location_method', value: 'dropdown' } },
                    { name: 'delivery_latitude', type: 'number', required: false, description: 'Delivery GPS latitude (-90 to 90)', example: '5.6037', showWhen: { field: '_delivery_location_method', value: 'coordinates' } },
                    { name: 'delivery_longitude', type: 'number', required: false, description: 'Delivery GPS longitude (-180 to 180)', example: '-0.1870', showWhen: { field: '_delivery_location_method', value: 'coordinates' } },
                    { name: 'delivery_gh_post_address', type: 'string', required: false, description: 'Delivery Ghana Post address', example: 'GA-123-4567', showWhen: { field: '_delivery_location_method', value: 'gh_post' } },
                    { name: 'delivery_landmark', type: 'string', required: false, description: 'Delivery landmark', example: 'Near the market', showWhen: { field: 'destination_mode', value: 'single' } },
                    { name: 'delivery_instructions', type: 'string', required: false, description: 'Delivery instructions', example: 'Call before delivery', showWhen: { field: 'destination_mode', value: 'single' } }
                ],
                sampleBody: null,
                exampleResponses: {
                    '201_single': {
                        success: true,
                        message: 'Shipment created successfully.',
                        data: {
                            shipment: {
                                id: 1,
                                shipment_number: 'PCM-2026-00001',
                                status: 'draft',
                                fulfillment_type: 'warehouse',
                                destination_mode: 'single',
                                pickup: {
                                    contact_name: 'Kwame Mensah',
                                    contact_phone: '+233244123456',
                                    location: {
                                        type: 'dropdown',
                                        region: 'Greater Accra',
                                        region_id: 1,
                                        district: 'Accra Metropolitan',
                                        district_id: 1,
                                        town: 'Labone',
                                        latitude: null,
                                        longitude: null,
                                        gh_post_address: null,
                                        landmark: 'Blue gate'
                                    },
                                    instructions: 'Pickup from reception'
                                },
                                delivery: {
                                    recipient_name: 'Ama Mensah',
                                    recipient_phone: '+233241234567',
                                    location: {
                                        type: 'dropdown',
                                        region: 'Greater Accra',
                                        region_id: 1,
                                        district: 'Accra Metropolitan',
                                        district_id: 1,
                                        town: 'Osu',
                                        latitude: null,
                                        longitude: null,
                                        gh_post_address: null,
                                        landmark: 'Near the market'
                                    },
                                    instructions: 'Call before delivery'
                                },
                                items: [],
                                collection: null,
                                can_edit: true,
                                can_delete: true,
                                can_submit: false,
                                submitted_at: null,
                                created_at: '2026-02-10T11:14:37+00:00',
                                updated_at: '2026-02-10T11:14:37+00:00',
                                invoice: null,
                                invoice_history: [],
                                pickup_assignment: null
                            }
                        }
                    },
                    '201_per_item': {
                        success: true,
                        message: 'Shipment created successfully.',
                        data: {
                            shipment: {
                                id: 2,
                                shipment_number: 'PCM-2026-00002',
                                status: 'draft',
                                fulfillment_type: 'warehouse',
                                destination_mode: 'per_item',
                                pickup: {
                                    contact_name: 'Yaw Asante',
                                    contact_phone: '+233247000333',
                                    location: {
                                        type: 'coordinates',
                                        region: null,
                                        region_id: null,
                                        district: null,
                                        district_id: null,
                                        town: null,
                                        latitude: '5.59130000',
                                        longitude: '-0.18640000',
                                        gh_post_address: null,
                                        landmark: 'Near police station'
                                    },
                                    instructions: null
                                },
                                delivery: null,
                                items: [],
                                collection: null,
                                can_edit: true,
                                can_delete: true,
                                can_submit: false,
                                submitted_at: null,
                                created_at: '2026-02-10T18:10:00+00:00',
                                updated_at: '2026-02-10T18:10:00+00:00',
                                invoice: null,
                                invoice_history: [],
                                pickup_assignment: null
                            }
                        }
                    },
                    '422': {
                        success: false,
                        message: 'Validation failed.',
                        errors: { location: ['At least one location method is required.'] }
                    }
                }
            },
            {
                method: 'GET',
                url: '/api/v1/vendor/shipments/{shipment}',
                name: 'View Shipment',
                description: 'Get shipment details with items and images.',
                auth: true,
                group: 'shipments',
                userType: 'vendor',
                urlParams: [
                    { name: 'shipment', type: 'dropdown', required: true, description: 'Select shipment', source: 'shipments', labelField: 'shipment_number', valueField: 'id' }
                ],
                fields: [],
                sampleBody: null,
                exampleResponses: {
                    '200': {
                        success: true,
                        message: 'Shipment retrieved successfully.',
                        data: {
                            shipment: {
                                id: 2,
                                shipment_number: 'PCM-2026-00002',
                                status: 'invoice_accepted',
                                destination_mode: 'single',
                                pickup: {
                                    contact_name: 'Kwame Mensah',
                                    contact_phone: '+233244123456',
                                    location: {
                                        type: 'dropdown',
                                        region: 'Greater Accra',
                                        region_id: 1,
                                        district: 'Accra Metropolitan',
                                        district_id: 1,
                                        town: 'Labone',
                                        latitude: null,
                                        longitude: null,
                                        gh_post_address: null,
                                        landmark: 'Blue gate'
                                    },
                                    instructions: 'Pickup from reception'
                                },
                                delivery: {
                                    recipient_name: 'Ama Mensah',
                                    recipient_phone: '+233241234567',
                                    location: {
                                        type: 'dropdown',
                                        region: 'Greater Accra',
                                        region_id: 1,
                                        district: 'Accra Metropolitan',
                                        district_id: 1,
                                        town: 'Osu',
                                        latitude: null,
                                        longitude: null,
                                        gh_post_address: null,
                                        landmark: 'Near the market'
                                    },
                                    instructions: 'Call before delivery'
                                },
                                items: [
                                    {
                                        id: 21,
                                        description: '55-inch TV',
                                        quantity: 1,
                                        status: 'pending',
                                        tracking_code: 'TRK8A3F2K9X',
                                        delivery: null,
                                        images: [
                                            'https://gateway.storjshare.io/shaxi/demo/shipments/2/items/21/1706284800_abc123.jpg?X-Amz-Signature=...'
                                        ],
                                        created_at: '2026-02-10T11:20:00+00:00',
                                        updated_at: '2026-02-10T11:20:00+00:00'
                                    }
                                ],
                                collection: null,
                                can_edit: false,
                                can_delete: false,
                                can_submit: false,
                                invoice: {
                                    id: 12,
                                    invoice_number: 'INV-2026-01000',
                                    shipment_id: 2,
                                    shipment_number: 'PCM-2026-00002',
                                    status: 'accepted',
                                    pickup_fee: 50.0,
                                    transport_fee: 120.0,
                                    handling_fee: 20.0,
                                    other_fee: 8.0,
                                    total_amount: 198.0,
                                    currency: 'GHS',
                                    notes: 'Fragile items',
                                    vendor_notes: 'Proceed with pickup',
                                    rejection_reason: null,
                                    cancel_reason: null,
                                    is_active: true,
                                    sent_at: '2026-02-10T12:00:00+00:00',
                                    accepted_at: '2026-02-10T13:24:00+00:00',
                                    rejected_at: null,
                                    cancelled_at: null,
                                    created_at: '2026-02-10T11:40:00+00:00',
                                    updated_at: '2026-02-10T13:24:00+00:00'
                                },
                                invoice_history: [
                                    {
                                        id: 11,
                                        invoice_number: 'INV-2026-00999',
                                        shipment_id: 2,
                                        shipment_number: 'PCM-2026-00002',
                                        status: 'rejected',
                                        pickup_fee: 45.0,
                                        transport_fee: 115.0,
                                        handling_fee: 20.0,
                                        other_fee: 8.0,
                                        total_amount: 188.0,
                                        currency: 'GHS',
                                        notes: 'Initial invoice',
                                        vendor_notes: null,
                                        rejection_reason: 'Transport fee mismatch',
                                        cancel_reason: null,
                                        is_active: false,
                                        sent_at: '2026-02-10T10:10:00+00:00',
                                        accepted_at: null,
                                        rejected_at: '2026-02-10T10:45:00+00:00',
                                        cancelled_at: null,
                                        created_at: '2026-02-10T10:00:00+00:00',
                                        updated_at: '2026-02-10T10:45:00+00:00'
                                    },
                                    {
                                        id: 12,
                                        invoice_number: 'INV-2026-01000',
                                        shipment_id: 2,
                                        shipment_number: 'PCM-2026-00002',
                                        status: 'accepted',
                                        pickup_fee: 50.0,
                                        transport_fee: 120.0,
                                        handling_fee: 20.0,
                                        other_fee: 8.0,
                                        total_amount: 198.0,
                                        currency: 'GHS',
                                        notes: 'Revised invoice',
                                        vendor_notes: 'Proceed with pickup',
                                        rejection_reason: null,
                                        cancel_reason: null,
                                        is_active: true,
                                        sent_at: '2026-02-10T12:00:00+00:00',
                                        accepted_at: '2026-02-10T13:24:00+00:00',
                                        rejected_at: null,
                                        cancelled_at: null,
                                        created_at: '2026-02-10T11:40:00+00:00',
                                        updated_at: '2026-02-10T13:24:00+00:00'
                                    }
                                ],
                                pickup_assignment: {
                                    id: 7,
                                    status: 'en_route',
                                    status_label: 'En Route',
                                    driver_name: 'Kojo Driver',
                                    driver_phone: '+233245000001',
                                    driver: {
                                        id: 4,
                                        name: 'Kojo Driver',
                                        phone: '+233245000001',
                                        vehicle_type: 'van',
                                        vehicle_number: 'GR-1234-24'
                                    },
                                    timeline: {
                                        assigned: { at: '2026-02-10T15:00:00+00:00' },
                                        en_route: { at: '2026-02-10T15:10:00+00:00' },
                                        arrived_pickup: { at: null, latitude: null, longitude: null },
                                        picked_up: { at: null },
                                        arrived_warehouse: { at: null, warehouse: null },
                                        received: { at: null, warehouse: null, received_by_user_id: null, notes: null },
                                        completed: { at: null },
                                        cancelled: { at: null, reason: null }
                                    }
                                },
                                submitted_at: '2026-02-10T11:30:00+00:00',
                                created_at: '2026-02-10T11:14:37+00:00',
                                updated_at: '2026-02-10T14:54:13+00:00'
                            }
                        }
                    }
                }
            },
            {
                method: 'PUT',
                url: '/api/v1/vendor/shipments/{shipment}',
                name: 'Update Shipment',
                description: 'Update a draft shipment. Pickup details are shipment-level. Shared delivery fields are only allowed in single mode.',
                auth: true,
                group: 'shipments',
                userType: 'vendor',
                bodyType: 'formdata',
                useFormInputs: true,
                urlParams: [
                    { name: 'shipment', type: 'dropdown', required: true, description: 'Select draft shipment', source: 'shipments?status=draft', labelField: 'shipment_number', valueField: 'id', onSelect: 'prefillShipmentData' }
                ],
                fields: [
                    { name: 'destination_mode', type: 'enum', required: false, description: 'Destination mode', options: ['single', 'per_item'], labels: { single: 'Single destination for all items', per_item: 'Each item has its own destination' } },
                    { name: 'fulfillment_type', type: 'enum', required: false, description: 'Fulfillment method (single destination only)', options: ['warehouse', 'self_pickup', 'direct'], labels: { warehouse: 'Warehouse Delivery (default)', self_pickup: 'Self Pickup by Recipient', direct: 'Direct Delivery' }, showWhen: { field: 'destination_mode', value: 'single' } },

                    { name: 'pickup_contact_name', type: 'string', required: false, description: 'Pickup contact full name', example: 'Kwame Mensah' },
                    { name: 'pickup_contact_phone', type: 'string', required: false, description: 'Pickup contact phone number', example: '+233244123456' },
                    { name: 'pickup_contact_phone_confirm', type: 'string', required: false, description: 'Confirm pickup phone (required if changing)', example: '+233244123456' },
                    { name: '_pickup_location_method', type: 'enum', uiOnly: true, required: false, description: 'Pickup location input method (UI only)', options: ['dropdown', 'coordinates', 'gh_post'], labels: { dropdown: 'Location Search (town/city)', coordinates: 'GPS Coordinates', gh_post: 'Ghana Post Address' } },
                    { name: '_pickup_location_search', type: 'location-search', uiOnly: true, required: false, description: 'Search for a town or city — selecting a result auto-fills the region ID, district ID and town below', fills: { region_id: 'pickup_region_id', district_id: 'pickup_district_id', town: 'pickup_town' }, showWhen: { field: '_pickup_location_method', value: 'dropdown' } },
                    { name: 'pickup_region_id', type: 'string', required: false, description: 'Auto-filled from location search (region ID)', example: '1', readonly: true, showWhen: { field: '_pickup_location_method', value: 'dropdown' } },
                    { name: 'pickup_district_id', type: 'string', required: false, description: 'Auto-filled from location search (district ID)', example: '3', readonly: true, showWhen: { field: '_pickup_location_method', value: 'dropdown' } },
                    { name: 'pickup_town', type: 'string', required: false, description: 'Auto-filled from location search (town name)', example: 'Osu', readonly: true, showWhen: { field: '_pickup_location_method', value: 'dropdown' } },
                    { name: 'pickup_latitude', type: 'number', required: false, description: 'Pickup GPS latitude (-90 to 90)', example: '5.5913', showWhen: { field: '_pickup_location_method', value: 'coordinates' } },
                    { name: 'pickup_longitude', type: 'number', required: false, description: 'Pickup GPS longitude (-180 to 180)', example: '-0.1864', showWhen: { field: '_pickup_location_method', value: 'coordinates' } },
                    { name: 'pickup_gh_post_address', type: 'string', required: false, description: 'Pickup Ghana Post address', example: 'GA-123-4567', showWhen: { field: '_pickup_location_method', value: 'gh_post' } },
                    { name: 'pickup_landmark', type: 'string', required: false, description: 'Pickup landmark', example: 'Near the big church' },
                    { name: 'pickup_instructions', type: 'string', required: false, description: 'Pickup instructions', example: 'Call before arrival' },

                    { name: 'delivery_recipient_name', type: 'string', required: false, description: 'Delivery recipient full name (single mode only)', example: 'Ama Mensah', showWhen: { field: 'destination_mode', value: 'single' } },
                    { name: 'delivery_recipient_phone', type: 'string', required: false, description: 'Delivery recipient phone (single mode only)', example: '+233241234567', showWhen: { field: 'destination_mode', value: 'single' } },
                    { name: 'delivery_recipient_phone_confirm', type: 'string', required: false, description: 'Confirm delivery phone (required if changing)', example: '+233241234567', showWhen: { field: 'destination_mode', value: 'single' } },
                    { name: '_delivery_location_method', type: 'enum', uiOnly: true, required: false, description: 'Delivery location input method (single mode only)', options: ['dropdown', 'coordinates', 'gh_post'], labels: { dropdown: 'Location Search (town/city)', coordinates: 'GPS Coordinates', gh_post: 'Ghana Post Address' }, showWhen: { field: 'destination_mode', value: 'single' } },
                    { name: '_delivery_location_search', type: 'location-search', uiOnly: true, required: false, description: 'Search for a town or city — selecting a result auto-fills the region ID, district ID and town below', fills: { region_id: 'delivery_region_id', district_id: 'delivery_district_id', town: 'delivery_town' }, showWhen: { field: '_delivery_location_method', value: 'dropdown' } },
                    { name: 'delivery_region_id', type: 'string', required: false, description: 'Auto-filled from location search (region ID)', example: '1', readonly: true, showWhen: { field: '_delivery_location_method', value: 'dropdown' } },
                    { name: 'delivery_district_id', type: 'string', required: false, description: 'Auto-filled from location search (district ID)', example: '3', readonly: true, showWhen: { field: '_delivery_location_method', value: 'dropdown' } },
                    { name: 'delivery_town', type: 'string', required: false, description: 'Auto-filled from location search (town name)', example: 'Tema', readonly: true, showWhen: { field: '_delivery_location_method', value: 'dropdown' } },
                    { name: 'delivery_latitude', type: 'number', required: false, description: 'Delivery GPS latitude (-90 to 90)', example: '5.6037', showWhen: { field: '_delivery_location_method', value: 'coordinates' } },
                    { name: 'delivery_longitude', type: 'number', required: false, description: 'Delivery GPS longitude (-180 to 180)', example: '-0.1870', showWhen: { field: '_delivery_location_method', value: 'coordinates' } },
                    { name: 'delivery_gh_post_address', type: 'string', required: false, description: 'Delivery Ghana Post address', example: 'GA-123-4567', showWhen: { field: '_delivery_location_method', value: 'gh_post' } },
                    { name: 'delivery_landmark', type: 'string', required: false, description: 'Delivery landmark', example: 'Near the market', showWhen: { field: 'destination_mode', value: 'single' } },
                    { name: 'delivery_instructions', type: 'string', required: false, description: 'Delivery instructions', example: 'Call before delivery', showWhen: { field: 'destination_mode', value: 'single' } }
                ],
                sampleBody: null,
                exampleResponses: {
                    '200': {
                        success: true,
                        message: 'Shipment updated successfully.',
                        data: {
                            shipment: {
                                id: 1,
                                shipment_number: 'PCM-2026-00001',
                                status: 'draft',
                                items: [],
                                submitted_at: null,
                                created_at: '2026-01-27T10:30:00+00:00',
                                updated_at: '2026-01-27T10:45:00+00:00'
                            }
                        }
                    },
                    '400': {
                        success: false,
                        message: 'Shipment cannot be edited in its current status.'
                    }
                }
            },
            {
                method: 'DELETE',
                url: '/api/v1/vendor/shipments/{shipment}',
                name: 'Delete Shipment',
                description: 'Delete a draft shipment. Only draft shipments can be deleted.',
                auth: true,
                group: 'shipments',
                userType: 'vendor',
                urlParams: [
                    { name: 'shipment', type: 'dropdown', required: true, description: 'Select draft shipment', source: 'shipments?status=draft', labelField: 'shipment_number', valueField: 'id' }
                ],
                fields: [],
                sampleBody: null,
                exampleResponses: {
                    '200': {
                        success: true,
                        message: 'Shipment deleted successfully.',
                        data: null
                    },
                    '400': {
                        success: false,
                        message: 'Shipment cannot be deleted in its current status.'
                    }
                }
            },
            {
                method: 'POST',
                url: '/api/v1/vendor/shipments/{shipment}/submit',
                name: 'Submit Shipment',
                description: 'Submit a shipment for invoicing. Shipment must have at least one item.',
                auth: true,
                group: 'shipments',
                userType: 'vendor',
                urlParams: [
                    { name: 'shipment', type: 'dropdown', required: true, description: 'Select draft shipment', source: 'shipments?status=draft', labelField: 'shipment_number', valueField: 'id' }
                ],
                fields: [],
                sampleBody: null,
                exampleResponses: {
                    '200': {
                        success: true,
                        message: 'Shipment submitted for invoicing successfully.',
                        data: {
                            shipment: {
                                id: 1,
                                shipment_number: 'PCM-2026-00001',
                                status: 'submitted',
                                items: [
                                    {
                                        id: 1,
                                        description: 'Fridge - Samsung 250L',
                                        quantity: 2,
                                        status: 'pending',
                                        tracking_code: null,
                                        images: [
                                            {
                                                id: 1,
                                                url: 'https://gateway.storjshare.io/shaxi/demo/shipments/1/items/1/image.jpg?X-Amz-Signature=...',
                                                original_name: 'fridge.jpg',
                                                expires_at: '2026-01-27T12:00:00Z'
                                            }
                                        ],
                                        created_at: '2026-01-27T10:30:00+00:00'
                                    }
                                ],
                                submitted_at: '2026-01-27T11:00:00+00:00',
                                created_at: '2026-01-27T10:00:00+00:00',
                                updated_at: '2026-01-27T11:00:00+00:00'
                            }
                        }
                    },
                    '400_no_items': {
                        success: false,
                        message: 'Shipment must have at least one item before submitting.'
                    },
                    '400_status': {
                        success: false,
                        message: 'Shipment cannot be submitted in its current status.'
                    }
                }
            },
            // ============ SHIPMENT ITEM ENDPOINTS ============
            {
                method: 'POST',
                url: '/api/v1/vendor/shipments/{shipment}/items',
                name: 'Add Item',
                description: 'Add an item to a draft shipment. Optional: upload item images in the same request using `images[]`. If shipment is per_item mode, recipient and delivery location are required on the item.',
                auth: true,
                group: 'shipment-items',
                userType: 'vendor',
                bodyType: 'formdata',
                useFormInputs: true,
                urlParams: [
                    { name: 'shipment', type: 'dropdown', required: true, description: 'Select draft shipment', source: 'shipments?status=draft', labelField: 'shipment_number', valueField: 'id', onSelect: 'handleItemShipmentSelection' }
                ],
                fields: [
                    { name: 'description', type: 'string', required: true, description: 'Item description', example: 'Fridge - Samsung 250L' },
                    { name: 'quantity', type: 'number', required: false, description: 'Quantity (default 1)', example: '2' },
                    { name: 'images[]', type: 'file', required: false, description: 'Optional item images (select multiple, JPEG/PNG/WebP, max 5MB each)', example: '', multiple: true },
                    { name: '_item_delivery_mode', type: 'enum', uiOnly: true, required: false, readOnly: true, noticeOnly: true, description: 'Auto-detected from selected shipment destination mode', options: ['single', 'per_item'], labels: { single: 'Single destination shipment', per_item: 'Per-item destination shipment' } },
                    { name: 'delivery_recipient_name', type: 'string', required: false, description: 'Item recipient name (required for per_item mode)', example: 'Ama Mensah', showWhen: { field: '_item_delivery_mode', value: 'per_item' } },
                    { name: 'delivery_recipient_phone', type: 'string', required: false, description: 'Item recipient phone (required for per_item mode)', example: '+233241234567', showWhen: { field: '_item_delivery_mode', value: 'per_item' } },
                    { name: 'delivery_recipient_phone_confirm', type: 'string', required: false, description: 'Confirm item recipient phone', example: '+233241234567', showWhen: { field: '_item_delivery_mode', value: 'per_item' } },
                    { name: '_item_delivery_location_method', type: 'enum', uiOnly: true, required: false, description: 'Item delivery location method (UI only)', options: ['dropdown', 'coordinates', 'gh_post'], labels: { dropdown: 'Location Search (town/city)', coordinates: 'GPS Coordinates', gh_post: 'Ghana Post Address' }, showWhen: { field: '_item_delivery_mode', value: 'per_item' } },
                    { name: '_item_delivery_location_search', type: 'location-search', uiOnly: true, required: false, description: 'Search for a town or city — selecting a result auto-fills the region ID, district ID and town below', fills: { region_id: 'delivery_region_id', district_id: 'delivery_district_id', town: 'delivery_town' }, showWhen: { field: '_item_delivery_location_method', value: 'dropdown' } },
                    { name: 'delivery_region_id', type: 'string', required: false, description: 'Auto-filled from location search (region ID)', example: '1', readonly: true, showWhen: { field: '_item_delivery_location_method', value: 'dropdown' } },
                    { name: 'delivery_district_id', type: 'string', required: false, description: 'Auto-filled from location search (district ID)', example: '3', readonly: true, showWhen: { field: '_item_delivery_location_method', value: 'dropdown' } },
                    { name: 'delivery_town', type: 'string', required: false, description: 'Auto-filled from location search (town name)', example: 'Tema', readonly: true, showWhen: { field: '_item_delivery_location_method', value: 'dropdown' } },
                    { name: 'delivery_latitude', type: 'number', required: false, description: 'Item delivery GPS latitude', example: '5.6037', showWhen: { field: '_item_delivery_location_method', value: 'coordinates' } },
                    { name: 'delivery_longitude', type: 'number', required: false, description: 'Item delivery GPS longitude', example: '-0.1870', showWhen: { field: '_item_delivery_location_method', value: 'coordinates' } },
                    { name: 'delivery_gh_post_address', type: 'string', required: false, description: 'Item delivery Ghana Post address', example: 'GA-123-4567', showWhen: { field: '_item_delivery_location_method', value: 'gh_post' } },
                    { name: 'delivery_landmark', type: 'string', required: false, description: 'Item delivery landmark', example: 'Near the market', showWhen: { field: '_item_delivery_mode', value: 'per_item' } },
                    { name: 'delivery_instructions', type: 'string', required: false, description: 'Item delivery instructions', example: 'Leave with reception', showWhen: { field: '_item_delivery_mode', value: 'per_item' } },
                    { name: 'fulfillment_type', type: 'enum', required: false, description: 'Fulfillment type for this item (per_item mode only)', options: ['warehouse', 'direct', 'self_pickup'], example: 'warehouse', showWhen: { field: '_item_delivery_mode', value: 'per_item' } }
                ],
                sampleBody: null,
                exampleResponses: {
                    '201': {
                        success: true,
                        message: 'Item added and 1 image(s) uploaded successfully.',
                        data: {
                            item: {
                                id: 1,
                                description: 'Fridge - Samsung 250L',
                                quantity: 2,
                                status: 'pending',
                                tracking_code: 'TRK8A3F2K9X',
                                fulfillment_type: 'warehouse',
                                images: [
                                    {
                                        id: 1,
                                        url: 'https://gateway.storjshare.io/shaxi/demo/shipments/1/items/1/1706284800_abc123.jpg?X-Amz-Signature=...',
                                        original_name: 'fridge-front.jpg',
                                        size: 245760,
                                        size_human: '240.00 KB',
                                        expires_at: '2026-01-27T11:00:00Z'
                                    }
                                ],
                                created_at: '2026-01-27T10:35:00+00:00',
                                updated_at: '2026-01-27T10:35:00+00:00'
                            }
                        }
                    },
                    '400': {
                        success: false,
                        message: 'Cannot add items to a shipment that is not in draft status.'
                    }
                }
            },
            {
                method: 'PUT',
                url: '/api/v1/vendor/shipments/{shipment}/items/{item}',
                name: 'Update Item',
                description: 'Update an item in a draft shipment. Optional: upload new images (`images[]`) and remove existing images (`remove_image_ids[]` as array). In per_item mode, keep item-level recipient and delivery location complete.',
                auth: true,
                group: 'shipment-items',
                userType: 'vendor',
                bodyType: 'formdata',
                useFormInputs: true,
                urlParams: [
                    { name: 'shipment', type: 'dropdown', required: true, description: 'Select draft shipment', source: 'shipments?status=draft', labelField: 'shipment_number', valueField: 'id', onSelect: 'handleItemShipmentSelection' },
                    { name: 'item', type: 'dropdown', required: true, description: 'Select item', dependsOn: 'shipment', labelField: 'description', valueField: 'id', onSelect: 'prefillItemData' }
                ],
                fields: [
                    { name: 'description', type: 'string', required: false, description: 'Item description', example: 'Fridge - Samsung 300L' },
                    { name: 'quantity', type: 'number', required: false, description: 'Quantity', example: '3' },
                    { name: 'images[]', type: 'file', required: false, description: 'Optional new images to upload (select multiple, JPEG/PNG/WebP, max 5MB each)', example: '', multiple: true },
                    { name: 'remove_image_ids[]', type: 'multiselect', required: false, hideUntilPopulated: true, description: 'Optional existing images to remove (multi-select)', options: [] },
                    { name: '_item_delivery_mode', type: 'enum', uiOnly: true, required: false, readOnly: true, noticeOnly: true, description: 'Auto-detected from selected shipment destination mode', options: ['single', 'per_item'], labels: { single: 'Single destination shipment', per_item: 'Per-item destination shipment' } },
                    { name: 'delivery_recipient_name', type: 'string', required: false, description: 'Item recipient name (per_item mode)', example: 'Ama Mensah', showWhen: { field: '_item_delivery_mode', value: 'per_item' } },
                    { name: 'delivery_recipient_phone', type: 'string', required: false, description: 'Item recipient phone (per_item mode)', example: '+233241234567', showWhen: { field: '_item_delivery_mode', value: 'per_item' } },
                    { name: 'delivery_recipient_phone_confirm', type: 'string', required: false, description: 'Confirm item recipient phone', example: '+233241234567', showWhen: { field: '_item_delivery_mode', value: 'per_item' } },
                    { name: '_item_delivery_location_method', type: 'enum', uiOnly: true, required: false, description: 'Item delivery location method (UI only)', options: ['dropdown', 'coordinates', 'gh_post'], labels: { dropdown: 'Location Search (town/city)', coordinates: 'GPS Coordinates', gh_post: 'Ghana Post Address' }, showWhen: { field: '_item_delivery_mode', value: 'per_item' } },
                    { name: '_item_delivery_location_search', type: 'location-search', uiOnly: true, required: false, description: 'Search for a town or city — selecting a result auto-fills the region ID, district ID and town below', fills: { region_id: 'delivery_region_id', district_id: 'delivery_district_id', town: 'delivery_town' }, showWhen: { field: '_item_delivery_location_method', value: 'dropdown' } },
                    { name: 'delivery_region_id', type: 'string', required: false, description: 'Auto-filled from location search (region ID)', example: '1', readonly: true, showWhen: { field: '_item_delivery_location_method', value: 'dropdown' } },
                    { name: 'delivery_district_id', type: 'string', required: false, description: 'Auto-filled from location search (district ID)', example: '3', readonly: true, showWhen: { field: '_item_delivery_location_method', value: 'dropdown' } },
                    { name: 'delivery_town', type: 'string', required: false, description: 'Auto-filled from location search (town name)', example: 'Tema', readonly: true, showWhen: { field: '_item_delivery_location_method', value: 'dropdown' } },
                    { name: 'delivery_latitude', type: 'number', required: false, description: 'Item delivery GPS latitude', example: '5.6037', showWhen: { field: '_item_delivery_location_method', value: 'coordinates' } },
                    { name: 'delivery_longitude', type: 'number', required: false, description: 'Item delivery GPS longitude', example: '-0.1870', showWhen: { field: '_item_delivery_location_method', value: 'coordinates' } },
                    { name: 'delivery_gh_post_address', type: 'string', required: false, description: 'Item delivery Ghana Post address', example: 'GA-123-4567', showWhen: { field: '_item_delivery_location_method', value: 'gh_post' } },
                    { name: 'delivery_landmark', type: 'string', required: false, description: 'Item delivery landmark', example: 'Near the market', showWhen: { field: '_item_delivery_mode', value: 'per_item' } },
                    { name: 'delivery_instructions', type: 'string', required: false, description: 'Item delivery instructions', example: 'Leave with reception', showWhen: { field: '_item_delivery_mode', value: 'per_item' } },
                    { name: 'fulfillment_type', type: 'enum', required: false, description: 'Fulfillment type for this item (per_item mode only)', options: ['warehouse', 'direct', 'self_pickup'], example: 'warehouse', showWhen: { field: '_item_delivery_mode', value: 'per_item' } }
                ],
                sampleBody: null,
                exampleResponses: {
                    '200': {
                        success: true,
                        message: 'Item updated successfully. 1 image(s) removed and 1 image(s) uploaded.',
                        data: {
                            item: {
                                id: 1,
                                description: 'Fridge - Samsung 300L',
                                quantity: 3,
                                status: 'pending',
                                tracking_code: 'TRK8A3F2K9X',
                                fulfillment_type: 'warehouse',
                                images: [
                                    {
                                        id: 3,
                                        url: 'https://gateway.storjshare.io/shaxi/demo/shipments/1/items/1/1706284900_ghi789.jpg?X-Amz-Signature=...',
                                        original_name: 'fridge-new.jpg',
                                        size: 289000,
                                        size_human: '282.23 KB',
                                        expires_at: '2026-01-27T11:30:00Z'
                                    }
                                ],
                                created_at: '2026-01-27T10:35:00+00:00',
                                updated_at: '2026-01-27T10:40:00+00:00'
                            }
                        }
                    }
                }
            },
            {
                method: 'DELETE',
                url: '/api/v1/vendor/shipments/{shipment}/items/{item}',
                name: 'Remove Item',
                description: 'Remove an item from a draft shipment.',
                auth: true,
                group: 'shipment-items',
                userType: 'vendor',
                urlParams: [
                    { name: 'shipment', type: 'dropdown', required: true, description: 'Select draft shipment', source: 'shipments?status=draft', labelField: 'shipment_number', valueField: 'id' },
                    { name: 'item', type: 'dropdown', required: true, description: 'Select item', dependsOn: 'shipment', labelField: 'description', valueField: 'id' }
                ],
                fields: [],
                sampleBody: null,
                exampleResponses: {
                    '200': {
                        success: true,
                        message: 'Item removed successfully.',
                        data: null
                    }
                }
            },
            {
                method: 'POST',
                url: '/api/v1/vendor/shipments/{shipment}/items/{item}/images',
                name: 'Upload Images',
                description: 'Upload one or multiple images for a shipment item. Max 5 images per item. Supports JPEG, PNG, WebP. Max 5MB per file. Response includes both raw bytes (`size`) and readable size (`size_human`).',
                auth: true,
                group: 'shipment-items',
                userType: 'vendor',
                bodyType: 'formdata',
                useFormInputs: true,
                urlParams: [
                    { name: 'shipment', type: 'dropdown', required: true, description: 'Select draft shipment', source: 'shipments?status=draft', labelField: 'shipment_number', valueField: 'id' },
                    { name: 'item', type: 'dropdown', required: true, description: 'Select item', dependsOn: 'shipment', labelField: 'description', valueField: 'id' }
                ],
                fields: [
                    { name: 'images[]', type: 'file', required: true, description: 'Image files (select multiple, JPEG/PNG/WebP, max 5MB each)', example: '', multiple: true }
                ],
                sampleBody: null,
                exampleResponses: {
                    '201': {
                        success: true,
                        message: '2 images uploaded successfully.',
                        data: {
                            images: [
                                {
                                    id: 1,
                                    url: 'https://gateway.storjshare.io/shaxi/demo/shipments/1/items/1/1706284800_abc123.jpg?X-Amz-Signature=...',
                                    original_name: 'fridge-front.jpg',
                                    size: 245760,
                                    size_human: '240.00 KB',
                                    expires_at: '2026-01-27T11:00:00Z'
                                },
                                {
                                    id: 2,
                                    url: 'https://gateway.storjshare.io/shaxi/demo/shipments/1/items/1/1706284801_def456.jpg?X-Amz-Signature=...',
                                    original_name: 'fridge-inside.jpg',
                                    size: 312450,
                                    size_human: '305.13 KB',
                                    expires_at: '2026-01-27T11:00:00Z'
                                }
                            ]
                        }
                    },
                    '400_limit': {
                        success: false,
                        message: 'Maximum 5 images allowed per item.'
                    },
                    '400_status': {
                        success: false,
                        message: 'Cannot upload images to a shipment that is not in draft status.'
                    }
                }
            },
            {
                method: 'DELETE',
                url: '/api/v1/vendor/shipments/{shipment}/items/{item}/images/{image}',
                name: 'Delete Image',
                description: 'Delete an image from a shipment item.',
                auth: true,
                group: 'shipment-items',
                userType: 'vendor',
                urlParams: [
                    { name: 'shipment', type: 'dropdown', required: true, description: 'Select draft shipment', source: 'shipments?status=draft', labelField: 'shipment_number', valueField: 'id' },
                    { name: 'item', type: 'dropdown', required: true, description: 'Select item', dependsOn: 'shipment', labelField: 'description', valueField: 'id' },
                    { name: 'image', type: 'dropdown', required: true, description: 'Select image', dependsOn: 'item', labelField: 'original_name', valueField: 'id' }
                ],
                fields: [],
                sampleBody: null,
                exampleResponses: {
                    '200': {
                        success: true,
                        message: 'Image deleted successfully.',
                        data: null
                    }
                }
            },
            // ============ VENDOR INVOICE ENDPOINTS ============
            {
                method: 'GET',
                url: '/api/v1/vendor/invoices',
                name: 'List Invoices',
                description: 'Get vendor invoices with filtering. Vendor-visible statuses: sent, accepted, rejected, cancelled. Active statuses: pending, sent, accepted.',
                auth: true,
                group: 'invoices',
                userType: 'vendor',
                fields: [
                    { name: 'shipment_id', type: 'dropdown', required: false, description: 'Filter by shipment ID', source: 'shipments?per_page=100', labelField: 'shipment_number', valueField: 'id' },
                    { name: 'invoice_number', type: 'dropdown', required: false, description: 'Filter by invoice number. If shipment_id is selected, this list is scoped to that shipment.', source: 'invoices?limit=100', labelField: 'invoice_number', valueField: 'invoice_number', dependsOn: 'shipment_id', dependsOnQueryKey: 'shipment_id', allowWithoutParent: true },
                    { name: 'search', type: 'string', required: false, description: 'Search across invoice number, shipment number, status, notes, rejection reason, and cancel reason', example: 'INV-2026-0001' },
                    { name: 'from_date', type: 'date', required: false, description: 'Created date from (YYYY-MM-DD)', example: '2026-02-01' },
                    { name: 'to_date', type: 'date', required: false, description: 'Created date to (YYYY-MM-DD)', example: '2026-02-10' },
                    { name: 'status', queryName: 'status[]', type: 'multiselect', required: false, description: 'Invoice statuses (array). Allowed: pending, sent, accepted, rejected, cancelled', options: ['pending', 'sent', 'accepted', 'rejected', 'cancelled'], example: 'status[]=sent&status[]=accepted' },
                    { name: 'is_active', type: 'enum', required: false, description: 'Filter active invoices only. Active (system) = pending, sent, accepted. Vendor-visible active = sent, accepted.', options: ['1', '0'], labels: { '1': 'Yes', '0': 'No' } },
                    { name: 'limit', type: 'number', required: false, description: 'Number of items to return (max 100)', example: '15' },
                    { name: 'offset', type: 'number', required: false, description: 'Number of items to skip', example: '0' },
                    { name: 'sort_by', type: 'enum', required: false, description: 'Sort field. Allowed: id, invoice_number, status, pickup_fee, transport_fee, handling_fee, other_fee, total_amount, sent_at, accepted_at, rejected_at, cancelled_at, created_at, updated_at', options: ['created_at', 'updated_at', 'id', 'invoice_number', 'status', 'pickup_fee', 'transport_fee', 'handling_fee', 'other_fee', 'total_amount', 'sent_at', 'accepted_at', 'rejected_at', 'cancelled_at'] },
                    { name: 'sort_order', type: 'enum', required: false, description: 'Sort direction', options: ['asc', 'desc'] }
                ],
                sampleBody: null,
                exampleResponses: {
                    '200': {
                        success: true,
                        message: 'Invoices retrieved successfully.',
                        data: {
                            invoices: [
                                {
                                    id: 1,
                                    invoice_number: 'INV-2026-0001',
                                    shipment_id: 5,
                                    shipment_number: 'PCM-2026-00005',
                                    status: 'sent',
                                    pickup_fee: 50.0,
                                    transport_fee: 80.0,
                                    handling_fee: 20.0,
                                    other_fee: 0.0,
                                    total_amount: 150.0,
                                    currency: 'GHS',
                                    notes: 'Fragile items',
                                    vendor_notes: null,
                                    rejection_reason: null,
                                    cancel_reason: null,
                                    is_active: true,
                                    sent_at: '2026-02-05T09:10:00+00:00',
                                    accepted_at: null,
                                    rejected_at: null,
                                    cancelled_at: null,
                                    created_at: '2026-01-28T09:00:00+00:00',
                                    updated_at: '2026-01-28T09:00:00+00:00'
                                },
                                {
                                    id: 2,
                                    invoice_number: 'INV-2026-0002',
                                    shipment_id: 8,
                                    shipment_number: 'PCM-2026-00008',
                                    status: 'accepted',
                                    pickup_fee: 100.0,
                                    transport_fee: 180.0,
                                    handling_fee: 40.5,
                                    other_fee: 0.0,
                                    total_amount: 320.5,
                                    currency: 'GHS',
                                    notes: null,
                                    vendor_notes: 'Approved for payment.',
                                    rejection_reason: null,
                                    cancel_reason: null,
                                    is_active: true,
                                    sent_at: '2026-01-25T14:00:00+00:00',
                                    accepted_at: '2026-01-26T10:00:00+00:00',
                                    rejected_at: null,
                                    cancelled_at: null,
                                    created_at: '2026-01-25T14:00:00+00:00',
                                    updated_at: '2026-01-26T10:00:00+00:00'
                                }
                            ],
                            pagination: {
                                offset: 0,
                                limit: 15,
                                total: 2,
                                has_more: false,
                                next_offset: null,
                                current_page: 1,
                                last_page: 1,
                                per_page: 15
                            }
                        }
                    },
                    '401': {
                        success: false,
                        message: 'Unauthenticated.'
                    }
                }
            },
            {
                method: 'GET',
                url: '/api/v1/vendor/invoices/view',
                name: 'View Invoice',
                description: 'Get a specific invoice by shipment_id and/or invoice_id.',
                auth: true,
                group: 'invoices',
                userType: 'vendor',
                urlParams: [],
                fields: [
                    { name: 'shipment_id', type: 'dropdown', required: false, description: 'Optional: select shipment to fetch its linked invoice', source: 'shipments?per_page=100', labelField: 'shipment_number', valueField: 'id' },
                    { name: 'invoice_id', type: 'dropdown', required: false, description: 'Optional: select invoice directly. If shipment_id is selected, options are filtered by shipment.', source: 'invoices?limit=100', labelField: 'invoice_number', valueField: 'id', dependsOn: 'shipment_id', dependsOnQueryKey: 'shipment_id', allowWithoutParent: true }
                ],
                sampleBody: null,
                exampleResponses: {
                    '200': {
                        success: true,
                        message: 'Invoice retrieved successfully.',
                        data: {
                            invoice: {
                                id: 1,
                                invoice_number: 'INV-2026-0001',
                                shipment_id: 5,
                                shipment_number: 'PCM-2026-00005',
                                status: 'pending',
                                pickup_fee: 50.0,
                                transport_fee: 80.0,
                                handling_fee: 20.0,
                                other_fee: 0.0,
                                total_amount: 150.0,
                                currency: 'GHS',
                                notes: 'Fragile items',
                                vendor_notes: null,
                                rejection_reason: null,
                                cancel_reason: null,
                                is_active: true,
                                sent_at: null,
                                accepted_at: null,
                                rejected_at: null,
                                cancelled_at: null,
                                created_at: '2026-01-28T09:00:00+00:00',
                                updated_at: '2026-01-28T09:00:00+00:00',
                                shipment: {
                                    id: 5,
                                    shipment_number: 'PCM-2026-00005',
                                    status: 'invoice_sent',
                                    recipient_name: 'Ama Mensah',
                                    recipient_phone: '+233241234567',
                                    town: 'Tema',
                                    landmark: 'Community 1',
                                    created_at: '2026-01-27T08:00:00+00:00'
                                }
                            }
                        }
                    },
                    '401': {
                        success: false,
                        message: 'Unauthenticated.'
                    },
                    '404': {
                        success: false,
                        message: 'Invoice not found.'
                    },
                    '422': {
                        success: false,
                        message: 'Either shipment_id or invoice_id is required.',
                        errors: {
                            shipment_id: ['Provide shipment_id or invoice_id.'],
                            invoice_id: ['Provide shipment_id or invoice_id.']
                        }
                    }
                }
            },
            {
                method: 'POST',
                url: '/api/v1/vendor/invoices/{id}/accept',
                name: 'Accept Invoice',
                description: 'Accept an invoice. Optionally include vendor notes.',
                auth: true,
                group: 'invoices',
                userType: 'vendor',
                useFormInputs: true,
                urlParams: [
                    { name: 'id', type: 'dropdown', required: true, description: 'Select a sent invoice', source: 'invoices?status=sent&limit=100', labelField: 'invoice_number', valueField: 'id' }
                ],
                fields: [
                    { name: 'vendor_notes', type: 'string', required: false, description: 'Optional notes from vendor', example: 'Approved for payment.' }
                ],
                sampleBody: {
                    vendor_notes: 'Approved for payment.'
                },
                exampleResponses: {
                    '200': {
                        success: true,
                        message: 'Invoice accepted successfully.',
                        data: {
                            invoice: {
                                id: 1,
                                invoice_number: 'INV-2026-0001',
                                shipment_id: 5,
                                shipment_number: 'PCM-2026-00005',
                                status: 'accepted',
                                pickup_fee: 50.0,
                                transport_fee: 80.0,
                                handling_fee: 20.0,
                                other_fee: 0.0,
                                total_amount: 150.0,
                                currency: 'GHS',
                                notes: 'Fragile items',
                                vendor_notes: 'Approved for payment.',
                                rejection_reason: null,
                                cancel_reason: null,
                                is_active: true,
                                sent_at: '2026-01-28T09:10:00+00:00',
                                accepted_at: '2026-01-29T10:00:00+00:00',
                                rejected_at: null,
                                cancelled_at: null,
                                created_at: '2026-01-28T09:00:00+00:00',
                                updated_at: '2026-01-29T10:00:00+00:00',
                                shipment: {
                                    id: 5,
                                    shipment_number: 'PCM-2026-00005',
                                    status: 'invoice_accepted',
                                    recipient_name: 'Ama Mensah',
                                    recipient_phone: '+233241234567',
                                    town: 'Tema',
                                    landmark: 'Community 1',
                                    created_at: '2026-01-27T08:00:00+00:00'
                                }
                            }
                        }
                    },
                    '400': {
                        success: false,
                        message: 'Only sent invoices can be accepted.'
                    },
                    '401': {
                        success: false,
                        message: 'Unauthenticated.'
                    },
                    '404': {
                        success: false,
                        message: 'Invoice not found.'
                    }
                }
            },
            {
                method: 'POST',
                url: '/api/v1/vendor/invoices/{id}/reject',
                name: 'Reject Invoice',
                description: 'Reject an invoice. A rejection reason is required.',
                auth: true,
                group: 'invoices',
                userType: 'vendor',
                useFormInputs: true,
                urlParams: [
                    { name: 'id', type: 'dropdown', required: true, description: 'Select a sent invoice', source: 'invoices?status=sent&limit=100', labelField: 'invoice_number', valueField: 'id' }
                ],
                fields: [
                    { name: 'rejection_reason', type: 'string', required: true, description: 'Reason for rejecting the invoice', example: 'Incorrect delivery fee amount.' }
                ],
                sampleBody: {
                    rejection_reason: 'Incorrect delivery fee amount.'
                },
                exampleResponses: {
                    '200': {
                        success: true,
                        message: 'Invoice rejected successfully.',
                        data: {
                            invoice: {
                                id: 1,
                                invoice_number: 'INV-2026-0001',
                                shipment_id: 5,
                                shipment_number: 'PCM-2026-00005',
                                status: 'rejected',
                                pickup_fee: 50.0,
                                transport_fee: 80.0,
                                handling_fee: 20.0,
                                other_fee: 0.0,
                                total_amount: 150.0,
                                currency: 'GHS',
                                notes: 'Fragile items',
                                vendor_notes: null,
                                rejection_reason: 'Incorrect delivery fee amount.',
                                cancel_reason: null,
                                is_active: false,
                                sent_at: '2026-01-28T09:10:00+00:00',
                                accepted_at: null,
                                rejected_at: '2026-01-29T10:00:00+00:00',
                                cancelled_at: null,
                                created_at: '2026-01-28T09:00:00+00:00',
                                updated_at: '2026-01-29T10:00:00+00:00',
                                shipment: {
                                    id: 5,
                                    shipment_number: 'PCM-2026-00005',
                                    status: 'submitted',
                                    recipient_name: 'Ama Mensah',
                                    recipient_phone: '+233241234567',
                                    town: 'Tema',
                                    landmark: 'Community 1',
                                    created_at: '2026-01-27T08:00:00+00:00'
                                }
                            }
                        }
                    },
                    '400': {
                        success: false,
                        message: 'Only sent invoices can be rejected.'
                    },
                    '401': {
                        success: false,
                        message: 'Unauthenticated.'
                    },
                    '404': {
                        success: false,
                        message: 'Invoice not found.'
                    },
                    '422': {
                        success: false,
                        message: 'The rejection reason field is required.'
                    }
                }
            },
            {
                method: 'GET',
                url: '/api/v1/vendor/invoices/{id}/pdf',
                name: 'Download Invoice PDF',
                description: 'Download invoice as a PDF file. Only available for invoices that are not in pending status.',
                auth: true,
                group: 'invoices',
                userType: 'vendor',
                urlParams: [
                    { name: 'id', type: 'dropdown', required: true, description: 'Invoice ID', source: 'invoices?limit=100', labelField: 'invoice_number', valueField: 'id' }
                ],
                fields: [],
                sampleResponses: {
                    '200': 'PDF file download (Content-Type: application/pdf)',
                    '401': {
                        success: false,
                        message: 'Unauthenticated.'
                    },
                    '404': {
                        success: false,
                        message: 'Invoice not found.'
                    }
                }
            },
            // ============ VENDOR NOTIFICATION ENDPOINTS ============
            {
                method: 'GET',
                url: '/api/v1/vendor/notifications',
                name: 'List Notifications',
                description: 'Get vendor notifications with filtering and pagination. Returns push notification history sent to this vendor.',
                auth: true,
                group: 'vendor-notifications',
                userType: 'vendor',
                useFormInputs: true,
                fields: [
                    { name: 'status', type: 'enum', required: false, description: 'Filter by send status', options: ['sent', 'failed'], labels: { 'sent': 'sent — Successfully delivered', 'failed': 'failed — Delivery failed' } },
                    { name: 'type', type: 'string', required: false, description: 'Filter by notification type (e.g. shipment_status, invoice_sent, general)', example: 'shipment_status' },
                    { name: 'is_read', type: 'enum', required: false, description: 'Filter by read status (boolean)', options: ['true', 'false'], labels: { 'true': 'true — Read', 'false': 'false — Unread' } },
                    { name: 'from_date', type: 'date', required: false, description: 'Start date filter (YYYY-MM-DD)', example: '2026-01-01' },
                    { name: 'to_date', type: 'date', required: false, description: 'End date filter (YYYY-MM-DD)', example: '2026-12-31' },
                    { name: 'limit', type: 'number', required: false, description: 'Results per page (1-100, default 20)', example: '20' },
                    { name: 'offset', type: 'number', required: false, description: 'Number of results to skip (default 0)', example: '0' },
                    { name: 'sort_by', type: 'enum', required: false, description: 'Sort field', options: ['id', 'type', 'status', 'created_at', 'read_at'], labels: { 'id': 'id', 'type': 'type', 'status': 'status', 'created_at': 'created_at', 'read_at': 'read_at' } },
                    { name: 'sort_order', type: 'enum', required: false, description: 'Sort direction', options: ['asc', 'desc'], labels: { 'asc': 'asc — Ascending', 'desc': 'desc — Descending' } }
                ],
                exampleResponses: {
                    '200': {
                        success: true,
                        message: 'Notifications retrieved successfully.',
                        data: {
                            notifications: [
                                {
                                    id: 1,
                                    type: 'shipment_status',
                                    channel: 'push',
                                    title: 'Shipment Status Updated',
                                    body: 'Your shipment PCM-2026-00001 is now picked up.',
                                    data: { shipment_id: '1', status: 'picked_up' },
                                    status: 'sent',
                                    is_read: false,
                                    read_at: null,
                                    created_at: '2026-02-18T10:00:00.000000Z'
                                },
                                {
                                    id: 2,
                                    type: 'invoice_sent',
                                    channel: 'push',
                                    title: 'New Invoice',
                                    body: 'Invoice INV-2026-0001 has been sent for shipment PCM-2026-00001.',
                                    data: { invoice_id: '1', shipment_id: '1' },
                                    status: 'sent',
                                    is_read: true,
                                    read_at: '2026-02-18T11:00:00.000000Z',
                                    created_at: '2026-02-18T10:30:00.000000Z'
                                }
                            ],
                            unread_count: 3,
                            pagination: {
                                offset: 0, limit: 20, total: 2, has_more: false,
                                next_offset: null, current_page: 1, last_page: 1, per_page: 20
                            }
                        }
                    },
                    '401': { success: false, message: 'Unauthenticated.' }
                }
            },
            {
                method: 'POST',
                url: '/api/v1/vendor/notifications/{id}/read',
                name: 'Mark as Read',
                description: 'Mark a single notification as read.',
                auth: true,
                group: 'vendor-notifications',
                userType: 'vendor',
                useFormInputs: true,
                urlParams: [
                    { name: 'id', type: 'number', required: true, description: 'Notification ID', example: '1' }
                ],
                fields: [],
                exampleResponses: {
                    '200': {
                        success: true,
                        message: 'Notification marked as read.',
                        data: {
                            notification: {
                                id: 1,
                                type: 'shipment_status',
                                channel: 'push',
                                title: 'Shipment Status Updated',
                                body: 'Your shipment PCM-2026-00001 is now picked up.',
                                data: { shipment_id: '1', status: 'picked_up' },
                                status: 'sent',
                                is_read: true,
                                read_at: '2026-02-18T11:00:00.000000Z',
                                created_at: '2026-02-18T10:00:00.000000Z'
                            }
                        }
                    },
                    '401': { success: false, message: 'Unauthenticated.' },
                    '404': { success: false, message: 'Notification not found.' }
                }
            },
            {
                method: 'POST',
                url: '/api/v1/vendor/notifications/read-all',
                name: 'Mark All as Read',
                description: 'Mark all unread notifications as read for the authenticated vendor.',
                auth: true,
                group: 'vendor-notifications',
                userType: 'vendor',
                useFormInputs: false,
                fields: [],
                exampleResponses: {
                    '200': {
                        success: true,
                        message: '5 notification(s) marked as read.',
                        data: {
                            updated_count: 5
                        }
                    },
                    '401': { success: false, message: 'Unauthenticated.' }
                }
            },
            // ============ DRIVER PICKUP ENDPOINTS ============
            {
                method: 'GET',
                url: '/api/v1/driver/pickups',
                name: 'List Pickups',
                description: 'Get driver pickups with filtering and pagination structure aligned with Vendor Invoices List.',
                auth: true,
                group: 'driver-assignments',
                userType: 'driver',
                fields: [
                    { name: 'shipment_id', type: 'dropdown', required: false, description: 'Filter by shipment ID (shipments assigned to this driver)', source: 'pickups?limit=100', labelField: 'shipment_number', valueField: 'shipment_id', uniqueBy: 'shipment_id' },
                    { name: 'status', queryName: 'status[]', type: 'multiselect', required: false, description: 'Pickup statuses (array). Allowed: assigned, en_route, arrived, picking_up, completed, cancelled', options: ['assigned', 'en_route', 'arrived', 'picking_up', 'completed', 'cancelled'], example: 'status[]=assigned&status[]=en_route' },
                    { name: 'search', type: 'string', required: false, description: 'Search by shipment number, pickup contact, item details, status, notes, vendor', example: 'PCM-2026' },
                    { name: 'from_date', type: 'date', required: false, description: 'Created date from (YYYY-MM-DD)', example: '2026-01-01' },
                    { name: 'to_date', type: 'date', required: false, description: 'Created date to (YYYY-MM-DD)', example: '2026-12-31' },
                    { name: 'limit', type: 'number', required: false, description: 'Number of items to return (max 100)', example: '15' },
                    { name: 'offset', type: 'number', required: false, description: 'Number of items to skip', example: '0' },
                    { name: 'sort_by', type: 'enum', required: false, description: 'Sort field. Allowed: id, shipment_id, status, assigned_at, en_route_at, arrived_at, picked_up_at, completed_at, cancelled_at, created_at, updated_at', options: ['created_at', 'updated_at', 'id', 'shipment_id', 'status', 'assigned_at', 'en_route_at', 'arrived_at', 'picked_up_at', 'completed_at', 'cancelled_at'] },
                    { name: 'sort_order', type: 'enum', required: false, description: 'Sort direction', options: ['asc', 'desc'] }
                ],
                sampleBody: null,
                exampleResponses: {
                    '200': {
                        success: true,
                        message: 'Pickups retrieved successfully.',
                        data: {
                            pickups: [
                                {
                                    id: 1,
                                    shipment_id: 5,
                                    shipment_number: 'PCM-2026-00005',
                                    status: 'assigned',
                                    is_direct_delivery: false,
                                    direct_delivery: null,
                                    cancellation_reason: null,
                                    notes: 'Handle with care',
                                    timeline: {
                                        assigned: { at: '2026-02-01T08:00:00+00:00' },
                                        en_route: { at: null },
                                        arrived_pickup: { at: null, latitude: null, longitude: null },
                                        picked_up: { at: null },
                                        arrived_warehouse: { at: null, warehouse: null },
                                        received: { at: null, warehouse: null, received_by_user_id: null, notes: null },
                                        completed: { at: null },
                                        cancelled: { at: null, reason: null }
                                    },
                                    shipment: {
                                        id: 5,
                                        shipment_number: 'PCM-2026-00005',
                                        status: 'invoice_accepted',
                                        vendor_name: 'Acme Stores',
                                        pickup: {
                                            contact_name: 'Kwame Asante',
                                            contact_phone: '+233244123456',
                                            location: {
                                                region: 'Greater Accra',
                                                district: 'Accra Metropolitan',
                                                town: 'Labone',
                                                latitude: null,
                                                longitude: null,
                                                gh_post_address: null,
                                                landmark: 'Blue gate'
                                            },
                                            instructions: 'Pickup from reception'
                                        },
                                        items: [
                                            {
                                                id: 1,
                                                description: 'Fridge - Samsung 250L',
                                                quantity: 1,
                                                status: 'pending',
                                                tracking_code: 'TRK8A3F2K9X',
                                                images: [
                                                    'https://gateway.storjshare.io/shaxi/demo/shipments/5/items/1/1706860200_img.jpg?X-Amz-Signature=...'
                                                ],
                                                created_at: '2026-01-31T07:45:00+00:00',
                                                updated_at: '2026-01-31T07:45:00+00:00'
                                            }
                                        ],
                                        submitted_at: '2026-01-31T08:00:00+00:00',
                                        created_at: '2026-01-31T07:30:00+00:00',
                                        updated_at: '2026-02-01T08:00:00+00:00'
                                    },
                                    created_at: '2026-01-30T12:00:00+00:00',
                                    updated_at: '2026-01-30T12:00:00+00:00'
                                },
                                {
                                    id: 2,
                                    shipment_id: 8,
                                    shipment_number: 'PCM-2026-00008',
                                    status: 'en_route',
                                    is_direct_delivery: false,
                                    direct_delivery: null,
                                    cancellation_reason: null,
                                    notes: null,
                                    timeline: {
                                        assigned: { at: '2026-02-01T09:30:00+00:00' },
                                        en_route: { at: '2026-02-01T09:45:00+00:00' },
                                        arrived_pickup: { at: null, latitude: null, longitude: null },
                                        picked_up: { at: null },
                                        arrived_warehouse: { at: null, warehouse: null },
                                        received: { at: null, warehouse: null, received_by_user_id: null, notes: null },
                                        completed: { at: null },
                                        cancelled: { at: null, reason: null }
                                    },
                                    shipment: {
                                        id: 8,
                                        shipment_number: 'PCM-2026-00008',
                                        status: 'pickup_assigned',
                                        vendor_name: 'Acme Stores',
                                        pickup: {
                                            contact_name: 'Kofi Owusu',
                                            contact_phone: '+233245667788',
                                            location: {
                                                region: null,
                                                district: null,
                                                town: 'Community 1',
                                                latitude: '5.60370000',
                                                longitude: '-0.18700000',
                                                gh_post_address: null,
                                                landmark: 'Market Circle'
                                            },
                                            instructions: null
                                        },
                                        items: [
                                            {
                                                id: 9,
                                                description: 'Boxed blender',
                                                quantity: 2,
                                                status: 'pending',
                                                tracking_code: null,
                                                images: [],
                                                created_at: '2026-01-30T13:40:00+00:00',
                                                updated_at: '2026-01-30T13:40:00+00:00'
                                            }
                                        ],
                                        submitted_at: '2026-01-30T13:30:00+00:00',
                                        created_at: '2026-01-30T13:10:00+00:00',
                                        updated_at: '2026-01-30T13:30:00+00:00'
                                    },
                                    created_at: '2026-01-30T14:00:00+00:00',
                                    updated_at: '2026-02-01T09:45:00+00:00'
                                }
                            ],
                            pagination: {
                                offset: 0,
                                limit: 15,
                                total: 2,
                                has_more: false,
                                next_offset: null,
                                current_page: 1,
                                last_page: 1,
                                per_page: 15
                            }
                        }
                    },
                    '401': {
                        success: false,
                        message: 'Unauthenticated.'
                    }
                }
            },
            {
                method: 'GET',
                url: '/api/v1/driver/pickups/{id}',
                name: 'View Pickup',
                description: 'Get detailed information about a specific pickup.',
                auth: true,
                group: 'driver-assignments',
                userType: 'driver',
                urlParams: [
                    { name: 'id', type: 'dropdown', required: true, description: 'Select a pickup', source: 'pickups', labelField: 'shipment_number', valueField: 'id' }
                ],
                fields: [],
                sampleBody: null,
                exampleResponses: {
                    '200': {
                        success: true,
                        message: 'Pickup retrieved successfully.',
                        data: {
                            pickup: {
                                id: 1,
                                shipment_id: 5,
                                shipment_number: 'PCM-2026-00005',
                                status: 'assigned',
                                is_direct_delivery: false,
                                direct_delivery: null,
                                cancellation_reason: null,
                                notes: 'Handle with care',
                                pickup_latitude: null,
                                pickup_longitude: null,
                                timeline: {
                                    assigned: { at: '2026-02-01T08:00:00+00:00' },
                                    en_route: { at: null },
                                    arrived_pickup: { at: null, latitude: null, longitude: null },
                                    picked_up: { at: null },
                                    arrived_warehouse: { at: null, warehouse: null },
                                    received: { at: null, warehouse: null, received_by_user_id: null, notes: null },
                                    completed: { at: null },
                                    cancelled: { at: null, reason: null }
                                },
                                shipment: {
                                    id: 5,
                                    shipment_number: 'PCM-2026-00005',
                                    status: 'invoice_accepted',
                                    vendor_name: 'Acme Stores',
                                    pickup: {
                                        contact_name: 'Kwame Asante',
                                        contact_phone: '+233244123456',
                                        location: {
                                            region: 'Greater Accra',
                                            district: 'Accra Metropolitan',
                                            town: 'Labone',
                                            latitude: null,
                                            longitude: null,
                                            gh_post_address: null,
                                            landmark: 'Blue gate'
                                        },
                                        instructions: 'Pickup from reception'
                                    },
                                    items: [
                                        {
                                            id: 1,
                                            description: 'Fridge - Samsung 250L',
                                            quantity: 1,
                                            status: 'pending',
                                            tracking_code: 'TRK8A3F2K9X',
                                            images: [
                                                {
                                                    id: 1,
                                                    url: 'https://gateway.storjshare.io/shaxi/demo/shipments/5/items/1/1706860200_img.jpg?X-Amz-Signature=...',
                                                    original_name: 'fridge.jpg',
                                                    size: 198400,
                                                    size_human: '193.75 KB',
                                                    expires_at: '2026-02-01T09:00:00+00:00'
                                                }
                                            ],
                                            created_at: '2026-01-31T07:45:00+00:00',
                                            updated_at: '2026-01-31T07:45:00+00:00'
                                        }
                                    ],
                                    submitted_at: '2026-01-31T08:00:00+00:00',
                                    created_at: '2026-01-31T07:30:00+00:00',
                                    updated_at: '2026-02-01T08:00:00+00:00'
                                },
                                created_at: '2026-01-30T12:00:00+00:00',
                                updated_at: '2026-01-30T12:00:00+00:00'
                            }
                        }
                    },
                    '401': {
                        success: false,
                        message: 'Unauthenticated.'
                    },
                    '404': {
                        success: false,
                        message: 'Pickup not found.'
                    }
                }
            },
            {
                method: 'POST',
                url: '/api/v1/driver/pickups/{id}/en-route',
                name: 'Start En Route',
                description: 'Mark pickup as en route. Indicates the driver has started heading to the pickup location.',
                auth: true,
                group: 'driver-assignments',
                userType: 'driver',
                urlParams: [
                    { name: 'id', type: 'dropdown', required: true, description: 'Select an assigned pickup', source: 'pickups?status=assigned', labelField: 'shipment_number', valueField: 'id' }
                ],
                fields: [],
                sampleBody: null,
                exampleResponses: {
                    '200': {
                        success: true,
                        message: 'Driver is now en route.',
                        data: {
                            assignment: {
                                id: 1,
                                shipment_id: 5,
                                shipment_number: 'PCM-2026-00005',
                                status: 'en_route',
                                is_direct_delivery: false,
                                direct_delivery: null,
                                cancellation_reason: null,
                                notes: 'Handle with care',
                                pickup_latitude: null,
                                pickup_longitude: null,
                                timeline: {
                                    assigned: { at: '2026-02-01T08:00:00+00:00' },
                                    en_route: { at: '2026-02-01T08:10:00+00:00' },
                                    arrived_pickup: { at: null, latitude: null, longitude: null },
                                    picked_up: { at: null },
                                    arrived_warehouse: { at: null, warehouse: null },
                                    received: { at: null, warehouse: null, received_by_user_id: null, notes: null },
                                    completed: { at: null },
                                    cancelled: { at: null, reason: null }
                                },
                                target_warehouse: null,
                                shipment: {
                                    id: 5,
                                    shipment_number: 'PCM-2026-00005',
                                    status: 'invoice_accepted',
                                    vendor_name: 'Acme Stores',
                                    pickup: {
                                        contact_name: 'Kwame Asante',
                                        contact_phone: '+233244123456',
                                        location: {
                                            region: 'Greater Accra',
                                            district: 'Accra Metropolitan',
                                            town: 'Labone',
                                            latitude: null,
                                            longitude: null,
                                            gh_post_address: null,
                                            landmark: 'Blue gate'
                                        },
                                        instructions: 'Pickup from reception'
                                    },
                                    items: [
                                        {
                                            id: 1,
                                            description: 'Fridge - Samsung 250L',
                                            quantity: 1,
                                            status: 'pending',
                                            tracking_code: 'TRK8A3F2K9X',
                                            images: [
                                                'https://gateway.storjshare.io/shaxi/demo/shipments/5/items/1/1706860200_img.jpg?X-Amz-Signature=...'
                                            ],
                                            created_at: '2026-01-31T07:45:00+00:00',
                                            updated_at: '2026-01-31T07:45:00+00:00'
                                        }
                                    ],
                                    submitted_at: '2026-01-31T08:00:00+00:00',
                                    created_at: '2026-01-31T07:30:00+00:00',
                                    updated_at: '2026-02-01T08:00:00+00:00'
                                },
                                created_at: '2026-01-30T12:00:00+00:00',
                                updated_at: '2026-02-01T08:10:00+00:00'
                            }
                        }
                    },
                    '400': {
                        success: false,
                        message: 'Assignment is not in assigned status.'
                    },
                    '401': {
                        success: false,
                        message: 'Unauthenticated.'
                    },
                    '404': {
                        success: false,
                        message: 'Pickup not found.'
                    }
                }
            },
            {
                method: 'POST',
                url: '/api/v1/driver/pickups/{id}/arrive',
                name: 'Arrive',
                description: 'Mark that the driver has arrived at the pickup location for this pickup. Requires current GPS coordinates.',
                auth: true,
                group: 'driver-assignments',
                userType: 'driver',
                useFormInputs: true,
                urlParams: [
                    { name: 'id', type: 'dropdown', required: true, description: 'Select an en-route pickup', source: 'pickups?status=en_route', labelField: 'shipment_number', valueField: 'id' }
                ],
                fields: [
                    { name: 'latitude', type: 'string', required: true, description: 'Current GPS latitude (-90 to 90)', example: '5.6037' },
                    { name: 'longitude', type: 'string', required: true, description: 'Current GPS longitude (-180 to 180)', example: '-0.1870' }
                ],
                sampleBody: {
                    latitude: '5.6037',
                    longitude: '-0.1870'
                },
                exampleResponses: {
                    '200': {
                        success: true,
                        message: 'Driver has arrived.',
                        data: {
                            assignment: {
                                id: 1,
                                shipment_id: 5,
                                shipment_number: 'PCM-2026-00005',
                                status: 'arrived',
                                is_direct_delivery: false,
                                direct_delivery: null,
                                cancellation_reason: null,
                                notes: 'Handle with care',
                                pickup_latitude: 5.6037,
                                pickup_longitude: -0.1870,
                                timeline: {
                                    assigned: { at: '2026-02-01T08:00:00+00:00' },
                                    en_route: { at: '2026-02-01T08:10:00+00:00' },
                                    arrived_pickup: { at: '2026-02-01T08:15:00+00:00', latitude: 5.6037, longitude: -0.1870 },
                                    picked_up: { at: null },
                                    arrived_warehouse: { at: null, warehouse: null },
                                    received: { at: null, warehouse: null, received_by_user_id: null, notes: null },
                                    completed: { at: null },
                                    cancelled: { at: null, reason: null }
                                },
                                target_warehouse: null,
                                shipment: {
                                    id: 5,
                                    shipment_number: 'PCM-2026-00005',
                                    status: 'invoice_accepted',
                                    vendor_name: 'Acme Stores',
                                    pickup: {
                                        contact_name: 'Kwame Asante',
                                        contact_phone: '+233244123456',
                                        location: {
                                            region: 'Greater Accra',
                                            district: 'Accra Metropolitan',
                                            town: 'Labone',
                                            latitude: null,
                                            longitude: null,
                                            gh_post_address: null,
                                            landmark: 'Blue gate'
                                        },
                                        instructions: 'Pickup from reception'
                                    },
                                    items: [
                                        {
                                            id: 1,
                                            description: 'Fridge - Samsung 250L',
                                            quantity: 1,
                                            status: 'pending',
                                            tracking_code: 'TRK8A3F2K9X',
                                            images: [
                                                'https://gateway.storjshare.io/shaxi/demo/shipments/5/items/1/1706860200_img.jpg?X-Amz-Signature=...'
                                            ],
                                            created_at: '2026-01-31T07:45:00+00:00',
                                            updated_at: '2026-01-31T07:45:00+00:00'
                                        }
                                    ],
                                    submitted_at: '2026-01-31T08:00:00+00:00',
                                    created_at: '2026-01-31T07:30:00+00:00',
                                    updated_at: '2026-02-01T08:00:00+00:00'
                                },
                                created_at: '2026-01-30T12:00:00+00:00',
                                updated_at: '2026-02-01T08:15:00+00:00'
                            }
                        }
                    },
                    '400': {
                        success: false,
                        message: 'Driver must be en route to arrive.'
                    },
                    '401': {
                        success: false,
                        message: 'Unauthenticated.'
                    },
                    '404': {
                        success: false,
                        message: 'Pickup not found.'
                    },
                    '422': {
                        success: false,
                        message: 'The latitude field is required.'
                    }
                }
            },
            {
                method: 'POST',
                url: '/api/v1/driver/pickups/{shipment_id}/items/{item}/confirm',
                name: 'Confirm Pickup Item',
                description: 'Confirm or update one shipment item before pickup finalization. You can upload additional photos and/or remove existing uploaded photos with remove_photo_ids[].',
                auth: true,
                group: 'driver-assignments',
                userType: 'driver',
                bodyType: 'formdata',
                useFormInputs: true,
                urlParams: [
                    { name: 'shipment_id', type: 'dropdown', required: true, description: 'Select a pickup to confirm/update item', source: 'pickups?status=arrived,picking_up', labelField: 'shipment_number', valueField: 'id' },
                    { name: 'item', type: 'dropdown', required: true, description: 'Select shipment item under chosen pickup', dependsOn: 'shipment_id', labelField: 'display_name', valueField: 'id', onSelect: 'prefillPickupConfirmItemData' }
                ],
                fields: [
                    { name: 'confirmed_quantity', type: 'number', required: true, description: 'Quantity physically picked for this item', example: '1' },
                    { name: 'notes', type: 'string', required: false, description: 'Optional item-level pickup notes', example: 'Box sealed and counted' },
                    { name: 'remove_photo_ids[]', type: 'multiselect', required: false, hideUntilPopulated: true, description: 'Optional existing pickup photos to remove (multi-select)', options: [] },
                    { name: 'photos[]', type: 'file', required: false, description: 'Optional additional item proof photos (JPEG/PNG/WebP, max 10MB each)', example: '', multiple: true }
                ],
                sampleBody: null,
                exampleResponses: {
                    '200': {
                        success: true,
                        message: 'Pickup item confirmed successfully.',
                        data: {
                            assignment: {
                                id: 1,
                                shipment_id: 5,
                                shipment_number: 'PCM-2026-00005',
                                status: 'picking_up',
                                is_direct_delivery: false,
                                direct_delivery: null,
                                cancellation_reason: null,
                                notes: 'Handle with care',
                                pickup_latitude: 5.6037,
                                pickup_longitude: -0.1870,
                                timeline: {
                                    assigned: { at: '2026-02-01T08:00:00+00:00' },
                                    en_route: { at: '2026-02-01T08:10:00+00:00' },
                                    arrived_pickup: { at: '2026-02-01T08:15:00+00:00', latitude: 5.6037, longitude: -0.1870 },
                                    picked_up: { at: null },
                                    arrived_warehouse: { at: null, warehouse: null },
                                    received: { at: null, warehouse: null, received_by_user_id: null, notes: null },
                                    completed: { at: null },
                                    cancelled: { at: null, reason: null }
                                },
                                target_warehouse: null,
                                shipment: {
                                    id: 5,
                                    shipment_number: 'PCM-2026-00005',
                                    status: 'pickup_assigned',
                                    vendor_name: 'Acme Stores',
                                    pickup: {
                                        contact_name: 'Kwame Asante',
                                        contact_phone: '+233244123456',
                                        location: {
                                            region: 'Greater Accra',
                                            district: 'Accra Metropolitan',
                                            town: 'Labone',
                                            latitude: null,
                                            longitude: null,
                                            gh_post_address: null,
                                            landmark: 'Blue gate'
                                        },
                                        instructions: 'Pickup from reception'
                                    },
                                    items: [
                                        {
                                            id: 1,
                                            description: 'Fridge - Samsung 250L',
                                            quantity: 1,
                                            status: 'picked_up',
                                            tracking_code: 'TRK8A3F2K9X',
                                            images: [
                                                'https://gateway.storjshare.io/shaxi/demo/shipments/5/items/1/1706860200_img.jpg?X-Amz-Signature=...'
                                            ],
                                            pickup_confirmation: {
                                                expected_quantity: 1,
                                                confirmed_quantity: 1,
                                                missing_quantity: 0,
                                                extra_quantity: 0,
                                                is_exact_match: true,
                                                notes: 'Box sealed',
                                                confirmed_at: '2026-02-01T08:20:00+00:00',
                                                photos: [
                                                    {
                                                        id: 1,
                                                        url: 'https://gateway.storjshare.io/shaxi/demo/assignments/1/pickup_1706860500_abc123.jpg?X-Amz-Signature=...',
                                                        original_name: 'item-photo-1.jpg',
                                                        size: 198400,
                                                        size_human: '193.75 KB',
                                                        created_at: '2026-02-01T08:20:00+00:00'
                                                    },
                                                    {
                                                        id: 2,
                                                        url: 'https://gateway.storjshare.io/shaxi/demo/assignments/1/pickup_1706860501_def456.jpg?X-Amz-Signature=...',
                                                        original_name: 'item-photo-2.jpg',
                                                        size: 215300,
                                                        size_human: '210.25 KB',
                                                        created_at: '2026-02-01T08:20:00+00:00'
                                                    }
                                                ]
                                            },
                                            created_at: '2026-01-31T07:45:00+00:00',
                                            updated_at: '2026-01-31T07:45:00+00:00'
                                        }
                                    ],
                                    submitted_at: '2026-01-31T08:00:00+00:00',
                                    created_at: '2026-01-31T07:30:00+00:00',
                                    updated_at: '2026-02-01T08:00:00+00:00'
                                },
                                created_at: '2026-01-30T12:00:00+00:00',
                                updated_at: '2026-02-01T08:20:00+00:00'
                            }
                        }
                    },
                    '400': {
                        success: false,
                        message: 'Driver must have arrived to confirm an item.'
                    },
                    '401': {
                        success: false,
                        message: 'Unauthenticated.'
                    },
                    '404': {
                        success: false,
                        message: 'Pickup not found.'
                    },
                    '422': {
                        success: false,
                        message: 'The confirmed quantity field is required.'
                    }
                }
            },
            {
                method: 'POST',
                url: '/api/v1/driver/pickups/{id}/confirm-pickup',
                name: 'Finalize Pickup',
                description: 'Finalize pickup after all shipment items have been confirmed.',
                auth: true,
                group: 'driver-assignments',
                userType: 'driver',
                useFormInputs: true,
                urlParams: [
                    { name: 'id', type: 'dropdown', required: true, description: 'Select a pickup in picking_up status', source: 'pickups?status=picking_up', labelField: 'shipment_number', valueField: 'id' }
                ],
                fields: [
                    { name: 'latitude', type: 'string', required: false, description: 'Optional final pickup latitude (-90 to 90)', example: '5.6037' },
                    { name: 'longitude', type: 'string', required: false, description: 'Optional final pickup longitude (-180 to 180)', example: '-0.1870' },
                    { name: 'notes', type: 'string', required: false, description: 'Optional pickup completion notes', example: 'All items confirmed and loaded.' }
                ],
                sampleBody: {
                    notes: 'All items confirmed and loaded.'
                },
                exampleResponses: {
                    '200 (warehouse)': {
                        success: true,
                        message: 'Pickup finalized successfully.',
                        data: {
                            delivery_run_id: null,
                            assignment: {
                                id: 1,
                                shipment_id: 5,
                                shipment_number: 'PCM-2026-00005',
                                status: 'completed',
                                is_direct_delivery: false,
                                direct_delivery: null,
                                cancellation_reason: null,
                                notes: 'All items confirmed and loaded.',
                                pickup_latitude: 5.6037,
                                pickup_longitude: -0.1870,
                                timeline: {
                                    assigned: { at: '2026-02-01T08:00:00+00:00' },
                                    en_route: { at: '2026-02-01T08:10:00+00:00' },
                                    arrived_pickup: { at: '2026-02-01T08:15:00+00:00', latitude: 5.6037, longitude: -0.1870 },
                                    picked_up: { at: '2026-02-01T08:25:00+00:00' },
                                    arrived_warehouse: { at: null, warehouse: null },
                                    received: { at: null, warehouse: null, received_by_user_id: null, notes: null },
                                    completed: { at: '2026-02-01T08:25:00+00:00' },
                                    cancelled: { at: null, reason: null }
                                },
                                target_warehouse: null,
                                shipment: {
                                    id: 5,
                                    shipment_number: 'PCM-2026-00005',
                                    status: 'picked_up',
                                    vendor_name: 'Acme Stores',
                                    pickup: {
                                        contact_name: 'Kwame Asante',
                                        contact_phone: '+233244123456',
                                        location: {
                                            region: 'Greater Accra',
                                            district: 'Accra Metropolitan',
                                            town: 'Labone',
                                            latitude: null,
                                            longitude: null,
                                            gh_post_address: null,
                                            landmark: 'Blue gate'
                                        },
                                        instructions: 'Pickup from reception'
                                    },
                                    items: [
                                        {
                                            id: 1,
                                            description: 'Fridge - Samsung 250L',
                                            quantity: 1,
                                            status: 'picked_up',
                                            tracking_code: 'TRK8A3F2K9X',
                                            images: [
                                                'https://gateway.storjshare.io/shaxi/demo/shipments/5/items/1/1706860200_img.jpg?X-Amz-Signature=...'
                                            ],
                                            pickup_confirmation: {
                                                expected_quantity: 1,
                                                confirmed_quantity: 1,
                                                missing_quantity: 0,
                                                extra_quantity: 0,
                                                is_exact_match: true,
                                                notes: 'Box sealed',
                                                confirmed_at: '2026-02-01T08:20:00+00:00',
                                                photos: [
                                                    {
                                                        id: 1,
                                                        url: 'https://gateway.storjshare.io/shaxi/demo/assignments/1/pickup_1706860500_abc123.jpg?X-Amz-Signature=...',
                                                        original_name: 'item-photo-1.jpg',
                                                        size: 198400,
                                                        size_human: '193.75 KB',
                                                        created_at: '2026-02-01T08:20:00+00:00'
                                                    }
                                                ]
                                            },
                                            created_at: '2026-01-31T07:45:00+00:00',
                                            updated_at: '2026-02-01T08:25:00+00:00'
                                        }
                                    ],
                                    submitted_at: '2026-01-31T08:00:00+00:00',
                                    created_at: '2026-01-31T07:30:00+00:00',
                                    updated_at: '2026-02-01T08:25:00+00:00'
                                },
                                created_at: '2026-01-30T12:00:00+00:00',
                                updated_at: '2026-02-01T08:25:00+00:00'
                            }
                        }
                    },
                    '200 (direct delivery)': {
                        success: true,
                        message: 'Pickup confirmed. Proceed to delivery.',
                        data: {
                            delivery_run_id: 15,
                            assignment: {
                                id: 2,
                                shipment_id: 8,
                                shipment_number: 'PCM-2026-00008',
                                status: 'completed',
                                is_direct_delivery: true,
                                direct_delivery: {
                                    recipient_name: 'Ama Mensah',
                                    recipient_phone: '+233241234567',
                                    location: {
                                        region: 'Greater Accra',
                                        district: 'Accra Metropolitan',
                                        town: 'Osu',
                                        latitude: '5.5558',
                                        longitude: '-0.1845',
                                        gh_post_address: 'GA-144-2020',
                                        landmark: 'Near Oxford Street'
                                    },
                                    instructions: 'Call when you arrive'
                                },
                                cancellation_reason: null,
                                notes: 'All items confirmed.',
                                '...': 'same structure as warehouse response'
                            }
                        }
                    },
                    '400': {
                        success: false,
                        message: 'All shipment items must be confirmed before finalizing pickup.'
                    },
                    '401': {
                        success: false,
                        message: 'Unauthenticated.'
                    },
                    '404': {
                        success: false,
                        message: 'Pickup not found.'
                    },
                    '422': {
                        success: false,
                        message: 'The longitude field is required when latitude is present.'
                    }
                }
            },
            // ============ DRIVER TRANSPORT ENDPOINTS ============
            {
                method: 'GET',
                url: '/api/v1/driver/transports',
                name: 'List Transports',
                description: 'Get transport assignments for the authenticated driver with filters and offset pagination.',
                auth: true,
                group: 'driver-transports',
                userType: 'driver',
                fields: [
                    { name: 'status', queryName: 'status[]', type: 'multiselect', required: false, description: 'Filter by manifest status (array). See Field Reference below for all values.', options: ['draft', 'assigned', 'loading', 'in_transit', 'arrived', 'received', 'cancelled'] },
                    { name: 'search', type: 'string', required: false, description: 'Search by manifest number, shipment number, status', example: 'TM-2026' },
                    { name: 'limit', type: 'number', required: false, description: 'Number of rows to return (max 100)', example: '15' },
                    { name: 'offset', type: 'number', required: false, description: 'Rows to skip', example: '0' },
                    { name: 'sort_by', type: 'enum', required: false, description: 'Sort field', options: ['created_at', 'updated_at', 'id', 'manifest_number', 'status', 'assigned_at', 'dispatched_at', 'arrived_at'] },
                    { name: 'sort_order', type: 'enum', required: false, description: 'Sort direction', options: ['asc', 'desc'] }
                ],
                enums: {
                    'Manifest status': [
                        { value: 'draft',       description: 'Manifest created but no driver assigned yet' },
                        { value: 'assigned',    description: 'Driver assigned — waiting for loading to begin' },
                        { value: 'loading',     description: 'Driver is actively scanning items onto the vehicle (scan_out)' },
                        { value: 'in_transit',  description: 'Vehicle has departed from origin warehouse' },
                        { value: 'arrived',     description: 'Driver has arrived at the destination warehouse' },
                        { value: 'received',    description: 'Destination warehouse has scanned in and accepted all items' },
                        { value: 'cancelled',   description: 'Manifest was cancelled' },
                    ],
                    'Item line_status': [
                        { value: 'pending',     description: 'Item is on the manifest but has not been scanned yet' },
                        { value: 'loaded',      description: 'Driver scanned item OUT at origin — scan_out_count incremented, loaded_quantity set' },
                        { value: 'received',    description: 'Destination warehouse scanned item IN — scan_in_count incremented, received_quantity set' },
                        { value: 'short',       description: 'Item arrived but fewer units than expected_quantity' },
                        { value: 'excess',      description: 'Item arrived with more units than expected_quantity' },
                        { value: 'damaged',     description: 'Item arrived in damaged condition' },
                    ],
                    'Item scan counts': [
                        { value: 'scan_out_count', description: 'How many times the driver scanned this item OUT at the origin warehouse during loading. Normally 1; increments on each scan of the same tracking code.' },
                        { value: 'scan_in_count',  description: 'How many times the destination warehouse scanned this item IN on arrival. Normally 1; increments on each re-scan.' },
                    ],
                },
                sampleBody: null,
                exampleResponses: {
                    '200': {
                        success: true,
                        message: 'Transports retrieved successfully.',
                        data: {
                            transports: [
                                {
                                    id: 4,
                                    manifest_number: 'TM-2026-AC01-KM01-0001',
                                    status: 'loading',
                                    origin_warehouse: {
                                        id: 1,
                                        name: 'Accra Main Hub',
                                        code: 'AC01',
                                        address: '123 Ring Road, Accra',
                                        latitude: '5.60391200',
                                        longitude: '-0.18690900',
                                        contact_phone: '+233201234567'
                                    },
                                    destination_warehouse: {
                                        id: 2,
                                        name: 'Kumasi Hub',
                                        code: 'KM01',
                                        address: '45 Adum Street, Kumasi',
                                        latitude: '6.68702800',
                                        longitude: '-1.62460600',
                                        contact_phone: '+233209876543'
                                    },
                                    timeline: {
                                        assigned:   { at: '2026-02-18T09:00:00.000000Z' },
                                        dispatched: { at: '2026-02-18T09:10:00.000000Z' },
                                        arrived:    { at: null },
                                        received:   { at: null }
                                    },
                                    items: [
                                        {
                                            shipment_item_id: 14,
                                            shipment_number: 'PCM-2026-00014',
                                            description: 'LED TV 50-inch',
                                            tracking_code: 'TRK5PNQ13E',
                                            expected_quantity: 2,
                                            loaded_quantity: 2,
                                            received_quantity: 0,
                                            line_status: 'loaded',
                                            scan_out_count: 1,
                                            scan_in_count: 0,
                                            loaded_at: '2026-02-18T09:12:00.000000Z',
                                            received_at: null,
                                            notes: null
                                        },
                                        {
                                            shipment_item_id: 15,
                                            shipment_number: 'PCM-2026-00015',
                                            description: 'Laptop Bag',
                                            tracking_code: 'TRKWCQ2TNJH',
                                            expected_quantity: 5,
                                            loaded_quantity: 0,
                                            received_quantity: 0,
                                            line_status: 'pending',
                                            scan_out_count: 0,
                                            scan_in_count: 0,
                                            loaded_at: null,
                                            received_at: null,
                                            notes: null
                                        }
                                    ],
                                    notes: null,
                                    created_at: '2026-02-18T08:50:00.000000Z',
                                    updated_at: '2026-02-18T09:12:00.000000Z'
                                }
                            ],
                            pagination: {
                                offset: 0,
                                limit: 15,
                                total: 1,
                                has_more: false,
                                next_offset: null,
                                current_page: 1,
                                last_page: 1,
                                per_page: 15
                            }
                        }
                    },
                    '401': { success: false, message: 'Unauthenticated.' }
                }
            },
            {
                method: 'GET',
                url: '/api/v1/driver/transports/{manifest}',
                name: 'View Transport',
                description: 'Get a single transport manifest assigned to the authenticated driver.',
                auth: true,
                group: 'driver-transports',
                userType: 'driver',
                useFormInputs: true,
                urlParams: [
                    { name: 'manifest', type: 'dropdown', required: true, description: 'Select a transport manifest', source: 'transports?limit=100', labelField: 'manifest_number', valueField: 'id' }
                ],
                fields: [],
                enums: {
                    'Manifest status': [
                        { value: 'draft',       description: 'Manifest created but no driver assigned yet' },
                        { value: 'assigned',    description: 'Driver assigned — waiting for loading to begin' },
                        { value: 'loading',     description: 'Driver is actively scanning items onto the vehicle (scan_out)' },
                        { value: 'in_transit',  description: 'Vehicle has departed from origin warehouse' },
                        { value: 'arrived',     description: 'Driver has arrived at the destination warehouse' },
                        { value: 'received',    description: 'Destination warehouse has scanned in and accepted all items' },
                        { value: 'cancelled',   description: 'Manifest was cancelled' },
                    ],
                    'Item line_status': [
                        { value: 'pending',     description: 'Item is on the manifest but has not been scanned yet' },
                        { value: 'loaded',      description: 'Driver scanned item OUT at origin — scan_out_count incremented, loaded_quantity set' },
                        { value: 'received',    description: 'Destination warehouse scanned item IN — scan_in_count incremented, received_quantity set' },
                        { value: 'short',       description: 'Item arrived but fewer units than expected_quantity' },
                        { value: 'excess',      description: 'Item arrived with more units than expected_quantity' },
                        { value: 'damaged',     description: 'Item arrived in damaged condition' },
                    ],
                    'Item scan counts': [
                        { value: 'scan_out_count', description: 'How many times the driver scanned this item OUT at the origin warehouse during loading.' },
                        { value: 'scan_in_count',  description: 'How many times the destination warehouse scanned this item IN on arrival.' },
                    ],
                },
                sampleBody: {},
                exampleResponses: {
                    '200': {
                        success: true,
                        message: 'Transport retrieved successfully.',
                        data: {
                            transport: {
                                id: 4,
                                manifest_number: 'TM-2026-AC01-KM01-0001',
                                status: 'in_transit',
                                origin_warehouse: {
                                    id: 1,
                                    name: 'Accra Main Hub',
                                    code: 'AC01',
                                    address: '123 Ring Road, Accra',
                                    latitude: '5.60391200',
                                    longitude: '-0.18690900',
                                    contact_phone: '+233201234567'
                                },
                                destination_warehouse: {
                                    id: 2,
                                    name: 'Kumasi Hub',
                                    code: 'KM01',
                                    address: '45 Adum Street, Kumasi',
                                    latitude: '6.68702800',
                                    longitude: '-1.62460600',
                                    contact_phone: '+233209876543'
                                },
                                timeline: {
                                    assigned:   { at: '2026-02-18T09:00:00.000000Z' },
                                    dispatched: { at: '2026-02-18T09:10:00.000000Z' },
                                    arrived:    { at: null },
                                    received:   { at: null }
                                },
                                items: [
                                    {
                                        shipment_item_id: 14,
                                        shipment_number: 'PCM-2026-00014',
                                        description: 'LED TV 50-inch',
                                        tracking_code: 'TRK5PNQ13E',
                                        expected_quantity: 2,
                                        loaded_quantity: 2,
                                        received_quantity: 0,
                                        line_status: 'loaded',
                                        scan_out_count: 1,
                                        scan_in_count: 0,
                                        loaded_at: '2026-02-18T09:12:00.000000Z',
                                        received_at: null,
                                        notes: null
                                    }
                                ],
                                notes: null,
                                created_at: '2026-02-18T08:50:00.000000Z',
                                updated_at: '2026-02-18T09:15:00.000000Z'
                            }
                        }
                    },
                    '404': { success: false, message: 'Transport not found.' }
                }
            },
            {
                method: 'POST',
                url: '/api/v1/driver/transports/{manifest}/start-loading',
                name: 'Start Loading',
                description: 'Move assigned manifest into loading state.',
                auth: true,
                group: 'driver-transports',
                userType: 'driver',
                useFormInputs: true,
                urlParams: [
                    { name: 'manifest', type: 'dropdown', required: true, description: 'Select an assigned/loading manifest', source: 'transports?status[]=assigned&status[]=loading&limit=100', labelField: 'manifest_number', valueField: 'id' }
                ],
                fields: [],
                sampleBody: {},
                exampleResponses: {
                    '200': {
                        success: true,
                        message: 'Loading started.',
                        data: {
                            transport: {
                                id: 4, manifest_number: 'TM-2026-AC01-KM01-0001', status: 'loading',
                                origin_warehouse: { id: 1, name: 'Accra Main Hub', code: 'AC01', address: '123 Ring Road, Accra', latitude: '5.60391200', longitude: '-0.18690900', contact_phone: '+233201234567' },
                                destination_warehouse: { id: 2, name: 'Kumasi Hub', code: 'KM01', address: '45 Adum Street, Kumasi', latitude: '6.68702800', longitude: '-1.62460600', contact_phone: '+233209876543' },
                                timeline: { assigned: { at: '2026-02-18T09:00:00.000000Z' }, dispatched: { at: null }, arrived: { at: null }, received: { at: null } },
                                items: [
                                    { shipment_item_id: 14, shipment_number: 'PCM-2026-00014', description: 'LED TV 50-inch', tracking_code: 'TRK5PNQ13E', expected_quantity: 2, loaded_quantity: 0, received_quantity: 0, line_status: 'pending', scan_out_count: 0, scan_in_count: 0, loaded_at: null, received_at: null, notes: null }
                                ],
                                notes: null, created_at: '2026-02-18T08:50:00.000000Z', updated_at: '2026-02-18T09:00:00.000000Z'
                            }
                        }
                    },
                    '400': { success: false, message: 'Manifest is not ready for loading.' }
                }
            },
            {
                method: 'POST',
                url: '/api/v1/driver/transports/{manifest}/scan-load',
                name: 'Scan Load',
                description: 'Scan a shipment item tracking code to mark it as loaded onto the vehicle. Calling Start Loading first is not required — this endpoint works directly from <code>assigned</code> status and auto-transitions the manifest to <code>loading</code> on the first scan. Each scan increments <code>scan_out_count</code> and sets the item\'s <code>line_status</code> to <code>loaded</code>.',
                auth: true,
                group: 'driver-transports',
                userType: 'driver',
                useFormInputs: true,
                urlParams: [
                    { name: 'manifest', type: 'dropdown', required: true, description: 'Select an assigned/loading manifest', source: 'transports?status[]=assigned&status[]=loading&limit=100', labelField: 'manifest_number', valueField: 'id', onSelect: 'handleScanLoadManifestSelection' }
                ],
                fields: [
                    { name: 'tracking_code', type: 'dropdown', required: true, description: 'Select from items in the chosen manifest above', dependsOnUrlParam: 'manifest' }
                ],
                sampleBody: {},
                exampleResponses: {
                    '200': {
                        success: true,
                        message: 'Item loaded successfully.',
                        data: {
                            transport: {
                                id: 4, manifest_number: 'TM-2026-AC01-KM01-0001', status: 'loading',
                                origin_warehouse: { id: 1, name: 'Accra Main Hub', code: 'AC01', address: '123 Ring Road, Accra', latitude: '5.60391200', longitude: '-0.18690900', contact_phone: '+233201234567' },
                                destination_warehouse: { id: 2, name: 'Kumasi Hub', code: 'KM01', address: '45 Adum Street, Kumasi', latitude: '6.68702800', longitude: '-1.62460600', contact_phone: '+233209876543' },
                                timeline: { assigned: { at: '2026-02-18T09:00:00.000000Z' }, dispatched: { at: null }, arrived: { at: null }, received: { at: null } },
                                items: [
                                    { shipment_item_id: 14, shipment_number: 'PCM-2026-00014', description: 'LED TV 50-inch', tracking_code: 'TRK5PNQ13E', expected_quantity: 2, loaded_quantity: 2, received_quantity: 0, line_status: 'loaded', scan_out_count: 1, scan_in_count: 0, loaded_at: '2026-02-18T09:12:00.000000Z', received_at: null, notes: null }
                                ],
                                notes: null, created_at: '2026-02-18T08:50:00.000000Z', updated_at: '2026-02-18T09:12:00.000000Z'
                            }
                        }
                    },
                    '400': { success: false, message: 'Tracking code not found in this manifest.' }
                }
            },
            {
                method: 'POST',
                url: '/api/v1/driver/transports/{manifest}/depart',
                name: 'Depart',
                description: 'Confirm departure after all items are scanned. Transitions manifest to <code>in_transit</code>. All items must have <code>line_status: loaded</code> before this succeeds.',
                auth: true,
                group: 'driver-transports',
                userType: 'driver',
                useFormInputs: true,
                urlParams: [
                    { name: 'manifest', type: 'dropdown', required: true, description: 'Select a loading manifest', source: 'transports?status[]=loading&limit=100', labelField: 'manifest_number', valueField: 'id' }
                ],
                fields: [],
                sampleBody: {},
                exampleResponses: {
                    '200': {
                        success: true,
                        message: 'Manifest departed successfully.',
                        data: {
                            transport: {
                                id: 4, manifest_number: 'TM-2026-AC01-KM01-0001', status: 'in_transit',
                                origin_warehouse: { id: 1, name: 'Accra Main Hub', code: 'AC01', address: '123 Ring Road, Accra', latitude: '5.60391200', longitude: '-0.18690900', contact_phone: '+233201234567' },
                                destination_warehouse: { id: 2, name: 'Kumasi Hub', code: 'KM01', address: '45 Adum Street, Kumasi', latitude: '6.68702800', longitude: '-1.62460600', contact_phone: '+233209876543' },
                                timeline: { assigned: { at: '2026-02-18T09:00:00.000000Z' }, dispatched: { at: '2026-02-18T09:15:00.000000Z' }, arrived: { at: null }, received: { at: null } },
                                items: [
                                    { shipment_item_id: 14, shipment_number: 'PCM-2026-00014', description: 'LED TV 50-inch', tracking_code: 'TRK5PNQ13E', expected_quantity: 2, loaded_quantity: 2, received_quantity: 0, line_status: 'loaded', scan_out_count: 1, scan_in_count: 0, loaded_at: '2026-02-18T09:12:00.000000Z', received_at: null, notes: null }
                                ],
                                notes: null, created_at: '2026-02-18T08:50:00.000000Z', updated_at: '2026-02-18T09:15:00.000000Z'
                            }
                        }
                    },
                    '400': { success: false, message: 'All manifest items must be scanned before departure.' }
                }
            },
            {
                method: 'POST',
                url: '/api/v1/driver/transports/{manifest}/arrive',
                name: 'Arrive',
                description: 'Record arrival at the destination warehouse. Transitions manifest to <code>arrived</code>. The destination warehouse staff will then scan items in via Incoming Manifests.',
                auth: true,
                group: 'driver-transports',
                userType: 'driver',
                useFormInputs: true,
                urlParams: [
                    { name: 'manifest', type: 'dropdown', required: true, description: 'Select an in-transit manifest', source: 'transports?status[]=in_transit&limit=100', labelField: 'manifest_number', valueField: 'id' }
                ],
                fields: [],
                sampleBody: {},
                exampleResponses: {
                    '200': {
                        success: true,
                        message: 'Arrival recorded successfully.',
                        data: {
                            transport: {
                                id: 4, manifest_number: 'TM-2026-AC01-KM01-0001', status: 'arrived',
                                origin_warehouse: { id: 1, name: 'Accra Main Hub', code: 'AC01', address: '123 Ring Road, Accra', latitude: '5.60391200', longitude: '-0.18690900', contact_phone: '+233201234567' },
                                destination_warehouse: { id: 2, name: 'Kumasi Hub', code: 'KM01', address: '45 Adum Street, Kumasi', latitude: '6.68702800', longitude: '-1.62460600', contact_phone: '+233209876543' },
                                timeline: { assigned: { at: '2026-02-18T09:00:00.000000Z' }, dispatched: { at: '2026-02-18T09:15:00.000000Z' }, arrived: { at: '2026-02-18T13:40:00.000000Z' }, received: { at: null } },
                                items: [
                                    { shipment_item_id: 14, shipment_number: 'PCM-2026-00014', description: 'LED TV 50-inch', tracking_code: 'TRK5PNQ13E', expected_quantity: 2, loaded_quantity: 2, received_quantity: 0, line_status: 'loaded', scan_out_count: 1, scan_in_count: 0, loaded_at: '2026-02-18T09:12:00.000000Z', received_at: null, notes: null }
                                ],
                                notes: null, created_at: '2026-02-18T08:50:00.000000Z', updated_at: '2026-02-18T13:40:00.000000Z'
                            }
                        }
                    },
                    '400': { success: false, message: 'Manifest is not in transit.' }
                }
            },
            // ============ DRIVER DELIVERY ENDPOINTS ============
            {
                method: 'GET',
                url: '/api/v1/driver/deliveries',
                name: 'List Deliveries',
                description: 'Get delivery runs assigned to driver with stop-level summaries and pagination.',
                auth: true,
                group: 'driver-deliveries',
                userType: 'driver',
                fields: [
                    { name: 'status', queryName: 'status[]', type: 'multiselect', required: false, description: 'Run statuses (array). Allowed: draft, assigned, out_for_delivery, partially_delivered, completed, cancelled', options: ['draft', 'assigned', 'out_for_delivery', 'partially_delivered', 'completed', 'cancelled'] },
                    { name: 'search', type: 'string', required: false, description: 'Search by run number, recipient name/phone, status', example: 'DR-2026' },
                    { name: 'limit', type: 'number', required: false, description: 'Number of rows to return (max 100)', example: '15' },
                    { name: 'offset', type: 'number', required: false, description: 'Rows to skip', example: '0' },
                    { name: 'sort_by', type: 'enum', required: false, description: 'Sort field', options: ['created_at', 'updated_at', 'id', 'run_number', 'status', 'assigned_at', 'dispatched_at', 'completed_at'] },
                    { name: 'sort_order', type: 'enum', required: false, description: 'Sort direction', options: ['asc', 'desc'] }
                ],
                sampleBody: null,
                exampleResponses: {
                    '200': {
                        success: true,
                        message: 'Deliveries retrieved successfully.',
                        data: {
                            deliveries: [
                                {
                                    id: 3,
                                    run_number: 'DR-2026-AC01-0001',
                                    status: 'out_for_delivery',
                                    is_direct_delivery: false,
                                    warehouse: { id: 1, name: 'Accra Main Hub', code: 'AC01', address: '123 Ring Road, Accra', latitude: '5.60391200', longitude: '-0.18690900', contact_phone: '+233201234567' },
                                    timeline: {
                                        assigned: { at: '2026-02-18T10:00:00Z' },
                                        out_for_delivery: { at: '2026-02-18T10:30:00Z' },
                                        completed: { at: null }
                                    },
                                    stops: [
                                        {
                                            id: 9,
                                            recipient_name: 'Ama Mensah',
                                            recipient_phone: '+233241234567',
                                            status: 'pending',
                                            total_packages: 2,
                                            verification: {
                                                code_sent_at: '2026-02-18T10:30:00Z',
                                                code_expires_at: '2026-02-19T10:30:00Z',
                                                attempts: 0,
                                                max_attempts: 5,
                                                skipped: false,
                                                skip_reason: null,
                                                skipped_at: null
                                            },
                                            delivery_notes: null
                                        }
                                    ],
                                    notes: null,
                                    created_at: '2026-02-18T09:50:00.000000Z',
                                    updated_at: '2026-02-18T10:30:00.000000Z'
                                }
                            ],
                            pagination: {
                                offset: 0,
                                limit: 15,
                                total: 1,
                                has_more: false,
                                next_offset: null,
                                current_page: 1,
                                last_page: 1,
                                per_page: 15
                            }
                        }
                    },
                    '401': { success: false, message: 'Unauthenticated.' }
                }
            },
            {
                method: 'GET',
                url: '/api/v1/driver/deliveries/{run}',
                name: 'View Delivery Run',
                description: 'Get full delivery run details with grouped stops and line items.',
                auth: true,
                group: 'driver-deliveries',
                userType: 'driver',
                useFormInputs: true,
                urlParams: [
                    { name: 'run', type: 'dropdown', required: true, description: 'Select a delivery run', source: 'deliveries?limit=100', labelField: 'run_number', valueField: 'id', onSelect: 'handleDeliveryRunSelection' }
                ],
                fields: [],
                sampleBody: {},
                exampleResponses: {
                    '200': {
                        success: true,
                        message: 'Delivery run retrieved successfully.',
                        data: {
                            delivery: {
                                id: 3,
                                run_number: 'DR-2026-AC01-0001',
                                status: 'out_for_delivery',
                                warehouse: { id: 1, name: 'Accra Main Hub', code: 'AC01', address: '123 Ring Road, Accra', latitude: '5.60391200', longitude: '-0.18690900', contact_phone: '+233201234567' },
                                timeline: {
                                    assigned: { at: '2026-02-18T10:00:00.000000Z' },
                                    out_for_delivery: { at: '2026-02-18T10:30:00.000000Z' },
                                    completed: { at: null }
                                },
                                stops: [
                                    {
                                        id: 9,
                                        recipient_name: 'Ama Mensah',
                                        recipient_phone: '+233241234567',
                                        status: 'pending',
                                        total_packages: 2,
                                        location: { region: 'Greater Accra', district: 'Accra Metropolitan', town: 'Osu', latitude: '5.5558', longitude: '-0.1845', gh_post_address: 'GA-144-2020', landmark: 'Near Oxford Street' },
                                        verification: { code_sent_at: '2026-02-18T10:30:00.000000Z', code_expires_at: '2026-02-19T10:30:00.000000Z', attempts: 0, max_attempts: 5, skipped: false, skip_reason: null, skipped_at: null },
                                        timeline: { arrived: { at: null }, delivered: { at: null } },
                                        failure_reason: null,
                                        failure_notes: null,
                                        delivery_notes: null,
                                        items: [
                                            { shipment_item_id: 14, shipment_number: 'PCM-2026-00014', description: 'LED TV 50-inch', tracking_code: 'TRK5PNQ13E', expected_quantity: 1, delivered_quantity: 0, status: 'pending', notes: null, delivered_at: null }
                                        ]
                                    }
                                ],
                                notes: null,
                                created_at: '2026-02-18T09:50:00.000000Z',
                                updated_at: '2026-02-18T10:30:00.000000Z'
                            }
                        }
                    },
                    '404': { success: false, message: 'Delivery run not found.' }
                }
            },
            {
                method: 'POST',
                url: '/api/v1/driver/deliveries/{run}/stops/{stop}/arrive',
                name: 'Arrive Stop',
                description: 'Mark arrival at a recipient stop for an active delivery run.',
                auth: true,
                group: 'driver-deliveries',
                userType: 'driver',
                useFormInputs: true,
                urlParams: [
                    { name: 'run', type: 'dropdown', required: true, description: 'Select active delivery run', source: 'deliveries?status[]=out_for_delivery&status[]=partially_delivered&limit=100', labelField: 'run_number', valueField: 'id', onSelect: 'handleDeliveryRunSelection' },
                    { name: 'stop', type: 'dropdown', required: true, description: 'Select stop under chosen run', dependsOn: 'run' }
                ],
                fields: [],
                sampleBody: {},
                exampleResponses: {
                    '200': {
                        success: true,
                        message: 'Arrival at recipient stop recorded.',
                        data: {
                            delivery: {
                                id: 3, run_number: 'DR-2026-AC01-0001', status: 'out_for_delivery', is_direct_delivery: false,
                                warehouse: { id: 1, name: 'Accra Main Hub', code: 'AC01', address: '123 Ring Road, Accra', latitude: '5.60391200', longitude: '-0.18690900', contact_phone: '+233201234567' },
                                timeline: { assigned: { at: '2026-02-18T10:00:00.000000Z' }, out_for_delivery: { at: '2026-02-18T10:30:00.000000Z' }, completed: { at: null } },
                                stops: [
                                    {
                                        id: 9,
                                        recipient_name: 'Ama Mensah', recipient_phone: '+233241234567', status: 'arrived',
                                        total_packages: 2,
                                        location: { region: 'Greater Accra', district: 'Accra Metropolitan', town: 'Osu', latitude: '5.5558', longitude: '-0.1845', gh_post_address: 'GA-144-2020', landmark: 'Near Oxford Street' },
                                        verification: { code_sent_at: '2026-02-18T11:05:00.000000Z', code_expires_at: '2026-02-19T11:05:00.000000Z', attempts: 0, max_attempts: 5, skipped: false, skip_reason: null, skipped_at: null },
                                        timeline: { arrived: { at: '2026-02-18T11:05:00.000000Z' }, delivered: { at: null } },
                                        failure_reason: null, failure_notes: null, delivery_notes: null,
                                        items: [
                                            { shipment_item_id: 14, shipment_number: 'PCM-2026-00014', description: 'LED TV 50-inch', tracking_code: 'TRK5PNQ13E', expected_quantity: 1, delivered_quantity: 0, status: 'pending', notes: null, delivered_at: null }
                                        ]
                                    }
                                ],
                                notes: null, created_at: '2026-02-18T09:50:00.000000Z', updated_at: '2026-02-18T11:05:00.000000Z'
                            }
                        }
                    },
                    '400': { success: false, message: 'Delivery run is not active.' }
                }
            },
            {
                method: 'POST',
                url: '/api/v1/driver/deliveries/{run}/stops/{stop}/confirm',
                name: 'Confirm Stop Delivery',
                description: 'Confirm that items have been delivered to a recipient at this stop.',
                notes: `
<strong>How a delivery run is structured</strong><br>
A delivery run is a driver's route for the day. The run has multiple <strong>stops</strong> (one per recipient). Each stop can have one or more <strong>items</strong> (packages for that recipient). Call <code>GET /deliveries/{run}</code> first to get the full run with all stops and their items.
<br><br>
<strong>The <code>items[]</code> array</strong><br>
When confirming a stop, you must report the outcome for every item at that stop. Get the item list from <code>stop.items[]</code> in the delivery response. For each item, send:
<ul style="margin:6px 0 6px 16px;padding:0;">
  <li><code>shipment_item_id</code> — the item ID from <code>stop.items[].shipment_item_id</code></li>
  <li><code>delivered_quantity</code> — how many were actually handed to the recipient</li>
  <li><code>notes</code> — optional note for this item</li>
</ul>
Only send items belonging to the current stop, not all items in the run. In this API tester the item rows are injected automatically when you select a stop — quantities are pre-filled with the expected values.
<br><br>
<strong>What <code>delivered_quantity</code> means</strong>
<table class="docs-table" style="margin-top:6px;">
  <thead><tr><th>Value</th><th>Outcome</th></tr></thead>
  <tbody>
    <tr><td class="docs-field-name">= <code>expected_quantity</code></td><td>Item marked <strong>delivered</strong> ✓</td></tr>
    <tr><td class="docs-field-name">&lt; <code>expected_quantity</code></td><td>Item marked <strong>partial</strong> — some units missing</td></tr>
    <tr><td class="docs-field-name"><code>0</code></td><td>Item marked <strong>failed</strong> — could not deliver</td></tr>
  </tbody>
</table>
For packaged/bagged items where the driver cannot open and count, send <code>expected_quantity</code> as-is.
<br><br>
<strong>What happens after confirmation</strong>
<ul style="margin:6px 0 6px 16px;padding:0;">
  <li>All items fully delivered → stop becomes <code>delivered</code>; if this was the last stop, the run becomes <code>completed</code> and the driver is freed</li>
  <li>Any item partial or failed → stop becomes <code>failed</code>; those items return to the destination warehouse queue for re-delivery</li>
  <li>Run becomes <code>partially_delivered</code> while some stops are done and others remain</li>
</ul>
<br>
<strong>Skipping verification</strong><br>
If the recipient did not receive the SMS code, the driver can skip verification by sending <code>skip_verification=true</code> and <code>skip_reason</code> (e.g. "SMS not received"). The stop will be flagged as <strong>unverified</strong> for warehouse review. <code>proof_photo</code> is still required.
<br><br>
<strong>Request body structure (JSON equivalent)</strong><br>
<small style="color:#64748b;">The actual request uses <code>multipart/form-data</code> (to support the proof photo file upload), but the structure maps directly to this JSON shape:</small>
<pre class="docs-code-block">{
  "verification_code": "483219",
  "skip_verification": false,
  "skip_reason": null,
  "latitude": "5.6037",
  "longitude": "-0.1870",
  "proof_photo": "&lt;file&gt;",
  "items": [
    {
      "shipment_item_id": 14,
      "delivered_quantity": 1,
      "notes": "Handed to recipient directly"
    },
    {
      "shipment_item_id": 15,
      "delivered_quantity": 0,
      "notes": "Item was damaged, could not deliver"
    }
  ]
}</pre>
Each object in <code>items[]</code> corresponds to one package at this stop. The <code>shipment_item_id</code> values come from <code>stop.items[].shipment_item_id</code> in the View Delivery Run response.`,
                auth: true,
                group: 'driver-deliveries',
                userType: 'driver',
                useFormInputs: true,
                bodyType: 'formdata',
                urlParams: [
                    { name: 'run', type: 'dropdown', required: true, description: 'Select active delivery run', source: 'deliveries?status[]=out_for_delivery&status[]=partially_delivered&limit=100', labelField: 'run_number', valueField: 'id', onSelect: 'handleDeliveryRunSelection' },
                    { name: 'stop', type: 'dropdown', required: true, description: 'Select stop under chosen run', dependsOn: 'run', onSelect: 'handleDeliveryStopSelection' }
                ],
                fields: [
                    { name: 'verification_code', type: 'string', required: false, description: '6-digit code from recipient. Required unless skip_verification is true.', example: '483219' },
                    { name: 'skip_verification', type: 'enum', required: false, description: 'Skip OTP verification (e.g. SMS not received). When true, skip_reason is required and stop is flagged for warehouse review.', options: ['false', 'true'], example: 'false' },
                    { name: 'skip_reason', type: 'string', required: false, description: 'Reason for skipping verification. Required when skip_verification is true.', example: 'SMS not received by recipient' },
                    { name: 'latitude', type: 'string', required: true, description: 'Delivery GPS latitude', example: '5.6037' },
                    { name: 'longitude', type: 'string', required: true, description: 'Delivery GPS longitude', example: '-0.1870' },
                    { name: 'proof_photo', type: 'file', required: true, description: 'Delivery proof image', accept: 'image/jpeg,image/png,image/webp' },
                    { name: 'delivery_items', noticeOnly: true, type: 'string', required: false, description: 'Rows for each item at this stop are added automatically when you select a stop above. Quantities are pre-filled with expected values — only adjust if the actual delivered amount differs.' }
                ],
                sampleBody: {
                    verification_code: '483219',
                    latitude: '5.6037',
                    longitude: '-0.1870',
                },
                exampleResponses: {
                    '200': {
                        success: true,
                        message: 'Delivery stop confirmed successfully.',
                        data: {
                            delivery: {
                                id: 3, run_number: 'DR-2026-AC01-0001', status: 'completed', is_direct_delivery: false,
                                warehouse: { id: 1, name: 'Accra Main Hub', code: 'AC01', address: '123 Ring Road, Accra', latitude: '5.60391200', longitude: '-0.18690900', contact_phone: '+233201234567' },
                                timeline: { assigned: { at: '2026-02-18T10:00:00.000000Z' }, out_for_delivery: { at: '2026-02-18T10:30:00.000000Z' }, completed: { at: '2026-02-18T11:20:00.000000Z' } },
                                stops: [
                                    {
                                        id: 9,
                                        recipient_name: 'Ama Mensah', recipient_phone: '+233241234567', status: 'delivered',
                                        total_packages: 2,
                                        location: { region: 'Greater Accra', district: 'Accra Metropolitan', town: 'Osu', latitude: '5.5558', longitude: '-0.1845', gh_post_address: 'GA-144-2020', landmark: 'Near Oxford Street' },
                                        verification: { code_sent_at: '2026-02-18T11:05:00.000000Z', code_expires_at: '2026-02-19T11:05:00.000000Z', attempts: 1, max_attempts: 5, skipped: false, skip_reason: null, skipped_at: null },
                                        timeline: { arrived: { at: '2026-02-18T11:05:00.000000Z' }, delivered: { at: '2026-02-18T11:20:00.000000Z' } },
                                        failure_reason: null, failure_notes: null, delivery_notes: 'Left with security guard at gate.',
                                        items: [
                                            { shipment_item_id: 14, shipment_number: 'PCM-2026-00014', description: 'LED TV 50-inch', tracking_code: 'TRK5PNQ13E', expected_quantity: 1, delivered_quantity: 1, status: 'delivered', notes: 'Handed to recipient', delivered_at: '2026-02-18T11:20:00.000000Z' }
                                        ]
                                    }
                                ],
                                notes: null, created_at: '2026-02-18T09:50:00.000000Z', updated_at: '2026-02-18T11:20:00.000000Z'
                            }
                        }
                    },
                    '400_invalid_code': { success: false, message: 'Invalid verification code. 4 attempt(s) remaining.' },
                    '400_locked': { success: false, message: 'Verification code attempts exceeded. Ask warehouse manager to regenerate.', locked: true },
                    '422': { success: false, message: 'The proof photo field is required.' }
                }
            },
            {
                method: 'POST',
                url: '/api/v1/driver/deliveries/{run}/stops/{stop}/confirm-packages',
                name: 'Confirm Stop (Package Level)',
                description: 'Confirm delivery at package level — driver reports how many sealed packages were handed over without opening them. Use this instead of the item-level confirm endpoint.',
                notes: `
<strong>Package-level confirmation</strong><br>
The driver confirms how many sealed packages were handed over to the recipient without inspecting individual items inside. This is the recommended endpoint for the mobile app.
<br><br>
<strong>How it works</strong>
<ul style="margin:6px 0 6px 16px;padding:0;">
  <li><code>packages_delivered</code> >= <code>stop.total_packages</code> → all items marked <strong>delivered</strong></li>
  <li><code>packages_delivered</code> = 0 → all items marked <strong>failed</strong></li>
  <li><code>0 &lt; packages_delivered &lt; total_packages</code> → stop flagged as <strong>partial</strong> for warehouse review</li>
</ul>
The <code>total_packages</code> count is set by the warehouse team before dispatch (editable on the delivery run show page). It represents the number of physical sealed parcels at each stop, which may differ from the item count. It defaults to 1 if not set.
<ul style="margin:6px 0 6px 16px;padding:0;">
</ul>
<br>
<strong>Skipping verification</strong><br>
If the recipient did not receive the SMS code, send <code>skip_verification=true</code> and <code>skip_reason</code>. The stop will be flagged as unverified for warehouse review. <code>proof_photo</code> is still required.
`,
                auth: true,
                group: 'driver-deliveries',
                userType: 'driver',
                useFormInputs: true,
                bodyType: 'formdata',
                urlParams: [
                    { name: 'run', type: 'dropdown', required: true, description: 'Select active delivery run', source: 'deliveries?status[]=out_for_delivery&status[]=partially_delivered&limit=100', labelField: 'run_number', valueField: 'id', onSelect: 'handleDeliveryRunSelection' },
                    { name: 'stop', type: 'dropdown', required: true, description: 'Select stop under chosen run', dependsOn: 'run' }
                ],
                fields: [
                    { name: 'verification_code', type: 'string', required: false, description: '6-digit code from recipient. Required unless skip_verification is true.', example: '483219' },
                    { name: 'skip_verification', type: 'enum', required: false, description: 'Skip OTP verification when SMS was not received.', options: ['false', 'true'], example: 'false' },
                    { name: 'skip_reason', type: 'string', required: false, description: 'Reason for skipping verification. Required when skip_verification is true.', example: 'SMS not received by recipient' },
                    { name: 'packages_delivered', type: 'number', required: true, description: 'Number of sealed packages handed to recipient', example: '2' },
                    { name: 'latitude', type: 'string', required: true, description: 'Delivery GPS latitude', example: '5.6037' },
                    { name: 'longitude', type: 'string', required: true, description: 'Delivery GPS longitude', example: '-0.1870' },
                    { name: 'proof_photo', type: 'file', required: true, description: 'Delivery proof image', accept: 'image/jpeg,image/png,image/webp' },
                    { name: 'delivery_notes', type: 'textarea', required: false, description: 'General notes or remarks about the delivery (max 1000 chars)', example: 'Recipient was not home, left packages with security guard.' },
                ],
                sampleBody: {
                    verification_code: '483219',
                    packages_delivered: 2,
                    latitude: '5.6037',
                    longitude: '-0.1870',
                },
                exampleResponses: {
                    '200_all': { success: true, message: 'All packages delivered successfully.' },
                    '200_partial': { success: true, message: '1 of 3 packages delivered. Flagged for warehouse review.' },
                    '400_invalid_code': { success: false, message: 'Invalid verification code. 4 attempt(s) remaining.' },
                    '422': { success: false, message: 'The packages delivered field is required.' }
                }
            },
            {
                method: 'POST',
                url: '/api/v1/driver/deliveries/{run}/stops/{stop}/fail',
                name: 'Fail Stop Delivery',
                description: 'Mark stop as failed with reason/notes so items return to destination warehouse queue.',
                auth: true,
                group: 'driver-deliveries',
                userType: 'driver',
                useFormInputs: true,
                urlParams: [
                    { name: 'run', type: 'dropdown', required: true, description: 'Select active delivery run', source: 'deliveries?status[]=out_for_delivery&status[]=partially_delivered&limit=100', labelField: 'run_number', valueField: 'id', onSelect: 'handleDeliveryRunSelection' },
                    { name: 'stop', type: 'dropdown', required: true, description: 'Select stop under chosen run', dependsOn: 'run' }
                ],
                fields: [
                    { name: 'reason', type: 'string', required: true, description: 'Failure reason', example: 'recipient_unreachable' },
                    { name: 'notes', type: 'string', required: false, description: 'Additional failure notes', example: 'Phone switched off after 3 attempts' }
                ],
                sampleBody: {
                    reason: 'recipient_unreachable',
                    notes: 'Phone switched off after 3 attempts'
                },
                exampleResponses: {
                    '200': {
                        success: true,
                        message: 'Delivery stop marked as failed.',
                        data: {
                            delivery: {
                                id: 3, run_number: 'DR-2026-AC01-0001', status: 'partially_delivered', is_direct_delivery: false,
                                warehouse: { id: 1, name: 'Accra Main Hub', code: 'AC01', address: '123 Ring Road, Accra', latitude: '5.60391200', longitude: '-0.18690900', contact_phone: '+233201234567' },
                                timeline: { assigned: { at: '2026-02-18T10:00:00.000000Z' }, out_for_delivery: { at: '2026-02-18T10:30:00.000000Z' }, completed: { at: null } },
                                stops: [
                                    {
                                        id: 9,
                                        recipient_name: 'Ama Mensah', recipient_phone: '+233241234567', status: 'failed',
                                        total_packages: 2,
                                        location: { region: 'Greater Accra', district: 'Accra Metropolitan', town: 'Osu', latitude: '5.5558', longitude: '-0.1845', gh_post_address: 'GA-144-2020', landmark: 'Near Oxford Street' },
                                        verification: { code_sent_at: '2026-02-18T11:05:00.000000Z', code_expires_at: '2026-02-19T11:05:00.000000Z', attempts: 0, max_attempts: 5, skipped: false, skip_reason: null, skipped_at: null },
                                        timeline: { arrived: { at: '2026-02-18T11:05:00.000000Z' }, delivered: { at: null } },
                                        failure_reason: 'recipient_unreachable', failure_notes: 'Phone switched off after 3 attempts', delivery_notes: null,
                                        items: [
                                            { shipment_item_id: 14, shipment_number: 'PCM-2026-00014', description: 'LED TV 50-inch', tracking_code: 'TRK5PNQ13E', expected_quantity: 1, delivered_quantity: 0, status: 'failed', notes: 'Phone switched off after 3 attempts', delivered_at: '2026-02-18T11:15:00.000000Z' }
                                        ]
                                    }
                                ],
                                notes: null, created_at: '2026-02-18T09:50:00.000000Z', updated_at: '2026-02-18T11:15:00.000000Z'
                            }
                        }
                    },
                    '400': { success: false, message: 'Delivery run is not active.' }
                }
            },
            // ============ DRIVER NOTIFICATION ENDPOINTS ============
            {
                method: 'GET',
                url: '/api/v1/driver/notifications',
                name: 'List Notifications',
                description: 'Get driver notifications with filtering and pagination. Returns push notification history sent to this driver.',
                auth: true,
                group: 'driver-notifications',
                userType: 'driver',
                useFormInputs: true,
                fields: [
                    { name: 'status', type: 'enum', required: false, description: 'Filter by send status', options: ['sent', 'failed'], labels: { 'sent': 'sent — Successfully delivered', 'failed': 'failed — Delivery failed' } },
                    { name: 'type', type: 'string', required: false, description: 'Filter by notification type (e.g. driver_assigned, driver_unassigned, general)', example: 'driver_assigned' },
                    { name: 'is_read', type: 'enum', required: false, description: 'Filter by read status (boolean)', options: ['true', 'false'], labels: { 'true': 'true — Read', 'false': 'false — Unread' } },
                    { name: 'from_date', type: 'date', required: false, description: 'Start date filter (YYYY-MM-DD)', example: '2026-01-01' },
                    { name: 'to_date', type: 'date', required: false, description: 'End date filter (YYYY-MM-DD)', example: '2026-12-31' },
                    { name: 'limit', type: 'number', required: false, description: 'Results per page (1-100, default 20)', example: '20' },
                    { name: 'offset', type: 'number', required: false, description: 'Number of results to skip (default 0)', example: '0' },
                    { name: 'sort_by', type: 'enum', required: false, description: 'Sort field', options: ['id', 'type', 'status', 'created_at', 'read_at'], labels: { 'id': 'id', 'type': 'type', 'status': 'status', 'created_at': 'created_at', 'read_at': 'read_at' } },
                    { name: 'sort_order', type: 'enum', required: false, description: 'Sort direction', options: ['asc', 'desc'], labels: { 'asc': 'asc — Ascending', 'desc': 'desc — Descending' } }
                ],
                exampleResponses: {
                    '200': {
                        success: true,
                        message: 'Notifications retrieved successfully.',
                        data: {
                            notifications: [
                                {
                                    id: 5,
                                    type: 'driver_assigned',
                                    channel: 'push',
                                    title: 'New Pickup Assignment',
                                    body: 'You have been assigned to pick up shipment PCM-2026-00010.',
                                    data: { assignment_id: '3', shipment_id: '10' },
                                    status: 'sent',
                                    is_read: false,
                                    read_at: null,
                                    created_at: '2026-02-18T08:00:00.000000Z'
                                },
                                {
                                    id: 6,
                                    type: 'transport_assigned',
                                    channel: 'push',
                                    title: 'Transport Assignment',
                                    body: 'You have been assigned to transport manifest TM-2026-AC01-0001.',
                                    data: { manifest_id: '1' },
                                    status: 'sent',
                                    is_read: true,
                                    read_at: '2026-02-18T09:00:00.000000Z',
                                    created_at: '2026-02-18T08:30:00.000000Z'
                                }
                            ],
                            unread_count: 2,
                            pagination: {
                                offset: 0, limit: 20, total: 2, has_more: false,
                                next_offset: null, current_page: 1, last_page: 1, per_page: 20
                            }
                        }
                    },
                    '401': { success: false, message: 'Unauthenticated.' }
                }
            },
            {
                method: 'POST',
                url: '/api/v1/driver/notifications/{id}/read',
                name: 'Mark as Read',
                description: 'Mark a single notification as read.',
                auth: true,
                group: 'driver-notifications',
                userType: 'driver',
                useFormInputs: true,
                urlParams: [
                    { name: 'id', type: 'number', required: true, description: 'Notification ID', example: '1' }
                ],
                fields: [],
                exampleResponses: {
                    '200': {
                        success: true,
                        message: 'Notification marked as read.',
                        data: {
                            notification: {
                                id: 5,
                                type: 'driver_assigned',
                                channel: 'push',
                                title: 'New Pickup Assignment',
                                body: 'You have been assigned to pick up shipment PCM-2026-00010.',
                                data: { assignment_id: '3', shipment_id: '10' },
                                status: 'sent',
                                is_read: true,
                                read_at: '2026-02-18T11:00:00.000000Z',
                                created_at: '2026-02-18T08:00:00.000000Z'
                            }
                        }
                    },
                    '401': { success: false, message: 'Unauthenticated.' },
                    '404': { success: false, message: 'Notification not found.' }
                }
            },
            {
                method: 'POST',
                url: '/api/v1/driver/notifications/read-all',
                name: 'Mark All as Read',
                description: 'Mark all unread notifications as read for the authenticated driver.',
                auth: true,
                group: 'driver-notifications',
                userType: 'driver',
                useFormInputs: false,
                fields: [],
                exampleResponses: {
                    '200': {
                        success: true,
                        message: '3 notification(s) marked as read.',
                        data: {
                            updated_count: 3
                        }
                    },
                    '401': { success: false, message: 'Unauthenticated.' }
                }
            }
        ];

        // State
        let selectedEndpoint = null;
        let responseData = null;
        let selectedExampleStatus = null;
        let vendorProfileCache = null;

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            renderEndpoints();
            updateTokenDisplay();
        });

        // Render endpoints in sidebar
        function renderEndpoints() {
            // Clear all group containers
            const authContainer = document.getElementById('group-auth');
            const profileContainer = document.getElementById('group-profile');
            const locationContainer = document.getElementById('group-location');
            const shipmentsContainer = document.getElementById('group-shipments');
            const shipmentItemsContainer = document.getElementById('group-shipment-items');
            const driverAuthContainer = document.getElementById('group-driver-auth');
            const driverProfileContainer = document.getElementById('group-driver-profile');
            const invoicesContainer = document.getElementById('group-invoices');
            const driverAssignmentsContainer = document.getElementById('group-driver-assignments');
            const driverTransportsContainer = document.getElementById('group-driver-transports');
            const driverDeliveriesContainer = document.getElementById('group-driver-deliveries');
            const vendorNotificationsContainer = document.getElementById('group-vendor-notifications');
            const driverNotificationsContainer = document.getElementById('group-driver-notifications');
            authContainer.innerHTML = '';
            profileContainer.innerHTML = '';
            locationContainer.innerHTML = '';
            shipmentsContainer.innerHTML = '';
            shipmentItemsContainer.innerHTML = '';
            driverAuthContainer.innerHTML = '';
            driverProfileContainer.innerHTML = '';
            invoicesContainer.innerHTML = '';
            driverAssignmentsContainer.innerHTML = '';
            driverTransportsContainer.innerHTML = '';
            driverDeliveriesContainer.innerHTML = '';
            vendorNotificationsContainer.innerHTML = '';
            driverNotificationsContainer.innerHTML = '';

            endpoints.forEach((ep, index) => {
                const div = document.createElement('div');
                div.className = 'endpoint-item';
                div.setAttribute('data-index', index);
                div.onclick = () => selectEndpoint(index);

                div.innerHTML = `
                    <span class="method-badge method-${ep.method}">${ep.method}</span>
                    <span class="endpoint-name">${ep.name}</span>
                    ${ep.auth ? '<span class="auth-indicator">AUTH</span>' : ''}
                `;

                // Add to appropriate group
                if (ep.group === 'profile') {
                    profileContainer.appendChild(div);
                } else if (ep.group === 'location') {
                    locationContainer.appendChild(div);
                } else if (ep.group === 'shipments') {
                    shipmentsContainer.appendChild(div);
                } else if (ep.group === 'shipment-items') {
                    shipmentItemsContainer.appendChild(div);
                } else if (ep.group === 'driver-auth') {
                    driverAuthContainer.appendChild(div);
                } else if (ep.group === 'driver-profile') {
                    driverProfileContainer.appendChild(div);
                } else if (ep.group === 'invoices') {
                    invoicesContainer.appendChild(div);
                } else if (ep.group === 'driver-assignments') {
                    driverAssignmentsContainer.appendChild(div);
                } else if (ep.group === 'driver-transports') {
                    driverTransportsContainer.appendChild(div);
                } else if (ep.group === 'driver-deliveries') {
                    driverDeliveriesContainer.appendChild(div);
                } else if (ep.group === 'vendor-notifications') {
                    vendorNotificationsContainer.appendChild(div);
                } else if (ep.group === 'driver-notifications') {
                    driverNotificationsContainer.appendChild(div);
                } else {
                    authContainer.appendChild(div);
                }
            });
        }

        // Filter endpoints
        function filterEndpoints() {
            const query = document.getElementById('searchInput').value.toLowerCase().trim();
            const allItems = document.querySelectorAll('.endpoint-item');
            let hasVisible = false;

            allItems.forEach(item => {
                const index = parseInt(item.getAttribute('data-index'));
                const ep = endpoints[index];
                const matches = ep.name.toLowerCase().includes(query) ||
                               ep.url.toLowerCase().includes(query) ||
                               ep.method.toLowerCase().includes(query);

                item.classList.toggle('hidden', !matches);
                if (matches) hasVisible = true;
            });

            document.getElementById('noResults').classList.toggle('hidden', hasVisible);
        }

        // Toggle folder
        function toggleFolder(id) {
            const folder = document.getElementById('folder-' + id);
            const chevron = document.getElementById('chevron-' + id);

            if (folder.style.display === 'none') {
                folder.style.display = '';
                chevron.classList.add('open');
            } else {
                folder.style.display = 'none';
                chevron.classList.remove('open');
            }
        }

        // Toggle group
        function toggleGroup(id) {
            const group = document.getElementById('group-' + id);
            const chevron = document.getElementById('chevron-' + id);

            if (group.style.display === 'none') {
                group.style.display = '';
                chevron.classList.add('open');
            } else {
                group.style.display = 'none';
                chevron.classList.remove('open');
            }
        }

        // Select endpoint
        async function selectEndpoint(index) {
            selectedEndpoint = endpoints[index];

            // Update active state
            document.querySelectorAll('.endpoint-item').forEach(item => {
                item.classList.remove('active');
            });
            document.querySelector(`.endpoint-item[data-index="${index}"]`).classList.add('active');

            // Update UI - determine breadcrumb based on group
            let groupName = 'Auth';
            let folderName = 'Vendor';
            if (selectedEndpoint.group === 'profile') {
                groupName = 'Profile';
                folderName = 'Vendor';
            } else if (selectedEndpoint.group === 'location') {
                groupName = 'Location';
                folderName = 'Vendor';
            } else if (selectedEndpoint.group === 'shipments') {
                groupName = 'Shipments';
                folderName = 'Vendor';
            } else if (selectedEndpoint.group === 'shipment-items') {
                groupName = 'Shipment Items';
                folderName = 'Vendor';
            } else if (selectedEndpoint.group === 'invoices') {
                groupName = 'Invoices';
                folderName = 'Vendor';
            } else if (selectedEndpoint.group === 'driver-auth') {
                groupName = 'Auth';
                folderName = 'Driver';
            } else if (selectedEndpoint.group === 'driver-profile') {
                groupName = 'Profile';
                folderName = 'Driver';
            } else if (selectedEndpoint.group === 'driver-assignments') {
                groupName = 'Pickups';
                folderName = 'Driver';
            } else if (selectedEndpoint.group === 'driver-transports') {
                groupName = 'Transports';
                folderName = 'Driver';
            } else if (selectedEndpoint.group === 'driver-deliveries') {
                groupName = 'Deliveries';
                folderName = 'Driver';
            }
            document.getElementById('breadcrumbGroup').textContent = folderName + ' / ' + groupName;
            document.getElementById('breadcrumbEndpoint').textContent = selectedEndpoint.name;
            document.getElementById('methodDisplay').textContent = selectedEndpoint.method;
            document.getElementById('methodDisplay').className = 'method-display method-' + selectedEndpoint.method;
            document.getElementById('urlInput').value = '{{ url('') }}' + selectedEndpoint.url;
            document.getElementById('endpointDesc').innerHTML = selectedEndpoint.description;
            document.getElementById('sendBtn').disabled = false;

            // Handle params tab visibility and rendering
            const paramsTabBtn = document.getElementById('paramsTabBtn');
            const hasUrlParams = selectedEndpoint.urlParams && selectedEndpoint.urlParams.length > 0;
            const hasQueryParams = selectedEndpoint.method === 'GET'
                && selectedEndpoint.fields
                && selectedEndpoint.fields.length > 0;

            if (hasUrlParams || hasQueryParams) {
                paramsTabBtn.classList.remove('hidden');
                await renderParamsTab(selectedEndpoint);
                // Auto-switch to params tab when endpoint has request params
                switchTab('request', 'params');
            } else {
                paramsTabBtn.classList.add('hidden');
                // Switch to docs tab if no params
                switchTab('request', 'docs');
            }

            // Update body - either form inputs or JSON editor
            const formContainer = document.getElementById('formInputsContainer');
            const jsonEditor = document.getElementById('requestBody');

            if (selectedEndpoint.useFormInputs && selectedEndpoint.fields && selectedEndpoint.fields.length > 0) {
                // Render form inputs
                formContainer.classList.remove('hidden');
                jsonEditor.classList.add('hidden');
                await renderFormInputs(selectedEndpoint.fields, selectedEndpoint.sampleBody);

                if (selectedEndpoint.method === 'POST' && selectedEndpoint.url === '/api/v1/vendor/shipments') {
                    await prefillCreateShipmentFromVendorProfile();
                }
            } else {
                // Use JSON editor
                formContainer.classList.add('hidden');
                jsonEditor.classList.remove('hidden');
                jsonEditor.value = JSON.stringify(selectedEndpoint.sampleBody, null, 2);
            }

            // Load the correct token for this endpoint type (like Postman variables)
            loadCurrentToken();

            // Update docs
            renderDocs();

            // Clear response area
            clearResponse();
        }

        async function renderParamsTab(endpoint) {
            const urlParamsSection = document.getElementById('urlParamsSection');
            const queryParamsSection = document.getElementById('queryParamsSection');
            const hasUrlParams = endpoint.urlParams && endpoint.urlParams.length > 0;
            const hasQueryParams = endpoint.method === 'GET'
                && endpoint.fields
                && endpoint.fields.length > 0;

            if (hasUrlParams) {
                urlParamsSection.classList.remove('hidden');
                await renderUrlParams(endpoint.urlParams);
            } else {
                urlParamsSection.classList.add('hidden');
                document.getElementById('urlParamsContainer').innerHTML = '';
            }

            if (hasQueryParams) {
                queryParamsSection.classList.remove('hidden');
                await renderQueryParams(endpoint.fields);
            } else {
                queryParamsSection.classList.add('hidden');
                document.getElementById('queryParamsContainer').innerHTML = '';
            }
        }

        // Clear response area
        function clearResponse() {
            responseData = null;
            document.getElementById('statusCode').textContent = '-';
            document.getElementById('statusCode').className = 'status-value';
            document.getElementById('responseTime').textContent = '-';
            document.getElementById('responseContent').textContent = 'Send a request to see the response';
        }

        // Render documentation
        function renderDocs() {
            if (!selectedEndpoint) return;

            document.getElementById('docsEmpty').classList.add('hidden');
            const docsContent = document.getElementById('docsContent');
            docsContent.classList.remove('hidden');

            let html = '';

            // Overview / Notes
            if (selectedEndpoint.notes) {
                html += `
                    <div class="docs-section">
                        <div class="docs-section-title">📖 Overview</div>
                        <div class="docs-notes">${selectedEndpoint.notes}</div>
                    </div>
                `;
            }

            // Device Headers Info
            html += `
                <div class="docs-section device-headers-info">
                    <div class="docs-section-title">📱 Device Headers (Optional)</div>
                    <p class="docs-hint">Mobile apps should send these headers for activity logging:</p>
                    <table class="docs-table">
                        <tbody>
                            <tr><td class="docs-field-name">X-Device-Type</td><td>android / ios / web</td></tr>
                            <tr><td class="docs-field-name">X-Device-Name</td><td>Device model name</td></tr>
                            <tr><td class="docs-field-name">X-OS-Version</td><td>Operating system version</td></tr>
                            <tr><td class="docs-field-name">X-App-Version</td><td>App build version</td></tr>
                        </tbody>
                    </table>
                </div>
            `;

            // Request Fields
            if (selectedEndpoint.fields && selectedEndpoint.fields.length > 0) {
                html += `
                    <div class="docs-section">
                        <div class="docs-section-title">📝 Request Fields</div>
                        <table class="docs-table">
                            <thead>
                                <tr>
                                    <th>Field</th>
                                    <th>Type</th>
                                    <th>Required</th>
                                    <th>Description</th>
                                </tr>
                            </thead>
                            <tbody>
                `;

                selectedEndpoint.fields.forEach(field => {
                    html += `
                        <tr>
                            <td class="docs-field-name">${field.name}</td>
                            <td><span class="docs-type-badge">${field.type}</span></td>
                            <td>
                                <span class="${field.required ? 'docs-required' : 'docs-optional'}">${field.required ? 'Required' : 'Optional'}</span>
                            </td>
                            <td>
                                ${field.description}
                                ${field.example ? `<br><small style="color:#888">Example: <code>${field.example}</code></small>` : ''}
                            </td>
                        </tr>
                    `;
                });

                html += `
                            </tbody>
                        </table>
                    </div>
                `;
            } else {
                html += `
                    <div class="docs-section">
                        <div class="docs-hint">No request body required for this endpoint.</div>
                    </div>
                `;
            }

            // Enum Reference
            if (selectedEndpoint.enums && Object.keys(selectedEndpoint.enums).length > 0) {
                html += `<div class="docs-section"><div class="docs-section-title">📖 Field Reference</div>`;
                Object.entries(selectedEndpoint.enums).forEach(([groupName, values]) => {
                    html += `
                        <p style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.08em;color:#64748b;margin:10px 0 4px;">${groupName}</p>
                        <table class="docs-table" style="margin-bottom:8px;">
                            <thead><tr><th>Value</th><th>Meaning</th></tr></thead>
                            <tbody>
                    `;
                    values.forEach(row => {
                        html += `<tr><td class="docs-field-name"><code>${row.value}</code></td><td style="font-size:12px;color:#475569;">${row.description}</td></tr>`;
                    });
                    html += `</tbody></table>`;
                });
                html += `</div>`;
            }

            // Auth Info
            html += `
                <div class="docs-section">
                    <div class="docs-section-title">🔐 Authentication</div>
                    <div class="docs-auth-badge ${selectedEndpoint.auth ? 'auth-required' : 'auth-none'}">
                        ${selectedEndpoint.auth ? 'Bearer Token Required' : 'No Authentication Required'}
                    </div>
                </div>
            `;

            // Example Responses
            if (selectedEndpoint.exampleResponses) {
                const statuses = Object.keys(selectedEndpoint.exampleResponses);
                selectedExampleStatus = statuses[0]; // Default to first status

                html += `
                    <div class="docs-section example-responses-section">
                        <div class="docs-section-title">📋 Example Responses</div>
                        <div class="example-selector">
                            <label for="example-select">Select Response:</label>
                            <select id="example-select" class="example-select-dropdown" onchange="updateExampleResponse()">
                `;

                statuses.forEach(status => {
                    const label = getStatusLabel(status);
                    html += `<option value="${status}">${label}</option>`;
                });

                html += `
                            </select>
                        </div>
                        <div class="example-response-content">
                            <pre class="example-response-json" id="example-json"></pre>
                        </div>
                        <button class="copy-example-btn" onclick="copyExampleResponse()">Copy Response</button>
                    </div>
                `;
            }

            docsContent.innerHTML = html;

            // Update example response display
            if (selectedEndpoint.exampleResponses) {
                updateExampleResponse();
            }
        }

        function toTitleCase(value) {
            return value
                .split(' ')
                .filter(Boolean)
                .map(word => word.charAt(0).toUpperCase() + word.slice(1))
                .join(' ');
        }

        function formatStatusVariantLabel(variantRaw) {
            if (!variantRaw) return '';

            const knownLabels = {
                already: 'Already Verified',
                unverified: 'Unverified',
                inactive: 'Inactive',
                phone: 'Invalid Phone',
                single: 'Single Destination',
                per_item: 'Per Item Destination',
            };

            if (knownLabels[variantRaw]) {
                return knownLabels[variantRaw];
            }

            return toTitleCase(variantRaw.replace(/[_-]+/g, ' '));
        }

        // Get status label for example response selector
        function getStatusLabel(status) {
            const [statusCode, ...variantParts] = status.split('_');
            const statusNum = parseInt(statusCode, 10);
            const variantRaw = variantParts.join('_');
            const variantLabel = formatStatusVariantLabel(variantRaw);
            const variantSuffix = variantLabel ? ` - ${variantLabel}` : '';

            if (Number.isNaN(statusNum)) {
                return status;
            }

            if (statusNum === 401) {
                return `401 Unauthenticated${variantSuffix}`;
            }

            if (statusNum === 422) {
                return `422 Validation Error${variantSuffix}`;
            }

            if (statusNum >= 200 && statusNum < 300) {
                return `${statusCode} Success${variantSuffix}`;
            }

            if (statusNum >= 400 && statusNum < 500) {
                if (variantRaw === 'already') return `${statusCode} Already Verified`;
                if (variantRaw === 'unverified') return `${statusCode} Unverified`;
                if (variantRaw === 'inactive') return `${statusCode} Inactive`;
                if (variantRaw === 'phone') return `${statusCode} Invalid Phone`;
                return `${statusCode} Error${variantSuffix}`;
            }

            if (statusNum >= 500) {
                return `${statusCode} Server Error${variantSuffix}`;
            }

            return `${statusCode}${variantSuffix}`;
        }

        // Update example response display
        function updateExampleResponse() {
            if (!selectedEndpoint || !selectedEndpoint.exampleResponses) return;

            const select = document.getElementById('example-select');
            if (!select) return;

            selectedExampleStatus = select.value;
            const response = selectedEndpoint.exampleResponses[selectedExampleStatus];

            const jsonEl = document.getElementById('example-json');
            if (jsonEl && response) {
                jsonEl.innerHTML = '<div class="json-tree">' + renderCollapsibleJson(response) + '</div>';
            }
        }

        // Collapsible JSON rendering
        function renderCollapsibleJson(obj) {
            if (typeof obj === 'string') {
                try { obj = JSON.parse(obj); } catch { return `<pre>${escapeHtml(obj)}</pre>`; }
            }
            return renderNode(obj, null, true);
        }

        function renderNode(value, key, isLast) {
            const comma = isLast ? '' : '<span class="json-comma">,</span>';
            const keyStr = key !== null ? `<span class="json-key">"${escapeHtml(key)}"</span><span class="json-colon">: </span>` : '';
            const indent = '<span class="json-indent"></span>';

            // Primitive values
            if (value === null) {
                return `<div class="json-row">${indent}${keyStr}<span class="json-null">null</span>${comma}</div>`;
            }
            if (typeof value === 'boolean') {
                return `<div class="json-row">${indent}${keyStr}<span class="json-boolean">${value}</span>${comma}</div>`;
            }
            if (typeof value === 'number') {
                return `<div class="json-row">${indent}${keyStr}<span class="json-number">${value}</span>${comma}</div>`;
            }
            if (typeof value === 'string') {
                const escaped = escapeHtml(value);
                return `<div class="json-row">${indent}${keyStr}<span class="json-string">"${escaped}"</span>${comma}</div>`;
            }

            // Arrays and Objects
            const isArray = Array.isArray(value);
            const openBracket = isArray ? '[' : '{';
            const closeBracket = isArray ? ']' : '}';
            const count = isArray ? value.length : Object.keys(value).length;

            if (count === 0) {
                return `<div class="json-row">${indent}${keyStr}<span class="json-bracket">${openBracket}${closeBracket}</span>${comma}</div>`;
            }

            const id = 'j' + Math.random().toString(36).substr(2, 8);

            let html = `<div class="json-row">`;
            html += `<span class="json-toggle expanded" data-id="${id}" onclick="toggleJson('${id}')"></span>`;
            html += `${keyStr}<span class="json-bracket">${openBracket}</span>`;
            html += `<span class="json-info">${count} ${isArray ? (count === 1 ? 'item' : 'items') : (count === 1 ? 'key' : 'keys')}</span>`;
            html += `<span class="json-collapsed-preview" id="${id}-preview"> ... <span class="json-bracket">${closeBracket}</span></span>`;
            html += `</div>`;

            html += `<div class="json-children" id="${id}">`;
            if (isArray) {
                value.forEach((item, idx) => {
                    html += renderNode(item, null, idx === value.length - 1);
                });
            } else {
                const entries = Object.entries(value);
                entries.forEach(([k, v], idx) => {
                    html += renderNode(v, k, idx === entries.length - 1);
                });
            }
            html += `</div>`;
            html += `<div class="json-row json-close">${indent}<span class="json-bracket">${closeBracket}</span>${comma}</div>`;

            return html;
        }

        function escapeHtml(str) {
            if (typeof str !== 'string') return str;
            return str
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');
        }

        // Copy example response
        function copyExampleResponse() {
            if (!selectedEndpoint || !selectedEndpoint.exampleResponses || !selectedExampleStatus) return;

            const response = selectedEndpoint.exampleResponses[selectedExampleStatus];
            navigator.clipboard.writeText(JSON.stringify(response, null, 2)).then(() => {
                showToast('Example response copied!');
            });
        }

        // Switch tabs
        function switchTab(pane, tab) {
            // Update buttons
            const buttons = document.querySelectorAll(`.${pane}-pane .tab-btn, .request-pane .tab-btn`);
            buttons.forEach(btn => {
                btn.classList.toggle('active', btn.getAttribute('data-tab') === tab);
            });

            // Update content
            const tabs = ['docs', 'params', 'body', 'auth', 'headers'];
            tabs.forEach(t => {
                const el = document.getElementById('tab-' + t);
                if (el) el.classList.toggle('hidden', t !== tab);
            });
        }

        // Cache for loaded data sources (like regions)
        let dataSourceCache = {};

        // Render URL parameters with dropdowns
        async function renderUrlParams(urlParams) {
            const container = document.getElementById('urlParamsContainer');
            let html = '<table class="headers-table"><thead><tr><th>Parameter</th><th>Value</th></tr></thead><tbody>';

            for (const param of urlParams) {
                html += `<tr>
                    <td>
                        <span style="font-family: 'SF Mono', Monaco, monospace; color: #0066b8;">{${param.name}}</span>
                        ${param.required ? '<span style="color: #c62828; font-size: 10px; margin-left: 4px;">*</span>' : ''}
                        <br><small style="color: #888;">${param.description}</small>
                    </td>
                    <td>`;

                if (param.type === 'dropdown') {
                    const isDependent = param.dependsOn ? true : false;
                    const disableByDependency = isDependent && !param.allowWithoutParent;
                    const disabled = disableByDependency ? 'disabled' : '';
                    const initialText = disableByDependency ? `-- Select ${param.dependsOn} first --` : '-- Loading... --';

                    html += `<div style="display: flex; gap: 8px; align-items: center;">
                        <select id="url-param-${param.name}" class="form-input" style="flex: 1;" ${disabled} onchange="onUrlParamChange('${param.name}')">
                            <option value="">${initialText}</option>
                        </select>`;

                    if (param.source) {
                        html += `<button type="button" onclick="refreshDropdown('${param.name}', '${param.source}')" style="padding: 6px 10px; border: 1px solid var(--border-color); border-radius: 4px; background: #fff; cursor: pointer; font-size: 12px;" title="Refresh options">↻</button>`;
                    }

                    html += `</div>`;
                } else {
                    html += `<input type="text" id="url-param-${param.name}" class="form-input" placeholder="Enter ${param.name}" oninput="updateUrlWithParams()">`;
                }

                html += `</td></tr>`;
            }

            html += '</tbody></table>';
            container.innerHTML = html;

            // Load dropdown options asynchronously
            for (const param of urlParams) {
                if (param.type === 'dropdown' && param.source && (!param.dependsOn || param.allowWithoutParent)) {
                    await loadDropdownOptions(param);
                }
            }
        }

        // Load options for dropdown from API
        async function loadDropdownOptions(param) {
            const select = document.getElementById('url-param-' + param.name);
            if (!select) return;

            try {
                const items = await fetchSourceItems(param.source);
                populateDropdown(select, items, param);
            } catch (error) {
                console.error('Error loading dropdown options:', error);
                select.innerHTML = '<option value="">-- Error: ' + error.message + ' --</option>';
            }
        }

        function getSourceDataKey(source) {
            const baseSource = String(source || '').split('?')[0];
            if (baseSource === 'regions') return 'regions';
            if (baseSource.startsWith('shipments')) return 'shipments';
            if (baseSource.startsWith('invoices')) return 'invoices';
            if (baseSource.startsWith('pickups')) return 'pickups';
            if (baseSource.startsWith('transports')) return 'transports';
            if (baseSource.startsWith('deliveries')) return 'deliveries';
            return baseSource;
        }

        function getSourceApiUrl(source) {
            if (!source) return '';
            if (source === 'regions') {
                return '{{ url('') }}/api/v1/vendor/regions';
            }
            if (String(source).startsWith('assignments') || String(source).startsWith('pickups')) {
                return '{{ url('') }}/api/v1/driver/' + source;
            }
            if (String(source).startsWith('transports') || String(source).startsWith('deliveries')) {
                return '{{ url('') }}/api/v1/driver/' + source;
            }
            return '{{ url('') }}/api/v1/vendor/' + source;
        }

        async function fetchSourceItems(source) {
            if (!source) {
                throw new Error('Unknown source');
            }

            if (dataSourceCache[source]) {
                return dataSourceCache[source];
            }

            const apiUrl = getSourceApiUrl(source);
            if (!apiUrl) {
                throw new Error('Unknown source');
            }

            const token = getActiveToken();
            if (!token) {
                throw new Error('Please login first');
            }

            const response = await fetch(apiUrl, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'Authorization': 'Bearer ' + token
                }
            });

            const data = await response.json();
            if (!response.ok || !data.success || !data.data) {
                throw new Error(data.message || 'Error loading data');
            }

            const dataKey = getSourceDataKey(source);
            const items = data.data[dataKey] || [];
            dataSourceCache[source] = items;

            return items;
        }

        async function fetchVendorProfile() {
            if (vendorProfileCache) {
                return vendorProfileCache;
            }

            const token = getActiveToken();
            if (!token) {
                return null;
            }

            const response = await fetch('{{ url('') }}/api/v1/vendor/profile', {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'Authorization': 'Bearer ' + token
                }
            });

            const data = await response.json();
            if (!response.ok || !data.success || !data.data || !data.data.user) {
                return null;
            }

            vendorProfileCache = data.data.user;
            return vendorProfileCache;
        }

        async function prefillCreateShipmentFromVendorProfile() {
            try {
                const profile = await fetchVendorProfile();
                if (!profile) {
                    return;
                }

                const pickupName = String(profile.name || profile.business_name || '').trim();
                const pickupPhone = String(profile.phone || '').trim();

                const setIfEmpty = (fieldId, value) => {
                    if (!value) return;
                    const input = document.getElementById(fieldId);
                    if (!input) return;

                    if (String(input.value || '').trim() === '') {
                        input.value = value;
                    }
                };

                setIfEmpty('form-field-pickup_contact_name', pickupName);
                setIfEmpty('form-field-pickup_contact_phone', pickupPhone);
                setIfEmpty('form-field-pickup_contact_phone_confirm', pickupPhone);
            } catch (error) {
                console.error('Error prefilling vendor shipment fields:', error);
            }
        }

        // Populate dropdown with options
        function populateDropdown(select, items, param) {
            let listItems = Array.isArray(items) ? items : [];

            if (param && param.uniqueBy) {
                const seenKeys = new Set();
                listItems = listItems.filter(item => {
                    const keyValue = item?.[param.uniqueBy];
                    if (keyValue === undefined || keyValue === null || keyValue === '') {
                        return false;
                    }

                    const key = String(keyValue);
                    if (seenKeys.has(key)) {
                        return false;
                    }

                    seenKeys.add(key);
                    return true;
                });
            }

            let html = '<option value="">-- Select ' + param.name + ' --</option>';
            listItems.forEach(item => {
                const value = item[param.valueField];
                const label = item[param.labelField];
                html += `<option value="${value}" data-item='${JSON.stringify(item).replace(/'/g, "&apos;")}'>${label}</option>`;
            });
            select.innerHTML = html;

            // Attach onSelect handler if specified
            if (param.onSelect) {
                select.addEventListener('change', function() {
                    if (this.value && param.onSelect) {
                        const selectedOption = this.options[this.selectedIndex];
                        const itemData = selectedOption.getAttribute('data-item');
                        if (itemData) {
                            const item = JSON.parse(itemData);
                            // Call the onSelect function by name
                            if (typeof window[param.onSelect] === 'function') {
                                window[param.onSelect](item, param);
                            }
                        }
                    }
                });
            }
        }

        // Render query parameters (for GET endpoints)
        async function renderQueryParams(fields) {
            const container = document.getElementById('queryParamsContainer');
            if (!container) return;

            let html = '<table class="headers-table"><thead><tr><th>Parameter</th><th>Value</th></tr></thead><tbody>';

            fields.forEach(field => {
                html += `<tr>
                    <td>
                        <span style="font-family: 'SF Mono', Monaco, monospace; color: #0066b8;">${field.name}</span>
                        ${field.required ? '<span style="color: #c62828; font-size: 10px; margin-left: 4px;">*</span>' : ''}
                        <br><small style="color: #888;">${field.description || ''}</small>
                    </td>
                    <td>`;

                if (field.type === 'dropdown') {
                    const shouldDisable = field.dependsOn && !field.allowWithoutParent;
                    const initialText = shouldDisable
                        ? `-- Select ${field.dependsOn} first --`
                        : '-- Loading... --';

                    html += `<div style="display: flex; gap: 8px; align-items: center;">
                        <select id="query-param-${field.name}" class="form-input" ${shouldDisable ? 'disabled' : ''} onchange="onQueryParamChange('${field.name}')">
                            <option value="">${initialText}</option>
                        </select>`;

                    if (field.source) {
                        html += `<button type="button" onclick="refreshQueryDropdown('${field.name}')" style="padding: 6px 10px; border: 1px solid var(--border-color); border-radius: 4px; background: #fff; cursor: pointer; font-size: 12px;" title="Refresh options">↻</button>`;
                    }

                    html += `</div>`;
                } else if (field.type === 'multiselect' && field.options) {
                    const size = Math.min(Math.max(field.options.length, 3), 6);
                    html += `<select id="query-param-${field.name}" class="form-input" multiple size="${size}" onchange="updateUrlWithParams()">`;
                    field.options.forEach(opt => {
                        const label = field.labels && field.labels[opt] ? field.labels[opt] : opt;
                        html += `<option value="${opt}">${label}</option>`;
                    });
                    html += '</select>';
                } else if (field.type === 'enum' && field.options) {
                    html += `<select id="query-param-${field.name}" class="form-input" onchange="updateUrlWithParams()">
                        <option value="">-- Select --</option>`;
                    field.options.forEach(opt => {
                        const label = field.labels && field.labels[opt] ? field.labels[opt] : opt;
                        html += `<option value="${opt}">${label}</option>`;
                    });
                    html += '</select>';
                } else {
                    if (field.type === 'date') {
                        html += `<input
                            type="text"
                            id="query-param-${field.name}"
                            class="form-input"
                            placeholder="YYYY-MM-DD"
                            onfocus="activateDateInput(this)"
                            onblur="deactivateDateInput(this)"
                            oninput="updateUrlWithParams()"
                        >`;
                    } else {
                        const inputType = field.type === 'number' ? 'number' : 'text';
                        const placeholder = field.example ? `e.g. ${field.example}` : `Enter ${field.name}`;
                        html += `<input
                            type="${inputType}"
                            id="query-param-${field.name}"
                            class="form-input"
                            placeholder="${placeholder}"
                            oninput="updateUrlWithParams()"
                        >`;
                    }
                }

                html += `</td></tr>`;
            });

            html += '</tbody></table>';
            container.innerHTML = html;

            for (const field of fields) {
                if (field.type === 'dropdown' && field.source && (!field.dependsOn || field.allowWithoutParent)) {
                    await loadQueryDropdownOptions(field);
                }
            }

            updateUrlWithParams();
        }

        function buildQueryParamSource(field) {
            if (!field || !field.source) return null;
            if (!field.dependsOn) return field.source;

            const parentInput = document.getElementById('query-param-' + field.dependsOn);
            const parentValue = parentInput ? String(parentInput.value ?? '').trim() : '';

            if (!parentValue) {
                return field.allowWithoutParent ? field.source : null;
            }

            const queryKey = field.dependsOnQueryKey || field.dependsOn;
            const separator = field.source.includes('?') ? '&' : '?';

            return `${field.source}${separator}${encodeURIComponent(queryKey)}=${encodeURIComponent(parentValue)}`;
        }

        async function loadQueryDropdownOptions(field) {
            const select = document.getElementById('query-param-' + field.name);
            if (!select) return;

            const source = buildQueryParamSource(field);
            if (!source) {
                select.disabled = true;
                select.innerHTML = `<option value="">-- Select ${field.dependsOn} first --</option>`;
                return;
            }

            const previousValue = select.value;
            select.disabled = false;
            select.innerHTML = '<option value="">-- Loading... --</option>';

            try {
                const items = await fetchSourceItems(source);
                populateDropdown(select, items, field);

                const hasPrevious = Array.from(select.options).some(opt => opt.value === previousValue);
                if (hasPrevious) {
                    select.value = previousValue;
                } else {
                    select.value = '';
                }
            } catch (error) {
                select.innerHTML = '<option value="">-- Error: ' + error.message + ' --</option>';
            }

            updateUrlWithParams();
        }

        async function onQueryParamChange(fieldName) {
            updateUrlWithParams();

            if (!selectedEndpoint || !selectedEndpoint.fields) return;

            const dependentFields = selectedEndpoint.fields.filter(field => field.dependsOn === fieldName);
            for (const dependentField of dependentFields) {
                if (dependentField.type === 'dropdown' && dependentField.source) {
                    await loadQueryDropdownOptions(dependentField);
                }
            }
        }

        async function refreshQueryDropdown(fieldName) {
            if (!selectedEndpoint || !selectedEndpoint.fields) return;

            const field = selectedEndpoint.fields.find(f => f.name === fieldName);
            if (!field || !field.source) return;
            const baseSource = String(field.source).split('?')[0];

            Object.keys(dataSourceCache).forEach(cacheKey => {
                const cacheBaseSource = String(cacheKey).split('?')[0];
                if (cacheKey === field.source || cacheBaseSource === baseSource) {
                    delete dataSourceCache[cacheKey];
                }
            });

            await loadQueryDropdownOptions(field);
            showToast('Options refreshed');
        }

        // Refresh a specific dropdown
        async function refreshDropdown(paramName, source) {
            // Find the param config
            if (!selectedEndpoint || !selectedEndpoint.urlParams) return;
            const param = selectedEndpoint.urlParams.find(p => p.name === paramName);
            if (param) {
                const baseSource = String(source || param.source || '').split('?')[0];
                Object.keys(dataSourceCache).forEach(cacheKey => {
                    const cacheBaseSource = String(cacheKey).split('?')[0];
                    if (cacheKey === source || cacheKey === param.source || cacheBaseSource === baseSource) {
                        delete dataSourceCache[cacheKey];
                    }
                });

                const select = document.getElementById('url-param-' + paramName);
                if (select) {
                    select.innerHTML = '<option value="">-- Loading... --</option>';
                }

                if (param.dependsOn) {
                    const parentSelect = document.getElementById('url-param-' + param.dependsOn);
                    const parentValue = parentSelect ? parentSelect.value : '';
                    await loadDependentUrlDropdown(param, param.dependsOn, parentValue);
                } else {
                    await loadDropdownOptions(param);
                }

                showToast('Options refreshed');
            }
        }

        function activateDateInput(input) {
            if (!input) return;
            const currentValue = input.value;
            input.type = 'date';
            input.value = currentValue;
            if (typeof input.showPicker === 'function') {
                input.showPicker();
            }
        }

        function deactivateDateInput(input) {
            if (!input) return;
            const currentValue = input.value;
            input.type = 'text';
            input.value = currentValue;
            updateUrlWithParams();
        }

        function encodeQueryKey(key) {
            return encodeURIComponent(key)
                .replace(/%5B/gi, '[')
                .replace(/%5D/gi, ']');
        }

        // Update URL input when params change
        function updateUrlWithParams() {
            if (!selectedEndpoint) return;

            let url = selectedEndpoint.url;
            const queryPairs = [];

            if (selectedEndpoint.urlParams && selectedEndpoint.urlParams.length > 0) {
                selectedEndpoint.urlParams.forEach(param => {
                    const input = document.getElementById('url-param-' + param.name);
                    if (input && input.value) {
                        url = url.replace('{' + param.name + '}', input.value);
                    }
                });
            }

            if (selectedEndpoint.method === 'GET' && selectedEndpoint.fields && selectedEndpoint.fields.length > 0) {
                selectedEndpoint.fields.forEach(field => {
                    const input = document.getElementById('query-param-' + field.name);
                    if (!input) return;

                    const queryKey = field.queryName || (field.type === 'multiselect' ? `${field.name}[]` : field.name);

                    if (field.type === 'multiselect') {
                        const selectedValues = Array.from(input.selectedOptions || [])
                            .map(option => String(option.value ?? '').trim())
                            .filter(value => value !== '');

                        selectedValues.forEach(value => {
                            queryPairs.push(`${encodeQueryKey(queryKey)}=${encodeURIComponent(value)}`);
                        });
                        return;
                    }

                    const rawValue = input.value ?? '';
                    const value = typeof rawValue === 'string' ? rawValue.trim() : String(rawValue);
                    if (value !== '') {
                        queryPairs.push(`${encodeQueryKey(queryKey)}=${encodeURIComponent(value)}`);
                    }
                });
            }

            const queryString = queryPairs.length > 0 ? '?' + queryPairs.join('&') : '';
            document.getElementById('urlInput').value = '{{ url('') }}' + url + queryString;
        }

        function buildDependentUrlParamSource(param, parentParamName, parentValue) {
            if (!param || !param.source) return null;

            const normalizedParentValue = String(parentValue ?? '').trim();
            if (normalizedParentValue === '') {
                return param.allowWithoutParent ? param.source : null;
            }

            const queryKey = param.dependsOnQueryKey || parentParamName;
            const separator = param.source.includes('?') ? '&' : '?';
            return `${param.source}${separator}${encodeURIComponent(queryKey)}=${encodeURIComponent(normalizedParentValue)}`;
        }

        // Handle URL param dropdown change
        async function onUrlParamChange(paramName) {
            updateUrlWithParams();

            if (!selectedEndpoint || !selectedEndpoint.urlParams) return;

            const changedParam = selectedEndpoint.urlParams.find(p => p.name === paramName);
            if (!changedParam) return;

            const select = document.getElementById('url-param-' + paramName);
            const selectedValue = select ? select.value : null;

            if (changedParam.onSelect && select && selectedValue) {
                const selectedOption = select.options[select.selectedIndex];
                const itemData = selectedOption ? selectedOption.getAttribute('data-item') : null;
                if (itemData && typeof window[changedParam.onSelect] === 'function') {
                    try {
                        window[changedParam.onSelect](JSON.parse(itemData), changedParam);
                    } catch (error) {
                        console.error('Error in url param onSelect handler:', error);
                    }
                }
            }

            if (
                selectedEndpoint.group === 'driver-assignments' &&
                selectedEndpoint.name === 'Confirm Pickup Item' &&
                (paramName === 'shipment_id' || paramName === 'item')
            ) {
                if (paramName === 'shipment_id' || !selectedValue) {
                    resetPickupConfirmItemForm();
                }
            }

            if (!selectedValue) {
                // Clear dependent dropdowns
                const dependentParams = selectedEndpoint.urlParams.filter(p => p.dependsOn === paramName);
                for (const param of dependentParams) {
                    if (param.dependsOn === paramName) {
                        if (param.source && param.allowWithoutParent) {
                            await loadDependentUrlDropdown(param, paramName, '');
                        } else {
                            const dependentSelect = document.getElementById('url-param-' + param.name);
                            if (dependentSelect) {
                                dependentSelect.disabled = true;
                                dependentSelect.innerHTML = `<option value="">-- Select ${paramName} first --</option>`;
                            }
                        }
                    }
                }
                return;
            }

            // Load dependent dropdowns
            const dependentParams = selectedEndpoint.urlParams.filter(p => p.dependsOn === paramName);
            for (const dependentParam of dependentParams) {
                await loadDependentUrlDropdown(dependentParam, paramName, selectedValue);
            }
        }

        // Load dependent URL param dropdown
        async function loadDependentUrlDropdown(param, parentParamName, parentValue) {
            const select = document.getElementById('url-param-' + param.name);
            if (!select) return;

            const source = buildDependentUrlParamSource(param, parentParamName, parentValue);
            if (source) {
                select.disabled = false;
                select.innerHTML = '<option value="">-- Loading... --</option>';
                const previousValue = select.value;

                try {
                    const items = await fetchSourceItems(source);
                    populateDropdown(select, items, param);

                    const hasPrevious = Array.from(select.options).some(opt => opt.value === previousValue);
                    if (hasPrevious && previousValue) {
                        select.value = previousValue;
                    } else if (param.autoSelectFirst && select.options.length > 1) {
                        // Option index 0 is the placeholder.
                        select.value = select.options[1].value;
                    } else {
                        select.value = '';
                    }
                    updateUrlWithParams();
                } catch (error) {
                    console.error('Error loading dependent dropdown:', error);
                    select.innerHTML = '<option value="">-- Error loading --</option>';
                }

                return;
            }

            select.disabled = false;
            select.innerHTML = '<option value="">-- Loading... --</option>';

            try {
                const token = getActiveToken();
                let items = [];

                // Determine what data to fetch based on parent
                if (parentParamName === 'shipment' && param.name === 'item') {
                    // Fetch shipment details to get items
                    const response = await fetch('{{ url('') }}/api/v1/vendor/shipments/' + parentValue, {
                        headers: {
                            'Accept': 'application/json',
                            'Authorization': 'Bearer ' + token
                        }
                    });

                    if (response.ok) {
                        const data = await response.json();
                        if (data.success && data.data && data.data.shipment && data.data.shipment.items) {
                            items = data.data.shipment.items;
                        }
                    }
                } else if (parentParamName === 'shipment_id' && param.name === 'item' && selectedEndpoint && selectedEndpoint.group === 'driver-assignments') {
                    // Fetch pickup details to get shipment items for driver pickup item confirmation.
                    const response = await fetch('{{ url('') }}/api/v1/driver/pickups/' + parentValue, {
                        headers: {
                            'Accept': 'application/json',
                            'Authorization': 'Bearer ' + token
                        }
                    });

                    if (response.ok) {
                        const data = await response.json();
                        const pickupItems = data?.data?.pickup?.shipment?.items;
                        if (Array.isArray(pickupItems)) {
                            items = pickupItems.map(item => ({
                                ...item,
                                display_name: `${item.description || ('Item #' + item.id)} (qty: ${item.quantity ?? 0})`
                            }));
                        }
                    }
                } else if (parentParamName === 'item' && param.name === 'image') {
                    // Find shipment value to fetch item details
                    const shipmentSelect = document.getElementById('url-param-shipment');
                    const shipmentValue = shipmentSelect ? shipmentSelect.value : null;

                    if (shipmentValue && parentValue) {
                        const response = await fetch('{{ url('') }}/api/v1/vendor/shipments/' + shipmentValue, {
                            headers: {
                                'Accept': 'application/json',
                                'Authorization': 'Bearer ' + token
                            }
                        });

                        if (response.ok) {
                            const data = await response.json();
                            if (data.success && data.data && data.data.shipment && data.data.shipment.items) {
                                const item = data.data.shipment.items.find(i => i.id == parentValue);
                                if (item && item.images) {
                                    items = item.images;
                                }
                            }
                        }
                    }
                }

                // Populate dropdown
                if (items.length > 0) {
                    let html = '<option value="">-- Select ' + param.name + ' --</option>';
                    items.forEach(item => {
                        const value = item[param.valueField] || item.id;
                        const label = item[param.labelField] || item.name;
                        html += `<option value="${value}" data-item='${JSON.stringify(item).replace(/'/g, "&apos;")}'>${label}</option>`;
                    });
                    select.innerHTML = html;

                    // Attach onSelect handler if specified
                    if (param.onSelect) {
                        select.addEventListener('change', function() {
                            if (this.value && param.onSelect) {
                                const selectedOption = this.options[this.selectedIndex];
                                const itemData = selectedOption.getAttribute('data-item');
                                if (itemData) {
                                    const item = JSON.parse(itemData);
                                    // Call the onSelect function by name
                                    if (typeof window[param.onSelect] === 'function') {
                                        window[param.onSelect](item, param);
                                    }
                                }
                            }
                        });
                    }
                } else {
                    select.innerHTML = '<option value="">-- No ' + param.name + 's available --</option>';
                }
            } catch (error) {
                console.error('Error loading dependent dropdown:', error);
                select.innerHTML = '<option value="">-- Error loading --</option>';
            }
        }

        // Prefill form fields with item data
        function prefillItemData(item, param) {
            if (!item || !item.id) return;

            const descriptionInput = document.getElementById('form-field-description');
            if (descriptionInput) descriptionInput.value = item.description || '';

            const quantityInput = document.getElementById('form-field-quantity');
            if (quantityInput) quantityInput.value = item.quantity || '1';

            const removeImagesRow = document.getElementById('form-row-remove_image_ids[]');
            const removeImagesInput = document.getElementById('form-field-remove_image_ids[]');
            if (removeImagesInput) {
                const images = Array.isArray(item.images) ? item.images : [];
                if (images.length > 0) {
                    let optionsHtml = '';
                    images.forEach((image) => {
                        const imageSize = image.size_human || (image.size ? `${image.size} B` : 'size n/a');
                        const label = `${image.original_name || 'image'} (${imageSize})`;
                        optionsHtml += `<option value="${image.id}">${label}</option>`;
                    });
                    removeImagesInput.innerHTML = optionsHtml;
                    removeImagesInput.selectedIndex = -1;
                    if (removeImagesRow) removeImagesRow.style.display = '';
                } else {
                    removeImagesInput.innerHTML = '';
                    if (removeImagesRow) removeImagesRow.style.display = 'none';
                }
            }

            const modeInput = document.getElementById('form-field-_item_delivery_mode');
            const inferredMode = item.delivery ? 'per_item' : (modeInput ? modeInput.value || 'single' : 'single');
            if (modeInput) {
                modeInput.value = inferredMode;
                modeInput.dispatchEvent(new Event('change'));
            }

            if (inferredMode === 'per_item' && item.delivery) {
                const recipientNameInput = document.getElementById('form-field-delivery_recipient_name');
                if (recipientNameInput) recipientNameInput.value = item.delivery.recipient_name || '';

                const recipientPhoneInput = document.getElementById('form-field-delivery_recipient_phone');
                if (recipientPhoneInput) recipientPhoneInput.value = item.delivery.recipient_phone || '';

                const recipientPhoneConfirmInput = document.getElementById('form-field-delivery_recipient_phone_confirm');
                if (recipientPhoneConfirmInput) recipientPhoneConfirmInput.value = item.delivery.recipient_phone || '';

                const itemLocationMethodInput = document.getElementById('form-field-_item_delivery_location_method');
                if (itemLocationMethodInput && item.delivery.location) {
                    itemLocationMethodInput.value = item.delivery.location.type || '';
                    itemLocationMethodInput.dispatchEvent(new Event('change'));
                }

                const deliveryRegionInput = document.getElementById('form-field-delivery_region_id');
                if (deliveryRegionInput && item.delivery.location?.region_id) {
                    deliveryRegionInput.value = item.delivery.location.region_id;
                    deliveryRegionInput.dispatchEvent(new Event('change'));
                }

                setTimeout(() => {
                    const deliveryDistrictInput = document.getElementById('form-field-delivery_district_id');
                    if (deliveryDistrictInput && item.delivery.location?.district_id) {
                        deliveryDistrictInput.value = item.delivery.location.district_id;
                    }
                }, 350);

                const deliveryTownInput = document.getElementById('form-field-delivery_town');
                if (deliveryTownInput) deliveryTownInput.value = item.delivery.location?.town || '';

                const deliveryLatitudeInput = document.getElementById('form-field-delivery_latitude');
                if (deliveryLatitudeInput) deliveryLatitudeInput.value = item.delivery.location?.latitude || '';

                const deliveryLongitudeInput = document.getElementById('form-field-delivery_longitude');
                if (deliveryLongitudeInput) deliveryLongitudeInput.value = item.delivery.location?.longitude || '';

                const deliveryGhPostInput = document.getElementById('form-field-delivery_gh_post_address');
                if (deliveryGhPostInput) deliveryGhPostInput.value = item.delivery.location?.gh_post_address || '';

                const deliveryLandmarkInput = document.getElementById('form-field-delivery_landmark');
                if (deliveryLandmarkInput) deliveryLandmarkInput.value = item.delivery.location?.landmark || '';

                const deliveryInstructionsInput = document.getElementById('form-field-delivery_instructions');
                if (deliveryInstructionsInput) deliveryInstructionsInput.value = item.delivery.instructions || '';
            }

            showToast('Item data prefilled successfully');
        }

        function resetPickupConfirmItemForm() {
            const removePhotosRow = document.getElementById('form-row-remove_photo_ids[]');
            const removePhotosInput = document.getElementById('form-field-remove_photo_ids[]');
            if (removePhotosInput) {
                removePhotosInput.innerHTML = '';
                removePhotosInput.selectedIndex = -1;
            }
            if (removePhotosRow) {
                removePhotosRow.style.display = 'none';
            }

            const confirmedQtyInput = document.getElementById('form-field-confirmed_quantity');
            if (confirmedQtyInput) {
                confirmedQtyInput.value = '';
            }

            const notesInput = document.getElementById('form-field-notes');
            if (notesInput) {
                notesInput.value = '';
            }
        }

        function prefillPickupConfirmItemData(item, param) {
            if (!item || !item.id) {
                resetPickupConfirmItemForm();
                return;
            }

            const confirmation = item.pickup_confirmation || null;

            const confirmedQtyInput = document.getElementById('form-field-confirmed_quantity');
            if (confirmedQtyInput) {
                if (confirmation && confirmation.confirmed_quantity !== null && confirmation.confirmed_quantity !== undefined) {
                    confirmedQtyInput.value = String(confirmation.confirmed_quantity);
                } else if (item.quantity !== null && item.quantity !== undefined) {
                    confirmedQtyInput.value = String(item.quantity);
                } else {
                    confirmedQtyInput.value = '';
                }
            }

            const notesInput = document.getElementById('form-field-notes');
            if (notesInput) {
                notesInput.value = confirmation && confirmation.notes ? confirmation.notes : '';
            }

            const removePhotosRow = document.getElementById('form-row-remove_photo_ids[]');
            const removePhotosInput = document.getElementById('form-field-remove_photo_ids[]');
            if (!removePhotosInput) {
                return;
            }

            const photos = Array.isArray(confirmation?.photos) ? confirmation.photos : [];
            if (photos.length > 0) {
                let optionsHtml = '';
                photos.forEach((photo) => {
                    const photoSize = photo.size_human || (photo.size ? `${photo.size} B` : 'size n/a');
                    const label = `${photo.original_name || 'photo'} (${photoSize})`;
                    optionsHtml += `<option value="${photo.id}">${label}</option>`;
                });
                removePhotosInput.innerHTML = optionsHtml;
                removePhotosInput.selectedIndex = -1;
                if (removePhotosRow) {
                    removePhotosRow.style.display = '';
                }
            } else {
                removePhotosInput.innerHTML = '';
                if (removePhotosRow) {
                    removePhotosRow.style.display = 'none';
                }
            }
        }

        // Populate tracking_code dropdown when a manifest is selected on Scan Load
        window.handleScanLoadManifestSelection = function(manifest) {
            const select = document.getElementById('form-field-tracking_code');
            if (!select) return;

            const items = manifest.items || [];
            if (items.length === 0) {
                select.innerHTML = '<option value="">-- No items in manifest --</option>';
                select.disabled = true;
                return;
            }

            let html = '<option value="">-- Select tracking code --</option>';
            items.forEach(item => {
                const code = item.tracking_code || '';
                const desc = item.description ? ` — ${item.description}` : '';
                const badge = item.line_status && item.line_status !== 'pending' ? ` [${item.line_status}]` : '';
                html += `<option value="${code}">${code}${desc}${badge}</option>`;
            });
            select.innerHTML = html;
            select.disabled = false;
        };

        // Render form inputs for endpoints with useFormInputs: true
        async function renderFormInputs(fields, sampleBody) {
            const container = document.getElementById('formInputsContainer');
            const notices = [];
            let hiddenNoticeControls = '';
            let html = '<table class="headers-table"><thead><tr><th>Field</th><th>Value</th></tr></thead><tbody>';

            fields.forEach(field => {
                const value = sampleBody ? (sampleBody[field.name] || '') : '';
                const disabledAttr = (field.readOnly || field.readonly || field.disabled) ? 'disabled' : '';
                const optionLabel = field.labels && value ? (field.labels[value] || value) : (value || (field.noticeOnly ? '' : 'Not detected yet'));

                if (field.noticeOnly) {
                    const noticeTitle = field.name.replace(/_/g, ' ');
                    notices.push(`
                        <div class="form-notice" id="form-notice-${field.name}">
                            <span class="form-notice-title">${noticeTitle}</span>
                            <span id="form-notice-value-${field.name}">${optionLabel}</span><br>
                            <small>${field.description || ''}</small>
                        </div>
                    `);

                    if (field.type === 'enum' && field.options) {
                        let hiddenControl = `<select id="form-field-${field.name}" ${disabledAttr}>`;
                        hiddenControl += '<option value="">-- Select --</option>';
                        field.options.forEach(opt => {
                            const selected = opt === value ? 'selected' : '';
                            const label = field.labels && field.labels[opt] ? field.labels[opt] : opt;
                            hiddenControl += `<option value="${opt}" ${selected}>${label}</option>`;
                        });
                        hiddenControl += '</select>';
                        hiddenNoticeControls += hiddenControl;
                    } else {
                        hiddenNoticeControls += `<input type="text" id="form-field-${field.name}" value="${value}" ${disabledAttr}>`;
                    }

                    return;
                }

                // Add data attributes for conditional visibility
                const showWhenAttr = field.showWhen ? `data-show-when-field="${field.showWhen.field}" data-show-when-value="${field.showWhen.value}"` : '';
                const initiallyHidden = (field.showWhen || field.hideUntilPopulated) ? 'style="display: none;"' : '';

                html += `<tr id="form-row-${field.name}" ${showWhenAttr} ${initiallyHidden}>
                    <td>
                        <span style="font-family: 'SF Mono', Monaco, monospace; color: #0066b8;">${field.name.replace('_location_method', 'location_method')}</span>
                        ${field.required ? '<span style="color: #c62828; font-size: 10px; margin-left: 4px;">*</span>' : ''}
                        ${field.uiOnly ? '<span style="display: inline-block; margin-left: 6px; padding: 2px 6px; background: #e3f2fd; color: #1976d2; border-radius: 3px; font-size: 9px; font-weight: 600;">UI ONLY</span>' : ''}
                        <br><small style="color: #888;">${field.description}</small>
                    </td>
                    <td>`;

                if (field.type === 'enum' && field.options) {
                    // Render dropdown for enum fields with optional labels
                    html += `<select id="form-field-${field.name}" class="form-input" style="width: 100%;" ${disabledAttr}>
                        <option value="">-- Select --</option>`;
                    field.options.forEach(opt => {
                        const selected = opt === value ? 'selected' : '';
                        const label = field.labels && field.labels[opt] ? field.labels[opt] : opt;
                        html += `<option value="${opt}" ${selected}>${label}</option>`;
                    });
                    html += `</select>`;
                } else if (field.type === 'multiselect') {
                    const size = Math.min(Math.max((field.options || []).length || 3, 3), 8);
                    html += `<select id="form-field-${field.name}" class="form-input" multiple size="${size}" style="width: 100%;" ${disabledAttr}>`;
                    (field.options || []).forEach(opt => {
                        const optionValue = typeof opt === 'object' ? opt.value : opt;
                        const optionLabelText = typeof opt === 'object' ? opt.label : opt;
                        html += `<option value="${optionValue}">${optionLabelText}</option>`;
                    });
                    html += `</select>`;
                } else if (field.type === 'dropdown') {
                    // Render dropdown for API-loaded fields with refresh button
                    html += `<div style="display: flex; gap: 4px; align-items: center;">
                        <select id="form-field-${field.name}" class="form-input" style="flex: 1;" ${disabledAttr}>
                            <option value="">-- Select --</option>
                        </select>`;
                    if (!field.dependsOn && !field.dependsOnUrlParam) {
                        // Add refresh button for dropdowns that don't depend on others
                        html += `<button type="button" onclick="refreshFormDropdown('${field.name}', '${field.source}')" style="padding: 6px 10px; font-size: 13px; cursor: pointer; border: 1px solid var(--border-color); border-radius: 3px; background: #f5f5f5; color: #333;">↻</button>`;
                    }
                    html += `</div>`;
                } else if (field.type === 'location-search') {
                    // Render location typeahead search
                    const fillsJson = JSON.stringify(field.fills || {}).replace(/"/g, '&quot;');
                    html += `
                        <div id="loc-widget-${field.name}" style="position:relative;">
                            <div id="loc-chip-${field.name}" style="display:none; align-items:center; gap:6px; padding:6px 10px; background:#e8f5e9; border:1px solid #a5d6a7; border-radius:4px; margin-bottom:4px; font-size:13px;">
                                <span id="loc-chip-text-${field.name}"></span>
                                <button type="button" onclick="clearLocationSearch('${field.name}', JSON.parse(this.dataset.fills))" data-fills="${fillsJson}" style="background:none;border:none;cursor:pointer;color:#2e7d32;font-size:16px;padding:0 0 0 4px;line-height:1;font-weight:bold;">×</button>
                            </div>
                            <input type="text" id="loc-input-${field.name}" class="form-input" placeholder="Type a town or city name..."
                                   oninput="debouncedLocationSearch('${field.name}', JSON.parse(this.dataset.fills))" data-fills="${fillsJson}"
                                   autocomplete="off" style="width:100%;">
                            <div id="loc-results-${field.name}" style="display:none; position:absolute; top:100%; left:0; right:0; z-index:9999; background:#fff; border:1px solid #ccc; border-top:none; border-radius:0 0 4px 4px; max-height:220px; overflow-y:auto; box-shadow:0 4px 10px rgba(0,0,0,0.12);">
                            </div>
                        </div>`;
                } else if (field.type === 'file') {
                    // Render file input
                    const multipleAttr = field.multiple ? 'multiple' : '';
                    const acceptAttr = field.accept || 'image/jpeg,image/png,image/webp';
                    html += `<input type="file" id="form-field-${field.name}" class="form-input" ${multipleAttr} accept="${acceptAttr}" ${disabledAttr}>`;
                } else {
                    // Render text input for other fields
                    html += `<input type="text" id="form-field-${field.name}" class="form-input" value="${value}" placeholder="${field.example || ''}" ${disabledAttr}>`;
                }

                html += `</td></tr>`;
            });

            html += '</tbody></table>';
            const noticeHtml = notices.length > 0 ? notices.join('') : '';
            const hiddenControlsHtml = hiddenNoticeControls ? `<div style="display: none;">${hiddenNoticeControls}</div>` : '';
            container.innerHTML = noticeHtml + hiddenControlsHtml + html;

            // Initialize dependent dropdowns as disabled
            initializeDependentDropdowns(fields);

            // Load dropdown options for fields with source
            for (const field of fields) {
                if (field.type === 'dropdown' && field.source && !field.dependsOn) {
                    await loadFormDropdownOptions(field);
                }
            }

            // Setup change handlers for dependent dropdowns
            fields.forEach(field => {
                if (field.type === 'dropdown' && field.dependsOn) {
                    const parentSelect = document.getElementById('form-field-' + field.dependsOn);
                    if (parentSelect) {
                        parentSelect.addEventListener('change', async () => {
                            await loadDependentDistricts(field.name, parentSelect.value);
                        });
                    }
                }
            });

            // Setup conditional visibility handlers
            setupConditionalVisibility(fields);
        }

        // Setup conditional visibility for form fields
        function setupConditionalVisibility(fields) {
            const controlFieldNames = [...new Set(
                fields
                    .filter(f => f.showWhen && f.showWhen.field)
                    .map(f => f.showWhen.field)
            )];

            controlFieldNames.forEach(controlFieldName => {
                const input = document.getElementById('form-field-' + controlFieldName);
                if (input) {
                    input.addEventListener('change', () => {
                        applyConditionalVisibility(fields);
                        updateFormNoticeValue(controlFieldName, fields);
                    });
                }
            });

            fields
                .filter(field => field.noticeOnly)
                .forEach(field => {
                    updateFormNoticeValue(field.name, fields);
                });

            applyConditionalVisibility(fields);
        }

        function updateFormNoticeValue(fieldName, fields) {
            const noticeValue = document.getElementById('form-notice-value-' + fieldName);
            if (!noticeValue) {
                return;
            }

            const input = document.getElementById('form-field-' + fieldName);
            const fieldConfig = Array.isArray(fields)
                ? fields.find(field => field.name === fieldName)
                : null;

            if (!input) {
                const isStaticNotice = fieldConfig && fieldConfig.noticeOnly && !fieldConfig.autoDetect;
                noticeValue.textContent = isStaticNotice ? '' : 'Not detected yet';
                return;
            }

            let displayValue = '';
            if (fieldConfig && fieldConfig.type === 'enum' && fieldConfig.labels && input.value) {
                displayValue = fieldConfig.labels[input.value] || input.value;
            } else if (input.tagName === 'SELECT') {
                const selectedOption = input.options[input.selectedIndex];
                displayValue = selectedOption ? selectedOption.text : '';
            } else {
                displayValue = input.value || '';
            }

            const isStaticNotice = fieldConfig && fieldConfig.noticeOnly && !fieldConfig.autoDetect;
            noticeValue.textContent = displayValue || (isStaticNotice ? '' : 'Not detected yet');
        }

        function isFormFieldVisible(fieldName, visited = new Set()) {
            if (visited.has(fieldName)) {
                return true;
            }

            const row = document.getElementById('form-row-' + fieldName);
            if (!row) {
                return true;
            }

            const controlFieldName = row.getAttribute('data-show-when-field');
            const expectedValue = row.getAttribute('data-show-when-value');

            if (!controlFieldName) {
                return true;
            }

            visited.add(fieldName);
            const isControlVisible = isFormFieldVisible(controlFieldName, visited);
            visited.delete(fieldName);

            if (!isControlVisible) {
                return false;
            }

            const controlInput = document.getElementById('form-field-' + controlFieldName);
            if (!controlInput) {
                return false;
            }

            return String(controlInput.value ?? '') === String(expectedValue ?? '');
        }

        function applyConditionalVisibility(fields) {
            fields.forEach(field => {
                const row = document.getElementById('form-row-' + field.name);
                if (!row || !row.hasAttribute('data-show-when-field')) {
                    return;
                }

                row.style.display = isFormFieldVisible(field.name) ? '' : 'none';
            });
        }

        // Toggle visibility of fields based on control field value
        function toggleConditionalFields(controlFieldName, value) {
            if (!selectedEndpoint || !selectedEndpoint.fields) return;
            applyConditionalVisibility(selectedEndpoint.fields);
        }

        // Load dropdown options from API for form fields
        async function loadFormDropdownOptions(field) {
            const select = document.getElementById('form-field-' + field.name);
            if (!select) return;

            // Check cache first
            if (dataSourceCache[field.source]) {
                populateFormDropdown(select, dataSourceCache[field.source], field);
                return;
            }

            try {
                const items = await fetchSourceItems(field.source);
                populateFormDropdown(select, items, field);
            } catch (error) {
                select.innerHTML = '<option value="">-- Error: ' + error.message + ' --</option>';
            }
        }

        // Populate form dropdown with options
        function populateFormDropdown(select, items, field) {
            let html = '<option value="">-- Select --</option>';
            items.forEach(item => {
                const value = item[field.valueField];
                const label = item[field.labelField];
                html += `<option value="${value}">${label}</option>`;
            });
            select.innerHTML = html;
        }

        // Load districts based on selected region
        async function loadDependentDistricts(fieldName, regionId) {
            const select = document.getElementById('form-field-' + fieldName);
            if (!select) return;

            if (!regionId) {
                select.innerHTML = '<option value="">-- Select region first --</option>';
                select.disabled = true;
                return;
            }

            select.disabled = false;
            select.innerHTML = '<option value="">-- Loading... --</option>';

            try {
                const token = getActiveToken();

                // Get the region data from cache to find its districts
                if (dataSourceCache['regions']) {
                    const region = dataSourceCache['regions'].find(r => r.id == regionId);
                    if (region && region.districts) {
                        let html = '<option value="">-- Select --</option>';
                        region.districts.forEach(district => {
                            html += `<option value="${district.id}">${district.name}</option>`;
                        });
                        select.innerHTML = html;
                        return;
                    }
                }

                // Fallback: fetch from API if not in cache
                const response = await fetch('{{ url('') }}/api/v1/vendor/regions/' + regionId + '/districts', {
                    headers: {
                        'Accept': 'application/json',
                        'Authorization': token ? 'Bearer ' + token : ''
                    }
                });

                if (!response.ok) {
                    select.innerHTML = '<option value="">-- Error loading --</option>';
                    return;
                }

                const data = await response.json();
                const districts = data.data.districts || [];

                let html = '<option value="">-- Select --</option>';
                districts.forEach(district => {
                    html += `<option value="${district.id}">${district.name}</option>`;
                });
                select.innerHTML = html;
            } catch (error) {
                select.innerHTML = '<option value="">-- Error loading --</option>';
            }
        }

        // Initialize dependent dropdowns as disabled
        function initializeDependentDropdowns(fields) {
            fields.forEach(field => {
                if (field.type === 'dropdown' && (field.dependsOn || field.dependsOnUrlParam)) {
                    const select = document.getElementById('form-field-' + field.name);
                    if (select) {
                        select.disabled = true;
                        const dep = field.dependsOn || field.dependsOnUrlParam;
                        select.innerHTML = `<option value="">-- Select ${dep} first --</option>`;
                    }
                }
            });
        }

        // Refresh form dropdown
        async function refreshFormDropdown(fieldName, source) {
            delete dataSourceCache[source];

            const field = selectedEndpoint.fields.find(f => f.name === fieldName);
            if (field) {
                const select = document.getElementById('form-field-' + fieldName);
                if (select) {
                    select.innerHTML = '<option value="">-- Loading... --</option>';
                }
                await loadFormDropdownOptions(field);
                showToast('Options refreshed');
            }
        }

        // Prefill form fields with shipment data
        async function prefillShipmentData(shipment, param) {
            if (!shipment || !shipment.id) return;

            try {
                const token = getActiveToken();
                const response = await fetch('{{ url('') }}/api/v1/vendor/shipments/' + shipment.id, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'Authorization': 'Bearer ' + token
                    }
                });

                const data = await response.json();

                if (data.success && data.data && data.data.shipment) {
                    const shipmentData = data.data.shipment;

                    const destinationModeInput = document.getElementById('form-field-destination_mode');
                    if (destinationModeInput) {
                        destinationModeInput.value = shipmentData.destination_mode || 'single';
                        destinationModeInput.dispatchEvent(new Event('change'));
                    }

                    const pickupContactNameInput = document.getElementById('form-field-pickup_contact_name');
                    if (pickupContactNameInput) pickupContactNameInput.value = shipmentData.pickup?.contact_name || '';

                    const pickupContactPhoneInput = document.getElementById('form-field-pickup_contact_phone');
                    if (pickupContactPhoneInput) pickupContactPhoneInput.value = shipmentData.pickup?.contact_phone || '';

                    const pickupContactPhoneConfirmInput = document.getElementById('form-field-pickup_contact_phone_confirm');
                    if (pickupContactPhoneConfirmInput) pickupContactPhoneConfirmInput.value = shipmentData.pickup?.contact_phone || '';

                    const pickupLocationMethodInput = document.getElementById('form-field-_pickup_location_method');
                    if (pickupLocationMethodInput && shipmentData.pickup?.location) {
                        pickupLocationMethodInput.value = shipmentData.pickup.location.type || '';
                        pickupLocationMethodInput.dispatchEvent(new Event('change'));
                    }

                    const pickupRegionInput = document.getElementById('form-field-pickup_region_id');
                    if (pickupRegionInput && shipmentData.pickup?.location?.region_id) {
                        pickupRegionInput.value = shipmentData.pickup.location.region_id;
                        pickupRegionInput.dispatchEvent(new Event('change'));
                    }

                    setTimeout(() => {
                        const pickupDistrictInput = document.getElementById('form-field-pickup_district_id');
                        if (pickupDistrictInput && shipmentData.pickup?.location?.district_id) {
                            pickupDistrictInput.value = shipmentData.pickup.location.district_id;
                        }
                    }, 350);

                    const pickupTownInput = document.getElementById('form-field-pickup_town');
                    if (pickupTownInput) pickupTownInput.value = shipmentData.pickup?.location?.town || '';

                    const pickupLatitudeInput = document.getElementById('form-field-pickup_latitude');
                    if (pickupLatitudeInput) pickupLatitudeInput.value = shipmentData.pickup?.location?.latitude || '';

                    const pickupLongitudeInput = document.getElementById('form-field-pickup_longitude');
                    if (pickupLongitudeInput) pickupLongitudeInput.value = shipmentData.pickup?.location?.longitude || '';

                    const pickupGhPostInput = document.getElementById('form-field-pickup_gh_post_address');
                    if (pickupGhPostInput) pickupGhPostInput.value = shipmentData.pickup?.location?.gh_post_address || '';

                    const pickupLandmarkInput = document.getElementById('form-field-pickup_landmark');
                    if (pickupLandmarkInput) pickupLandmarkInput.value = shipmentData.pickup?.location?.landmark || '';

                    const pickupInstructionsInput = document.getElementById('form-field-pickup_instructions');
                    if (pickupInstructionsInput) pickupInstructionsInput.value = shipmentData.pickup?.instructions || '';

                    if (shipmentData.destination_mode === 'single' && shipmentData.delivery) {
                        const deliveryRecipientNameInput = document.getElementById('form-field-delivery_recipient_name');
                        if (deliveryRecipientNameInput) deliveryRecipientNameInput.value = shipmentData.delivery.recipient_name || '';

                        const deliveryRecipientPhoneInput = document.getElementById('form-field-delivery_recipient_phone');
                        if (deliveryRecipientPhoneInput) deliveryRecipientPhoneInput.value = shipmentData.delivery.recipient_phone || '';

                        const deliveryRecipientPhoneConfirmInput = document.getElementById('form-field-delivery_recipient_phone_confirm');
                        if (deliveryRecipientPhoneConfirmInput) deliveryRecipientPhoneConfirmInput.value = shipmentData.delivery.recipient_phone || '';

                        const deliveryLocationMethodInput = document.getElementById('form-field-_delivery_location_method');
                        if (deliveryLocationMethodInput && shipmentData.delivery.location) {
                            deliveryLocationMethodInput.value = shipmentData.delivery.location.type || '';
                            deliveryLocationMethodInput.dispatchEvent(new Event('change'));
                        }

                        const deliveryRegionInput = document.getElementById('form-field-delivery_region_id');
                        if (deliveryRegionInput && shipmentData.delivery.location?.region_id) {
                            deliveryRegionInput.value = shipmentData.delivery.location.region_id;
                            deliveryRegionInput.dispatchEvent(new Event('change'));
                        }

                        setTimeout(() => {
                            const deliveryDistrictInput = document.getElementById('form-field-delivery_district_id');
                            if (deliveryDistrictInput && shipmentData.delivery.location?.district_id) {
                                deliveryDistrictInput.value = shipmentData.delivery.location.district_id;
                            }
                        }, 350);

                        const deliveryTownInput = document.getElementById('form-field-delivery_town');
                        if (deliveryTownInput) deliveryTownInput.value = shipmentData.delivery.location?.town || '';

                        const deliveryLatitudeInput = document.getElementById('form-field-delivery_latitude');
                        if (deliveryLatitudeInput) deliveryLatitudeInput.value = shipmentData.delivery.location?.latitude || '';

                        const deliveryLongitudeInput = document.getElementById('form-field-delivery_longitude');
                        if (deliveryLongitudeInput) deliveryLongitudeInput.value = shipmentData.delivery.location?.longitude || '';

                        const deliveryGhPostInput = document.getElementById('form-field-delivery_gh_post_address');
                        if (deliveryGhPostInput) deliveryGhPostInput.value = shipmentData.delivery.location?.gh_post_address || '';

                        const deliveryLandmarkInput = document.getElementById('form-field-delivery_landmark');
                        if (deliveryLandmarkInput) deliveryLandmarkInput.value = shipmentData.delivery.location?.landmark || '';

                        const deliveryInstructionsInput = document.getElementById('form-field-delivery_instructions');
                        if (deliveryInstructionsInput) deliveryInstructionsInput.value = shipmentData.delivery.instructions || '';
                    }

                    showToast('Shipment data prefilled successfully');
                } else {
                    showToast('Error loading shipment details', 'error');
                }
            } catch (error) {
                console.error('Error prefilling shipment data:', error);
                showToast('Error loading shipment details', 'error');
            }
        }

        async function handleItemShipmentSelection(shipment, param) {
            if (!shipment || !shipment.id) return;

            try {
                const token = getActiveToken();
                const response = await fetch('{{ url('') }}/api/v1/vendor/shipments/' + shipment.id, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'Authorization': 'Bearer ' + token
                    }
                });

                const data = await response.json();
                if (!data.success || !data.data || !data.data.shipment) {
                    return;
                }

                const mode = data.data.shipment.destination_mode || 'single';
                const modeInput = document.getElementById('form-field-_item_delivery_mode');
                if (modeInput) {
                    modeInput.value = mode;
                    modeInput.dispatchEvent(new Event('change'));
                }
            } catch (error) {
                console.error('Error loading shipment mode:', error);
            }
        }

        function handleDeliveryRunSelection(run) {
            if (!run || !run.id) return;

            const stopSelect = document.getElementById('url-param-stop');
            if (!stopSelect) return;

            const stops = Array.isArray(run.stops) ? run.stops : [];
            let optionsHtml = '<option value="">-- Select stop --</option>';

            stops.forEach((stop) => {
                const label = `${stop.recipient_name || 'Recipient'} (${stop.recipient_phone || 'n/a'})`;
                optionsHtml += `<option value="${stop.id}" data-item='${JSON.stringify(stop).replace(/'/g, "&apos;")}'>${label}</option>`;
            });

            stopSelect.innerHTML = optionsHtml;
            stopSelect.disabled = stops.length === 0;
            stopSelect.value = '';
            updateUrlWithParams();
        }

        function handleDeliveryStopSelection(stop) {
            if (!stop || !stop.id) return;

            const container = document.getElementById('formInputsContainer');
            if (!container) return;
            const tbody = container.querySelector('table tbody');
            if (!tbody) return;

            // Remove previously injected item rows
            tbody.querySelectorAll('[data-dynamic-item-row]').forEach(row => row.remove());

            const items = Array.isArray(stop.items) ? stop.items : [];
            if (items.length === 0) return;

            items.forEach((item, index) => {
                const idField    = `items[${index}][shipment_item_id]`;
                const qtyField   = `items[${index}][delivered_quantity]`;
                const notesField = `items[${index}][notes]`;
                const desc = item.description || ('Item #' + item.shipment_item_id);

                const idRow = document.createElement('tr');
                idRow.setAttribute('data-dynamic-item-row', '1');
                idRow.innerHTML = `
                    <td><span style="font-family:'SF Mono',Monaco,monospace;color:#0066b8;">${idField}</span>
                        <span style="color:#c62828;font-size:10px;margin-left:4px;">*</span>
                        <br><small style="color:#888;">${desc}</small></td>
                    <td><input type="text" id="form-field-${idField}" class="form-input" value="${item.shipment_item_id}" style="background:#f5f5f5;" readonly></td>`;
                tbody.appendChild(idRow);

                const qtyRow = document.createElement('tr');
                qtyRow.setAttribute('data-dynamic-item-row', '1');
                qtyRow.innerHTML = `
                    <td><span style="font-family:'SF Mono',Monaco,monospace;color:#0066b8;">${qtyField}</span>
                        <span style="color:#c62828;font-size:10px;margin-left:4px;">*</span>
                        <br><small style="color:#888;">Delivered qty (expected: ${item.expected_quantity})</small></td>
                    <td><input type="text" id="form-field-${qtyField}" class="form-input" value="${item.expected_quantity}" placeholder="${item.expected_quantity}"></td>`;
                tbody.appendChild(qtyRow);

                const notesRow = document.createElement('tr');
                notesRow.setAttribute('data-dynamic-item-row', '1');
                notesRow.innerHTML = `
                    <td><span style="font-family:'SF Mono',Monaco,monospace;color:#0066b8;">${notesField}</span>
                        <br><small style="color:#888;">Optional notes for this item</small></td>
                    <td><input type="text" id="form-field-${notesField}" class="form-input" value="" placeholder="e.g. Handed to recipient"></td>`;
                tbody.appendChild(notesRow);
            });
        }

        // Collect form input values
        function collectFormInputs() {
            const body = {};
            const files = {};
            if (!selectedEndpoint || !selectedEndpoint.fields) return { body, files };

            selectedEndpoint.fields.forEach(field => {
                // Skip UI-only fields (not sent to API)
                if (field.uiOnly) return;

                const row = document.getElementById('form-row-' + field.name);
                if (row && row.style.display === 'none') return;

                const input = document.getElementById('form-field-' + field.name);

                if (input && field.type === 'file') {
                    // Handle file inputs
                    if (input.files && input.files.length > 0) {
                        files[field.name] = {
                            files: input.files,
                            multiple: field.multiple || false
                        };
                    }
                } else if (input && input.tagName === 'SELECT' && input.multiple) {
                    const selectedValues = Array.from(input.selectedOptions || [])
                        .map(option => String(option.value ?? '').trim())
                        .filter(value => value !== '');

                    if (selectedValues.length > 0) {
                        const normalizedFieldName = field.name.endsWith('[]')
                            ? field.name.slice(0, -2)
                            : field.name;
                        body[normalizedFieldName] = selectedValues;
                    }
                } else if (input && String(input.value ?? '').trim()) {
                    // Handle regular inputs
                    body[field.name] = input.value.trim();
                }
            });

            // Collect dynamically injected item rows (e.g. Confirm Stop Delivery)
            document.querySelectorAll('[data-dynamic-item-row] input').forEach(input => {
                const fieldName = input.id.replace('form-field-', '');
                const value = String(input.value ?? '').trim();
                if (fieldName && value && !(fieldName in body)) {
                    body[fieldName] = value;
                }
            });

            return { body, files };
        }

        // Send request
        async function sendRequest() {
            if (!selectedEndpoint) return;

            // Validate URL params are filled
            if (selectedEndpoint.urlParams && selectedEndpoint.urlParams.length > 0) {
                for (const param of selectedEndpoint.urlParams) {
                    if (!param.required) continue;
                    const input = document.getElementById('url-param-' + param.name);
                    if (!input || !input.value) {
                        showToast('Please select a ' + param.name + ' first');
                        // Switch to params tab
                        switchTab('request', 'params');
                        return;
                    }
                }
            }

            const sendBtn = document.getElementById('sendBtn');
            const sendText = document.getElementById('sendText');
            const sendSpinner = document.getElementById('sendSpinner');

            sendBtn.disabled = true;
            sendText.classList.add('hidden');
            sendSpinner.classList.remove('hidden');

            const startTime = performance.now();

            try {
                const headers = {
                    'Accept': 'application/json',
                    'X-Device-Type': document.getElementById('header-device-type').value || 'web',
                    'X-Device-Name': document.getElementById('header-device-name').value || 'API Tester',
                    'X-OS-Version': document.getElementById('header-os-version').value || 'Web Browser',
                    'X-App-Version': document.getElementById('header-app-version').value || '1.0.0'
                };

                // Use the correct token based on endpoint URL
                const token = getActiveToken();
                if (token) {
                    headers['Authorization'] = 'Bearer ' + token;
                }

                const options = {
                    method: selectedEndpoint.method,
                    headers: headers
                };

                if (selectedEndpoint.method !== 'GET') {
                    if (selectedEndpoint.bodyType === 'formdata' && selectedEndpoint.useFormInputs) {
                        // Use FormData for file uploads and multipart data
                        const formData = new FormData();
                        const { body: formInputs, files: fileInputs } = collectFormInputs();

                        // Append regular form fields
                        Object.keys(formInputs).forEach(key => {
                            const value = formInputs[key];

                            if (Array.isArray(value)) {
                                value.forEach((entry) => {
                                    formData.append(key + '[]', entry);
                                });
                                return;
                            }

                            formData.append(key, value);
                        });

                        // Append file fields
                        Object.keys(fileInputs).forEach(fieldName => {
                            const fileData = fileInputs[fieldName];
                            if (fileData.multiple) {
                                // Append multiple files with array notation
                                Array.from(fileData.files).forEach(file => {
                                    formData.append(fieldName, file);
                                });
                            } else {
                                // Append single file
                                formData.append(fieldName, fileData.files[0]);
                            }
                        });

                        options.body = formData;
                        // Don't set Content-Type header - browser will set it with boundary
                    } else if (selectedEndpoint.useFormInputs && selectedEndpoint.fields && selectedEndpoint.fields.length > 0) {
                        // Use JSON for regular requests
                        headers['Content-Type'] = 'application/json';
                        const { body: formInputs } = collectFormInputs();
                        options.body = JSON.stringify(formInputs);
                    } else {
                        // Use raw body from textarea
                        headers['Content-Type'] = 'application/json';
                        const bodyText = document.getElementById('requestBody').value;
                        if (bodyText && bodyText.trim()) {
                            options.body = bodyText;
                        }
                    }
                }

                // Use the URL from input field (with params substituted)
                const requestUrl = document.getElementById('urlInput').value;
                const response = await fetch(requestUrl, options);
                const endTime = performance.now();

                responseData = await response.json();

                // Update status
                const statusCode = document.getElementById('statusCode');
                statusCode.textContent = response.status;
                statusCode.className = 'status-value';
                if (response.status >= 200 && response.status < 300) {
                    statusCode.classList.add('status-2xx');
                } else if (response.status >= 400 && response.status < 500) {
                    statusCode.classList.add('status-4xx');
                } else if (response.status >= 500) {
                    statusCode.classList.add('status-5xx');
                }

                document.getElementById('responseTime').textContent = Math.round(endTime - startTime) + 'ms';

                // Update response body with collapsible JSON
                document.getElementById('responseContent').innerHTML = '<div class="json-tree">' + renderCollapsibleJson(responseData) + '</div>';

                // Auto-save token if received (like Postman post-response script)
                if (responseData.data && responseData.data.token) {
                    const userType = getCurrentUserType();
                    saveToken(responseData.data.token);
                    showToast(userType + '_token saved!');
                }

                // Clear token on successful logout
                if (selectedEndpoint.url.includes('/logout') && responseData.success) {
                    const userType = getCurrentUserType();
                    localStorage.removeItem('parcelman_' + userType + '_token');
                    vendorProfileCache = null;
                    document.getElementById('bearerToken').value = '';
                    updateTokenDisplay();
                    showToast(userType + '_token cleared!');
                }

            } catch (error) {
                document.getElementById('statusCode').textContent = 'Error';
                document.getElementById('statusCode').className = 'status-value status-5xx';
                document.getElementById('responseContent').textContent = 'Error: ' + error.message;
            }

            sendBtn.disabled = false;
            sendText.classList.remove('hidden');
            sendSpinner.classList.add('hidden');
        }

        // Syntax highlight JSON
        function syntaxHighlight(json) {
            json = json.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
            return json.replace(/("(\\u[a-zA-Z0-9]{4}|\\[^u]|[^\\"])*"(\s*:)?|\b(true|false|null)\b|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?)/g, function (match) {
                let cls = 'json-number';
                if (/^"/.test(match)) {
                    if (/:$/.test(match)) {
                        cls = 'json-key';
                    } else {
                        cls = 'json-string';
                    }
                } else if (/true|false/.test(match)) {
                    cls = 'json-boolean';
                } else if (/null/.test(match)) {
                    cls = 'json-null';
                }
                return '<span class="' + cls + '">' + match + '</span>';
            });
        }

        // Copy URL
        function copyUrl() {
            const url = document.getElementById('urlInput').value;
            navigator.clipboard.writeText(url).then(() => {
                showToast('URL copied!');
            });
        }

        // Copy response
        function copyResponse() {
            if (responseData) {
                navigator.clipboard.writeText(JSON.stringify(responseData, null, 2)).then(() => {
                    showToast('Response copied!');
                });
            }
        }

        // Token management - separate storage for vendor and driver (like Postman variables)
        // Detect user type from endpoint URL
        function getUserTypeFromUrl(url) {
            if (url && url.includes('/driver/')) {
                return 'driver';
            }
            return 'vendor';
        }

        function getCurrentUserType() {
            return selectedEndpoint ? getUserTypeFromUrl(selectedEndpoint.url) : 'vendor';
        }

        function getActiveToken() {
            const userType = getCurrentUserType();
            return localStorage.getItem('parcelman_' + userType + '_token');
        }

        // Load the current token based on selected endpoint
        function loadCurrentToken() {
            const token = getActiveToken();
            document.getElementById('bearerToken').value = token || '';
            updateTokenDisplay();
        }

        // Save token to the correct variable based on endpoint URL
        function saveToken(token) {
            const userType = getCurrentUserType();
            localStorage.setItem('parcelman_' + userType + '_token', token);
            // Clear data source cache when token changes (user logged in/out)
            dataSourceCache = {};
            vendorProfileCache = null;
            loadCurrentToken();
        }

        // When user manually edits the token input
        function onTokenInput() {
            const token = document.getElementById('bearerToken').value.trim();
            const userType = getCurrentUserType();
            if (token) {
                localStorage.setItem('parcelman_' + userType + '_token', token);
            } else {
                localStorage.removeItem('parcelman_' + userType + '_token');
            }
            vendorProfileCache = null;
            updateTokenDisplay();
        }

        function updateTokenDisplay() {
            const vendorToken = localStorage.getItem('parcelman_vendor_token');
            const driverToken = localStorage.getItem('parcelman_driver_token');

            // Update top bar indicators
            const vendorDot = document.getElementById('vendorTokenDot');
            const driverDot = document.getElementById('driverTokenDot');
            vendorDot.className = 'token-dot ' + (vendorToken ? 'active' : 'inactive');
            driverDot.className = 'token-dot ' + (driverToken ? 'active' : 'inactive');
        }

        // Toast notification
        function showToast(message) {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.classList.add('show');

            setTimeout(() => {
                toast.classList.remove('show');
            }, 2000);
        }

        // Global function for toggling JSON nodes (called from onclick in rendered HTML)
        function toggleJson(id) {
            const children = document.getElementById(id);
            const preview = document.getElementById(id + '-preview');
            const toggle = children?.previousElementSibling?.querySelector('.json-toggle');
            const closeRow = children?.nextElementSibling;

            if (children && toggle) {
                const isExpanded = toggle.classList.contains('expanded');

                if (isExpanded) {
                    // Collapse
                    toggle.classList.remove('expanded');
                    toggle.classList.add('collapsed');
                    children.classList.add('collapsed');
                    if (preview) preview.style.display = 'inline';
                    if (closeRow) closeRow.style.display = 'none';
                } else {
                    // Expand
                    toggle.classList.remove('collapsed');
                    toggle.classList.add('expanded');
                    children.classList.remove('collapsed');
                    if (preview) preview.style.display = 'none';
                    if (closeRow) closeRow.style.display = 'block';
                }
            }
        }

        // ============================================================
        // Location Search Typeahead (for location-search field type)
        // ============================================================
        const _locSearchTimers = {};
        const _locSearchResults = {};

        function debouncedLocationSearch(fieldName, fills) {
            clearTimeout(_locSearchTimers[fieldName]);
            _locSearchTimers[fieldName] = setTimeout(() => _doLocationSearch(fieldName, fills), 300);
        }

        async function _doLocationSearch(fieldName, fills) {
            const input = document.getElementById('loc-input-' + fieldName);
            const resultsDiv = document.getElementById('loc-results-' + fieldName);
            if (!input || !resultsDiv) return;

            const q = input.value.trim();
            if (q.length < 2) {
                resultsDiv.style.display = 'none';
                return;
            }

            const token = document.getElementById('bearerToken')?.value?.trim() || '';
            resultsDiv.innerHTML = '<div style="padding:10px;color:#888;font-size:12px;">Searching...</div>';
            resultsDiv.style.display = 'block';

            try {
                const resp = await fetch(`{{ url('') }}/api/v1/vendor/locations/search?q=${encodeURIComponent(q)}`, {
                    headers: {
                        'Accept': 'application/json',
                        'Authorization': token ? 'Bearer ' + token : ''
                    }
                });
                const data = await resp.json();
                const locations = data?.data?.locations || [];
                _locSearchResults[fieldName] = locations;

                if (locations.length === 0) {
                    resultsDiv.innerHTML = '<div style="padding:10px;color:#888;font-size:13px;">No locations found</div>';
                } else {
                    resultsDiv.innerHTML = locations.map((loc, i) => `
                        <div onclick="_selectLocationResult('${fieldName}', ${i})"
                             style="padding:8px 12px;cursor:pointer;border-bottom:1px solid #f0f0f0;"
                             onmouseover="this.style.background='#f0f7ff'"
                             onmouseout="this.style.background=''">
                            <div style="font-size:13px;font-weight:600;color:#1a1a1a;">${loc.name}</div>
                            <div style="font-size:11px;color:#666;margin-top:2px;">${loc.district.name}, ${loc.region.name}</div>
                        </div>`).join('');
                }
                resultsDiv.style.display = 'block';
            } catch (e) {
                resultsDiv.style.display = 'none';
            }
        }

        function _selectLocationResult(fieldName, index) {
            const loc = (_locSearchResults[fieldName] || [])[index];
            if (!loc) return;

            // Determine fills from data attribute
            const input = document.getElementById('loc-input-' + fieldName);
            let fills = {};
            try { fills = JSON.parse(input?.dataset?.fills || '{}'); } catch(e) {}

            // Show chip, hide input
            const chip = document.getElementById('loc-chip-' + fieldName);
            const chipText = document.getElementById('loc-chip-text-' + fieldName);
            const resultsDiv = document.getElementById('loc-results-' + fieldName);
            if (chip) chip.style.display = 'flex';
            if (chipText) chipText.textContent = loc.display;
            if (input) input.style.display = 'none';
            if (resultsDiv) resultsDiv.style.display = 'none';

            // Fill target fields
            if (fills.region_id) {
                const el = document.getElementById('form-field-' + fills.region_id);
                if (el) el.value = loc.region.id;
            }
            if (fills.district_id) {
                const el = document.getElementById('form-field-' + fills.district_id);
                if (el) el.value = loc.district.id;
            }
            if (fills.town) {
                const el = document.getElementById('form-field-' + fills.town);
                if (el) el.value = loc.name;
            }
        }

        function clearLocationSearch(fieldName, fills) {
            const chip = document.getElementById('loc-chip-' + fieldName);
            const input = document.getElementById('loc-input-' + fieldName);
            const resultsDiv = document.getElementById('loc-results-' + fieldName);
            if (chip) chip.style.display = 'none';
            if (input) { input.style.display = ''; input.value = ''; }
            if (resultsDiv) resultsDiv.style.display = 'none';
            _locSearchResults[fieldName] = [];

            if (fills && fills.region_id) {
                const el = document.getElementById('form-field-' + fills.region_id);
                if (el) el.value = '';
            }
            if (fills && fills.district_id) {
                const el = document.getElementById('form-field-' + fills.district_id);
                if (el) el.value = '';
            }
            if (fills && fills.town) {
                const el = document.getElementById('form-field-' + fills.town);
                if (el) el.value = '';
            }
        }
    </script>
</body>
</html>

