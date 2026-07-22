<style>
  @keyframes toast-in {
    from { opacity: 0; transform: translate3d(0, -14px, 0) scale(.96); }
    to { opacity: 1; transform: translate3d(0, 0, 0) scale(1); }
  }

  .toast-message { animation: toast-in .35s cubic-bezier(.22, 1, .36, 1) both; }

  @media (prefers-reduced-motion: reduce) {
    .toast-message { animation: none; }
  }
</style>

@if (session('status'))
  <div class="toast-message fixed right-5 top-5 z-[60] flex max-w-sm items-start gap-3 rounded-2xl border border-uno-green/30 bg-[#071d33]/95 px-5 py-4 text-sm font-bold text-white shadow-2xl backdrop-blur-xl" role="status" aria-live="polite">
    <i class="bx bx-check-circle mt-0.5 text-xl text-uno-lime"></i>
    <span class="flex-1">{{ session('status') }}</span>
    <button type="button" class="toast-dismiss text-xl text-white/50 transition hover:text-white" aria-label="Dismiss notification"><i class="bx bx-x"></i></button>
  </div>
@endif

<script>
  window.showToast = window.showToast || function (message, type = 'success') {
    const toast = document.createElement('div');
    const isError = type === 'error';
    toast.className = `toast-message fixed right-5 top-5 z-[60] flex max-w-sm items-start gap-3 rounded-2xl border ${isError ? 'border-red-400/30' : 'border-uno-green/30'} bg-[#071d33]/95 px-5 py-4 text-sm font-bold text-white shadow-2xl backdrop-blur-xl`;
    toast.setAttribute('role', isError ? 'alert' : 'status');
    toast.setAttribute('aria-live', isError ? 'assertive' : 'polite');
    toast.innerHTML = `<i class="bx ${isError ? 'bx-error-circle text-red-300' : 'bx-check-circle text-uno-lime'} mt-0.5 text-xl"></i><span class="flex-1"></span><button type="button" class="toast-dismiss text-xl text-white/50 transition hover:text-white" aria-label="Dismiss notification"><i class="bx bx-x"></i></button>`;
    toast.querySelector('span').textContent = message;
    toast.querySelector('.toast-dismiss').addEventListener('click', () => toast.remove());
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 4500);
  };

  document.querySelectorAll('.toast-message .toast-dismiss').forEach((button) => {
    button.addEventListener('click', () => button.closest('.toast-message').remove());
  });
  document.querySelectorAll('.toast-message').forEach((toast) => {
    setTimeout(() => toast.remove(), 4500);
  });
</script>
