const dashboardRoot = document.querySelector('[data-spa-content]');
const dashboardPath = (url) => new URL(url, window.location.origin).pathname.startsWith('/dashboard');

if (dashboardRoot && dashboardPath(window.location.href)) {
  const progress = document.createElement('div');
  progress.setAttribute('data-spa-progress', '');
  document.body.appendChild(progress);

  let requestController = null;
  let requestSequence = 0;
  let loadingTimer = null;

  const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const setLoading = (loading) => {
    progress.classList.toggle('is-loading', loading);
    document.body.classList.toggle('spa-is-loading', loading);
    if (loading) {
      window.clearTimeout(loadingTimer);
      loadingTimer = window.setTimeout(() => { progress.style.width = '92%'; }, 350);
    } else {
      window.clearTimeout(loadingTimer);
      progress.style.width = '100%';
      window.setTimeout(() => { progress.classList.remove('is-loading'); progress.style.width = ''; }, 220);
    }
  };

  const escapeHtml = (value) => String(value).replace(/[&<>'"]/g, (character) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;' })[character]);
  const skeleton = (url) => {
    const path = new URL(url, window.location.origin).pathname;
    if (/\/squad$/.test(path)) return '<div class="spa-skeleton mx-auto max-w-6xl"><div class="spa-skeleton-block h-5 w-32"></div><div class="spa-skeleton-block mt-8 h-16 w-3/4"></div><div class="mt-8 grid gap-5 lg:grid-cols-[1.4fr_.8fr]"><div class="spa-skeleton-block h-[34rem]"></div><div class="spa-skeleton-block h-96"></div></div></div>';
    if (/\/leagues\/\d+$/.test(path)) return '<div class="spa-skeleton mx-auto max-w-7xl"><div class="spa-skeleton-block h-5 w-32"></div><div class="spa-skeleton-block mt-8 h-16 w-2/3"></div><div class="spa-skeleton-block mt-8 h-24 w-full"></div><div class="spa-skeleton-block mt-5 h-96 w-full"></div><div class="mt-8 grid gap-4 sm:grid-cols-2"><div class="spa-skeleton-block h-32"></div><div class="spa-skeleton-block h-32"></div></div></div>';
    return '<div class="spa-skeleton mx-auto max-w-7xl"><div class="spa-skeleton-block h-16 w-2/3"></div><div class="spa-skeleton-block mt-10 h-16 w-full"></div><div class="mt-4 grid gap-3"><div class="spa-skeleton-block h-20"></div><div class="spa-skeleton-block h-20"></div><div class="spa-skeleton-block h-20"></div></div></div>';
  };

  const runFragmentScripts = (root) => {
    root.querySelectorAll('script').forEach((script) => {
      if (script.src) {
        const external = document.createElement('script');
        external.src = script.src;
        external.async = false;
        document.head.appendChild(external);
        script.remove();
        return;
      }
      const code = script.textContent;
      script.remove();
      if (code.trim()) {
        try { new Function(code)(); } catch (error) { console.error('Dashboard page script failed', error); }
      }
    });
  };

  const cleanupPage = () => {
    window.dispatchEvent(new CustomEvent('dashboard:before-unmount'));
    window.__dashboardSpaCleanup?.();
    window.__dashboardSpaCleanup = null;
  };

  const updateNavigationState = () => {
    document.querySelectorAll('header a[href]').forEach((link) => {
      const active = link.pathname === '/dashboard' && dashboardPath(window.location.href);
      link.setAttribute('aria-current', active ? 'page' : 'false');
      if (link.dataset.spaLink !== undefined) {
        link.classList.toggle('border-uno-lime/30', active);
        link.classList.toggle('bg-uno-lime/10', active);
        link.classList.toggle('text-uno-lime', active);
        link.classList.toggle('border-white/15', !active);
        link.classList.toggle('bg-white/5', !active);
        link.classList.toggle('text-white', !active);
      }
    });
  };

  const renderFragment = (html, url) => {
    const parsed = new DOMParser().parseFromString(html, 'text/html');
    const fragment = parsed.querySelector('[data-spa-fragment][data-spa-content]');
    if (!fragment) throw new Error('The server did not return a dashboard fragment.');
    cleanupPage();
    dashboardRoot.innerHTML = fragment.innerHTML;
    const title = fragment.dataset.pageTitle;
    if (title) document.title = title;
    updateNavigationState();
    runFragmentScripts(dashboardRoot);
    window.dispatchEvent(new CustomEvent('dashboard:mounted', { detail: { url, page: dashboardRoot.querySelector('[data-dashboard-page]')?.dataset.dashboardPage } }));
  };

  const navigate = async (url, { replace = false, restoreScroll = false } = {}) => {
    const target = new URL(url, window.location.href);
    if (!dashboardPath(target.href)) return window.location.assign(target.href);
    requestController?.abort();
    requestController = new AbortController();
    const sequence = ++requestSequence;
    const savedScroll = restoreScroll ? (history.state?.scrollY || 0) : 0;
    setLoading(true);
    dashboardRoot.innerHTML = skeleton(target.href);
    try {
      const response = await fetch(target.href, { headers: { Accept: 'text/html', 'X-SPA-Request': '1', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin', signal: requestController.signal });
      if (!response.ok || response.redirected || !response.url.includes('/dashboard')) throw new Error('Dashboard navigation is unavailable.');
      const html = await response.text();
      if (sequence !== requestSequence) return;
      renderFragment(html, target.href);
      history[replace ? 'replaceState' : 'pushState']({ dashboardSpa: true, scrollY: restoreScroll ? savedScroll : 0 }, '', target.href);
      window.scrollTo({ top: restoreScroll ? savedScroll : 0, behavior: prefersReducedMotion ? 'auto' : 'smooth' });
    } catch (error) {
      if (error.name !== 'AbortError') window.location.assign(target.href);
    } finally {
      if (sequence === requestSequence) setLoading(false);
    }
  };

  const rememberScroll = () => {
    if (history.state?.dashboardSpa) history.replaceState({ ...history.state, scrollY: window.scrollY }, '');
  };

  history.replaceState({ ...(history.state || {}), dashboardSpa: true, scrollY: window.scrollY }, '', window.location.href);
  window.addEventListener('scroll', rememberScroll, { passive: true });
  window.addEventListener('popstate', (event) => navigate(window.location.href, { replace: true, restoreScroll: true }));
  window.addEventListener('click', (event) => {
    if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
    const link = event.target.closest?.('a[href]');
    if (!link || link.target === '_blank' || link.hasAttribute('download') || link.dataset.spa === 'false') return;
    const target = new URL(link.href, window.location.href);
    if (!dashboardPath(target.href) || target.origin !== window.location.origin || target.hash) return;
    event.preventDefault();
    navigate(target.href);
  });

  const submitMutation = async (form, event) => {
    event.preventDefault();
    event.stopImmediatePropagation();
    const button = form.querySelector('button[type="submit"]');
    const original = button?.innerHTML;
    if (button) { button.disabled = true; button.innerHTML = '<i class="bx bx-loader-alt animate-spin"></i> Working…'; }
    try {
      const response = await fetch(form.action, { method: form.method || 'POST', body: new FormData(form), headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-SPA-Request': '1' }, credentials: 'same-origin' });
      const payload = await response.json().catch(() => ({}));
      if (!response.ok) throw new Error(payload.message || Object.values(payload.errors || {}).flat()[0] || 'Unable to complete that action.');
      window.showToast?.(payload.message || 'Saved.');
      if (payload.redirect_url) await navigate(payload.redirect_url);
      else await navigate(window.location.href, { replace: true });
    } catch (error) {
      if (error.name !== 'AbortError') window.showToast?.(error.message, 'error');
    } finally {
      if (button) { button.disabled = false; button.innerHTML = original; }
    }
  };

  document.addEventListener('submit', (event) => {
    const form = event.target.closest?.('form');
    if (!form || form.dataset.spa === 'false' || !dashboardPath(form.action)) return;
    submitMutation(form, event);
  }, true);

  window.DashboardSPA = { navigate, submitMutation, mount: () => runFragmentScripts(dashboardRoot) };
  updateNavigationState();
}
