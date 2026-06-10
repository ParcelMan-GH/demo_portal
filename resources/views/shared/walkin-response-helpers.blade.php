window.ParcelmanWalkinResponse = window.ParcelmanWalkinResponse || {
    async parse(res, options = {}) {
        const contentType = res.headers.get('content-type') || '';
        const bodyText = await res.text();
        const trimmedBody = bodyText.trim();
        let json = null;

        if (trimmedBody && (contentType.includes('application/json') || trimmedBody.startsWith('{') || trimmedBody.startsWith('['))) {
            try {
                json = JSON.parse(trimmedBody);
            } catch (error) {
                this.log(res, contentType, bodyText, error, options);
            }
        } else if (!res.ok) {
            this.log(res, contentType, bodyText, null, options);
        }

        if (!res.ok) {
            return {
                json,
                message: this.jsonErrorMessage(json) || this.failureMessage(res.status, options),
            };
        }

        if (!json) {
            this.log(res, contentType, bodyText, null, options);
            return {
                json: null,
                message: options.unreadableMessage || 'Server returned an unreadable response.',
            };
        }

        return { json, message: '' };
    },

    jsonErrorMessage(json) {
        if (!json) return '';
        if (json.errors) return Object.values(json.errors).flat().join(', ');
        return json.message || '';
    },

    failureMessage(status, options = {}) {
        const messages = options.messages || {};
        if (messages[status]) return messages[status];
        if (status === 401 || status === 419) {
            return messages.session || 'Session expired, please refresh and try again.';
        }
        if (status === 403) {
            return messages.forbidden || 'You do not have permission to perform this action.';
        }
        if (status === 422) {
            return messages.validation || 'Please check the form and try again.';
        }
        if (status >= 500) {
            return messages.server || 'Server error while processing the request. Please try again.';
        }

        return messages.fallback || `Request failed with status ${status}. Please try again.`;
    },

    log(res, contentType, bodyText, error = null, options = {}) {
        if (!options.debug) return;

        console.error(options.context || 'Unexpected walk-in response.', {
            status: res.status,
            contentType,
            bodyPreview: bodyText.slice(0, 500),
            error,
        });
    },
};
