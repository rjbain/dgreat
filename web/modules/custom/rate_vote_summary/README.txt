NAME
----
Rate Vote Summary

DESCRIPTION
-----------
Provides a summary display of Rate/VotingAPI votes for nodes.

The summary shows:
 - Total up votes for the node
 - Total down votes for the node
 - The current user's vote (up, down, or none)

The display updates automatically via AJAX after a user casts a vote.

REQUIREMENTS
------------
 - Drupal 9.5 or later
 - VotingAPI module
 - Rate module

INSTALLATION
------------
1. Place the module in: web/modules/custom/rate_vote_summary
2. Enable with Drush or the Drupal admin UI:
     drush en rate_vote_summary -y
3. Clear caches:
     drush cr
4. Ensure Rate and VotingAPI modules are enabled and configured.

USAGE
-----
- By default, the summary is shown for nodes of type "submission" only.
- The summary appears directly below the Body field in Full Content view mode.
- The values automatically refresh when votes are cast.

FILES
-----
rate_vote_summary.module
  - Implements hooks and injects the summary into rendered nodes.
src/VoteTallyService.php
  - Fetches up/down/user vote totals from votingapi_vote table.
src/Controller/RefreshController.php
  - Provides an AJAX endpoint for refreshing the summary.
rate_vote_summary.routing.yml
  - Defines the refresh route.
rate_vote_summary.services.yml
  - Registers VoteTallyService.
rate_vote_summary.libraries.yml
  - Declares JS library for AJAX refresh.
js/refresh.js
  - Listens for Drupal AJAX vote events and reloads the summary.
templates/rate-vote-summary.html.twig
  - Twig template for summary output.
themes/custom/myusf/templates/node/node--submission.html.twig
  - Ensures the summary appears below the body field.

DATABASE
--------
Compatible with the standard VotingAPI schema:

  id, type, uuid, entity_type, entity_id,
  value, value_type, user_id, timestamp,
  vote_source, rate_widget
