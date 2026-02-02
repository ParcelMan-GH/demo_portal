/**
 * Users DataTable Alpine.js Component
 * Server-side pagination, filtering, sorting, and export functionality
 */

document.addEventListener('alpine:init', () => {
    Alpine.data('usersDataTable', (config) => ({
        // Data
        users: [],
        loading: true,
        error: null,

        // Pagination
        currentPage: 1,
        perPage: 10,
        totalUsers: 0,
        lastPage: 1,
        from: 0,
        to: 0,

        // Search & Filters
        search: '',
        searchDebounce: null,
        roleFilter: '',
        statusFilter: '',
        dateFrom: '',
        dateTo: '',

        // Sorting
        sortField: 'created_at',
        sortDirection: 'desc',

        // Column visibility
        showColumnMenu: false,
        columns: {
            name: { label: 'Name', visible: true, sortable: true },
            role: { label: 'Role', visible: true, sortable: false },
            email: { label: 'Email', visible: true, sortable: true },
            status: { label: 'Status', visible: true, sortable: false },
            createdAt: { label: 'Created At', visible: true, sortable: true },
            createdBy: { label: 'Created By', visible: true, sortable: false },
            lastLogin: { label: 'Last Login', visible: true, sortable: true },
        },

        // Export
        showExportMenu: false,
        exporting: false,

        // Actions menu
        activeActionsMenu: null,

        // Delete modal
        showDeleteModal: false,
        userToDelete: null,
        deleting: false,

        // Config
        dataUrl: config.dataUrl,
        exportUrl: config.exportUrl,
        csrfToken: config.csrfToken,
        canCreate: config.canCreate,
        createUrl: config.createUrl,

        // Initialize
        init() {
            this.fetchData();

            // Close menus on click outside
            document.addEventListener('click', (e) => {
                if (!e.target.closest('[data-actions-menu]')) {
                    this.activeActionsMenu = null;
                }
                if (!e.target.closest('[data-column-menu]')) {
                    this.showColumnMenu = false;
                }
                if (!e.target.closest('[data-export-menu]')) {
                    this.showExportMenu = false;
                }
            });
        },

        // Fetch data from server
        async fetchData() {
            this.loading = true;
            this.error = null;

            try {
                const params = new URLSearchParams({
                    page: this.currentPage,
                    per_page: this.perPage,
                    sort: this.sortField,
                    direction: this.sortDirection,
                });

                if (this.search) params.append('search', this.search);
                if (this.roleFilter) params.append('role', this.roleFilter);
                if (this.statusFilter !== '') params.append('status', this.statusFilter);
                if (this.dateFrom) params.append('date_from', this.dateFrom);
                if (this.dateTo) params.append('date_to', this.dateTo);

                const response = await fetch(`${this.dataUrl}?${params}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (!response.ok) throw new Error('Failed to fetch data');

                const result = await response.json();
                this.users = result.data;
                this.totalUsers = result.meta.total;
                this.lastPage = result.meta.last_page;
                this.from = result.meta.from;
                this.to = result.meta.to;
            } catch (err) {
                this.error = err.message;
                console.error('DataTable Error:', err);
            } finally {
                this.loading = false;
            }
        },

        // Search with debounce
        handleSearch() {
            clearTimeout(this.searchDebounce);
            this.searchDebounce = setTimeout(() => {
                this.currentPage = 1;
                this.fetchData();
            }, 300);
        },

        // Apply filters
        applyFilters() {
            this.currentPage = 1;
            this.fetchData();
        },

        // Clear all filters
        clearFilters() {
            this.search = '';
            this.roleFilter = '';
            this.statusFilter = '';
            this.dateFrom = '';
            this.dateTo = '';
            this.currentPage = 1;
            this.fetchData();
        },

        // Sorting
        sortBy(field) {
            if (!this.columns[field]?.sortable) return;

            const fieldMap = {
                name: 'name',
                email: 'email',
                createdAt: 'created_at',
                lastLogin: 'last_login_at',
            };

            const apiField = fieldMap[field] || field;

            if (this.sortField === apiField) {
                this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
            } else {
                this.sortField = apiField;
                this.sortDirection = 'asc';
            }
            this.fetchData();
        },

        getSortIcon(field) {
            const fieldMap = {
                name: 'name',
                email: 'email',
                createdAt: 'created_at',
                lastLogin: 'last_login_at',
            };
            const apiField = fieldMap[field] || field;

            if (this.sortField !== apiField) return 'sort';
            return this.sortDirection === 'asc' ? 'sort-asc' : 'sort-desc';
        },

        // Pagination
        goToPage(page) {
            if (page < 1 || page > this.lastPage) return;
            this.currentPage = page;
            this.fetchData();
        },

        changePerPage() {
            this.currentPage = 1;
            this.fetchData();
        },

        get paginationRange() {
            const range = [];
            const delta = 2;
            const left = this.currentPage - delta;
            const right = this.currentPage + delta;

            for (let i = 1; i <= this.lastPage; i++) {
                if (i === 1 || i === this.lastPage || (i >= left && i <= right)) {
                    range.push(i);
                } else if (range[range.length - 1] !== '...') {
                    range.push('...');
                }
            }
            return range;
        },

        // Toggle actions menu
        toggleActionsMenu(userId) {
            this.activeActionsMenu = this.activeActionsMenu === userId ? null : userId;
        },

        // Toggle user status
        async toggleUserStatus(user) {
            if (user.is_self) return;

            try {
                const response = await fetch(user.toggle_url, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (response.ok) {
                    this.fetchData();
                    this.showNotification('User status updated successfully', 'success');
                } else {
                    throw new Error('Failed to update status');
                }
            } catch (err) {
                this.showNotification('Failed to update user status', 'error');
            }
            this.activeActionsMenu = null;
        },

        // Export functions
        async exportData(format) {
            this.exporting = true;
            this.showExportMenu = false;

            try {
                const params = new URLSearchParams();
                if (this.search) params.append('search', this.search);
                if (this.roleFilter) params.append('role', this.roleFilter);
                if (this.statusFilter !== '') params.append('status', this.statusFilter);

                const response = await fetch(`${this.exportUrl}?${params}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (!response.ok) throw new Error('Export failed');

                const result = await response.json();

                if (format === 'csv') {
                    this.downloadCSV(result.data);
                } else if (format === 'json') {
                    this.downloadJSON(result.data);
                }

                this.showNotification('Export completed successfully', 'success');
            } catch (err) {
                this.showNotification('Export failed', 'error');
            } finally {
                this.exporting = false;
            }
        },

        downloadCSV(data) {
            if (!data.length) return;

            const headers = Object.keys(data[0]);
            const csvContent = [
                headers.join(','),
                ...data.map(row =>
                    headers.map(header => {
                        let cell = row[header] ?? '';
                        cell = String(cell).replace(/"/g, '""');
                        return `"${cell}"`;
                    }).join(',')
                )
            ].join('\n');

            this.downloadFile(csvContent, 'users.csv', 'text/csv');
        },

        downloadJSON(data) {
            this.downloadFile(JSON.stringify(data, null, 2), 'users.json', 'application/json');
        },

        downloadFile(content, filename, type) {
            const blob = new Blob([content], { type });
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = filename;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(url);
        },

        // Column visibility
        toggleColumn(column) {
            this.columns[column].visible = !this.columns[column].visible;
        },

        get visibleColumnCount() {
            return Object.values(this.columns).filter(c => c.visible).length;
        },

        // Notifications
        showNotification(message, type = 'info') {
            // Dispatch custom event for notification
            window.dispatchEvent(new CustomEvent('show-notification', {
                detail: { message, type }
            }));
        },

        // Check if any filters are active
        get hasActiveFilters() {
            return this.search || this.roleFilter || this.statusFilter !== '' || this.dateFrom || this.dateTo;
        },
    }));
});
