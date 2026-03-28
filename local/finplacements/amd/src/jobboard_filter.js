// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * AMD module for job board AJAX filtering.
 *
 * Provides instant search/filter without page reloads.
 *
 * @module      local_finplacements/jobboard_filter
 * @copyright   2026 FinTree Education
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/ajax', 'core/notification'], function($, Ajax, Notification) {
    'use strict';

    var debounceTimer = null;
    var isLoading = false;

    return {
        /**
         * Initialize the job board filter.
         */
        init: function() {
            var self = this;

            // Attach event listeners to all filter inputs.
            $('#fp-filter-form input, #fp-filter-form select').on('input change', function() {
                self.onFilterChange();
            });

            // Handle sort dropdown separately.
            $('#fp-sort-select').on('change', function() {
                self.onFilterChange();
            });

            // Handle pagination clicks.
            $(document).on('click', '.fp-pagination-btn', function(e) {
                e.preventDefault();
                var page = $(this).data('page');
                self.fetchJobs(page);
            });

            // Handle form submit (fallback for non-JS).
            $('#fp-filter-form').on('submit', function(e) {
                e.preventDefault();
                self.onFilterChange();
            });
        },

        /**
         * Handle filter change event with debouncing.
         */
        onFilterChange: function() {
            var self = this;

            // Clear existing timer.
            if (debounceTimer) {
                clearTimeout(debounceTimer);
            }

            // Debounce: wait 300ms after user stops typing.
            debounceTimer = setTimeout(function() {
                self.fetchJobs(0); // Reset to page 1 on filter change.
            }, 300);
        },

        /**
         * Fetch filtered jobs via AJAX.
         *
         * @param {number} page Page number (0-based).
         */
        fetchJobs: function(page) {
            if (isLoading) {
                return; // Prevent duplicate requests.
            }

            var self = this;
            isLoading = true;

            // Gather form data.
            var formData = $('#fp-filter-form').serializeArray();
            var params = {};

            // Build params object from form data.
            formData.forEach(function(item) {
                params[item.name] = item.value;
            });

            // Add sort and page params.
            params.sort = $('#fp-sort-select').val() || 'recent';
            params.page = page || 0;

            // Show loading state.
            $('#fp-jobs-container').addClass('fp-loading');
            $('#fp-pagination-container').addClass('fp-loading');

            // Call the external API.
            var request = Ajax.call([{
                methodname: 'local_finplacements_get_filtered_jobs',
                args: params
            }]);

            request[0].done(function(response) {
                self.handleSuccess(response, page);
            }).fail(function(error) {
                self.handleError(error);
            }).always(function() {
                isLoading = false;
            });
        },

        /**
         * Handle successful AJAX response.
         *
         * @param {object} response Server response.
         * @param {number} currentPage Current page number.
         */
        handleSuccess: function(response, currentPage) {
            // Update job grid.
            $('#fp-jobs-container').html(response.html);

            // Update pagination.
            this.renderPagination(response.pagination, currentPage);

            // Update URL without page reload (for bookmarking).
            this.updateURL(currentPage);

            // Hide loading state.
            $('#fp-jobs-container').removeClass('fp-loading');
            $('#fp-pagination-container').removeClass('fp-loading');

            // Show count message.
            if (response.total > 0) {
                $('#fp-job-count').text(response.total + ' job(s) found');
            } else {
                $('#fp-job-count').text('No jobs found');
            }
        },

        /**
         * Render pagination controls.
         *
         * @param {object} pagination Pagination data.
         * @param {number} currentPage Current page (0-based).
         */
        renderPagination: function(pagination, currentPage) {
            if (pagination.total_pages <= 1) {
                $('#fp-pagination-container').html('');
                return;
            }

            var html = '<div class="fp-pagination">';

            // Previous button.
            if (pagination.has_prev) {
                html += '<button type="button" class="fp-btn fp-btn-secondary fp-pagination-btn" data-page="' + pagination.prev_page + '">← Previous</button>';
            }

            // Page info.
            html += '<span class="fp-page-info">Page ' + pagination.current_page + ' of ' + pagination.total_pages + '</span>';

            // Next button.
            if (pagination.has_next) {
                html += '<button type="button" class="fp-btn fp-btn-secondary fp-pagination-btn" data-page="' + pagination.next_page + '">Next →</button>';
            }

            html += '</div>';

            $('#fp-pagination-container').html(html);
        },

        /**
         * Handle AJAX error.
         *
         * @param {object} error Error object.
         */
        handleError: function(error) {
            // Hide loading state.
            $('#fp-jobs-container').removeClass('fp-loading');
            $('#fp-pagination-container').removeClass('fp-loading');

            // Show error notification.
            Notification.addNotification({
                message: 'Failed to load jobs. Please try again.',
                type: 'error'
            });

            isLoading = false;
        },

        /**
         * Update browser URL without page reload.
         *
         * @param {number} page Page number (0-based).
         */
        updateURL: function(page) {
            var formData = $('#fp-filter-form').serialize();
            var url = window.location.pathname + '?' + formData + '&page=' + page;

            if (history.pushState) {
                history.pushState({}, '', url);
            }
        }
    };
});
