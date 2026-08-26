(function () {
    'use strict';

    var root = document.querySelector('[data-job-finder]');
    if (!root || root.getAttribute('data-live-filter') !== '1') {
        return;
    }

    var form = root.querySelector('[data-job-filters]');
    var results = root.querySelector('[data-job-results]');
    var count = root.querySelector('[data-job-count]');
    var filterUrl = root.getAttribute('data-filter-url');
    if (!form || !results || !filterUrl) {
        return;
    }

    var timer = null;
    var controller = null;

    function pluginParams() {
        var data = new FormData(form);
        var params = new URLSearchParams();
        data.forEach(function (value, key) {
            if (String(value).trim() !== '') {
                params.append(key, String(value));
            }
        });
        return params;
    }

    /**
     * The filter action is a normal TYPO3 page request, so the response still
     * contains language menu, navigation and breadcrumb. Only the job list
     * fragment belongs in the results container.
     */
    function extractResultsHtml(html) {
        var doc = new DOMParser().parseFromString(html, 'text/html');
        var fragment = doc.querySelector('[data-job-filter-fragment]');
        if (fragment) {
            return fragment.innerHTML;
        }
        var nested = doc.querySelector('[data-job-results]');
        if (nested) {
            return nested.innerHTML;
        }
        var counted = doc.querySelector('[data-count]');
        if (counted) {
            return counted.outerHTML;
        }
        return '';
    }

    function updateCount() {
        if (!count) {
            return;
        }
        var counted = results.querySelector('[data-count]');
        var cards = results.querySelectorAll('.job-card').length;
        var total = counted ? counted.getAttribute('data-count') : '';
        if (total === '' || (total === '0' && cards > 0)) {
            total = String(cards);
        }
        var template = count.getAttribute('data-count-template') || '%s';
        count.textContent = template.replace('%s', total || '0');
    }

    function fetchResults() {
        var params = pluginParams();
        var url = new URL(filterUrl, window.location.origin);
        params.forEach(function (value, key) {
            url.searchParams.set(key, value);
        });
        url.searchParams.set('tx_smartjobfinder_joblist[action]', 'filter');

        if (controller) {
            controller.abort();
        }
        controller = new AbortController();
        root.classList.add('is-loading');
        root.setAttribute('aria-busy', 'true');

        fetch(url.toString(), {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'text/html',
            },
            signal: controller.signal,
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('Filter request failed');
                }
                return response.text();
            })
            .then(function (html) {
                var fragment = extractResultsHtml(html);
                if (!fragment) {
                    throw new Error('Filter fragment missing');
                }
                results.innerHTML = fragment;
                updateCount();
                var next = url.pathname + url.search;
                window.history.replaceState({}, '', next);
            })
            .catch(function (error) {
                if (error.name !== 'AbortError') {
                    form.submit();
                }
            })
            .finally(function () {
                root.classList.remove('is-loading');
                root.removeAttribute('aria-busy');
            });
    }

    function scheduleFetch() {
        window.clearTimeout(timer);
        timer = window.setTimeout(fetchResults, 280);
    }

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        fetchResults();
    });

    form.querySelectorAll('input, select').forEach(function (field) {
        var eventName = field.tagName === 'SELECT' ? 'change' : 'input';
        field.addEventListener(eventName, scheduleFetch);
    });
})();
