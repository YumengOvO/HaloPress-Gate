(function () {
	'use strict';

	var config = window.ACG_CONFIG || {};
	var root = document.documentElement;
	var gate = document.getElementById('acg-content-gate');

	if (!gate || !root.classList.contains('acg-active')) {
		return;
	}

	var progressLayer = gate.querySelector('.acg-gate__progress');
	var yesButton = gate.querySelector('[data-acg-yes]');
	var noButton = gate.querySelector('[data-acg-no]');
	var reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
	var remembered = hasConsentCookie();
	var returning = remembered && !config.forcePreview;
	var closed = false;

	gate.hidden = false;
	gate.setAttribute('aria-hidden', 'false');
	document.body.classList.add('acg-scroll-lock');

	if (returning) {
		gate.classList.add('is-returning');
	}

	preloadPage().then(function () {
		gate.classList.add('is-ready');

		if (returning) {
			window.setTimeout(beginExit, reducedMotion ? 0 : 220);
			return;
		}

		window.setTimeout(function () {
			yesButton.focus({ preventScroll: true });
		}, reducedMotion ? 0 : 520);
	});

	yesButton.addEventListener('click', function () {
		setConsentCookie();
		gate.classList.add('is-confirming');
		window.setTimeout(beginExit, reducedMotion ? 0 : 500);
	});

	noButton.addEventListener('click', function () {
		var leaveUrl = noButton.getAttribute('data-leave-url');
		if (leaveUrl) {
			window.location.assign(leaveUrl);
		}
	});

	gate.addEventListener('keydown', function (event) {
		if (event.key !== 'Tab' || returning) {
			return;
		}

		var buttons = [yesButton, noButton].filter(function (button) {
			return !button.disabled;
		});
		var first = buttons[0];
		var last = buttons[buttons.length - 1];

		if (event.shiftKey && document.activeElement === first) {
			event.preventDefault();
			last.focus();
		} else if (!event.shiftKey && document.activeElement === last) {
			event.preventDefault();
			first.focus();
		}
	});

	function preloadPage() {
		return new Promise(function (resolve) {
			var images = Array.prototype.slice.call(
				document.querySelectorAll('img:not([loading="lazy"])')
			).filter(function (image) {
				return !gate.contains(image);
			});
			var total = Math.max(images.length, 1);
			var loaded = images.filter(function (image) { return image.complete; }).length;
			var displayed = 0;
			var startedAt = performance.now();
			var forced = false;
			var maximumWait = window.setTimeout(function () { forced = true; }, 4000);

			if (!images.length) {
				loaded = 1;
			}

			images.forEach(function (image) {
				if (image.complete) {
					return;
				}
				var done = function () {
					loaded += 1;
					image.removeEventListener('load', done);
					image.removeEventListener('error', done);
				};
				image.addEventListener('load', done, { once: true });
				image.addEventListener('error', done, { once: true });
			});

			function frame(now) {
				var target = forced ? 1 : Math.min(loaded / total, 1);
				displayed += (target - displayed) * (reducedMotion ? 1 : 0.12);
				if (target === 1 && displayed > 0.995) {
					displayed = 1;
				}
				progressLayer.style.transform = 'scaleY(' + displayed.toFixed(4) + ')';

				if (displayed === 1 && (reducedMotion || now - startedAt >= 650)) {
					window.clearTimeout(maximumWait);
					progressLayer.style.transform = 'scaleY(1)';
					resolve();
					return;
				}
				window.requestAnimationFrame(frame);
			}

			window.requestAnimationFrame(frame);
		});
	}

	function beginExit() {
		if (closed) {
			return;
		}
		closed = true;
		gate.classList.add('is-exiting');
		window.setTimeout(finishExit, reducedMotion ? 20 : 760);
	}

	function finishExit() {
		gate.hidden = true;
		gate.setAttribute('aria-hidden', 'true');
		root.classList.remove('acg-active');
		root.classList.add(config.enteredClass || 'acg-entered');
		document.body.classList.remove('acg-scroll-lock');
		document.body.classList.add(config.enteredClass || 'acg-entered');
		window.dispatchEvent(new CustomEvent('acg:entered'));
	}

	function hasConsentCookie() {
		var needle = encodeURIComponent(config.cookieName || 'acg_age_confirmed') + '=yes';
		return document.cookie.split('; ').indexOf(needle) !== -1;
	}

	function setConsentCookie() {
		var name = encodeURIComponent(config.cookieName || 'acg_age_confirmed');
		var days = Math.max(1, Number(config.cookieDays) || 1);
		var maxAge = Math.round(days * 86400);
		var cookie = name + '=yes; path=/; max-age=' + maxAge + '; samesite=lax';
		if (window.location.protocol === 'https:') {
			cookie += '; secure';
		}
		document.cookie = cookie;
	}
})();
