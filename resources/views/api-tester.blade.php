<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>API Tester - Parcelman Express</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
            --sidebar-bg: #252526;
            --sidebar-hover: #2a2d2e;
            --sidebar-active: #37373d;
            --header-bg: #ffffff;
            --body-bg: #f3f3f3;
            --border-color: #e0e0e0;
            --text-primary: #1e1e1e;
            --text-secondary: #616161;
            --text-muted: #9e9e9e;
            --accent: #0066b8;
            --method-get: #61affe;
            --method-post: #49cc90;
            --method-put: #fca130;
            --method-patch: #50e3c2;
            --method-delete: #f93e3e;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--body-bg);
            color: var(--text-primary);
            font-size: 13px;
            line-height: 1.5;
        }

        .container { display: flex; height: 100vh; }

        /* Sidebar */
        .sidebar {
            width: 300px;
            background: var(--sidebar-bg);
            color: #cccccc;
            display: flex;
            flex-direction: column;
            border-right: 1px solid #1e1e1e;
        }

        .sidebar-header {
            padding: 12px;
            border-bottom: 1px solid #1e1e1e;
        }

        .sidebar-title {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #888;
            margin-bottom: 10px;
        }

        .search-box input {
            width: 100%;
            padding: 8px 10px;
            background: #3c3c3c;
            border: 1px solid #3c3c3c;
            border-radius: 4px;
            color: #fff;
            font-size: 12px;
        }

        .search-box input:focus {
            outline: none;
            border-color: var(--accent);
        }

        .search-box input::placeholder { color: #888; }

        .sidebar-nav {
            flex: 1;
            overflow-y: auto;
            padding: 8px 0;
        }

        /* Folders */
        .folder-header {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 500;
            color: #e0e0e0;
        }

        .folder-header:hover { background: var(--sidebar-hover); }

        .folder-chevron {
            font-size: 10px;
            transition: transform 0.15s;
            color: #888;
        }

        .folder-chevron.open { transform: rotate(90deg); }

        /* Subfolders */
        .subfolder-header {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 5px 12px 5px 24px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 500;
            color: #ccc;
        }

        .subfolder-header:hover { background: var(--sidebar-hover); }

        /* Groups */
        .group-header {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 5px 12px 5px 24px;
            cursor: pointer;
            font-size: 12px;
            color: #aaa;
        }

        .group-header.nested {
            padding-left: 36px;
        }

        .group-header:hover { background: var(--sidebar-hover); }

        /* Endpoints */
        .endpoint-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 5px 12px 5px 48px;
            cursor: pointer;
            transition: background 0.1s;
        }

        .endpoint-item:hover { background: var(--sidebar-hover); }

        .endpoint-item.active {
            background: var(--sidebar-active);
            border-left: 2px solid var(--accent);
            padding-left: 46px;
        }

        .method-badge {
            font-size: 10px;
            font-weight: 600;
            min-width: 42px;
            text-align: center;
            padding: 2px 4px;
            border-radius: 3px;
            font-family: 'SF Mono', Monaco, monospace;
        }

        .method-GET { background: rgba(97,175,254,0.2); color: #61affe; }
        .method-POST { background: rgba(73,204,144,0.2); color: #49cc90; }
        .method-PUT { background: rgba(252,161,48,0.2); color: #fca130; }
        .method-PATCH { background: rgba(80,227,194,0.2); color: #50e3c2; }
        .method-DELETE { background: rgba(249,62,62,0.2); color: #f93e3e; }

        .endpoint-name {
            font-size: 12px;
            color: #ccc;
            flex: 1;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .auth-indicator {
            font-size: 10px;
            color: #888;
        }

        .no-results {
            padding: 20px;
            text-align: center;
            color: #666;
            font-size: 12px;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        /* Top Bar */
        .top-bar {
            background: var(--header-bg);
            border-bottom: 1px solid var(--border-color);
            padding: 8px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .breadcrumb {
            font-size: 12px;
            color: var(--text-secondary);
        }

        .breadcrumb span { color: var(--text-primary); font-weight: 500; }

        .token-indicator {
            font-size: 11px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .token-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
        }

        .token-dot.active { background: #49cc90; }
        .token-dot.inactive { background: #fca130; }

        /* URL Bar */
        .url-bar {
            background: var(--header-bg);
            padding: 12px 16px;
            border-bottom: 1px solid var(--border-color);
        }

        .url-builder {
            display: flex;
            gap: 8px;
        }

        .method-display {
            padding: 8px 12px;
            background: #f5f5f5;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            font-weight: 600;
            font-size: 12px;
            min-width: 70px;
            text-align: center;
            font-family: 'SF Mono', Monaco, monospace;
        }

        .url-input-wrapper {
            flex: 1;
            display: flex;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            overflow: hidden;
            background: #fff;
        }

        .url-input-wrapper:focus-within {
            border-color: var(--accent);
        }

        .url-input {
            flex: 1;
            padding: 8px 12px;
            border: none;
            font-family: 'SF Mono', Monaco, monospace;
            font-size: 12px;
        }

        .url-input:focus { outline: none; }

        .copy-btn {
            padding: 8px 10px;
            background: transparent;
            border: none;
            border-left: 1px solid var(--border-color);
            cursor: pointer;
            color: var(--text-muted);
            font-size: 12px;
        }

        .copy-btn:hover { background: #f5f5f5; color: var(--text-primary); }

        .send-btn {
            padding: 8px 20px;
            background: var(--accent);
            color: white;
            border: none;
            border-radius: 4px;
            font-weight: 500;
            font-size: 12px;
            cursor: pointer;
        }

        .send-btn:hover { background: #005a9e; }
        .send-btn:disabled { opacity: 0.5; cursor: not-allowed; }

        .endpoint-desc {
            font-size: 11px;
            color: var(--text-secondary);
            margin-top: 8px;
        }

        /* Split Pane */
        .split-pane {
            flex: 1;
            display: flex;
            overflow: hidden;
        }

        .request-pane, .response-pane {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .request-pane {
            border-right: 1px solid var(--border-color);
        }

        .pane-header {
            display: flex;
            background: #fafafa;
            border-bottom: 1px solid var(--border-color);
            padding: 0 12px;
        }

        .tab-btn {
            padding: 10px 16px;
            background: transparent;
            border: none;
            border-bottom: 2px solid transparent;
            font-size: 12px;
            color: var(--text-secondary);
            cursor: pointer;
        }

        .tab-btn:hover { color: var(--text-primary); }

        .tab-btn.active {
            color: var(--accent);
            border-bottom-color: var(--accent);
        }

        .pane-content {
            flex: 1;
            overflow-y: auto;
            padding: 12px;
            background: #fff;
        }

        /* Form Elements */
        .form-group { margin-bottom: 12px; }

        .form-label {
            display: block;
            font-size: 11px;
            font-weight: 500;
            color: var(--text-secondary);
            margin-bottom: 6px;
            text-transform: uppercase;
        }

        .form-input {
            width: 100%;
            padding: 8px 10px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            font-size: 12px;
            font-family: 'SF Mono', Monaco, monospace;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--accent);
        }

        .code-editor {
            width: 100%;
            min-height: 300px;
            padding: 12px;
            background: #1e1e1e;
            border: none;
            border-radius: 4px;
            color: #d4d4d4;
            font-family: 'SF Mono', Monaco, monospace;
            font-size: 12px;
            line-height: 1.5;
            resize: vertical;
        }

        .code-editor:focus { outline: 1px solid var(--accent); }

        /* Response */
        .response-status {
            display: flex;
            gap: 16px;
            padding: 8px 12px;
            background: #fafafa;
            border-bottom: 1px solid var(--border-color);
            font-size: 12px;
        }

        .status-item {
            display: flex;
            gap: 6px;
        }

        .status-label { color: var(--text-muted); }
        .status-value { font-weight: 500; }

        .status-2xx { color: #49cc90; }
        .status-4xx { color: #fca130; }
        .status-5xx { color: #f93e3e; }

        .response-body {
            flex: 1;
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 12px;
            font-family: 'SF Mono', Monaco, monospace;
            font-size: 12px;
            line-height: 1.5;
            overflow: auto;
            position: relative;
        }

        .response-body pre {
            margin: 0;
            white-space: pre-wrap;
            word-break: break-word;
        }

        /* JSON Syntax */
        .json-key { color: #9cdcfe; }
        .json-string { color: #ce9178; }
        .json-number { color: #b5cea8; }
        .json-boolean { color: #569cd6; }
        .json-null { color: #569cd6; }

        .copy-response-btn {
            position: absolute;
            top: 8px;
            right: 8px;
            padding: 4px 10px;
            background: #333;
            color: #aaa;
            border: none;
            border-radius: 3px;
            font-size: 11px;
            cursor: pointer;
        }

        .copy-response-btn:hover { background: #444; color: #fff; }

        /* Empty State */
        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px;
            color: var(--text-muted);
        }

        .empty-state-text {
            font-size: 13px;
            text-align: center;
        }

        /* Docs Section */
        .docs-section {
            margin-bottom: 20px;
            background: #fafafa;
            border: 1px solid #e9ecef;
            border-radius: 6px;
            padding: 12px;
        }

        .docs-section.device-headers-info {
            background: #f0f7ff;
            border-color: #b3d7ff;
        }

        .docs-section-title {
            font-size: 12px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 10px;
        }

        .docs-hint {
            font-size: 11px;
            color: var(--text-secondary);
            margin: 0 0 8px 0;
        }

        .docs-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }

        .docs-table th {
            text-align: left;
            padding: 8px 10px;
            background: #f5f5f5;
            border-bottom: 1px solid #e0e0e0;
            font-size: 10px;
            font-weight: 600;
            color: var(--text-secondary);
            text-transform: uppercase;
        }

        .docs-table td {
            padding: 8px 10px;
            border-bottom: 1px solid #eee;
            vertical-align: top;
        }

        .docs-table tr:last-child td { border-bottom: none; }

        .docs-field-name {
            font-family: 'SF Mono', Monaco, monospace;
            font-weight: 500;
            color: #0066b8;
            white-space: nowrap;
        }

        .docs-type-badge {
            display: inline-block;
            padding: 2px 6px;
            background: #e9ecef;
            border-radius: 3px;
            font-size: 10px;
            font-family: 'SF Mono', Monaco, monospace;
            color: #555;
        }

        .docs-required {
            color: #c62828;
            font-size: 10px;
            font-weight: 600;
        }

        .docs-optional {
            color: #888;
            font-size: 10px;
        }

        .docs-auth-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 500;
        }

        .docs-auth-badge.auth-required {
            background: #fff3e0;
            color: #e65100;
        }

        .docs-auth-badge.auth-none {
            background: #e8f5e9;
            color: #2e7d32;
        }

        /* Auth Types */
        .auth-types { display: flex; gap: 8px; margin-bottom: 16px; }

        .auth-type-btn {
            padding: 6px 14px;
            background: #f5f5f5;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            font-size: 12px;
            cursor: pointer;
        }

        .auth-type-btn.active {
            background: var(--accent);
            color: white;
            border-color: var(--accent);
        }

        .token-status {
            margin-top: 12px;
            padding: 8px 12px;
            background: #f5f5f5;
            border-radius: 4px;
            font-size: 11px;
            color: var(--text-secondary);
        }

        .token-status.success { background: #e8f5e9; color: #2e7d32; }
        .token-status.warning { background: #fff3e0; color: #e65100; }

        /* Headers Table */
        .headers-table { width: 100%; border-collapse: collapse; }

        .headers-table th, .headers-table td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        .headers-table th {
            font-size: 11px;
            font-weight: 500;
            color: var(--text-secondary);
            text-transform: uppercase;
        }

        .headers-table input {
            width: 100%;
            padding: 6px 8px;
            border: 1px solid var(--border-color);
            border-radius: 3px;
            font-family: 'SF Mono', Monaco, monospace;
            font-size: 12px;
        }

        /* Spinner */
        .spinner {
            width: 14px;
            height: 14px;
            border: 2px solid rgba(255,255,255,0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            display: inline-block;
        }

        @keyframes spin { to { transform: rotate(360deg); } }

        /* Toast */
        .toast {
            position: fixed;
            bottom: 20px;
            right: 20px;
            padding: 10px 16px;
            background: #333;
            color: white;
            border-radius: 4px;
            font-size: 12px;
            z-index: 1000;
            display: none;
        }

        .toast.show { display: block; }

        /* Scrollbar */
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: #f1f1f1; }
        ::-webkit-scrollbar-thumb { background: #ccc; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #999; }

        .sidebar ::-webkit-scrollbar-track { background: #2d2d2d; }
        .sidebar ::-webkit-scrollbar-thumb { background: #555; }

        .hidden { display: none !important; }

        /* Example Section */
        .example-section {
            margin-top: 16px;
            border: 1px solid #e9ecef;
            border-radius: 4px;
            overflow: hidden;
        }

        .example-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 8px 12px;
            background: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
        }

        .example-title {
            font-size: 11px;
            font-weight: 600;
            color: var(--text-secondary);
            text-transform: uppercase;
        }

        .example-select {
            padding: 4px 8px;
            font-size: 11px;
            border: 1px solid var(--border-color);
            border-radius: 3px;
            background: #fff;
            cursor: pointer;
        }

        .example-code {
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 10px 12px;
            font-family: 'SF Mono', Monaco, monospace;
            font-size: 11px;
            line-height: 1.4;
            overflow-x: auto;
            max-height: 250px;
        }

        .example-code pre { margin: 0; white-space: pre-wrap; }

        /* Example Responses */
        .example-responses-section {
            margin-top: 16px;
        }

        .example-selector {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 12px;
        }

        .example-selector label {
            font-size: 11px;
            color: var(--text-secondary);
        }

        .example-select-dropdown {
            padding: 6px 12px;
            font-size: 12px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            background: #fff;
            cursor: pointer;
            min-width: 180px;
        }

        .example-select-dropdown:focus {
            outline: none;
            border-color: var(--accent);
        }

        .example-response-content {
            background: #1e1e1e;
            border-radius: 4px;
            overflow: hidden;
        }

        .example-response-json {
            padding: 12px;
            margin: 0;
            color: #d4d4d4;
            font-family: 'SF Mono', Monaco, monospace;
            font-size: 11px;
            line-height: 1.5;
            max-height: 300px;
            overflow: auto;
        }

        .copy-example-btn {
            margin-top: 8px;
            padding: 6px 12px;
            background: #f5f5f5;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            font-size: 11px;
            cursor: pointer;
            color: var(--text-secondary);
        }

        .copy-example-btn:hover {
            background: #e9ecef;
            color: var(--text-primary);
        }

        .status-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: 600;
            margin-left: 8px;
        }

        .status-badge.success { background: rgba(73,204,144,0.2); color: #49cc90; }
        .status-badge.error { background: rgba(249,62,62,0.2); color: #f93e3e; }
        .status-badge.warning { background: rgba(252,161,48,0.2); color: #fca130; }

        /* Collapsible JSON Tree */
        .json-tree {
            font-family: 'SF Mono', Monaco, monospace;
            font-size: 12px;
            line-height: 1.6;
        }

        .json-row {
            display: block;
            padding: 1px 0;
            white-space: nowrap;
        }

        .json-toggle {
            cursor: pointer;
            user-select: none;
            color: #888;
            display: inline-block;
            width: 14px;
            text-align: center;
            font-size: 9px;
            margin-right: 4px;
        }

        .json-toggle:hover { color: #fff; }
        .json-toggle.collapsed::before { content: '▶'; }
        .json-toggle.expanded::before { content: '▼'; }

        .json-bracket { color: #ffd700; }
        .json-colon { color: #d4d4d4; }
        .json-comma { color: #d4d4d4; }
        .json-indent { display: inline-block; }

        .json-children {
            padding-left: 20px;
            display: block;
        }

        .json-children.collapsed { display: none; }

        .json-info {
            color: #888;
            font-size: 10px;
            margin-left: 6px;
        }

        .json-collapsed-preview {
            color: #888;
            display: none;
        }

        .json-close { display: block; }

        /* Token Indicators in Top Bar */
        .token-indicators {
            display: flex;
            gap: 16px;
        }

        .token-indicator {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 11px;
            color: var(--text-secondary);
        }

        /* Token Badges */
        .token-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 18px;
            height: 18px;
            border-radius: 3px;
            font-size: 10px;
            font-weight: 700;
        }

        .token-badge-vendor {
            background: rgba(73, 204, 144, 0.3);
            color: #2e7d32;
        }

        .token-badge-driver {
            background: rgba(97, 175, 254, 0.3);
            color: #0066b8;
        }

    </style>
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
                            <div class="docs-section">
                                <div class="docs-section-title">URL Parameters</div>
                                <p class="docs-hint">These parameters are part of the URL path</p>
                            </div>
                            <div id="urlParamsContainer">
                                <!-- URL params will be rendered here -->
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
                url: '/api/v1/auth/vendor/register',
                name: 'Register',
                description: 'Register a new vendor account. OTP will be sent to verify the phone number.',
                auth: false,
                group: 'auth',
                fields: [
                    { name: 'name', type: 'string', required: true, description: 'Vendor\'s full name', example: 'John Doe' },
                    { name: 'business_name', type: 'string', required: false, description: 'Business name (optional)', example: 'John\'s Delivery' },
                    { name: 'phone', type: 'string', required: true, description: 'Ghana phone (0244xxx or +233244xxx)', example: '+233244123456' },
                    { name: 'email', type: 'string', required: false, description: 'Email address (optional)', example: 'john@example.com' },
                    { name: 'pin', type: 'string', required: true, description: 'Exactly 4 digits', example: '1234' },
                    { name: 'confirm_pin', type: 'string', required: true, description: 'Must match pin', example: '1234' }
                ],
                sampleBody: {
                    name: 'John Doe',
                    business_name: 'John\'s Delivery',
                    phone: '+233244123456',
                    email: 'john@example.com',
                    pin: '1234',
                    confirm_pin: '1234'
                },
                exampleResponses: {
                    '200': {
                        success: true,
                        message: 'OTP sent to verify your phone.',
                        data: { expires_in: 300 }
                    },
                    '400': {
                        success: false,
                        message: 'This phone is already registered.'
                    },
                    '422': {
                        success: false,
                        message: 'The pin field must be exactly 4 digits.'
                    }
                }
            },
            {
                method: 'POST',
                url: '/api/v1/auth/vendor/verify-phone',
                name: 'Verify Phone',
                description: 'Verify phone number with OTP to complete registration.',
                auth: false,
                group: 'auth',
                fields: [
                    { name: 'phone', type: 'string', required: true, description: 'Ghana phone (0244xxx or +233244xxx)', example: '+233244123456' },
                    { name: 'otp', type: 'string', required: true, description: 'Exactly 6 digits sent via SMS', example: '123456' }
                ],
                sampleBody: {
                    phone: '+233244123456',
                    otp: '123456'
                },
                exampleResponses: {
                    '200': {
                        success: true,
                        message: 'Phone verified successfully.',
                        data: {
                            user: {
                                id: 1,
                                name: 'John Doe',
                                business_name: 'John\'s Delivery',
                                phone: '+233244123456',
                                email: 'john@example.com',
                                is_phone_verified: true,
                                is_active: true
                            },
                            token: '1|abc123xyz...'
                        }
                    },
                    '400': {
                        success: false,
                        message: 'Invalid or expired OTP.'
                    },
                    '400_already': {
                        success: false,
                        message: 'Phone is already verified.'
                    }
                }
            },
            {
                method: 'POST',
                url: '/api/v1/auth/vendor/login',
                name: 'Login',
                description: 'Login with phone number and 4-digit PIN.',
                auth: false,
                group: 'auth',
                fields: [
                    { name: 'phone', type: 'string', required: true, description: 'Ghana phone (0244xxx or +233244xxx)', example: '+233244123456' },
                    { name: 'pin', type: 'string', required: true, description: 'Exactly 4 digits', example: '1234' }
                ],
                sampleBody: {
                    phone: '+233244123456',
                    pin: '1234'
                },
                exampleResponses: {
                    '200': {
                        success: true,
                        message: 'Login successful.',
                        data: {
                            user: {
                                id: 1,
                                name: 'John Doe',
                                business_name: 'John\'s Delivery',
                                phone: '+233244123456',
                                email: 'john@example.com',
                                is_phone_verified: true,
                                is_active: true
                            },
                            token: '2|def456uvw...'
                        }
                    },
                    '400': {
                        success: false,
                        message: 'Invalid phone or PIN.'
                    },
                    '400_unverified': {
                        success: false,
                        message: 'Please verify your phone first.'
                    },
                    '400_inactive': {
                        success: false,
                        message: 'Your account has been deactivated.'
                    }
                }
            },
            {
                method: 'POST',
                url: '/api/v1/auth/vendor/forgot-pin',
                name: 'Forgot PIN',
                description: 'Request OTP to reset PIN. Same response is returned whether phone exists or not (security).',
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
                        message: 'If this phone is registered, an OTP has been sent.',
                        data: { expires_in: 300 }
                    }
                }
            },
            {
                method: 'POST',
                url: '/api/v1/auth/vendor/reset-pin',
                name: 'Reset PIN',
                description: 'Reset PIN using OTP received via SMS.',
                auth: false,
                group: 'auth',
                fields: [
                    { name: 'phone', type: 'string', required: true, description: 'Ghana phone (0244xxx or +233244xxx)', example: '+233244123456' },
                    { name: 'otp', type: 'string', required: true, description: 'Exactly 6 digits sent via SMS', example: '123456' },
                    { name: 'new_pin', type: 'string', required: true, description: 'New PIN (exactly 4 digits)', example: '5678' },
                    { name: 'confirm_pin', type: 'string', required: true, description: 'Must match new_pin', example: '5678' }
                ],
                sampleBody: {
                    phone: '+233244123456',
                    otp: '123456',
                    new_pin: '5678',
                    confirm_pin: '5678'
                },
                exampleResponses: {
                    '200': {
                        success: true,
                        message: 'PIN reset successful.',
                        data: {
                            user: {
                                id: 1,
                                name: 'John Doe',
                                business_name: 'John\'s Delivery',
                                phone: '+233244123456',
                                email: 'john@example.com',
                                is_phone_verified: true,
                                is_active: true
                            },
                            token: '3|ghi789rst...'
                        }
                    },
                    '400': {
                        success: false,
                        message: 'Invalid or expired OTP.'
                    },
                    '400_phone': {
                        success: false,
                        message: 'Invalid phone number.'
                    },
                    '422': {
                        success: false,
                        message: 'The new pin field must be exactly 4 digits.'
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
                                email: 'john@example.com',
                                is_phone_verified: true,
                                is_active: true
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
                                email: 'newemail@example.com',
                                is_phone_verified: true,
                                is_active: true
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
                url: '/api/v1/vendor/request-pin-change',
                name: 'Request PIN Change',
                description: 'Request OTP to change PIN. OTP will be sent to the vendor\'s registered phone number.',
                auth: true,
                group: 'profile',
                fields: [],
                sampleBody: {},
                exampleResponses: {
                    '200': {
                        success: true,
                        message: 'OTP sent to your phone.',
                        data: { expires_in: 300 }
                    },
                    '401': {
                        success: false,
                        message: 'Unauthenticated.'
                    }
                }
            },
            {
                method: 'PUT',
                url: '/api/v1/vendor/change-pin',
                name: 'Change PIN',
                description: 'Change PIN with OTP verification. Request OTP first using "Request PIN Change" endpoint. All other sessions will be logged out.',
                auth: true,
                group: 'profile',
                fields: [
                    { name: 'otp', type: 'string', required: true, description: '6-digit OTP sent to phone', example: '123456' },
                    { name: 'new_pin', type: 'string', required: true, description: 'New 4-digit PIN', example: '5678' },
                    { name: 'confirm_pin', type: 'string', required: true, description: 'Must match new_pin', example: '5678' }
                ],
                sampleBody: {
                    otp: '123456',
                    new_pin: '5678',
                    confirm_pin: '5678'
                },
                exampleResponses: {
                    '200': {
                        success: true,
                        message: 'PIN changed successfully.',
                        data: {
                            user: {
                                id: 1,
                                name: 'John Doe',
                                business_name: 'John\'s Delivery',
                                phone: '+233244123456',
                                email: 'john@example.com',
                                is_phone_verified: true,
                                is_active: true
                            },
                            token: '4|newtoken123...'
                        }
                    },
                    '401': {
                        success: false,
                        message: 'Unauthenticated.'
                    },
                    '422_otp': {
                        success: false,
                        message: 'Invalid or expired OTP.'
                    },
                    '422_validation': {
                        success: false,
                        message: 'The new pin field must be exactly 4 digits.'
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
                    { name: 'password', type: 'string', required: true, description: 'Driver\'s password', example: 'password123' }
                ],
                sampleBody: {
                    email: 'driver@example.com',
                    password: 'password123'
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
            // ============ SHIPMENT ENDPOINTS ============
            {
                method: 'GET',
                url: '/api/v1/vendor/shipments',
                name: 'List Shipments',
                description: 'Get paginated list of vendor\'s shipments with optional filters.',
                auth: true,
                group: 'shipments',
                userType: 'vendor',
                fields: [
                    { name: 'status', type: 'string', required: false, description: 'Filter by status', example: 'draft' },
                    { name: 'search', type: 'string', required: false, description: 'Search by shipment number, name, or phone', example: 'PCM-2026' },
                    { name: 'from_date', type: 'string', required: false, description: 'Filter from date (Y-m-d)', example: '2026-01-01' },
                    { name: 'to_date', type: 'string', required: false, description: 'Filter to date (Y-m-d)', example: '2026-12-31' },
                    { name: 'per_page', type: 'number', required: false, description: 'Items per page (max 100)', example: '15' }
                ],
                sampleBody: null,
                exampleResponses: {
                    '200': {
                        success: true,
                        message: 'Shipments retrieved successfully.',
                        data: {
                            shipments: [
                                {
                                    id: 1,
                                    shipment_number: 'PCM-2026-00001',
                                    status: 'draft',
                                    status_label: 'Draft',
                                    recipient_name: 'John Mensah',
                                    recipient_phone: '+233244123456',
                                    location: { type: 'dropdown', region: 'Greater Accra', district: 'Accra Metropolitan', town: 'Osu' },
                                    can_edit: true,
                                    can_delete: true,
                                    can_submit: false,
                                    items_count: 2
                                }
                            ],
                            pagination: { current_page: 1, last_page: 1, per_page: 15, total: 1 }
                        }
                    }
                }
            },
            {
                method: 'POST',
                url: '/api/v1/vendor/shipments',
                name: 'Create Shipment',
                description: 'Create a new draft shipment. At least one location method required: dropdown (region+district), coordinates (lat+lng), or Ghana Post.',
                auth: true,
                group: 'shipments',
                userType: 'vendor',
                bodyType: 'formdata',
                useFormInputs: true,
                fields: [
                    { name: 'recipient_name', type: 'string', required: true, description: 'Recipient full name', example: 'John Mensah' },
                    { name: 'recipient_phone', type: 'string', required: true, description: 'Phone number (international format)', example: '+233244123456' },
                    { name: 'recipient_phone_confirm', type: 'string', required: true, description: 'Confirm phone (must match)', example: '+233244123456' },
                    { name: '_location_method', type: 'enum', uiOnly: true, required: false, description: 'Choose location input method (UI only - not sent to API)', options: ['dropdown', 'coordinates', 'gh_post'], labels: { dropdown: 'Region/District Dropdown', coordinates: 'GPS Coordinates', gh_post: 'Ghana Post Address' } },
                    { name: 'region_id', type: 'dropdown', required: false, description: 'Select region', source: 'regions', labelField: 'name', valueField: 'id', example: '1', showWhen: { field: '_location_method', value: 'dropdown' } },
                    { name: 'district_id', type: 'dropdown', required: false, description: 'Select district', dependsOn: 'region_id', example: '1', showWhen: { field: '_location_method', value: 'dropdown' } },
                    { name: 'town', type: 'string', required: false, description: 'Town/area name', example: 'Osu Oxford Street', showWhen: { field: '_location_method', value: 'dropdown' } },
                    { name: 'latitude', type: 'number', required: false, description: 'GPS latitude (-90 to 90)', example: '5.5913', showWhen: { field: '_location_method', value: 'coordinates' } },
                    { name: 'longitude', type: 'number', required: false, description: 'GPS longitude (-180 to 180)', example: '-0.1864', showWhen: { field: '_location_method', value: 'coordinates' } },
                    { name: 'gh_post_address', type: 'string', required: false, description: 'Ghana Post address', example: 'GA-123-4567', showWhen: { field: '_location_method', value: 'gh_post' } },
                    { name: 'landmark', type: 'string', required: false, description: 'Nearby landmark', example: 'Near the big church' },
                    { name: 'delivery_instructions', type: 'string', required: false, description: 'Special delivery instructions', example: 'Call before delivery' }
                ],
                sampleBody: null,
                exampleResponses: {
                    '201': {
                        success: true,
                        message: 'Shipment created successfully.',
                        data: {
                            shipment: {
                                id: 1,
                                shipment_number: 'PCM-2026-00001',
                                status: 'draft',
                                recipient_name: 'John Mensah',
                                recipient_phone: '+233244123456',
                                location: {
                                    type: 'dropdown',
                                    region: 'Greater Accra',
                                    region_id: 1,
                                    district: 'Accra Metropolitan',
                                    district_id: 1,
                                    town: 'Osu Oxford Street',
                                    latitude: null,
                                    longitude: null,
                                    gh_post_address: null,
                                    landmark: 'Near the big church'
                                },
                                delivery_instructions: 'Call before delivery',
                                items: [],
                                submitted_at: null,
                                created_at: '2026-01-27T10:30:00+00:00',
                                updated_at: '2026-01-27T10:30:00+00:00'
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
                                id: 1,
                                shipment_number: 'PCM-2026-00001',
                                status: 'draft',
                                recipient_name: 'John Mensah',
                                recipient_phone: '+233244123456',
                                location: {
                                    type: 'dropdown',
                                    region: 'Greater Accra',
                                    region_id: 1,
                                    district: 'Accra Metropolitan',
                                    district_id: 1,
                                    town: 'Osu Oxford Street',
                                    latitude: null,
                                    longitude: null,
                                    gh_post_address: null,
                                    landmark: 'Near the big church'
                                },
                                delivery_instructions: 'Call before delivery',
                                items: [
                                    {
                                        id: 1,
                                        description: 'Fridge',
                                        quantity: 2,
                                        status: 'pending',
                                        tracking_code: 'TRK8A3F2K9X',
                                        images: [
                                            {
                                                id: 1,
                                                url: 'https://gateway.storjshare.io/shaxi/demo/shipments/1/items/1/image.jpg?X-Amz-Signature=...',
                                                original_name: 'fridge.jpg',
                                                expires_at: '2026-01-27T11:30:00+00:00'
                                            }
                                        ],
                                        created_at: '2026-01-27T10:35:00+00:00',
                                        updated_at: '2026-01-27T10:35:00+00:00'
                                    }
                                ],
                                submitted_at: null,
                                created_at: '2026-01-27T10:30:00+00:00',
                                updated_at: '2026-01-27T10:35:00+00:00'
                            }
                        }
                    }
                }
            },
            {
                method: 'PUT',
                url: '/api/v1/vendor/shipments/{shipment}',
                name: 'Update Shipment',
                description: 'Update a draft shipment. Only draft shipments can be edited. All fields are optional.',
                auth: true,
                group: 'shipments',
                userType: 'vendor',
                bodyType: 'formdata',
                useFormInputs: true,
                urlParams: [
                    { name: 'shipment', type: 'dropdown', required: true, description: 'Select draft shipment', source: 'shipments?status=draft', labelField: 'shipment_number', valueField: 'id', onSelect: 'prefillShipmentData' }
                ],
                fields: [
                    { name: 'recipient_name', type: 'string', required: false, description: 'Recipient full name', example: 'John Mensah Updated' },
                    { name: 'recipient_phone', type: 'string', required: false, description: 'Phone number (international format)', example: '+233244123456' },
                    { name: 'recipient_phone_confirm', type: 'string', required: false, description: 'Confirm phone (required if changing phone)', example: '+233244123456' },
                    { name: '_location_method', type: 'enum', uiOnly: true, required: false, description: 'Choose location input method (UI only - not sent to API)', options: ['dropdown', 'coordinates', 'gh_post'], labels: { dropdown: 'Region/District Dropdown', coordinates: 'GPS Coordinates', gh_post: 'Ghana Post Address' } },
                    { name: 'region_id', type: 'dropdown', required: false, description: 'Select region', source: 'regions', labelField: 'name', valueField: 'id', example: '1', showWhen: { field: '_location_method', value: 'dropdown' } },
                    { name: 'district_id', type: 'dropdown', required: false, description: 'Select district', dependsOn: 'region_id', example: '1', showWhen: { field: '_location_method', value: 'dropdown' } },
                    { name: 'town', type: 'string', required: false, description: 'Town/area name', example: 'Osu East', showWhen: { field: '_location_method', value: 'dropdown' } },
                    { name: 'latitude', type: 'number', required: false, description: 'GPS latitude (-90 to 90)', example: '5.5913', showWhen: { field: '_location_method', value: 'coordinates' } },
                    { name: 'longitude', type: 'number', required: false, description: 'GPS longitude (-180 to 180)', example: '-0.1864', showWhen: { field: '_location_method', value: 'coordinates' } },
                    { name: 'gh_post_address', type: 'string', required: false, description: 'Ghana Post address', example: 'GA-123-4567', showWhen: { field: '_location_method', value: 'gh_post' } },
                    { name: 'landmark', type: 'string', required: false, description: 'Nearby landmark', example: 'Near the big church' },
                    { name: 'delivery_instructions', type: 'string', required: false, description: 'Special delivery instructions', example: 'Call before delivery' }
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
                                recipient_name: 'John Mensah Updated',
                                recipient_phone: '+233244123456',
                                location: {
                                    type: 'dropdown',
                                    region: 'Greater Accra',
                                    region_id: 1,
                                    district: 'Accra Metropolitan',
                                    district_id: 1,
                                    town: 'Osu East',
                                    latitude: null,
                                    longitude: null,
                                    gh_post_address: null,
                                    landmark: 'Near the big church'
                                },
                                delivery_instructions: 'Call before delivery',
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
                                recipient_name: 'John Doe',
                                recipient_phone: '+233241234567',
                                location: {
                                    type: 'dropdown',
                                    region: 'Greater Accra',
                                    region_id: 1,
                                    district: 'Accra Metropolitan',
                                    district_id: 1,
                                    town: 'Osu Oxford Street',
                                    latitude: null,
                                    longitude: null,
                                    gh_post_address: null,
                                    landmark: 'Near the big tree'
                                },
                                delivery_instructions: 'Call before delivery',
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
                description: 'Add an item to a draft shipment.',
                auth: true,
                group: 'shipment-items',
                userType: 'vendor',
                bodyType: 'formdata',
                useFormInputs: true,
                urlParams: [
                    { name: 'shipment', type: 'dropdown', required: true, description: 'Select draft shipment', source: 'shipments?status=draft', labelField: 'shipment_number', valueField: 'id' }
                ],
                fields: [
                    { name: 'description', type: 'string', required: true, description: 'Item description', example: 'Fridge - Samsung 250L' },
                    { name: 'quantity', type: 'number', required: false, description: 'Quantity (default 1)', example: '2' }
                ],
                sampleBody: null,
                exampleResponses: {
                    '201': {
                        success: true,
                        message: 'Item added successfully.',
                        data: {
                            item: {
                                id: 1,
                                description: 'Fridge - Samsung 250L',
                                quantity: 2,
                                status: 'pending',
                                tracking_code: 'TRK8A3F2K9X',
                                images: [],
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
                description: 'Update an item in a draft shipment.',
                auth: true,
                group: 'shipment-items',
                userType: 'vendor',
                bodyType: 'formdata',
                useFormInputs: true,
                urlParams: [
                    { name: 'shipment', type: 'dropdown', required: true, description: 'Select draft shipment', source: 'shipments?status=draft', labelField: 'shipment_number', valueField: 'id' },
                    { name: 'item', type: 'dropdown', required: true, description: 'Select item', dependsOn: 'shipment', labelField: 'description', valueField: 'id', onSelect: 'prefillItemData' }
                ],
                fields: [
                    { name: 'description', type: 'string', required: false, description: 'Item description', example: 'Fridge - Samsung 300L' },
                    { name: 'quantity', type: 'number', required: false, description: 'Quantity', example: '3' }
                ],
                sampleBody: null,
                exampleResponses: {
                    '200': {
                        success: true,
                        message: 'Item updated successfully.',
                        data: {
                            item: {
                                id: 1,
                                description: 'Fridge - Samsung 300L',
                                quantity: 3,
                                status: 'pending',
                                tracking_code: 'TRK8A3F2K9X',
                                images: [],
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
                description: 'Upload one or multiple images for a shipment item. Max 5 images per item. Supports JPEG, PNG, WebP. Max 5MB per file.',
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
                                    expires_at: '2026-01-27T11:00:00Z'
                                },
                                {
                                    id: 2,
                                    url: 'https://gateway.storjshare.io/shaxi/demo/shipments/1/items/1/1706284801_def456.jpg?X-Amz-Signature=...',
                                    original_name: 'fridge-inside.jpg',
                                    size: 312450,
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
            }
        ];

        // State
        let selectedEndpoint = null;
        let responseData = null;
        let selectedExampleStatus = null;

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
            authContainer.innerHTML = '';
            profileContainer.innerHTML = '';
            locationContainer.innerHTML = '';
            shipmentsContainer.innerHTML = '';
            shipmentItemsContainer.innerHTML = '';
            driverAuthContainer.innerHTML = '';
            driverProfileContainer.innerHTML = '';

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
            } else if (selectedEndpoint.group === 'driver-auth') {
                groupName = 'Auth';
                folderName = 'Driver';
            } else if (selectedEndpoint.group === 'driver-profile') {
                groupName = 'Profile';
                folderName = 'Driver';
            }
            document.getElementById('breadcrumbGroup').textContent = folderName + ' / ' + groupName;
            document.getElementById('breadcrumbEndpoint').textContent = selectedEndpoint.name;
            document.getElementById('methodDisplay').textContent = selectedEndpoint.method;
            document.getElementById('methodDisplay').className = 'method-display method-' + selectedEndpoint.method;
            document.getElementById('urlInput').value = '{{ url('') }}' + selectedEndpoint.url;
            document.getElementById('endpointDesc').textContent = selectedEndpoint.description;
            document.getElementById('sendBtn').disabled = false;

            // Handle URL params tab visibility and rendering
            const paramsTabBtn = document.getElementById('paramsTabBtn');
            if (selectedEndpoint.urlParams && selectedEndpoint.urlParams.length > 0) {
                paramsTabBtn.classList.remove('hidden');
                renderUrlParams(selectedEndpoint.urlParams);
                // Auto-switch to params tab when endpoint has URL params
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

        // Get status label with badge
        function getStatusLabel(status) {
            const cleanStatus = status.replace(/_.*/, ''); // Remove suffix like _already, _phone, etc
            const statusNum = parseInt(cleanStatus);

            let label = status;
            let type = 'success';

            if (statusNum >= 200 && statusNum < 300) {
                type = 'success';
                label = `${cleanStatus} Success`;
            } else if (statusNum >= 400 && statusNum < 500) {
                type = 'error';
                // Add description based on suffix
                if (status.includes('_already')) label = `${cleanStatus} Already Verified`;
                else if (status.includes('_unverified')) label = `${cleanStatus} Unverified`;
                else if (status.includes('_inactive')) label = `${cleanStatus} Inactive`;
                else if (status.includes('_phone')) label = `${cleanStatus} Invalid Phone`;
                else label = `${cleanStatus} Error`;
            } else if (statusNum === 401) {
                type = 'warning';
                label = '401 Unauthenticated';
            } else if (statusNum === 422) {
                type = 'warning';
                label = '422 Validation Error';
            }

            return label;
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
                    const disabled = isDependent ? 'disabled' : '';
                    const initialText = isDependent ? `-- Select ${param.dependsOn} first --` : '-- Loading... --';

                    html += `<div style="display: flex; gap: 8px; align-items: center;">
                        <select id="url-param-${param.name}" class="form-input" style="flex: 1;" ${disabled} onchange="onUrlParamChange('${param.name}')">
                            <option value="">${initialText}</option>
                        </select>`;

                    if (!isDependent && param.source) {
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

            // Load dropdown options asynchronously (only for non-dependent dropdowns)
            for (const param of urlParams) {
                if (param.type === 'dropdown' && param.source && !param.dependsOn) {
                    await loadDropdownOptions(param);
                }
            }
        }

        // Load options for dropdown from API
        async function loadDropdownOptions(param) {
            const select = document.getElementById('url-param-' + param.name);
            if (!select) return;

            // Check cache first
            if (dataSourceCache[param.source]) {
                populateDropdown(select, dataSourceCache[param.source], param);
                return;
            }

            // Determine API endpoint based on source
            let apiUrl = '';
            let dataKey = '';
            if (param.source === 'regions') {
                apiUrl = '{{ url('') }}/api/v1/vendor/regions';
                dataKey = 'regions';
            } else if (param.source.startsWith('shipments')) {
                apiUrl = '{{ url('') }}/api/v1/vendor/' + param.source;
                dataKey = 'shipments';
            }

            if (!apiUrl) {
                select.innerHTML = '<option value="">-- Error: Unknown source --</option>';
                return;
            }

            // Get token
            const token = localStorage.getItem('parcelman_vendor_token');
            if (!token) {
                select.innerHTML = '<option value="">-- Please login first --</option>';
                return;
            }

            try {
                const response = await fetch(apiUrl, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'Authorization': 'Bearer ' + token
                    }
                });

                const data = await response.json();

                if (data.success && data.data) {
                    // Cache the data - use dataKey if specified, otherwise use param.source
                    const cacheKey = param.source;
                    const items = data.data[dataKey] || data.data.shipments || [];
                    dataSourceCache[cacheKey] = items;
                    populateDropdown(select, items, param);
                } else {
                    select.innerHTML = '<option value="">-- Error loading data --</option>';
                }
            } catch (error) {
                console.error('Error loading dropdown options:', error);
                select.innerHTML = '<option value="">-- Error: ' + error.message + ' --</option>';
            }
        }

        // Populate dropdown with options
        function populateDropdown(select, items, param) {
            let html = '<option value="">-- Select ' + param.name + ' --</option>';
            items.forEach(item => {
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

        // Refresh a specific dropdown
        async function refreshDropdown(paramName, source) {
            // Clear cache for this source
            delete dataSourceCache[source];

            // Find the param config
            if (!selectedEndpoint || !selectedEndpoint.urlParams) return;
            const param = selectedEndpoint.urlParams.find(p => p.name === paramName);
            if (param) {
                const select = document.getElementById('url-param-' + paramName);
                if (select) {
                    select.innerHTML = '<option value="">-- Loading... --</option>';
                }
                await loadDropdownOptions(param);
                showToast('Options refreshed');
            }
        }

        // Update URL input when params change
        function updateUrlWithParams() {
            if (!selectedEndpoint || !selectedEndpoint.urlParams) return;

            let url = selectedEndpoint.url;
            selectedEndpoint.urlParams.forEach(param => {
                const input = document.getElementById('url-param-' + param.name);
                if (input && input.value) {
                    url = url.replace('{' + param.name + '}', input.value);
                }
            });

            document.getElementById('urlInput').value = '{{ url('') }}' + url;
        }

        // Handle URL param dropdown change
        async function onUrlParamChange(paramName) {
            updateUrlWithParams();

            if (!selectedEndpoint || !selectedEndpoint.urlParams) return;

            const changedParam = selectedEndpoint.urlParams.find(p => p.name === paramName);
            if (!changedParam) return;

            const select = document.getElementById('url-param-' + paramName);
            const selectedValue = select ? select.value : null;

            if (!selectedValue) {
                // Clear dependent dropdowns
                selectedEndpoint.urlParams.forEach(param => {
                    if (param.dependsOn === paramName) {
                        const dependentSelect = document.getElementById('url-param-' + param.name);
                        if (dependentSelect) {
                            dependentSelect.disabled = true;
                            dependentSelect.innerHTML = `<option value="">-- Select ${paramName} first --</option>`;
                        }
                    }
                });
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

            // Prefill description
            const descriptionInput = document.getElementById('form-field-description');
            if (descriptionInput) descriptionInput.value = item.description || '';

            // Prefill quantity
            const quantityInput = document.getElementById('form-field-quantity');
            if (quantityInput) quantityInput.value = item.quantity || '1';

            showToast('Item data prefilled successfully');
        }

        // Render form inputs for endpoints with useFormInputs: true
        async function renderFormInputs(fields, sampleBody) {
            const container = document.getElementById('formInputsContainer');
            let html = '<table class="headers-table"><thead><tr><th>Field</th><th>Value</th></tr></thead><tbody>';

            fields.forEach(field => {
                const value = sampleBody ? (sampleBody[field.name] || '') : '';

                // Add data attributes for conditional visibility
                const showWhenAttr = field.showWhen ? `data-show-when-field="${field.showWhen.field}" data-show-when-value="${field.showWhen.value}"` : '';
                const initiallyHidden = field.showWhen ? 'style="display: none;"' : '';

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
                    html += `<select id="form-field-${field.name}" class="form-input" style="width: 100%;">
                        <option value="">-- Select --</option>`;
                    field.options.forEach(opt => {
                        const selected = opt === value ? 'selected' : '';
                        const label = field.labels && field.labels[opt] ? field.labels[opt] : opt;
                        html += `<option value="${opt}" ${selected}>${label}</option>`;
                    });
                    html += `</select>`;
                } else if (field.type === 'dropdown') {
                    // Render dropdown for API-loaded fields with refresh button
                    html += `<div style="display: flex; gap: 4px; align-items: center;">
                        <select id="form-field-${field.name}" class="form-input" style="flex: 1;">
                            <option value="">-- Select --</option>
                        </select>`;
                    if (!field.dependsOn) {
                        // Add refresh button for dropdowns that don't depend on others
                        html += `<button type="button" onclick="refreshFormDropdown('${field.name}', '${field.source}')" style="padding: 6px 10px; font-size: 13px; cursor: pointer; border: 1px solid var(--border-color); border-radius: 3px; background: #f5f5f5; color: #333;">↻</button>`;
                    }
                    html += `</div>`;
                } else if (field.type === 'file') {
                    // Render file input
                    const multipleAttr = field.multiple ? 'multiple' : '';
                    const acceptAttr = field.accept || 'image/jpeg,image/png,image/webp';
                    html += `<input type="file" id="form-field-${field.name}" class="form-input" ${multipleAttr} accept="${acceptAttr}">`;
                } else {
                    // Render text input for other fields
                    html += `<input type="text" id="form-field-${field.name}" class="form-input" value="${value}" placeholder="${field.example || ''}">`;
                }

                html += `</td></tr>`;
            });

            html += '</tbody></table>';
            container.innerHTML = html;

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
            // Find control fields (fields that other fields depend on)
            const controlFields = fields.filter(f => fields.some(other => other.showWhen && other.showWhen.field === f.name));

            controlFields.forEach(controlField => {
                const select = document.getElementById('form-field-' + controlField.name);
                if (select) {
                    select.addEventListener('change', () => {
                        toggleConditionalFields(controlField.name, select.value);
                    });
                }
            });
        }

        // Toggle visibility of fields based on control field value
        function toggleConditionalFields(controlFieldName, value) {
            const rows = document.querySelectorAll(`[data-show-when-field="${controlFieldName}"]`);
            rows.forEach(row => {
                const expectedValue = row.getAttribute('data-show-when-value');
                if (value === expectedValue) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
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
                const token = getActiveToken();
                const apiUrl = '{{ url('') }}/api/v1/vendor/' + field.source;

                const response = await fetch(apiUrl, {
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
                dataSourceCache[field.source] = data.data[field.source] || [];
                populateFormDropdown(select, dataSourceCache[field.source], field);
            } catch (error) {
                select.innerHTML = '<option value="">-- Error loading --</option>';
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
                if (field.type === 'dropdown' && field.dependsOn) {
                    const select = document.getElementById('form-field-' + field.name);
                    if (select) {
                        select.disabled = true;
                        select.innerHTML = '<option value="">-- Select region first --</option>';
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
                // Fetch full shipment details
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

                    // Prefill recipient fields
                    const recipientNameInput = document.getElementById('form-field-recipient_name');
                    if (recipientNameInput) recipientNameInput.value = shipmentData.recipient_name || '';

                    const recipientPhoneInput = document.getElementById('form-field-recipient_phone');
                    if (recipientPhoneInput) recipientPhoneInput.value = shipmentData.recipient_phone || '';

                    const recipientPhoneConfirmInput = document.getElementById('form-field-recipient_phone_confirm');
                    if (recipientPhoneConfirmInput) recipientPhoneConfirmInput.value = shipmentData.recipient_phone || '';

                    // Prefill location method
                    const locationMethodSelect = document.getElementById('form-field-_location_method');
                    if (locationMethodSelect && shipmentData.location) {
                        locationMethodSelect.value = shipmentData.location.type || '';

                        // Trigger conditional field visibility
                        const changeEvent = new Event('change');
                        locationMethodSelect.dispatchEvent(changeEvent);

                        // Wait for conditional fields to show
                        setTimeout(() => {
                            // Prefill based on location type
                            if (shipmentData.location.type === 'dropdown') {
                                const regionSelect = document.getElementById('form-field-region_id');
                                if (regionSelect && shipmentData.location.region_id) {
                                    regionSelect.value = shipmentData.location.region_id;
                                    // Trigger region change to load districts
                                    regionSelect.dispatchEvent(new Event('change'));

                                    // Wait for districts to load, then set district
                                    setTimeout(() => {
                                        const districtSelect = document.getElementById('form-field-district_id');
                                        if (districtSelect && shipmentData.location.district_id) {
                                            districtSelect.value = shipmentData.location.district_id;
                                        }
                                    }, 500);
                                }

                                const townInput = document.getElementById('form-field-town');
                                if (townInput) townInput.value = shipmentData.location.town || '';
                            } else if (shipmentData.location.type === 'coordinates') {
                                const latInput = document.getElementById('form-field-latitude');
                                if (latInput) latInput.value = shipmentData.location.latitude || '';

                                const lngInput = document.getElementById('form-field-longitude');
                                if (lngInput) lngInput.value = shipmentData.location.longitude || '';
                            } else if (shipmentData.location.type === 'gh_post') {
                                const ghPostInput = document.getElementById('form-field-gh_post_address');
                                if (ghPostInput) ghPostInput.value = shipmentData.location.gh_post_address || '';
                            }

                            // Prefill common location fields
                            const landmarkInput = document.getElementById('form-field-landmark');
                            if (landmarkInput) landmarkInput.value = shipmentData.location.landmark || '';
                        }, 100);
                    }

                    // Prefill delivery instructions
                    const deliveryInstructionsInput = document.getElementById('form-field-delivery_instructions');
                    if (deliveryInstructionsInput) deliveryInstructionsInput.value = shipmentData.delivery_instructions || '';

                    showToast('Shipment data prefilled successfully');
                } else {
                    showToast('Error loading shipment details', 'error');
                }
            } catch (error) {
                console.error('Error prefilling shipment data:', error);
                showToast('Error loading shipment details', 'error');
            }
        }

        // Collect form input values
        function collectFormInputs() {
            const body = {};
            const files = {};
            if (!selectedEndpoint || !selectedEndpoint.fields) return { body, files };

            selectedEndpoint.fields.forEach(field => {
                // Skip UI-only fields (not sent to API)
                if (field.uiOnly) return;

                const input = document.getElementById('form-field-' + field.name);

                if (input && field.type === 'file') {
                    // Handle file inputs
                    if (input.files && input.files.length > 0) {
                        files[field.name] = {
                            files: input.files,
                            multiple: field.multiple || false
                        };
                    }
                } else if (input && input.value.trim()) {
                    // Handle regular inputs
                    body[field.name] = input.value.trim();
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
                            formData.append(key, formInputs[key]);
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
    </script>
</body>
</html>
