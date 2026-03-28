// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * AMD module for profile collapsible sections.
 *
 * @module      local_finplacements/profile_sections
 * @copyright   2026 FinTree Education
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/ajax', 'core/notification'], function($, Ajax, Notification) {
    'use strict';

    return {
        /**
         * Initialize the profile sections.
         */
        init: function() {
            var self = this;

            // Handle section toggle clicks.
            $('.fp-section-header').on('click', function() {
                var sectionId = $(this).data('section');
                self.toggleSection(sectionId);
            });

            // Handle expand all button.
            $('#fp-expand-all').on('click', function() {
                self.expandAll();
            });

            // Handle collapse all button.
            $('#fp-collapse-all').on('click', function() {
                self.collapseAll();
            });

            // Auto-collapse empty sections on load.
            self.autoCollapseEmpty();

            // Update section progress bars on input change.
            $('.fp-section-content input, .fp-section-content select, .fp-section-content textarea')
                .on('change input', function() {
                    self.updateSectionProgress($(this));
                });

            // Initialize section progress indicators.
            this.initProgressIndicators();
        },

        /**
         * Toggle a section's expanded/collapsed state.
         *
         * @param {string} sectionId Section identifier.
         */
        toggleSection: function(sectionId) {
            var $section = $('#fp-section-' + sectionId);
            var $content = $section.find('.fp-section-content');
            var $icon = $section.find('.fp-section-icon');

            if ($content.is(':visible')) {
                // Collapse.
                $content.slideUp(200);
                $section.removeClass('fp-section-expanded').addClass('fp-section-collapsed');
                $icon.text('+');
                this.saveSectionState(sectionId, false);
            } else {
                // Expand.
                $content.slideDown(200);
                $section.removeClass('fp-section-collapsed').addClass('fp-section-expanded');
                $icon.text('−');
                this.saveSectionState(sectionId, true);
            }
        },

        /**
         * Expand all sections.
         */
        expandAll: function() {
            var self = this;
            $('.fp-section').each(function() {
                var sectionId = $(this).data('section');
                var $content = $(this).find('.fp-section-content');
                var $icon = $(this).find('.fp-section-icon');

                $content.show();
                $(this).removeClass('fp-section-collapsed').addClass('fp-section-expanded');
                $icon.text('−');
                self.saveSectionState(sectionId, true);
            });
        },

        /**
         * Collapse all sections.
         */
        collapseAll: function() {
            var self = this;
            $('.fp-section').each(function() {
                var sectionId = $(this).data('section');
                var $content = $(this).find('.fp-section-content');
                var $icon = $(this).find('.fp-section-icon');

                $content.hide();
                $(this).removeClass('fp-section-expanded').addClass('fp-section-collapsed');
                $icon.text('+');
                self.saveSectionState(sectionId, false);
            });
        },

        /**
         * Auto-collapse sections that are 100% complete on page load.
         */
        autoCollapseEmpty: function() {
            var self = this;
            $('.fp-section').each(function() {
                var sectionId = $(this).data('section');
                var score = parseInt($(this).data('score') || 0);

                // Collapse sections that are complete.
                if (score === 100) {
                    var $content = $(this).find('.fp-section-content');
                    var $icon = $(this).find('.fp-section-icon');

                    $content.hide();
                    $(this).removeClass('fp-section-expanded').addClass('fp-section-collapsed');
                    $icon.text('+');
                    self.saveSectionState(sectionId, false);
                }
            });
        },

        /**
         * Update section progress bar when input changes.
         *
         * @param {jQuery} $input Changed input element.
         */
        updateSectionProgress: function($input) {
            var $section = $input.closest('.fp-section');
            var sectionId = $section.data('section');
            var filled = 0;
            var total = 0;

            // Count filled fields in this section.
            $section.find('.fp-field-required').each(function() {
                total++;
                var $field = $(this);
                if ($field.is('input[type="checkbox"]')) {
                    if ($field.is(':checked')) {
                        filled++;
                    }
                } else if ($field.is('select')) {
                    if ($field.val() && $field.val() !== 'none' && $field.val() !== '') {
                        filled++;
                    }
                } else {
                    if ($field.val() && $field.val().trim() !== '') {
                        filled++;
                    }
                }
            });

            // Update progress bar.
            var percentage = total > 0 ? Math.round((filled / total) * 100) : 0;
            var $progressBar = $section.find('.fp-section-progress-bar');
            var $progressText = $section.find('.fp-section-progress-text');

            $progressBar.css('width', percentage + '%');
            $progressText.text(filled + ' / ' + total + ' fields');

            // Update section status icon.
            var $statusIcon = $section.find('.fp-section-status');
            if (percentage === 100) {
                $statusIcon.text('✓').addClass('fp-status-complete').removeClass('fp-status-incomplete');
            } else {
                $statusIcon.text('⚠').removeClass('fp-status-complete').addClass('fp-status-incomplete');
            }

            // Update data attribute.
            $section.attr('data-score', percentage);
        },

        /**
         * Initialize progress indicators on page load.
         */
        initProgressIndicators: function() {
            var self = this;
            $('.fp-section').each(function() {
                var $section = $(this);
                var sectionId = $section.data('section');
                var score = parseInt($section.data('score') || 0);
                var total = parseInt($section.data('total') || 0);
                var filled = parseInt($section.data('filled') || 0);

                // Update progress bar.
                var $progressBar = $section.find('.fp-section-progress-bar');
                var $progressText = $section.find('.fp-section-progress-text');

                $progressBar.css('width', score + '%');
                $progressText.text(filled + ' / ' + total + ' fields');

                // Update section status icon.
                var $statusIcon = $section.find('.fp-section-status');
                if (score === 100) {
                    $statusIcon.text('✓').addClass('fp-status-complete').removeClass('fp-status-incomplete');
                } else {
                    $statusIcon.text('⚠').removeClass('fp-status-complete').addClass('fp-status-incomplete');
                }
            });
        },

        /**
         * Save section state to localStorage.
         *
         * @param {string} sectionId Section identifier.
         * @param {boolean} isExpanded Whether section is expanded.
         */
        saveSectionState: function(sectionId, isExpanded) {
            if (typeof localStorage === 'undefined') {
                return; // localStorage not available.
            }

            var key = 'finplacements_profile_sections';
            var state = JSON.parse(localStorage.getItem(key) || '{}');
            state[sectionId] = isExpanded;
            localStorage.setItem(key, JSON.stringify(state));
        },

        /**
         * Restore section states from localStorage.
         */
        restoreSectionStates: function() {
            if (typeof localStorage === 'undefined') {
                return;
            }

            var self = this;
            var key = 'finplacements_profile_sections';
            var state = JSON.parse(localStorage.getItem(key) || '{}');

            Object.keys(state).forEach(function(sectionId) {
                var isExpanded = state[sectionId];
                var $section = $('#fp-section-' + sectionId);

                if ($section.length) {
                    var $content = $section.find('.fp-section-content');
                    var $icon = $section.find('.fp-section-icon');

                    if (isExpanded) {
                        $content.show();
                        $section.removeClass('fp-section-collapsed').addClass('fp-section-expanded');
                        $icon.text('−');
                    } else {
                        $content.hide();
                        $section.removeClass('fp-section-expanded').addClass('fp-section-collapsed');
                        $icon.text('+');
                    }
                }
            });
        }
    };
});
