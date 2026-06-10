(function (Drupal, drupalSettings, once) {
  const sitewideAlertsSelector = '[data-sitewide-alert]';

  const shouldShowOnThisPage = (pages = [], negate = true) => {
    if (pages.length === 0) {
      return true;
    }

    let pagePathMatches = false;
    const currentPath = window.location.pathname;

    for (let i = 0; i < pages.length; i++) {
      const baseUrl = drupalSettings.path.baseUrl.slice(0, -1);
      const page = baseUrl + pages[i];
      // Check if we have to deal with a wild card.
      if (page.charAt(page.length - 1) === '*') {
        if (currentPath.startsWith(page.substring(0, page.length - 1))) {
          pagePathMatches = true;
          break;
        }
      } else if (page === currentPath) {
        pagePathMatches = true;
        break;
      }
    }

    return negate ? !pagePathMatches : pagePathMatches;
  };

  const alertWasDismissed = (alert) => {
    if (!(`alert-dismissed-${alert.uuid}` in window.localStorage)) {
      return false;
    }

    const dismissedAtTimestamp = Number(
      window.localStorage.getItem(`alert-dismissed-${alert.uuid}`)
    );

    // If the visitor has already dismissed the alert, but we are supposed to ignore dismissals before a set date.
    return dismissedAtTimestamp >= alert.dismissalIgnoreBefore;
  };

  const dismissAlert = (alert) => {
    window.localStorage.setItem(
      `alert-dismissed-${alert.uuid}`,
      String(Math.round(new Date().getTime() / 1000))
    );
    document
      .querySelectorAll(`[data-uuid="${alert.uuid}"]`)
      .forEach((alert) => {
        alert.dispatchEvent(
          new CustomEvent('sitewide-alert-dismissed', {
            bubbles: true,
            composed: true
          })
        );
        removeAlert(alert);
      });
  };

  const buildAlertElement = (alert) => {
    const alertElement = document.createElement('div');
    alertElement.innerHTML = alert.renderedAlert;

    if (alert.dismissible) {
      const dismissButtons =
        alertElement.getElementsByClassName('js-dismiss-button');
      for (let i = 0; i < dismissButtons.length; i++) {
        dismissButtons[i].addEventListener('click', () => dismissAlert(alert));
      }
    }

    return alertElement.firstElementChild;
  };

  const removeAlert = (alert) => {
    alert.dispatchEvent(
      new CustomEvent('sitewide-alert-removed', {
        bubbles: true,
        composed: true
      })
    );

    alert.remove();
  };

  const fetchAlerts = () => {
    return fetch(
      `${
        window.location.origin +
        drupalSettings.path.baseUrl +
        drupalSettings.path.pathPrefix
      }sitewide_alert/load`
    )
      .then((res) => res.json())
      .then(
        (result) => result.sitewideAlerts,
        // Note: it's important to handle errors here
        // instead of a catch() block so that we don't swallow
        // exceptions from actual bugs in components.
        (error) => {
          console.error(error);
        }
      );
  };

  const removeStaleAlerts = (alerts) => {
    const roots = document.querySelectorAll(sitewideAlertsSelector);
    roots.forEach((root) => {
      const existingAlerts = root.querySelectorAll('[data-uuid]');

      // We have to convert and filter existing alerts based on newly fetched alerts.
      // This can be done by comparing uuids.
      // If the uuid can't be found in fetched alerts,
      // the alert with the same uuid should be removed.
      const alertsToBeRemoved = Array.from(existingAlerts).filter(
        (alert) => !alerts.includes(alert.getAttribute('data-uuid'))
      );

      alertsToBeRemoved.forEach((alert) => removeAlert(alert));
    });
  };

  const initAlerts = () => {
    const roots = document.querySelectorAll(sitewideAlertsSelector);
    // Fetch alerts and prepare rendering.
    fetchAlerts().then((alerts) => {
      removeStaleAlerts(alerts);
      alerts.forEach((alert) => {
        // Check if alert has been dismissed.
        const dismissed = alertWasDismissed(alert);
        // Check if current page is one of the pages the alert should be shown on or not.
        const showOnThisPage = shouldShowOnThisPage(
          alert.showOnPages,
          alert.negateShowOnPages
        );
        roots.forEach((root) => {
          // Check for existing alert element.
          const existingAlertElement = root.querySelector(
            `[data-uuid="${alert.uuid}"]`
          );

          if (showOnThisPage && !dismissed) {
            const renderableAlertElement = buildAlertElement(alert);
            // To prevent an alert from being rendered multiple times
            // replace the old alert with the new one when new alerts are being fetched.
            existingAlertElement
              ? root.replaceChild(renderableAlertElement, existingAlertElement)
              : root.appendChild(renderableAlertElement);

            renderableAlertElement.dispatchEvent(
              new CustomEvent('sitewide-alert-rendered', {
                bubbles: true,
                composed: true
              })
            );

            return;
          }

          // Remove alert if it is on the page and should no longer be.
          if ((dismissed || !showOnThisPage) && existingAlertElement) {
            removeAlert(existingAlertElement);
          }
        });
      });
    });
  };

  /**
   * Check if window.history pushstate is available
   * @returns {boolean}
   */
  const supportsHistoryPushState = () => {
    return (
      'pushState' in window.history && window.history['pushState'] !== null
    );
  };

  /**
   * Check if window.history replaceState is available
   * @returns {boolean}
   */
  const supportsHistoryReplaceState = () => {
    return (
      'replaceState' in window.history &&
      window.history['replaceState'] !== null
    );
  };

  /**
   * Add Proxy to standard pushState function to fire CustomEvent.
   *
   * Nor history.pushState either history.replaceState will trigger a popstate event.
   * Therefor a proxy behaviour is added to trigger a CustomEvent whenever the history is changed.
   *
   * @see https://developer.mozilla.org/en-US/docs/Web/API/History/pushState
   * @see https://developer.mozilla.org/en-US/docs/Web/API/Window/popstate_event
   */
  const proxyPushState = () => {
    if (supportsHistoryPushState()) {
      window.history.pushState = new Proxy(window.history.pushState, {
        apply(target, thisArg, argArray) {
          // triggerEvent
          triggerHistoryEvent(thisArg, argArray);
          // execute original
          return target.apply(thisArg, argArray);
        }
      });
    }
    if (supportsHistoryReplaceState()) {
      window.history.replaceState = new Proxy(window.history.replaceState, {
        apply(target, thisArg, argArray) {
          // triggerEvent
          triggerHistoryEvent(thisArg, argArray);
          // execute original
          return target.apply(thisArg, argArray);
        }
      });
    }
  };

  /**
   * Trigger CustomEvent sitewidealerts.popstate.
   *
   * @param thisArg
   * @param argArray
   */
  const triggerHistoryEvent = (thisArg, argArray) => {
    const event = new CustomEvent('sitewidealerts.popstate', {
      detail: { state: thisArg, options: argArray }
    });
    window.dispatchEvent(event);
  };

  /**
   * Reinitialize the alters on A: CustomEvent and B: Standard popstate.
   *
   * @see shouldShowOnThisPage
   * @see initAlerts
   */
  const historyListener = () => {
    window.addEventListener('sitewidealerts.popstate', () => initAlerts());
    window.addEventListener('popstate', () => initAlerts());
  };

  Drupal.behaviors.sitewide_alert_init = {
    attach: (context) => {
      once('sitewide_alerts_init', 'html', context).forEach(() => {
        // On load.
        initAlerts();
        proxyPushState();
        historyListener();
        if (drupalSettings.sitewideAlert.automaticRefresh === true) {
          const interval = setInterval(
            () => initAlerts(),
            drupalSettings.sitewideAlert.refreshInterval < 1000
              ? 1000
              : drupalSettings.sitewideAlert.refreshInterval
          );
          // Clear interval if automatic refresh has been turned off.
          // Only do this if an interval has previously been set.
          if (!drupalSettings.sitewideAlert.automaticRefresh) {
            clearInterval(interval);
          }
        }
      });
    }
  };
})(Drupal, drupalSettings, once);
