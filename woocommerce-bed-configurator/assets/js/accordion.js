/**
 * Vanilla JS accordion — no jQuery/wcbcData dependency.
 */
(function () {
	'use strict';

	function closestAccordionHead(node) {
		while (node && node !== document) {
			if (node.classList && node.classList.contains('wcbc-accordian-head')) {
				return node;
			}
			node = node.parentNode;
		}
		return null;
	}

	function setChevron(head, open) {
		var chevron = head.querySelector('.ev_ln_filter_chevron');
		if (!chevron) {
			return;
		}
		if (open) {
			chevron.classList.remove('ev_ln_filter_chevron_closed');
		} else {
			chevron.classList.add('ev_ln_filter_chevron_closed');
		}
	}

	function closeAllPanels(root) {
		root.querySelectorAll('.wcbc-accordian-head').forEach(function (head) {
			head.classList.remove('isopen');
			head.setAttribute('aria-expanded', 'false');
			setChevron(head, false);
		});
		root.querySelectorAll('.wcbc-accordian-body').forEach(function (body) {
			body.classList.remove('isopen');
			body.style.display = 'none';
		});
	}

	function openPanel(head, body) {
		head.classList.add('isopen');
		head.setAttribute('aria-expanded', 'true');
		setChevron(head, true);
		body.classList.add('isopen');
		body.style.display = 'block';
	}

	function togglePanel(head) {
		var root = head.closest('.wcbc-accordian');
		if (!root) {
			return;
		}

		var tabId = head.getAttribute('data-tabid');
		if (!tabId) {
			return;
		}

		var body = root.querySelector('.wcbc-accordian-body[data-tabid="' + tabId + '"]');
		if (!body) {
			return;
		}

		var isOpen = head.classList.contains('isopen');
		closeAllPanels(root);

		if (!isOpen) {
			openPanel(head, body);
		}
	}

	function onClick(event) {
		var head = closestAccordionHead(event.target);
		if (!head) {
			return;
		}
		event.preventDefault();
		event.stopPropagation();
		togglePanel(head);
	}

	function onKeydown(event) {
		if (event.key !== 'Enter' && event.key !== ' ') {
			return;
		}
		var head = closestAccordionHead(event.target);
		if (!head) {
			return;
		}
		event.preventDefault();
		togglePanel(head);
	}

	function init() {
		document.addEventListener('click', onClick, true);
		document.addEventListener('keydown', onKeydown, true);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
