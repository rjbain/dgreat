/**
 * @file
 * Defines JavaScript behaviors for the sitewide alert form.
 */

(function ($, Drupal) {
  /**
   * Behaviors for summaries for tabs in the sitewide alert edit form.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches summary behavior for tabs in the sitewide alert edit form.
   */
  Drupal.behaviors.sitewideAlertFormSummaries = {
    attach(context) {
      const $context = $(context);

      $context
        .find('.sitewide_alert-form-author')
        .drupalSetSummary((context) => {
          const $authorContext = $(context);
          const name = $authorContext.find('.field--name-user-id input').val();
          const date = $authorContext.find('.field--name-created input').val();

          if (name && date) {
            return Drupal.t('By @name on @date', {
              '@name': name,
              '@date': date
            });
          }
          if (name) {
            return Drupal.t('By @name', { '@name': name });
          }
          if (date) {
            return Drupal.t('Authored on @date', { '@date': date });
          }
        });

      $context
        .find('.sitewide-alert--form--page-visibility-options')
        .drupalSetSummary((context) => {
          const $visibilityOptionsContext = $(context);
          const limitedByPages = $visibilityOptionsContext
            .find('.js-form-item-limit-alert-by-pages input')
            .is(':checked');

          if (!limitedByPages) {
            return Drupal.t('All pages');
          }

          const negatedChecked = $visibilityOptionsContext
            .find('.field--name-limit-to-pages-negate input')
            .filter(':checked')[0];
          const negated = negatedChecked && negatedChecked.value === '1';

          if (limitedByPages && negated) {
            return Drupal.t('Restricted from certain pages');
          }

          if (limitedByPages && !negated) {
            return Drupal.t('Restricted to certain pages');
          }
        });

      $context
        .find('.sitewide-alert--form--scheduling-options')
        .drupalSetSummary((context) => {
          const $schedulingOptionsContext = $(context);
          const scheduled = $schedulingOptionsContext
            .find('.field--name-scheduled-alert input')
            .is(':checked');

          if (!scheduled) {
            return Drupal.t('Not scheduled');
          }

          const scheduledStartDate = $schedulingOptionsContext
            .find(
              '.field--name-scheduled-date [name="scheduled_date[0][value][date]"]'
            )
            .val();
          const scheduledStartTime = $schedulingOptionsContext
            .find(
              '.field--name-scheduled-date [name="scheduled_date[0][value][time]"]'
            )
            .val();
          const scheduledEndDate = $schedulingOptionsContext
            .find(
              '.field--name-scheduled-date [name="scheduled_date[0][end_value][date]"]'
            )
            .val();
          const scheduledEndTime = $schedulingOptionsContext
            .find(
              '.field--name-scheduled-date [name="scheduled_date[0][end_value][time]"]'
            )
            .val();

          if (
            scheduledStartDate &&
            scheduledStartTime &&
            scheduledEndDate &&
            scheduledEndTime
          ) {
            return Drupal.t(
              'Scheduled to show from @startDate @startTime till @endDate @endTime',
              {
                '@startDate': scheduledStartDate,
                '@startTime': scheduledStartTime,
                '@endDate': scheduledEndDate,
                '@endTime': scheduledEndTime
              }
            );
          }

          return Drupal.t('Not scheduled (scheduled date/times not set)');
        });

      $context
        .find('.sitewide-alert--form--dismissible-options')
        .drupalSetSummary((context) => {
          const $dismissibleOptionsContext = $(context);

          const dismissible = $dismissibleOptionsContext
            .find('.field--name-dismissible input')
            .is(':checked');

          if (dismissible) {
            return Drupal.t('Dismissible');
          }

          return Drupal.t('Not Dismissible');
        });
    }
  };
})(jQuery, Drupal);
