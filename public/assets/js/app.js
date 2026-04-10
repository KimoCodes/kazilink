document.addEventListener('DOMContentLoaded', function () {
    var header = document.querySelector('.site-header');
    var navToggle = document.querySelector('[data-nav-toggle]');
    var navShell = document.querySelector('[data-nav-shell]');

    function setMobileNavState(isOpen) {
        if (!header || !navToggle || !navShell) {
            return;
        }

        header.classList.toggle('nav-open', isOpen);
        navToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    }

    if (header && navToggle && navShell) {
        navToggle.addEventListener('click', function () {
            setMobileNavState(!header.classList.contains('nav-open'));
        });

        navShell.querySelectorAll('a').forEach(function (link) {
            link.addEventListener('click', function () {
                setMobileNavState(false);
            });
        });

        window.addEventListener('resize', function () {
            if (window.innerWidth >= 860) {
                setMobileNavState(false);
            }
        });
    }

    var navDropdowns = document.querySelectorAll('.nav-dropdown');

    function closeAllDropdowns() {
        navDropdowns.forEach(function (dropdown) {
            dropdown.setAttribute('aria-expanded', 'false');
            var toggle = dropdown.querySelector('.nav-dropdown-toggle');
            var panel = dropdown.querySelector('.nav-dropdown-panel');

            if (toggle) {
                toggle.setAttribute('aria-expanded', 'false');
            }

            if (panel) {
                panel.setAttribute('aria-hidden', 'true');
            }
        });
    }

    navDropdowns.forEach(function (dropdown) {
        var toggle = dropdown.querySelector('.nav-dropdown-toggle');
        var panel = dropdown.querySelector('.nav-dropdown-panel');

        if (!toggle || !panel) {
            return;
        }

        toggle.addEventListener('click', function (event) {
            event.preventDefault();
            var isExpanded = dropdown.getAttribute('aria-expanded') === 'true';
            closeAllDropdowns();

            if (!isExpanded) {
                dropdown.setAttribute('aria-expanded', 'true');
                toggle.setAttribute('aria-expanded', 'true');
                panel.setAttribute('aria-hidden', 'false');
            }
        });
    });

    document.addEventListener('click', function (event) {
        if (!event.target.closest('.nav-dropdown')) {
            closeAllDropdowns();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeAllDropdowns();
            setMobileNavState(false);
        }
    });

    document.querySelectorAll('[data-confirm]').forEach(function (element) {
        element.addEventListener('click', function (event) {
            var message = element.getAttribute('data-confirm') || 'Are you sure?';

            if (!window.confirm(message)) {
                event.preventDefault();
            }
        });
    });

    document.querySelectorAll('[data-flash-dismiss]').forEach(function (button) {
        button.addEventListener('click', function () {
            var flash = button.closest('[data-flash]');

            if (flash) {
                flash.remove();
            }
        });
    });

    document.querySelectorAll('.price:not(.price-display)').forEach(function (el) {
        var text = el.textContent.trim();

        if (text && !text.includes('RWF')) {
            el.innerHTML = '<span class="price-amount">' + text + '</span> <span class="price-currency">RWF</span>';
        }
    });

    var welcomePanels = document.querySelectorAll('[data-return-visitor]');
    var visitKey = 'kazilink_last_visit_at';
    var visitCountKey = 'kazilink_visit_count';
    var lastVisitRaw = window.localStorage.getItem(visitKey);
    var visitCount = Number(window.localStorage.getItem(visitCountKey) || '0');
    var lastVisitDate = lastVisitRaw ? new Date(lastVisitRaw) : null;

    if (lastVisitDate instanceof Date && !isNaN(lastVisitDate.getTime()) && visitCount >= 1) {
        welcomePanels.forEach(function (panel) {
            var copy = panel.querySelector('[data-return-visitor-copy]');
            var name = panel.getAttribute('data-personalization-name') || 'friend';

            panel.classList.add('is-visible');

            if (copy) {
                copy.textContent = 'Welcome back, ' + name + '. Your last visit was ' + lastVisitDate.toLocaleDateString() + '.';
            }
        });
    }

    window.localStorage.setItem(visitKey, new Date().toISOString());
    window.localStorage.setItem(visitCountKey, String(visitCount + 1));

    var locationButton = document.getElementById('use-current-location');
    var locationFeedback = document.getElementById('location-feedback');
    var cityInput = document.getElementById('city');
    var regionSelect = document.getElementById('region');

    function determineKigaliDistrict(lat, lng) {
        var latNum = Number(lat);
        var lngNum = Number(lng);

        if (isNaN(latNum) || isNaN(lngNum)) {
            return '';
        }

        if (latNum >= -1.95 && lngNum >= 30.05) {
            return 'gasabo';
        }

        if (latNum <= -1.97) {
            return 'kicukiro';
        }

        return 'nyarugenge';
    }

    if (locationButton && locationFeedback && cityInput && regionSelect) {
        locationButton.addEventListener('click', function () {
            if (!navigator.geolocation) {
                locationFeedback.textContent = 'Geolocation is not available in your browser.';
                return;
            }

            locationButton.disabled = true;
            locationButton.textContent = 'Detecting location...';
            locationFeedback.textContent = 'Finding nearest tasks from your current location...';

            navigator.geolocation.getCurrentPosition(function (position) {
                var region = determineKigaliDistrict(position.coords.latitude, position.coords.longitude);

                if (region) {
                    if (!cityInput.value.trim()) {
                        cityInput.value = 'Kigali';
                    }

                    regionSelect.value = region;
                    locationFeedback.textContent = 'Current location detected. Searching nearby tasks in ' + region.charAt(0).toUpperCase() + region.slice(1) + '.';
                } else {
                    locationFeedback.textContent = 'Your location is within Kigali, but we could not identify the exact district.';
                }

                locationButton.disabled = false;
                locationButton.textContent = 'Use my current location';
            }, function (error) {
                var message = 'Unable to determine your location.';

                if (error.code === error.PERMISSION_DENIED) {
                    message = 'Location access was denied. Please allow location access to use this feature.';
                } else if (error.code === error.TIMEOUT) {
                    message = 'Location lookup timed out. Please try again.';
                }

                locationFeedback.textContent = message;
                locationButton.disabled = false;
                locationButton.textContent = 'Use my current location';
            }, {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 60000,
            });
        });
    }

    var subscriptionStatusCard = document.querySelector('[data-subscription-status]');

    if (subscriptionStatusCard) {
        var statusUrl = subscriptionStatusCard.getAttribute('data-status-url');
        var statusText = subscriptionStatusCard.querySelector('[data-subscription-status-text]');

        function pollSubscriptionStatus() {
            if (!statusUrl || !statusText) {
                return;
            }

            window.fetch(statusUrl, {
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json'
                }
            }).then(function (response) {
                return response.json();
            }).then(function (data) {
                if (!data || !data.status) {
                    return;
                }

                statusText.textContent = data.status;

                if (data.status === 'successful') {
                    window.location.reload();
                }
            }).catch(function () {
                // Leave the pending state visible and allow manual refresh.
            });
        }

        window.setInterval(pollSubscriptionStatus, 8000);
        pollSubscriptionStatus();
    }

    var sessionTimeoutSeconds = Number(document.body.getAttribute('data-session-timeout-seconds') || '0');
    var sessionHeartbeatSeconds = Number(document.body.getAttribute('data-session-heartbeat-seconds') || '0');
    var sessionPingUrl = document.body.getAttribute('data-session-ping-url') || '';
    var sessionLogoutForm = document.querySelector('[data-session-logout-form]');
    var lastActivityAt = Date.now();
    var activityEvents = ['click', 'keydown', 'mousemove', 'scroll', 'touchstart'];

    function noteActivity() {
        lastActivityAt = Date.now();
    }

    function submitIdleLogout() {
        if (!sessionLogoutForm || sessionLogoutForm.dataset.submitting === 'true') {
            return;
        }

        sessionLogoutForm.dataset.submitting = 'true';

        var reasonInput = sessionLogoutForm.querySelector('input[name="logout_reason"]');

        if (reasonInput) {
            reasonInput.value = 'idle_timeout';
        }

        sessionLogoutForm.submit();
    }

    function pingSession() {
        if (!sessionPingUrl || document.visibilityState === 'hidden') {
            return;
        }

        window.fetch(sessionPingUrl, {
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json'
            }
        }).then(function (response) {
            if (response.status === 401) {
                window.location.reload();
            }
        }).catch(function () {
            // Ignore transient heartbeat issues and let the next ping retry.
        });
    }

    if (sessionTimeoutSeconds > 0 && sessionLogoutForm) {
        activityEvents.forEach(function (eventName) {
            document.addEventListener(eventName, noteActivity, { passive: true });
        });

        document.addEventListener('visibilitychange', function () {
            if (document.visibilityState === 'visible') {
                noteActivity();
                pingSession();
            }
        });

        window.setInterval(function () {
            var idleForMs = Date.now() - lastActivityAt;

            if (idleForMs >= (sessionTimeoutSeconds * 1000)) {
                submitIdleLogout();
            }
        }, 10000);

        if (sessionHeartbeatSeconds > 0 && sessionPingUrl) {
            window.setInterval(function () {
                var idleForMs = Date.now() - lastActivityAt;

                if (idleForMs < (sessionTimeoutSeconds * 1000)) {
                    pingSession();
                }
            }, sessionHeartbeatSeconds * 1000);

            pingSession();
        }
    }
});
