(function(){
	'use strict';

	var storageKey = 'pts-admin-sidebar-state-v1';
	var sidebarSelector = '.main-sidebar .sidebar';
	var activeLinkSelector = '.nav-sidebar .nav-link.active[href]';

	function currentRoute()
	{
		return window.location.pathname + window.location.search;
	}

	function readState()
	{
		try
		{
			var state = JSON.parse(window.sessionStorage.getItem(storageKey) || 'null');
			return state && typeof state === 'object' ? state : null;
		}
		catch(error)
		{
			return null;
		}
	}

	function writeState(state)
	{
		try
		{
			window.sessionStorage.setItem(storageKey, JSON.stringify(state));
		}
		catch(error) {}
	}

	function expandActiveBranches(sidebar)
	{
		var activeLinks = sidebar.querySelectorAll(activeLinkSelector);
		for(var i = 0; i < activeLinks.length; i++)
		{
			var ancestor = activeLinks[i].parentElement;
			while(ancestor && ancestor !== sidebar)
			{
				if(ancestor.classList && ancestor.classList.contains('has-treeview'))
				{
					ancestor.classList.add('menu-open');
					var directLink = ancestor.firstElementChild;
					if(directLink && directLink.classList.contains('nav-link')) directLink.classList.add('active');
				}
				ancestor = ancestor.parentElement;
			}
		}
	}

	function deepestActiveLink(sidebar)
	{
		var activeLinks = sidebar.querySelectorAll(activeLinkSelector);
		return activeLinks.length ? activeLinks[activeLinks.length - 1] : null;
	}

	function restoreSidebar(sidebar)
	{
		expandActiveBranches(sidebar);
		var state = readState();
		if(!state) return;

		var target = parseFloat(state.scrollTop);
		if(!isFinite(target)) target = 0;

		if(state.destination === currentRoute() && isFinite(parseFloat(state.anchorOffset)))
		{
			var activeLink = deepestActiveLink(sidebar);
			if(activeLink)
			{
				var activeContentTop = activeLink.getBoundingClientRect().top - sidebar.getBoundingClientRect().top + sidebar.scrollTop;
				target = activeContentTop - parseFloat(state.anchorOffset);
			}
		}

		var maxScroll = Math.max(0, sidebar.scrollHeight - sidebar.clientHeight);
		sidebar.scrollTop = Math.max(0, Math.min(target, maxScroll));
	}

	function internalRoute(link)
	{
		if(!link || !link.getAttribute('href') || link.getAttribute('href').charAt(0) === '#') return '';
		if(link.target && link.target.toLowerCase() !== '_self') return '';
		if(link.origin && link.origin !== window.location.origin) return '';
		return link.pathname + link.search;
	}

	function initSidebarState()
	{
		var sidebar = document.querySelector(sidebarSelector);
		if(!sidebar || sidebar.getAttribute('data-scroll-state-ready') === '1') return;
		sidebar.setAttribute('data-scroll-state-ready', '1');

		restoreSidebar(sidebar);
		var restoreFrame = window.requestAnimationFrame || function(callback){ return window.setTimeout(callback, 0); };
		restoreFrame(function(){ restoreSidebar(sidebar); });

		var scrollFrame = null;
		var navigationPending = false;
		sidebar.addEventListener('scroll', function(){
			if(scrollFrame !== null) return;
			scrollFrame = restoreFrame(function(){
				scrollFrame = null;
				if(navigationPending) return;
				writeState({scrollTop: sidebar.scrollTop, destination: currentRoute()});
			});
		}, {passive: true});

		sidebar.addEventListener('click', function(event){
			if(event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
			var link = event.target.closest ? event.target.closest('a.nav-link[href]') : null;
			var destination = internalRoute(link);
			if(!destination || !sidebar.contains(link)) return;
			navigationPending = true;
			writeState({
				scrollTop: sidebar.scrollTop,
				destination: destination,
				anchorOffset: link.getBoundingClientRect().top - sidebar.getBoundingClientRect().top
			});
		});

		window.addEventListener('load', function(){
			window.setTimeout(function(){ restoreSidebar(sidebar); }, 50);
		}, {once: true});
	}

	if(document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initSidebarState);
	else initSidebarState();

	window.PTSAdminSidebarState = {
		init: initSidebarState,
		restore: restoreSidebar,
		storageKey: storageKey
	};
})();
