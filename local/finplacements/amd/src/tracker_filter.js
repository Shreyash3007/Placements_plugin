// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * AMD module for tracker AJAX filtering.
 *
 * @module      local_finplacements/tracker_filter
 * @copyright   2026 FinTree Education
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/ajax', 'core/notification'], function($, Ajax, Notification) {
    'use strict';

    var isLoading = false;

    return {
        /**
         * Initialize the tracker filter.
         */
        init: function() {
            var self = this;

            // Handle status tab clicks.
            $('.fp-status-tab').on('click', function(e) {
                e.preventDefault();
                var status = $(this).data('status');
                self.fetchApplications(status);
            });

            // Handle search input with debounce.
            var debounceTimer;
            $('#fp_tracker_search').on('input', function() {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(function() {
                    self.fetchApplications();
                }, 300);
            });

            // Handle sort dropdown.
            $('#fp_tracker_sort').on('change', function() {
                self.fetchApplications();
            });

            // Handle form submit.
            $('#fp-tracker-filter-form').on('submit', function(e) {
                e.preventDefault();
                self.fetchApplications();
            });
        },

        /**
         * Fetch filtered applications via AJAX.
         *
         * @param {string} status Optional status filter.
         */
        fetchApplications: function(status) {
            if (isLoading) {
                return;
            }

            var self = this;
            isLoading = true;

            // Gather current params.
            var currentStatus = status || $('#fp-status-filter').val() || 'all';
            var search = $('#fp_tracker_search').val() || '';
            var sort = $('#fp_tracker_sort').val() || 'date';

            // Show loading state.
            $('#fp-tracker-container').addClass('fp-loading');

            // Call the external API.
            var request = Ajax.call([{
                methodname: 'local_finplacements_get_tracker_applications',
                args: {
                    status: currentStatus,
                    search: search,
                    sort: sort
                }
            }]);

            request[0].done(function(response) {
                self.handleSuccess(response, currentStatus);
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
         * @param {string} currentStatus Current status filter.
         */
        handleSuccess: function(response, currentStatus) {
            // Update applications table.
            $('#fp-tracker-container').html(response.html);

            // Update active tab.
            $('.fp-status-tab').removeClass('active');
            $('.fp-status-tab[data-status="' + currentStatus + '"]').addClass('active');

            // Update URL without page reload.
            this.updateURL(currentStatus);

            // Hide loading state.
            $('#fp-tracker-container').removeClass('fp-loading');

            // Update count.
            $('#fp-app-count').text(response.count + ' application(s)');
        },

        /**
         * Handle AJAX error.
         *
         * @param {object} error Error object.
         */
        handleError: function(error) {
            // Hide loading state.
            $('#fp-tracker-container').removeClass('fp-loading');

            // Show error notification.
            Notification.addNotification({
                message: 'Failed to load applications. Please refresh the page.',
                type: 'error'
            });

            isLoading = false;
        },

        /**
         * Update browser URL without page reload.
         *
         * @param {string} status Current status filter.
         */
        updateURL: function(status) {
            var search = $('#fp_tracker_search').val() || '';
            var sort = $('#fp_tracker_sort').val() || 'date';
            
            var params = [];
            if (status !== 'all') params.push('status=' + status);
            if (search) params.push('search=' + encodeURIComponent(search));
            if (sort !== 'date') params.push('sort=' + sort);
            
            var url = window.location.pathname + (params.length ? '?' + params.join('&') : '');

            if (history.pushState) {
                history.pushState({}, '', url);
            }
        }
    };
});
