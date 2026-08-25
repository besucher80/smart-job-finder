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

    function updateCount(html) {
        if (!count) {
            return;
        }
        var wrapper = document.createElement('div');
        wrapper.innerHTML = html;
        var counted = wrapper.querySelector('[data-count]');
        var total = counted ? counted.getAttribute('data-count') : '0';
        count.textContent = count.textContent.replace(/\d+/, total || '0');
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
                results.innerHTML = html;
                updateCount(html);
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
