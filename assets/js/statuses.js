/**
 * Handles the status settings screens at Plugin and Post level,
 * for the UI and saving changes.
 *
 * @since 	1.0.0
 *
 * @package
 * @author Social Post Flow
 */

/**
 * Reinitializes autosize instances
 *
 * @since 	1.0.0
 */
function socialPostFlowReinitAutosize() {
	(function ($) {
		// Bail if no autosize instances exist.
		if (
			$('.wpzinc-autosize-js', $(social_post_flow.status_form)).length ===
			0
		) {
			return;
		}

		autosize.destroy($('.wpzinc-autosize-js'));
		autosize($('.wpzinc-autosize-js'));
	})(jQuery);
}

/**
 * Reinitializes autocomplete instances
 *
 * @since 	1.0.0
 */
function socialPostFlowReinitAutocomplete() {
	wp_zinc_autocomplete_destroy();
	wp_zinc_autocomplete_setup();
	wp_zinc_autocomplete_initialize();
}

/**
 * Reinitializes status tag instances
 *
 * @since 	1.0.0
 */
function socialPostFlowInitTags() {
	(function ($) {
		// Bail if no tag instances exist.
		if ($('select.tags', $(social_post_flow.status_form)).length === 0) {
			return;
		}

		$('select.tags', $(social_post_flow.status_form)).each(function () {
			$(this).on('change.social-post-flow', function () {
				// Insert tag into required textarea.
				let tag = $(this).val();
				const textarea = $(this).attr('data-textarea');
				const option = $('option:selected', $(this));
				const status = $(this).closest('#social-post-flow-status-form');
				const sel = $(textarea, $(status));
				const val = $(sel).val();

				// If the selected option contains data attributes, we need to show a prompt to fetch an input
				// before inserting the tag.
				if (typeof $(option).data('question') !== 'undefined') {
					// Prompt question.
					let tag_replacement = prompt(
						$(option).data('question'),
						$(option).data('default-value')
					);

					// If no answer was given, use the default.
					if (tag_replacement.length === 0) {
						tag_replacement = $(option).data('default-value');
					}

					// Replace the replacement string with the input.
					tag = tag.replace(
						$(option).data('replace'),
						tag_replacement
					);
				}

				// Get position of cursor.
				const pos = $(sel)[0].selectionStart;

				// Pad tag if cursor not at start and the character immediately preceding and/or following
				// the cursor isn't a space.
				if (pos > 0) {
					if (val.substring(pos - 1, pos) !== ' ') {
						tag = ' ' + tag;
					}
					if (val.substring(pos, pos + 1) !== ' ') {
						tag = tag + ' ';
					}
				}

				// Insert tag if it has a value.
				if (tag.trim().length > 0) {
					$(sel)
						.val(val.substring(0, pos) + tag + val.substring(pos))
						.trigger('change');
				}

				// Reset tag selector (if we don't, selecting the same option twice results in the tag not being inserted
				// the second time).
				$(this).val('');
			});
		});
	})(jQuery);
}

/**
 * Initializes selectize instances
 *
 * @since 	1.0.0
 *
 * @param {string} selector     Initialize .wpzinc-selectize instances within the given DOM selector.
 * @param {number} profile_id   Profile ID.
 * @param {string} action       Action (publish,update,repost,bulk_publish).
 * @param {number} status_index Zero based index of this status relative to all statuses for the Profile ID and Action.
 */
function socialPostFlowInitSelectize(
	selector,
	profile_id,
	action,
	status_index
) {
	(function ($) {
		$('.wpzinc-selectize', $(selector)).each(function () {
			const field_id = $(this).attr('id');
			let statuses_container = false,
				row = false,
				options = {},
				selectize_options = [];

			// If we're initializing selectize on a status, fetch the status which contains JSON to prepopulate existing values
			// for this selectize instance.
			if (profile_id && action) {
				statuses_container = $(
					'div.statuses[data-profile-id="' +
						profile_id +
						'"][data-action="' +
						action +
						'"]'
				);
				row = $(
					'tr[data-status-index="' + status_index + '"]',
					$(statuses_container)
				);
				options = JSON.parse($(row).attr('data-labels'));
				selectize_options =
					typeof options[field_id] !== typeof undefined
						? options[field_id]
						: [];
			}

			// Init selectize.
			$(this).selectize({
				valueField: 'id',
				labelField: 'text',
				searchField: 'text',
				plugins: ['drag_drop', 'remove_button'],
				options: selectize_options,
				delimiter: ',',
				persist: false,
				create: false,
				load(query, callback) {
					// Bail if the number of characters typed isn't enough.
					if (!query.length || query.length < 3) {
						return callback();
					}

					// Perform AJAX request.
					$.ajax({
						url: ajaxurl,
						data: {
							action: this.$input.data('action'),
							taxonomy: this.$input.data('taxonomy'),
							nonce: social_post_flow[
								this.$input.data('nonce-key')
							],
							q: query,
							page: 10,
						},
						error() {
							callback();
						},
						success(result) {
							callback(result.data);
						},
					});
				},
				onChange() {
					// If we're editing a status, assign a JSON string of this selectize instance's
					// IDs and values back to the status row.
					if (row) {
						// Build array of labels that can be used if we reinit this selectize instance on
						// this field again.
						const labels = [],
							length = this.items.length;
						for (i = 0; i < length; i++) {
							labels.push({
								id: this.options[this.items[i]].id,
								text: this.options[this.items[i]].text,
							});
						}

						// Add to options object and inject back into the data-labels status row.
						options = JSON.parse($(row).attr('data-labels'));
						options[field_id] = labels;
						$(row)
							.data('labels', JSON.stringify(options))
							.attr('data-labels', JSON.stringify(options));
					}
				},
			});
		});
	})(jQuery);
}

/**
 * Destroys selectize instances
 *
 * @since 	1.0.0
 *
 * @param {string} selector Destroy .wpzinc-selectize instances within the given DOM selector.
 */
function socialPostFlowDestroySelectize(selector) {
	(function ($) {
		$('.wpzinc-selectize', $(selector)).each(function () {
			if (this.selectize) {
				this.selectize.destroy();
			}
		});
	})(jQuery);
}

/**
 * Reindexes statuses
 *
 * @since 	1.0.0
 *
 * @param {string} statuses_container Profile and Action Statuses Container, containing the statuses to reindex.
 */
function socialPostFlowReindexStatuses(statuses_container) {
	(function ($) {
		// Find all sortable options in the status container (these are individual statuses)
		// and reindex them from 1.
		$('tr.sortable', $(statuses_container)).each(function (i) {
			// Update data-status-index, zero based.
			$(this).data('status-index', i).attr('data-status-index', i);

			// Display the index number, zero + 1 based.
			$('td.count ', $(this)).html('#' + (i + 1));

			// Set 'first' class.
			if (i === 0) {
				$(this).addClass('first');
			} else {
				$(this).removeClass('first');
			}
		});
	})(jQuery);
}

/**
 * Show/hide first comment option based on the chosen profile provider
 *
 * @since 	1.3.2
 *
 * @param {Object} profile Profile.
 */
function socialPostFlowUpdateFirstCommentOption(profile) {
	(function ($) {
		// Show the first comment option.
		$('div.first-comment', $(social_post_flow.status_form)).show();

		// If no profile is provided, we're on the Default tab. Bail.
		if (!profile) {
			return;
		}

		// Hide the first comment option if the profile provider is not supported.
		switch (profile.provider) {
			case 'mastodon':
			case 'tiktok':
			case 'telegram':
			case 'google':
				// First comment is not supported for these services.
				$('div.first-comment', $(social_post_flow.status_form)).hide();
				break;
		}
	})(jQuery);
}

/**
 * Show/hide schedule options based on the chosen schedule
 *
 * @since 	1.0.0
 *
 * @param {string} action Action (publish,update,repost,bulk_publish)
 */
function socialPostFlowUpdateScheduleOptions(action) {
	(function ($) {
		// Bail if no schedule dropdowns exist.
		if (
			$('select.schedule', $(social_post_flow.status_form)).length === 0
		) {
			return;
		}

		// Show / hide schedule options.
		switch ($('select.schedule', $(social_post_flow.status_form)).val()) {
			case 'custom':
				$('div.schedule', $(social_post_flow.status_form)).show();
				$('span.schedule', $(social_post_flow.status_form)).show();
				$(
					'span.hours_mins_secs',
					$(social_post_flow.status_form)
				).show();
				$('span.relative', $(social_post_flow.status_form)).hide();
				$('span.custom', $(social_post_flow.status_form))
					.text('after ' + action)
					.show();
				$('span.custom_field', $(social_post_flow.status_form)).hide();
				$(
					'span.the_events_calendar',
					$(social_post_flow.status_form)
				).hide();
				$(
					'span.events_manager',
					$(social_post_flow.status_form)
				).hide();
				$(
					'span.modern_events_calendar',
					$(social_post_flow.status_form)
				).hide();
				$('span.specific', $(social_post_flow.status_form)).hide();
				break;

			case 'custom_relative':
				$('div.schedule', $(social_post_flow.status_form)).show();
				$('span.schedule', $(social_post_flow.status_form)).show();
				$(
					'span.hours_mins_secs',
					$(social_post_flow.status_form)
				).hide();
				$('span.relative', $(social_post_flow.status_form)).show();
				$('span.custom', $(social_post_flow.status_form))
					.text('after ' + action)
					.show();
				$('span.custom_field', $(social_post_flow.status_form)).hide();
				$(
					'span.the_events_calendar',
					$(social_post_flow.status_form)
				).hide();
				$(
					'span.events_manager',
					$(social_post_flow.status_form)
				).hide();
				$(
					'span.modern_events_calendar',
					$(social_post_flow.status_form)
				).hide();
				$('span.specific', $(social_post_flow.status_form)).hide();
				break;

			case 'custom_field':
				$('div.schedule', $(social_post_flow.status_form)).show();
				$('span.schedule', $(social_post_flow.status_form)).show();
				$(
					'span.hours_mins_secs',
					$(social_post_flow.status_form)
				).show();
				$('span.relative', $(social_post_flow.status_form)).hide();
				$('span.custom', $(social_post_flow.status_form))
					.text('')
					.hide();
				$('span.custom_field', $(social_post_flow.status_form)).show();
				$(
					'span.the_events_calendar',
					$(social_post_flow.status_form)
				).hide();
				$(
					'span.events_manager',
					$(social_post_flow.status_form)
				).hide();
				$(
					'span.modern_events_calendar',
					$(social_post_flow.status_form)
				).hide();
				$('span.specific', $(social_post_flow.status_form)).hide();
				break;

			case '_EventStartDate':
			case '_EventEndDate':
				$('div.schedule', $(social_post_flow.status_form)).show();
				$('span.schedule', $(social_post_flow.status_form)).show();
				$(
					'span.hours_mins_secs',
					$(social_post_flow.status_form)
				).show();
				$('span.relative', $(social_post_flow.status_form)).hide();
				$('span.custom', $(social_post_flow.status_form))
					.text('')
					.hide();
				$('span.custom_field', $(social_post_flow.status_form)).hide();
				$(
					'span.the_events_calendar',
					$(social_post_flow.status_form)
				).show();
				$(
					'span.events_manager',
					$(social_post_flow.status_form)
				).hide();
				$(
					'span.modern_events_calendar',
					$(social_post_flow.status_form)
				).hide();
				$('span.specific', $(social_post_flow.status_form)).hide();
				break;

			case '_event_start_date':
			case '_event_end_date':
				$('div.schedule', $(social_post_flow.status_form)).show();
				$('span.schedule', $(social_post_flow.status_form)).show();
				$(
					'span.hours_mins_secs',
					$(social_post_flow.status_form)
				).show();
				$('span.relative', $(social_post_flow.status_form)).hide();
				$('span.custom', $(social_post_flow.status_form))
					.text('')
					.hide();
				$('span.custom_field', $(social_post_flow.status_form)).hide();
				$(
					'span.the_events_calendar',
					$(social_post_flow.status_form)
				).hide();
				$(
					'span.events_manager',
					$(social_post_flow.status_form)
				).show();
				$(
					'span.modern_events_calendar',
					$(social_post_flow.status_form)
				).hide();
				$('span.specific', $(social_post_flow.status_form)).hide();
				break;

			case 'mec_start_datetime':
			case 'mec_end_datetime':
				$('div.schedule', $(social_post_flow.status_form)).show();
				$('span.schedule', $(social_post_flow.status_form)).show();
				$(
					'span.hours_mins_secs',
					$(social_post_flow.status_form)
				).show();
				$('span.relative', $(social_post_flow.status_form)).hide();
				$('span.custom', $(social_post_flow.status_form))
					.text('')
					.hide();
				$('span.custom_field', $(social_post_flow.status_form)).hide();
				$(
					'span.the_events_calendar',
					$(social_post_flow.status_form)
				).hide();
				$(
					'span.events_manager',
					$(social_post_flow.status_form)
				).hide();
				$(
					'span.modern_events_calendar',
					$(social_post_flow.status_form)
				).show();
				$('span.specific', $(social_post_flow.status_form)).hide();
				break;

			case 'specific':
				$('div.schedule', $(social_post_flow.status_form)).show();
				$('span.schedule', $(social_post_flow.status_form)).show();
				$(
					'span.hours_mins_secs',
					$(social_post_flow.status_form)
				).hide();
				$('span.relative', $(social_post_flow.status_form)).hide();
				$('span.custom', $(social_post_flow.status_form))
					.text('')
					.hide();
				$('span.custom_field', $(social_post_flow.status_form)).hide();
				$(
					'span.the_events_calendar',
					$(social_post_flow.status_form)
				).hide();
				$(
					'span.events_manager',
					$(social_post_flow.status_form)
				).hide();
				$(
					'span.modern_events_calendar',
					$(social_post_flow.status_form)
				).hide();
				$('span.specific', $(social_post_flow.status_form)).show();
				break;

			default:
				// Hide additonal schedule options.
				$('div.schedule', $(social_post_flow.status_form)).hide();
				$('span.schedule', $(social_post_flow.status_form)).hide();
				$(
					'span.hours_mins_secs',
					$(social_post_flow.status_form)
				).hide();
				$('span.relative', $(social_post_flow.status_form)).hide();
				$('span.custom', $(social_post_flow.status_form))
					.text('')
					.hide();
				$('span.custom_field', $(social_post_flow.status_form)).hide();
				$(
					'span.the_events_calendar',
					$(social_post_flow.status_form)
				).hide();
				$(
					'span.events_manager',
					$(social_post_flow.status_form)
				).hide();
				$(
					'span.modern_events_calendar',
					$(social_post_flow.status_form)
				).hide();
				$('span.specific', $(social_post_flow.status_form)).hide();
				break;
		}
	})(jQuery);
}

/**
 * Show/hide text to image options based on the chosen image option
 *
 * @since 	1.0.0
 */
function socialPostFlowUpdateImageOptions() {
	(function ($) {
		// Bail if no image dropdowns exist.
		if ($('select.image', $(social_post_flow.status_form)).length === 0) {
			return;
		}

		// Get selected image option.
		const selected_image_option = $(
			'select.image',
			$(social_post_flow.status_form)
		).val();

		// Hide additional images, limit and text to image options.
		$(
			'tr.additional-images, tr.additional-images-limit, tr.text-to-image',
			$(social_post_flow.status_form)
		).hide();

		switch (selected_image_option) {
			case 'featured_image':
				$(
					'tr.additional-images',
					$(social_post_flow.status_form)
				).show();
				$(
					'tr.additional-images-limit',
					$(social_post_flow.status_form)
				).show();
				break;
			case 'text_to_image':
				$('tr.text-to-image', $(social_post_flow.status_form)).show();
				break;
		}

		// Enable options based on the Post Type.
		const post_type = $(
			'select.post_type',
			$(social_post_flow.status_form)
		).val();
		switch (post_type) {
			case 'image':
				// Enable all options in Additional Images.
				$(
					'option',
					$(
						'select.additional-images',
						$(social_post_flow.status_form)
					)
				).attr('disabled', false);
				break;

			case 'story':
			case 'pin':
			case 'google':
				// Disable all options in Additional Images.
				$(
					'option',
					$(
						'select.additional-images',
						$(social_post_flow.status_form)
					)
				).attr('disabled', true);

				// Enable the "None" Additional Images option.
				$(
					'option[value=""]',
					$(
						'select.additional-images',
						$(social_post_flow.status_form)
					)
				).attr('disabled', false);

				// Hide the row.
				$(
					'tr.additional-images',
					$(social_post_flow.status_form)
				).hide();
				$(
					'tr.additional-images-limit',
					$(social_post_flow.status_form)
				).hide();
				break;
		}
	})(jQuery);
}

function socialPostFlowUpdateAdditionalImagesLimitOption() {
	(function ($) {
		// Hide Limit option.
		$('tr.additional-images-limit', $(social_post_flow.status_form)).hide();

		// Show Limit option if Additional Images has more than one option that is enabled
		// i.e. "None" and other option(s) enabled by socialPostFlowUpdateImageOptions().
		if (
			$(
				'option:enabled',
				$('select.additional-images', $(social_post_flow.status_form))
			).length > 1
		) {
			$(
				'tr.additional-images-limit',
				$(social_post_flow.status_form)
			).show();
		}
	})(jQuery);
}

/**
 * Update post type options based on the profile provider.
 *
 * @since 	1.0.0
 *
 * @param {Object} profile Profile.
 */
function socialPostFlowUpdatePostTypeOptions(profile) {
	(function ($) {
		// Disable all options.
		$(
			'option',
			$('select.post_type', $(social_post_flow.status_form))
		).attr('disabled', true);

		// Enable options based on the profile provider.
		switch (profile.provider) {
			case 'instagram':
				$(
					'option[value="image"]',
					$('select.post_type', $(social_post_flow.status_form))
				).attr('disabled', false);
				$(
					'option[value="story"]',
					$('select.post_type', $(social_post_flow.status_form))
				).attr('disabled', false);
				break;

			case 'pinterest':
				$(
					'option[value="pin"]',
					$('select.post_type', $(social_post_flow.status_form))
				).attr('disabled', false);
				break;

			case 'google':
				$(
					'option[value="text"]',
					$('select.post_type', $(social_post_flow.status_form))
				).attr('disabled', false);
				$(
					'option[value="link"]',
					$('select.post_type', $(social_post_flow.status_form))
				).attr('disabled', false);
				$(
					'option[value="image"]',
					$('select.post_type', $(social_post_flow.status_form))
				).attr('disabled', false);
				$(
					'option[value="google"]',
					$('select.post_type', $(social_post_flow.status_form))
				).attr('disabled', false);
				break;

			default:
				$(
					'option[value="text"]',
					$('select.post_type', $(social_post_flow.status_form))
				).attr('disabled', false);
				$(
					'option[value="link"]',
					$('select.post_type', $(social_post_flow.status_form))
				).attr('disabled', false);
				$(
					'option[value="image"]',
					$('select.post_type', $(social_post_flow.status_form))
				).attr('disabled', false);
				break;
		}

		// If the current selected value is now disabled, set it to the first enabled value.
		if (
			$('select.post_type', $(social_post_flow.status_form)).val() ===
			'disabled'
		) {
			$('select.post_type', $(social_post_flow.status_form)).val(
				$(
					'option:enabled',
					$('select.post_type', $(social_post_flow.status_form))
				)
					.first()
					.val()
			);
		}
	})(jQuery);
}

/**
 * Show/hide status options based on the chosen post type
 *
 * @since 	1.0.0
 */
function socialPostFlowUpdateStatusSections() {
	(function ($) {
		// Hide all sections.
		$('.link', $(social_post_flow.status_form)).hide();
		$('.images', $(social_post_flow.status_form)).hide();

		// Show sections based on the chosen status post type.
		switch ($('select.post_type', $(social_post_flow.status_form)).val()) {
			case 'link':
				$('.link', $(social_post_flow.status_form)).show();
				$('.images', $(social_post_flow.status_form)).hide();
				break;

			case 'image':
			case 'story':
				$('.link', $(social_post_flow.status_form)).hide();
				$('.images', $(social_post_flow.status_form)).show();
				break;

			case 'pin':
			case 'google':
				$('.link', $(social_post_flow.status_form)).show();
				$('.images', $(social_post_flow.status_form)).show();
				break;
		}
	})(jQuery);
}

/**
 * Adds a status for the given profile ID and action, by duplicating
 * the last status.
 *
 * @since 	1.0.0
 *
 * @param {string} profile_id Profile ID.
 * @param {string} action     Action (publish,update,repost,bulk_publish).
 */
function socialPostFlowAddStatus(profile_id, action) {
	(function ($) {
		// Get last status row.
		// Get last status and container.
		const statuses_container = $(
				'div.statuses[data-profile-id="' +
					profile_id +
					'"][data-action="' +
					action +
					'"]'
			),
			statuses_table_body = $('tbody', $(statuses_container)),
			last_status = $('tr.status', $(statuses_table_body)).last();

		// Clone status to new row in table.
		$(statuses_table_body).first().after($(last_status).clone());

		// Reindex statuses.
		socialPostFlowReindexStatuses(statuses_container);

		// Populate hidden field with all statuses' data.
		socialPostFlowUpdateStatuses();
	})(jQuery);
}

/**
 * Displays an inline form for editing a given status, populating
 * the form's values.
 *
 * @since 	1.0.0
 *
 * @param {string} profile_id   Profile ID.
 * @param {Object} profile      Profile (from API).
 * @param {string} action       Action (publish,update,repost,bulk_publish).
 * @param {number} status_index Zero based index of this status relative to all statuses for the Profile ID and Action.
 * @param {Object} status       Status.
 */
function socialPostFlowEditStatus(
	profile_id,
	profile,
	action,
	status_index,
	status
) {
	// Destroy selectize.
	socialPostFlowDestroySelectize(social_post_flow.status_form);

	// Populate form values.
	socialPostFlowPopulateStatusForm(
		profile_id,
		profile,
		action,
		status_index,
		status
	);

	// Update post type options.
	socialPostFlowUpdatePostTypeOptions(profile);

	// Update first comment option.
	socialPostFlowUpdateFirstCommentOption(profile);

	// Update schedule options.
	socialPostFlowUpdateScheduleOptions(action);

	// Update image options.
	socialPostFlowUpdateImageOptions();

	// Update additional images limit option.
	socialPostFlowUpdateAdditionalImagesLimitOption();

	// Update status sections to display, depending on the status' post type.
	socialPostFlowUpdateStatusSections();

	// Display form.
	socialPostFlowDisplayStatusForm(profile_id, action, status_index);

	// Reinit autosize.
	socialPostFlowReinitAutosize();

	// Reinit autocomplete.
	socialPostFlowReinitAutocomplete();

	// Init selectize.
	socialPostFlowInitSelectize(
		social_post_flow.status_form,
		profile_id,
		action,
		status_index
	);
}

/**
 * Deletes a status for the given profile ID, action and index.
 *
 * @since 	1.0.0
 *
 * @param {string} profile_id   Profile ID.
 * @param {string} action       Action (publish,update,repost,bulk_publish).
 * @param {number} status_index Zero based index of this status relative to all statuses for the Profile ID and Action.
 */
function socialPostFlowDeleteStatus(profile_id, action, status_index) {
	(function ($) {
		// Confirm deletion.
		const result = confirm(social_post_flow.delete_status_message);
		if (!result) {
			return;
		}

		// Get status and container.
		const statuses_container = $(
				'div.statuses[data-profile-id="' +
					profile_id +
					'"][data-action="' +
					action +
					'"]'
			),
			row = $(
				'tr[data-status-index="' + status_index + '"]',
				$(statuses_container)
			);

		// Delete status.
		$(row).remove();

		// Reindex statuses.
		socialPostFlowReindexStatuses(statuses_container);

		// Populate hidden field with all statuses' data.
		socialPostFlowUpdateStatuses();
	})(jQuery);
}

/**
 * Saves the status form values back to the status as a JSON data object.
 *
 * @since 	1.0.0
 *
 * @param {string} profile_id   Profile ID.
 * @param {string} action       Action (publish,update,repost,bulk_publish).
 * @param {number} status_index Zero based index of this status relative to all statuses for the Profile ID and Action.
 */
function socialPostFlowUpdateStatus(profile_id, action, status_index) {
	(function ($) {
		// Get row.
		const row = $(
			'div.statuses[data-profile-id="' +
				profile_id +
				'"][data-action="' +
				action +
				'"] tr[data-status-index="' +
				status_index +
				'"]'
		);
		let status_custom_fields_index = -1;
		let status_authors_custom_fields_index = -1;
		const status = JSON.parse($(row).attr('data-status'));

		// Reset some status elements.
		status.conditions = {};
		status.custom_fields = {};
		status.authors_custom_fields = {};
		status.terms = {};

		// Iterate through the status form, building the status object.
		$.each(
			$(social_post_flow.status_form)
				.find('input, select, textarea')
				.serializeArray(),
			function (index, field) {
				// Remove prepended Plugin Name from Field Name.
				field.name = field.name.replace(
					social_post_flow.plugin_name + '_',
					''
				);

				switch (field.name) {
					/**
					 * Conditions: Custom Fields
					 */
					case 'custom_fields[key][]':
						status_custom_fields_index++;

						// Ignore if no key specified.
						if (field.value === '') {
							break;
						}

						status.custom_fields[status_custom_fields_index] = {};
						status.custom_fields[status_custom_fields_index].key =
							field.value;
						break;
					case 'custom_fields[compare][]':
						// Ignore if no key specified for this Custom Field Condition.
						if (
							typeof status.custom_fields[
								status_custom_fields_index
							] === typeof undefined
						) {
							break;
						}

						status.custom_fields[
							status_custom_fields_index
						].compare = field.value;
						break;
					case 'custom_fields[value][]':
						// Ignore if no key specified for this Custom Field Condition.
						if (
							typeof status.custom_fields[
								status_custom_fields_index
							] === typeof undefined
						) {
							break;
						}

						status.custom_fields[status_custom_fields_index].value =
							field.value;
						break;

					/**
					 * Authors
					 */
					case 'authors':
						if (field.value === 'false') {
							break;
						}

						status[field.name] = field.value.split(',');
						break;

					/**
					 * Authors Roles
					 */
					case 'authors_roles':
						if (field.value === 'false') {
							break;
						}

						status[field.name] = field.value.split(',');
						break;

					/**
					 * Conditions: Authors Custom Fields
					 */
					case 'authors_custom_fields[key][]':
						status_authors_custom_fields_index++;

						// Ignore if no key specified.
						if (field.value === '') {
							break;
						}

						status.authors_custom_fields[
							status_authors_custom_fields_index
						] = {};
						status.authors_custom_fields[
							status_authors_custom_fields_index
						].key = field.value;
						break;
					case 'authors_custom_fields[compare][]':
						// Ignore if no key specified for this Custom Field Condition.
						if (
							typeof status.authors_custom_fields[
								status_authors_custom_fields_index
							] === typeof undefined
						) {
							break;
						}

						status.authors_custom_fields[
							status_authors_custom_fields_index
						].compare = field.value;
						break;
					case 'authors_custom_fields[value][]':
						// Ignore if no key specified for this Custom Field Condition.
						if (
							typeof status.authors_custom_fields[
								status_authors_custom_fields_index
							] === typeof undefined
						) {
							break;
						}

						status.authors_custom_fields[
							status_authors_custom_fields_index
						].value = field.value;
						break;

					/**
					 * Other Fields
					 */
					default:
						/**
						 * Conditions: Taxonomy Conditions
						 */
						const taxonomy = field.name.match(
							/conditions\[([^)]+)\]/
						);
						if (taxonomy) {
							status.conditions[taxonomy[1]] = field.value;
							break;
						}

						/**
						 * Conditions: Terms
						 */
						const term = field.name.match(/terms\[([^)]+)\]/);
						if (term) {
							status.terms[term[1]] = field.value.split(',');
							break;
						}

						// Cast booleans.
						if (field.value === 'true') {
							field.value = true;
						}
						if (field.value === 'false') {
							field.value = false;
						}

						/**
						 * Handle array fields.
						 */
						const matches = field.name.match(/(.*?)\[(.*?)\]/);
						if (matches !== null) {
							status[matches[1]][matches[2]] = field.value;
						} else {
							status[field.name] = field.value;
						}
						break;
				}
			}
		);

		// Assign JSON string to data-status of the row.
		$(row)
			.data('status', JSON.stringify(status))
			.attr('data-status', JSON.stringify(status));

		// Populate hidden field with all statuses' data.
		socialPostFlowUpdateStatuses();

		// Fetch row cells data via AJAX.
		$.ajax({
			url: ajaxurl,
			type: 'POST',
			async: true,
			data: {
				action: social_post_flow.get_status_row_action,
				nonce: social_post_flow.get_status_row_nonce,
				post_type: social_post_flow.post_type,
				post_action: action,
				status: JSON.stringify(status),
			},
			error(xhr) {
				alert(
					'socialPostFlowUpdateStatus(): error: ' +
						xhr.status +
						' ' +
						xhr.statusText
				);
			},
			success(result) {
				// Bail if an error occured.
				if (!result.success) {
					alert(result.data);
				}

				// Post Type.
				$('td.post_type', $(row)).text(result.data.post_type);

				// Message.
				$('td.text', $(row)).text(result.data.text);

				// Schedule.
				$('td.schedule', $(row)).text(result.data.schedule);
			},
		});
	})(jQuery);
}

/**
 * Populates values in the status form for the given status
 *
 * @since 	1.0.0
 *
 * @param {string} profile_id   Profile ID.
 * @param {Object} profile      Profile (from API).
 * @param {string} action       Action (publish,update,repost,bulk_publish).
 * @param {number} status_index Zero based index of this status relative to all statuses for the Profile ID and Action.
 * @param {Object} status       Status.
 */
function socialPostFlowPopulateStatusForm(
	profile_id,
	profile,
	action,
	status_index,
	status
) {
	(function ($) {
		// Iterate through form fields.
		$('input, select, textarea', $(social_post_flow.status_form)).each(
			function () {
				let field = $(this).attr('name');

				// Skip if the field doesn't have a name, as it doesn't need to be populated.
				if (typeof field === 'undefined') {
					return;
				}

				// Remove prepended Plugin Name from Field Name.
				field = field.replace(social_post_flow.plugin_name + '_', '');

				// Depending on the attribute, populate the field.
				switch (field) {
					/**
					 * Text to Image
					 * If a Background Image is selected in text_to_image_background_image, populate the <img src=""> attribute.
					 */
					case 'text_to_image_background_image':
						if (typeof status[field] !== 'undefined') {
							$(this).val(status[field]);
						} else {
							$(this).val('');
						}
						break;
					case 'text_to_image_background_image_url':
						if (
							typeof status.text_to_image_background_image_url !==
							'undefined'
						) {
							$(this).val(status[field]);
							$(
								'.wpzinc-media-library-insert img',
								$(social_post_flow.status_form)
							).attr(
								'src',
								status.text_to_image_background_image_url
							);
						} else {
							$(this).val('');
							$(
								'.wpzinc-media-library-insert img',
								$(social_post_flow.status_form)
							).attr('src', '');
						}
						break;

					/**
					 * Conditions: Custom Fields
					 */
					case 'custom_fields[key][]':
						// Display Custom Field Rows.
						for (const custom_field_index in status.custom_fields) {
							$(
								'input[name="' +
									social_post_flow.plugin_name +
									'_custom_fields[key][]"]',
								$(social_post_flow.status_form)
							)
								.last()
								.val(
									status.custom_fields[custom_field_index].key
								);
							$(
								'select[name="' +
									social_post_flow.plugin_name +
									'_custom_fields[compare][]"]',
								$(social_post_flow.status_form)
							)
								.last()
								.val(
									status.custom_fields[custom_field_index]
										.compare
								);
							$(
								'input[name="' +
									social_post_flow.plugin_name +
									'_custom_fields[value][]"]',
								$(social_post_flow.status_form)
							)
								.last()
								.val(
									status.custom_fields[custom_field_index]
										.value
								);

							// Add New Table Row for the next Custom Field Condition, if there are additional
							// Custom Field Conditions to display.
							wpzinc_table_row_add(
								'custom-field',
								$(this).closest('table')
							);
						}
						break;
					case 'custom_fields[compare][]':
					case 'custom_fields[value][]':
						// Ignore, as we've already populated the Custom Field Conditions above.
						break;

					/**
					 * Authors
					 */
					case 'authors':
						if (!status[field]) {
							break;
						}

						$('input[name="' + $(this).attr('name') + '"]').val(
							status[field].join(',')
						);
						break;
					case 'authors_compare':
						if (!status[field]) {
							break;
						}

						$('select[name="' + $(this).attr('name') + '"]').val(
							status[field]
						);
						break;

					/**
					 * Authors Roles
					 */
					case 'authors_roles':
						if (!status[field]) {
							break;
						}

						$('input[name="' + $(this).attr('name') + '"]').val(
							status[field].join(',')
						);
						break;
					case 'authors_roles_compare':
						if (!status[field]) {
							break;
						}

						$('select[name="' + $(this).attr('name') + '"]').val(
							status[field]
						);
						break;

					/**
					 * Conditions: Custom Fields
					 */
					case 'authors_custom_fields[key][]':
						// Display Custom Field Rows.
						for (const authors_custom_field_index in status.authors_custom_fields) {
							$(
								'input[name="' +
									social_post_flow.plugin_name +
									'_authors_custom_fields[key][]"]',
								$(social_post_flow.status_form)
							)
								.last()
								.val(
									status.authors_custom_fields[
										authors_custom_field_index
									].key
								);
							$(
								'select[name="' +
									social_post_flow.plugin_name +
									'_authors_custom_fields[compare][]"]',
								$(social_post_flow.status_form)
							)
								.last()
								.val(
									status.authors_custom_fields[
										authors_custom_field_index
									].compare
								);
							$(
								'input[name="' +
									social_post_flow.plugin_name +
									'_authors_custom_fields[value][]"]',
								$(social_post_flow.status_form)
							)
								.last()
								.val(
									status.authors_custom_fields[
										authors_custom_field_index
									].value
								);

							// Add New Table Row for the next Custom Field Condition, if there are additional
							// Custom Field Conditions to display.
							wpzinc_table_row_add(
								'authors-custom-field',
								$(this).closest('table')
							);
						}
						break;
					case 'authors_custom_fields[compare][]':
					case 'authors_custom_fields[value][]':
						// Ignore, as we've already populated the Custom Field Conditions above.
						break;

					default:
						/**
						 * Conditions: Taxonomy Conditions
						 */
						const taxonomy = field.match(/conditions\[([^)]+)\]/);
						if (taxonomy) {
							if (
								typeof status.conditions[taxonomy[1]] !==
								typeof undefined
							) {
								$(this).val(status.conditions[taxonomy[1]]);
							}
							break;
						}

						/**
						 * Conditions: Terms
						 */
						const term = field.match(/terms\[([^)]+)\]/);
						if (term) {
							if (
								typeof status.terms[term[1]] !==
								typeof undefined
							) {
								$(this).val(status.terms[term[1]].join(','));
							}
							break;
						}

						/**
						 * Array fields.
						 */
						const matches = field.match(/(.*?)\[(.*?)\]/);
						if (matches !== null) {
							$(this).val(status[matches[1]][matches[2]]);
							break;
						}

						/**
						 * Standard fields.
						 */
						$(this).val(status[field]);
						break;
				}
			}
		);

		// Add the profile id, action and status index to the form.
		$(social_post_flow.status_form)
			.data('profile-id', profile_id)
			.attr('data-profile-id', profile_id);
		$(social_post_flow.status_form)
			.data('action', action)
			.attr('data-action', action);
		$(social_post_flow.status_form)
			.data('status-index', status_index)
			.attr('data-status-index', status_index);

		// Re-initialize any conditionals for e.g. Media Library image selection.
		$('input, select', $(social_post_flow.status_form)).conditional();
	})(jQuery);
}

/**
 * Displays the status form
 *
 * @since 	1.0.0
 *
 * @param {string} profile_id   Profile ID.
 * @param {string} action       Action (publish,update,repost,bulk_publish).
 * @param {number} status_index Zero based index of this status relative to all statuses for the Profile ID and Action.
 */
function socialPostFlowDisplayStatusForm(profile_id, action, status_index) {
	(function ($) {
		// Get row.
		const row = $(
				'div.statuses[data-profile-id="' +
					profile_id +
					'"][data-action="' +
					action +
					'"] tr[data-status-index="' +
					status_index +
					'"]'
			),
			status_form_container_row = $(
				'div.statuses[data-profile-id="' +
					profile_id +
					'"][data-action="' +
					action +
					'"] tr.status-form-container'
			);

		// Move status form inside edit form row.
		$('td', status_form_container_row).append(
			$(social_post_flow.status_form).removeClass('hidden')
		);

		// Move edit form row to immediately below the status row we're editing.
		$(row).after($(status_form_container_row).removeClass('hidden'));
	})(jQuery);
}

/**
 * Saves the contents of the status form, and then hides the status form
 *
 * @since 	1.0.0
 */
function socialPostFlowSaveAndHideStatusForm() {
	(function ($) {
		// If the status form is visible, save its values now.
		if ($('div.statuses div.social-post-flow-status-form').length > 0) {
			socialPostFlowUpdateStatus(
				$(
					'div.statuses tr.status-form-container #social-post-flow-status-form'
				).data('profile-id'),
				$(
					'div.statuses tr.status-form-container #social-post-flow-status-form'
				).data('action'),
				$(
					'div.statuses tr.status-form-container #social-post-flow-status-form'
				).data('status-index')
			);
		}

		// Reset Profile ID, Action and Status on Form.
		$(social_post_flow.status_form)
			.data('profile-id', '')
			.attr('data-profile-id', '');
		$(social_post_flow.status_form)
			.data('action', '')
			.attr('data-action', '');
		$(social_post_flow.status_form)
			.data('status-index', '')
			.attr('data-status-index', '');

		// Move form into container.
		$(social_post_flow.status_form_container).append(
			$(social_post_flow.status_form)
		);
	})(jQuery);
}

/**
 * Clears all values from the status form
 *
 * @since 	1.0.0
 */
function socialPostFlowClearStatusForm() {
	(function ($) {
		// Clear all values.
		$('input, select, textarea', $(social_post_flow.status_form)).each(
			function () {
				$(this).val('');
			}
		);

		// Remove Custom Field Condition Rows.
		$(
			'tr.custom-field:not(.hide-delete-button)',
			$(social_post_flow.status_form)
		).each(function () {
			$(this).remove();
		});

		// Remove Author Custom Field Condition Rows.
		$(
			'tr.authors-custom-field:not(.hide-delete-button)',
			$(social_post_flow.status_form)
		).each(function () {
			$(this).remove();
		});
	})(jQuery);
}

/**
 * Returns a statuses object that can be saved against a Post Type
 * based on the current UI.
 *
 * @since 	1.0.0
 */
function socialPostFlowGetStatuses() {
	const statuses = {};

	(function ($) {
		// Iterate through each Profile.
		$('li.wpzinc-nav-tab a').each(function () {
			// Skip if the link doesn't contain #profile-.
			if ($(this).attr('href').indexOf('#profile-') === -1) {
				return;
			}

			const profile = $(this).attr('href').split('#profile-').pop();

			if (profile === 'default') {
				statuses[profile] = {};
			} else {
				statuses[profile] = {
					enabled: $(
						'input[name="' +
							social_post_flow.plugin_name +
							'[' +
							profile +
							'][enabled]"]'
					).is(':checked'),
				};

				// Determine override value, which can be in a checkbox (if the user can choose) or a hidden field (if we require override
				// for e.g. Pinterest).
				if (
					$(
						'input[type="checkbox"][name="' +
							social_post_flow.plugin_name +
							'[' +
							profile +
							'][override]"]'
					).length > 0
				) {
					statuses[profile].override = $(
						'input[type="checkbox"][name="' +
							social_post_flow.plugin_name +
							'[' +
							profile +
							'][override]"]'
					).is(':checked');
				} else {
					statuses[profile].override =
						$(
							'input[type="hidden"][name="' +
								social_post_flow.plugin_name +
								'[' +
								profile +
								'][override]"]'
						).val() === '1' ||
						$(
							'input[type="hidden"][name="' +
								social_post_flow.plugin_name +
								'[' +
								profile +
								'][override]"]'
						).val() === 1
							? true
							: false;
				}
			}

			// Iterate through each Profile Action.
			$('li.wpzinc-nav-tab-horizontal a', '#profile-' + profile).each(
				function () {
					const action = $(this)
						.attr('href')
						.split('#profile-' + profile + '-')
						.pop();

					statuses[profile][action] = {
						enabled: $(
							'input[name="' +
								social_post_flow.plugin_name +
								'[' +
								profile +
								'][' +
								action +
								'][enabled]"]'
						).is(':checked'),
						status: [],
					};

					// Fetch statuses for the Profile and Action.
					$('tr.status', '#profile-' + profile + '-' + action).each(
						function () {
							statuses[profile][action].status.push(
								JSON.parse($(this).attr('data-status'))
							);
						}
					);
				}
			);
		});
	})(jQuery);

	// Return.
	return statuses;
}

/**
 * Populates a hidden field with a JSON string of all statuses and their settings
 * for the Post Type
 *
 * @since 	1.0.0
 */
function socialPostFlowUpdateStatuses() {
	let statuses;

	(function ($) {
		// Get statuses.
		statuses = socialPostFlowGetStatuses();

		// Update hidden field.
		$(
			'input[name="' +
				social_post_flow.plugin_name +
				'[statuses]"][type="hidden"]'
		).val(JSON.stringify(statuses));
	})(jQuery);

	// Return statuses.
	return statuses;
}

/**
 * Saves all statuses and their settings for the Post Type
 *
 * @since 	1.0.0
 *
 * @param {string} post_type Post Type
 * @param {string} tab       Tab (auth, profiles-error, profiles-missing, post-type-error, post-type-missing, post-type-enabled, post-type-disabled).
 */
function socialPostFlowSaveStatuses(post_type, tab) {
	let statuses;

	(function ($) {
		// Get statuses.
		statuses = socialPostFlowGetStatuses();

		// Show modal and overlay.
		wpzinc_modal_open(social_post_flow.save_statuses_modal.title, '');

		// Send via AJAX.
		$.ajax({
			url: ajaxurl,
			type: 'POST',
			async: true,
			data: {
				action: social_post_flow.save_statuses_action,
				nonce: social_post_flow.save_statuses_nonce,
				post_type,
				statuses: JSON.stringify(statuses),
			},
			error(xhr) {
				wpzinc_modal_show_error_message_and_exit(
					'socialPostFlowSaveStatuses(): error: ' +
						xhr.status +
						' ' +
						xhr.statusText
				);
			},
			success(result) {
				if (!result.success) {
					wpzinc_modal_show_error_message_and_exit(result.data);
				}

				// Depending on whether settings are enabled for this Post Type, show/hide notices and ticks.
				if (tab.length) {
					if (result.data.post_type_enabled) {
						$(tab).addClass('enabled');
						$('.notice-warning').hide();
					} else {
						$(tab).removeClass('enabled');
						$('.notice-warning').show();
					}
				}

				// Show success message and close.
				wpzinc_modal_show_success_and_exit(
					social_post_flow.save_statuses_modal.title_success
				);
			},
		});
	})(jQuery);
}

/**
 * Saves all statuses and their settings for the Post
 *
 * @since 	1.0.0
 *
 * @param {number} post_id           Post ID.
 * @param {number} override          Override Setting.
 * @param {number} featured_image    Featured Image.
 * @param {Array}  additional_images Additonal Images.
 */
function socialPostFlowSavePostStatuses(
	post_id,
	override,
	featured_image,
	additional_images
) {
	let statuses;

	(function ($) {
		// Get statuses.
		statuses = socialPostFlowGetStatuses();

		// Show modal and overlay.
		wpzinc_modal_open(social_post_flow.save_statuses_modal.title, '');

		// Send via AJAX.
		$.ajax({
			url: ajaxurl,
			type: 'POST',
			async: true,
			data: {
				action: social_post_flow.save_statuses_action,
				nonce: social_post_flow.save_statuses_nonce,
				post_id,
				override,
				featured_image,
				additional_images,
				statuses: JSON.stringify(statuses),
			},
			error() {
				// Close modal and overlay.
				wpzinc_modal_close();
			},
			success(result) {
				if (!result.success) {
					wpzinc_modal_show_error_message_and_exit(result.data);
				}

				// Show success message and close.
				wpzinc_modal_show_success_and_exit(
					social_post_flow.save_statuses_modal.title_success
				);
			},
		});
	})(jQuery);
}

let socialPostFlowCharacterCounting = false;

/**
 * Character Count
 *
 * @since 	1.0.0
 *
 * @param {string} textarea Textarea to count characters in.
 */
function socialPostFlowCharacterCount(textarea) {
	(function ($) {
		// If we're currently running an AJAX request, don't run another one.
		if (socialPostFlowCharacterCounting) {
			return;
		}

		// Set a flag so we know we're performing an AJAX request.
		socialPostFlowCharacterCounting = true;

		// Send an AJAX request to fetch the parsed statuses and character counts for each status.
		$.post(
			social_post_flow.ajax,
			{
				action: social_post_flow.character_count_action,
				post_id: social_post_flow.post_id,
				status: $(textarea).val(),
				nonce: social_post_flow.character_count_nonce,
			},
			function (response) {
				$('span.character-count', $(textarea).parent()).text(
					response.data.character_count
				);

				// Reset the flag after a few seconds, so we don't flood the server with requests.
				setTimeout(function () {
					socialPostFlowCharacterCounting = false;
				}, 3000);
			}
		);
	})(jQuery);
}

/**
 * Enables the dialog to confirm the user wants to navigate away from the current screen,
 * as settings haven't been saved
 *
 * @since 	1.0.0
 */
function socialPostFlowEnableNotSavedPrompt() {
	// Don't do anything if we're not on the Settings screen.
	// This prevents the prompt wrongly displaying on the Post Edit screen.
	if (!social_post_flow.prompt_unsaved_changes) {
		return;
	}

	window.onbeforeunload = function () {
		return true;
	};
}

/**
 * Disables the dialog to confirm the user wants to navigate away from the current screen,
 * as settings haven't been saved.
 *
 * @since 	1.0.0
 */
function socialPostFlowDisableNotSavedPrompt() {
	window.onbeforeunload = null;
}

/**
 * Bind DOM Event Listeners to perform status tasks
 */
jQuery(document).ready(function ($) {
	// Tags dropdown.
	socialPostFlowInitTags();

	// Status sections.
	$(social_post_flow.status_form).on(
		'change.' + social_post_flow.status_form,
		'select.post_type',
		function () {
			socialPostFlowUpdateStatusSections();
			socialPostFlowUpdateImageOptions();
			socialPostFlowUpdateAdditionalImagesLimitOption();
		}
	);

	// Schedule dropdown.
	$(social_post_flow.status_form).on(
		'change.' + social_post_flow.status_form,
		'select.schedule',
		function () {
			socialPostFlowUpdateScheduleOptions(
				$(this).closest('div.statuses').data('action')
			);
		}
	);

	// Image dropdown.
	$(social_post_flow.status_form).on(
		'change.' + social_post_flow.status_form,
		'select.image',
		function () {
			socialPostFlowUpdateImageOptions();
		}
	);

	// Additional Images dropdown.
	$(social_post_flow.status_form).on(
		'change.' + social_post_flow.status_form,
		'select.additional-images',
		function () {
			socialPostFlowUpdateAdditionalImagesLimitOption();
		}
	);

	/**
	 * Enable/Disable Profile or Action
	 */
	$('input.enable', $('#profiles-container')).on('change', function () {
		// Get Tab related to this checkbox and the checkbox's Enabled state.
		const tab_href = $(this).data('tab'),
			enabled = $(this).prop('checked');

		if (enabled) {
			$('a[href="#' + tab_href + '"]').addClass('enabled');
		} else {
			$('a[href="#' + tab_href + '"]').removeClass('enabled');
		}

		socialPostFlowSaveAndHideStatusForm();

		socialPostFlowClearStatusForm();

		socialPostFlowUpdateStatuses();

		socialPostFlowEnableNotSavedPrompt();
	});

	/**
	 * Enable/Disable Override Defaults
	 */
	$('input.override', $('#profiles-container')).on('change', function () {
		socialPostFlowSaveAndHideStatusForm();

		socialPostFlowClearStatusForm();

		socialPostFlowUpdateStatuses();

		socialPostFlowEnableNotSavedPrompt();
	});

	/**
	 * Tab click
	 */
	$('.wpzinc-js-tabs').on('click', function () {
		socialPostFlowSaveAndHideStatusForm();

		socialPostFlowClearStatusForm();
	});

	/**
	 * Add Status Update
	 */
	$('#profiles-container').on('click', 'a.button.add-status', function (e) {
		e.preventDefault();

		socialPostFlowSaveAndHideStatusForm();

		socialPostFlowClearStatusForm();

		socialPostFlowAddStatus(
			$(this).closest('div.statuses').data('profile-id'),
			$(this).closest('div.statuses').data('action')
		);

		socialPostFlowEnableNotSavedPrompt();
	});

	/**
	 * Edit Status Update
	 */
	$('#profiles-container').on('click', 'a.edit-status', function (e) {
		e.preventDefault();

		socialPostFlowSaveAndHideStatusForm();

		socialPostFlowClearStatusForm();

		socialPostFlowEditStatus(
			$(this).closest('div.statuses').data('profile-id'),
			$(this).closest('div.statuses').data('profile'),
			$(this).closest('div.statuses').data('action'),
			$(this).closest('tr').data('status-index'),
			JSON.parse($(this).closest('tr').attr('data-status'))
		);

		socialPostFlowEnableNotSavedPrompt();
	});

	/**
	 * Editing Status Update
	 */
	$(social_post_flow.status_form).on(
		'change',
		'input, select, textarea',
		function () {
			socialPostFlowUpdateStatus(
				$(social_post_flow.status_form).data('profile-id'),
				$(social_post_flow.status_form).data('action'),
				$(social_post_flow.status_form).data('status-index')
			);

			socialPostFlowEnableNotSavedPrompt();
		}
	);

	$('body').on('wpzinc-media-library-attachment-added', function () {
		// Ignore Media Library events outside of the status form.
		if (
			typeof $(social_post_flow.status_form).data('profile-id') ===
			'undefined'
		) {
			return;
		}

		socialPostFlowUpdateStatus(
			$(social_post_flow.status_form).data('profile-id'),
			$(social_post_flow.status_form).data('action'),
			$(social_post_flow.status_form).data('status-index')
		);

		socialPostFlowEnableNotSavedPrompt();
	});

	$('body').on('wpzinc-table-row-delete', function () {
		socialPostFlowUpdateStatus(
			$(social_post_flow.status_form).data('profile-id'),
			$(social_post_flow.status_form).data('action'),
			$(social_post_flow.status_form).data('status-index')
		);

		socialPostFlowEnableNotSavedPrompt();
	});

	/**
	 * Delete Status Update
	 */
	$('#profiles-container').on('click', 'a.delete-status', function (e) {
		e.preventDefault();

		socialPostFlowSaveAndHideStatusForm();

		socialPostFlowClearStatusForm();

		socialPostFlowDeleteStatus(
			$(this).closest('div.statuses').data('profile-id'),
			$(this).closest('div.statuses').data('action'),
			$(this).closest('tr').data('status-index')
		);

		socialPostFlowEnableNotSavedPrompt();
	});

	/**
	 * Reorder Status Updates
	 */
	if ($('#profiles-container div.statuses').length > 0) {
		$('#profiles-container div.statuses').sortable({
			containment: 'parent',
			items: '.sortable',
			stop(e, ui) {
				// Get status and container.
				const status = $(ui.item),
					statuses_container = $(status).closest('div.statuses');

				// Reindex statuses.
				socialPostFlowReindexStatuses($(statuses_container));

				// Populate hidden field with all statuses' data.
				socialPostFlowUpdateStatuses();

				socialPostFlowEnableNotSavedPrompt();
			},
		});
	}

	/**
	 * Force focus on inputs, so they can be accessed on mobile.
	 * For some reason using jQuery UI sortable prevents us accessing textareas on mobile
	 * See http://bugs.jqueryui.com/ticket/4429
	 */
	$('#profiles-container div.statuses').bind(
		'click.sortable mousedown.sortable',
		function (e) {
			e.target.focus();
		}
	);

	/**
	 * Character Count Events
	 *
	 * @since 	1.0.0
	 */
	if (social_post_flow.post_id > 0) {
		$('textarea.text', $(social_post_flow.status_form)).on(
			'keyup change',
			function () {
				socialPostFlowCharacterCount(this);
			}
		);
	}

	/**
	 * Plugin Settings: Save Post Type Statuses via AJAX
	 */
	$('form#social-post-flow').on('submit', function (e) {
		// Don't submit form.
		e.preventDefault();

		// Disable the not saved prompt, as we're about to save.
		socialPostFlowDisableNotSavedPrompt();

		// Populate hidden field with all statuses' data.
		socialPostFlowUpdateStatuses();

		// Save Post Type statuses.
		socialPostFlowSaveStatuses(
			social_post_flow.post_type,
			$(
				'h2.nav-tab-wrapper a[data-post-type="' +
					social_post_flow.post_type +
					'"]'
			)
		);
	});

	/**
	 * Post Settings: Save Post Statuses via AJAX
	 */
	$('button.' + social_post_flow.plugin_name + '-save-post-statuses').on(
		'click',
		function (e) {
			// Don't submit form.
			e.preventDefault();

			// Disable the not saved prompt, as we're about to save.
			socialPostFlowDisableNotSavedPrompt();

			// Populate hidden field with all statuses' data.
			socialPostFlowUpdateStatuses();

			// Get Additional Images.
			const additional_images = [];
			for (i = 0; i <= 10; i++) {
				if (
					!$(
						'input[name="' +
							social_post_flow.plugin_name +
							'[additional_images][' +
							i +
							']"]'
					).length
				) {
					continue;
				}

				additional_images.push(
					$(
						'input[name="' +
							social_post_flow.plugin_name +
							'[additional_images][' +
							i +
							']"]'
					).val()
				);
			}

			// Save Post statuses.
			socialPostFlowSavePostStatuses(
				social_post_flow.post_id,
				$(
					'select[name="' +
						social_post_flow.plugin_name +
						'[override]"]'
				).val(),
				$(
					'input[name="' +
						social_post_flow.plugin_name +
						'[featured_image]"]'
				).val(),
				additional_images
			);
		}
	);
});
